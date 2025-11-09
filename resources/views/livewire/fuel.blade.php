<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Fuel, Unit, Karyawan};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\ModelNotFoundException;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Filter
    public $filterDate = '';
    public $filterUnitId = null;

    // Form fields
    public $tanggal = '';
    public $unit_id = null;
    public $karyawan_id = null;
    public $jumlah_pengisian = '';
    // KM menggantikan HM pada antarmuka; tetap dipetakan ke kolom 'hm' di DB
    public $km = '';

    // Dropdown data
    public $units = [];
    public $drivers = [];

    // Modal state
    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;

    public function mount()
    {
        $this->loadDropdowns();
    }

    private function loadDropdowns(): void
    {
        // Cache dropdown untuk mengurangi query berulang (TTL 2 menit)
        $this->units = Cache::remember('fuel_units_dropdown', 120, function () {
            return Unit::query()
                ->with('typeUnit:id,jenis_alat')
                ->orderBy('nomor_lambung')
                ->get(['id', 'nomor_lambung', 'type_unit_id']);
        });

        $this->drivers = Cache::remember('fuel_drivers_dropdown', 120, function () {
            return Karyawan::query()
                ->orderBy('nama_karyawan')
                ->get(['id', 'nama_karyawan']);
        });
    }

    // Reset pagination ketika search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Sorting
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    // CRUD
    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('fuel-modal-opened');
    }

    public function edit($id)
    {
        try {
            $f = Fuel::findOrFail($id);
            $this->editingId = $id;
            $this->tanggal = optional($f->tanggal)->format('Y-m-d');
            $this->unit_id = $f->unit_id;
            $this->karyawan_id = $f->karyawan_id;
            $this->jumlah_pengisian = number_format((float)$f->jumlah_pengisian, 2, '.', '');
            // KM pada UI kini menggunakan kolom 'km' di DB
            $this->km = $f->km;
            $this->showModal = true;
            $this->dispatch('fuel-modal-opened');
        } catch (ModelNotFoundException $e) {
            session()->flash('error', 'Data fuel tidak ditemukan.');
        } catch (\Throwable $e) {
            Log::error('Gagal memuat fuel: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat data fuel.');
        }
    }

    public function save()
    {
        // Validasi KM (menggantikan HM pada UI), akan dipetakan ke 'hm' saat simpan
        $validated = $this->validate(
            [
                'tanggal' => ['required', 'date'],
                'unit_id' => ['required', 'exists:units,id'],
                'karyawan_id' => ['required', 'exists:karyawans,id'],
                'jumlah_pengisian' => ['required', 'numeric', 'min:0'],
                'km' => ['required', 'integer', 'min:0'],
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'unit_id.required' => 'Unit wajib dipilih.',
                'unit_id.exists' => 'Unit tidak ditemukan.',
                'karyawan_id.required' => 'Karyawan wajib dipilih.',
                'karyawan_id.exists' => 'Karyawan tidak ditemukan.',
                'jumlah_pengisian.required' => 'Jumlah pengisian wajib diisi.',
                'jumlah_pengisian.numeric' => 'Jumlah pengisian harus angka.',
                'jumlah_pengisian.min' => 'Jumlah pengisian minimal 0.',
                'km.required' => 'KM wajib diisi.',
                'km.integer' => 'KM harus bilangan bulat.',
                'km.min' => 'KM minimal 0.',
            ]
        );

        try {
            if ($this->editingId) {
                $payload = [
                    'tanggal' => $validated['tanggal'],
                    'unit_id' => $validated['unit_id'],
                    'karyawan_id' => $validated['karyawan_id'],
                    'jumlah_pengisian' => $validated['jumlah_pengisian'],
                    'km' => $validated['km'],
                ];
                Fuel::findOrFail($this->editingId)->update($payload);
                session()->flash('message', 'Data fuel berhasil diperbarui.');
            } else {
                $payload = [
                    'tanggal' => $validated['tanggal'],
                    'unit_id' => $validated['unit_id'],
                    'karyawan_id' => $validated['karyawan_id'],
                    'jumlah_pengisian' => $validated['jumlah_pengisian'],
                    'km' => $validated['km'],
                ];
                Fuel::create($payload);
                session()->flash('message', 'Data fuel berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('fuel-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan fuel: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('fuel-modal-closed');
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        try {
            if ($this->deletingId) {
                Fuel::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data fuel berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus fuel: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->karyawan_id = null;
        $this->jumlah_pengisian = '';
        $this->km = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        // Query utama dengan filter dan sorting, termasuk mapping sort KM->HM
        $query = Fuel::query()
            ->with(['unit.typeUnit', 'karyawan'])
            ->when($this->search, function ($q) {
                $q->whereHas('unit', function ($uq) {
                    $uq->where('nomor_lambung', 'like', '%' . $this->search . '%');
                })->orWhereHas('karyawan', function ($kq) {
                    $kq->where('nama_karyawan', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->filterUnitId, fn($q) => $q->where('unit_id', $this->filterUnitId))
            ->when($this->filterDate, fn($q) => $q->whereDate('tanggal', $this->filterDate));

        // Sorting support termasuk nomor_lambung via join
        if ($this->sortField === 'nomor_lambung') {
            $query->leftJoin('units', 'units.id', '=', 'fuel.unit_id')
                ->orderBy('units.nomor_lambung', $this->sortDirection)
                ->select('fuel.*');
        } elseif ($this->sortField === 'km') {
            // KM pada UI di-sort berdasarkan kolom 'km' di DB
            $query->orderBy('km', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $fuels = $query->paginate($this->perPage);

        return [
            'fuels' => $fuels,
        ];
    }
}; ?>

<div>
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="/">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Produksi</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Data Fuel</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Fuel</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Fuel
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Alert Messages -->
                        @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif
                        @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        <!-- Search, PerPage, Filters -->
                        <div class="row mb-3 align-items-end">
                            <div class="col-md-4 mb-2">
                                <input type="text" class="form-control" placeholder="Cari (lambung/driver)..."
                                    wire:model.live.debounce.300ms="search">
                            </div>
                            <div class="col-md-2 mb-2">
                                <select class="form-control" wire:model.live="perPage">
                                    <option value="10">10 per halaman</option>
                                    <option value="25">25 per halaman</option>
                                    <option value="50">50 per halaman</option>
                                    <option value="100">100 per halaman</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="date" class="form-control" wire:model.live="filterDate" placeholder="Filter tanggal">
                            </div>
                            <div class="col-md-3 mb-2" wire:ignore>
                                <select id="filter_unit_id" class="form-control" data-selected="{{ $filterUnitId }}">
                                    <option value="">Semua unit</option>
                                    @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->nomor_lambung }} @if($u->typeUnit) — {{ $u->typeUnit->jenis_alat }} @endif</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm table-timesheet">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 130px; cursor: pointer;" wire:click="sortBy('tanggal')">Tanggal
                                            @if ($sortField === 'tanggal')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th style="cursor: pointer;" wire:click="sortBy('nomor_lambung')">Nomor Lambung
                                            @if ($sortField === 'nomor_lambung')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Type Unit</th>
                                        <th style="cursor: pointer;" wire:click="sortBy('karyawan_id')">Karyawan
                                            @if ($sortField === 'karyawan_id')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th style="cursor: pointer;" wire:click="sortBy('jumlah_pengisian')">Jumlah Pengisian (L)
                                            @if ($sortField === 'jumlah_pengisian')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th style="cursor: pointer;" wire:click="sortBy('km')">KM
                                            @if ($sortField === 'km')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th class="text-right" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($fuels as $f)
                                    <tr>
                                        <td>{{ optional($f->tanggal)->format('d/m/Y') }}</td>
                                        <td>{{ optional($f->unit)->nomor_lambung }}</td>
                                        <td>{{ optional(optional($f->unit)->typeUnit)->jenis_alat }}</td>
                                        <td>{{ optional($f->karyawan)->nama_karyawan }}</td>
                                        <td>{{ number_format((float)$f->jumlah_pengisian, 2, ',', '.') }}</td>
                                        <td>{{ number_format((int)$f->km, 0, '.', '.') }}</td>           
                                        <td class="text-right">
                                            <button class="btn btn-warning btn-sm mb-1" wire:click="edit({{ $f->id }})">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" wire:click="confirmDelete({{ $f->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $fuels->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" id="fuelModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Fuel</h5>
                    <button type="button" class="close" aria-label="Close" wire:click="closeModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" id="tanggal" class="form-control" wire:model.live="tanggal" placeholder="Pilih tanggal">
                            @error('tanggal') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Tanggal pengisian bahan bakar.</small>
                        </div>
                        <div class="form-group" >
                            <label for="unit_id">Unit (Nomor Lambung — Tipe Unit)</label>
                            <div wire:ignore>
                                <select id="unit_id" class="form-control" wire:model="unit_id" data-selected="{{ $unit_id }}">
                                    <option value="">Pilih unit</option>
                                    @foreach ($units as $u)
                                    <option value="{{ $u->id }}">{{ $u->nomor_lambung }} @if($u->typeUnit) — {{ $u->typeUnit->jenis_alat }} @endif</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('unit_id') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Pilih unit berdasarkan nomor lambung.</small>
                        </div>
                        <div class="form-group" wire:ignore>
                            <label for="karyawan_id">Karyawan (Pengisi)</label>
                            <select id="karyawan_id" class="form-control" wire:model="karyawan_id" data-selected="{{ $karyawan_id }}">
                                <option value="">Pilih karyawan</option>
                                @foreach ($drivers as $d)
                                <option value="{{ $d->id }}">{{ $d->nama_karyawan }}</option>
                                @endforeach
                            </select>
                            @error('karyawan_id') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Pilih karyawan yang melakukan pengisian.</small>
                        </div>
                        <div class="form-group">
                            <label for="jumlah_pengisian">Jumlah Pengisian (Liter)</label>
                            <input type="number" step="0.01" min="0" id="jumlah_pengisian" class="form-control" wire:model.live="jumlah_pengisian" placeholder="Masukkan jumlah liter">
                            @error('jumlah_pengisian') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Masukkan jumlah bahan bakar (liter), contoh: 125.50.</small>
                        </div>
                        <div class="form-group">
                            <label for="km">KM (Kilometer)</label>
                            <input type="number" step="1" min="0" id="km" class="form-control" wire:model.live="km" placeholder="Masukkan nilai KM">
                            @error('km') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Input odometer (KM) saat pengisian.</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="save">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Hapus -->
    @if($showDeleteModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close text-white" wire:click="closeDeleteModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center">
                        Apakah Anda yakin ingin menghapus data fuel ini?<br>
                        <strong>Data yang sudah dihapus tidak dapat dikembalikan.</strong>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <i class="fas fa-trash"></i> Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Inisialisasi Select2 untuk dropdown di modal Fuel (Unit & Karyawan) -->
    <script>
document.addEventListener('livewire:initialized', () => {
    function initSelect2() {
        const $modal = window.jQuery ? jQuery('#fuelModal') : null;
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
            return; // Select2 not available
        }
        // Find nearest Livewire component root
        const compRoot = document.getElementById('fuelModal')?.closest('[wire\\:id]');
        const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
            Livewire.find(compRoot.getAttribute('wire:id')) :
            null;

        // Unit
        const $u = jQuery('#unit_id');
        if ($u.length && !$u.hasClass('select2-hidden-accessible')) {
            $u.select2({ width: '100%', dropdownParent: $modal });
            $u.on('change', function () {
                const val = jQuery(this).val();
                comp && comp.set('unit_id', val);
            });
        }

        // Karyawan
        const $k = jQuery('#karyawan_id');
        if ($k.length && !$k.hasClass('select2-hidden-accessible')) {
            $k.select2({ width: '100%', dropdownParent: $modal });
            $k.on('change', function () {
                const val = jQuery(this).val();
                comp && comp.set('karyawan_id', val);
            });
        }
    }

    // Initialize Select2 for filter Unit in toolbar
    function initSelect2Filter() {
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
        const $f = jQuery('#filter_unit_id');
        if ($f.length) {
            // Find nearest Livewire component root from filter element
            const compRoot = $f.get(0).closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) :
                null;

            if (!$f.hasClass('select2-hidden-accessible')) {
                $f.select2({ width: '100%', placeholder: 'Semua unit', allowClear: true });
                $f.on('change', function () {
                    const val = jQuery(this).val();
                    comp && comp.set('filterUnitId', val);
                });
            }
        }
    }

    function destroySelect2() {
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
        const $u = jQuery('#unit_id');
        const $k = jQuery('#karyawan_id');
        if ($u.length && $u.hasClass('select2-hidden-accessible')) $u.select2('destroy');
        if ($k.length && $k.hasClass('select2-hidden-accessible')) $k.select2('destroy');
    }

    if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('fuel-modal-opened', () => setTimeout(initSelect2, 50));
        Livewire.on('fuel-modal-closed', () => destroySelect2());
    }

    if (window.Livewire && typeof Livewire.hook === 'function') {
        Livewire.hook('message.processed', () => {
            const modalVisible = document.getElementById('fuelModal');
            if (modalVisible) setTimeout(initSelect2, 50);
            // Re-init filter Unit after Livewire processes DOM
            setTimeout(initSelect2Filter, 50);
        });
    }

    // Initial filter Unit initialization when page ready
    setTimeout(initSelect2Filter, 0);
});
    </script>
</div>
