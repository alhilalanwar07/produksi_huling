<?php

// Komponen Livewire Volt untuk halaman Standby.
// Menjaga konsistensi struktur, penamaan, layout, dan gaya dengan halaman Fuel.
// Fitur: pencarian, sorting, pagination, filter, CRUD, validasi, dan tampilan responsif.

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Standby, Unit};
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
    public $filterUnitId = null; // konsisten dengan halaman Fuel, gunakan id unit (UI)

    // Form fields (UI): unit_id menggunakan id Unit; akan di-map ke nomor_lambung ketika simpan
    public $tanggal = '';
    public $unit_id = null;
    public $alasan = '';

    // Dropdown data
    public $units = [];

    // Modal state
    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;

    // Inisialisasi dropdown dengan cache (TTL 2 menit)
    public function mount()
    {
        $this->loadDropdowns();
    }

    private function loadDropdowns(): void
    {
        $this->units = Cache::remember('standby_units_dropdown', 120, function () {
            return Unit::query()
                ->with('typeUnit:id,jenis_alat')
                ->orderBy('nomor_lambung')
                ->get(['id', 'nomor_lambung', 'type_unit_id']);
        });
    }

    // Reset pagination ketika search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Sorting toggle ASC/DESC
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
        $this->dispatch('standby-modal-opened');
    }

    public function edit($id)
    {
        try {
            $s = Standby::findOrFail($id);
            $this->editingId = $id;
            $this->tanggal = optional($s->tanggal)->format('Y-m-d');
            // Map dari nomor_lambung (DB) ke id unit (UI)
            $this->unit_id = optional($s->unit)->id;
            $this->alasan = $s->alasan;
            $this->showModal = true;
            $this->dispatch('standby-modal-opened');
        } catch (ModelNotFoundException $e) {
            session()->flash('error', 'Data standby tidak ditemukan.');
        } catch (\Throwable $e) {
            Log::error('Gagal memuat standby: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat data standby.');
        }
    }

    public function save()
    {
        // Validasi form (UI). unit_id menggunakan id Unit, bukan nomor_lambung.
        $validated = $this->validate(
            [
                'tanggal' => ['required', 'date'],
                'unit_id' => ['required', 'exists:units,id'],
                'alasan'  => ['required', 'string'],
            ],
            [
                'tanggal.required' => 'Tanggal wajib diisi.',
                'tanggal.date' => 'Format tanggal tidak valid.',
                'unit_id.required' => 'Unit wajib dipilih.',
                'unit_id.exists' => 'Unit tidak ditemukan.',
                'alasan.required' => 'Alasan wajib diisi.',
                'alasan.string' => 'Alasan harus berupa teks.',
            ]
        );

        try {
            // Map ke nomor_lambung untuk disimpan ke kolom standby.unit_id (string)
            $unit = Unit::find($validated['unit_id']);
            $unitNomorLambung = $unit ? $unit->nomor_lambung : null;

            if (!$unitNomorLambung) {
                throw new \RuntimeException('Unit tidak valid untuk penyimpanan.');
            }

            $payload = [
                'tanggal' => $validated['tanggal'],
                'unit_id' => $unitNomorLambung,
                'alasan'  => $validated['alasan'],
            ];

            if ($this->editingId) {
                Standby::findOrFail($this->editingId)->update($payload);
                session()->flash('message', 'Data standby berhasil diperbarui.');
            } else {
                Standby::create($payload);
                session()->flash('message', 'Data standby berhasil ditambahkan.');
            }

            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('standby-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan standby: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data standby.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('standby-modal-closed');
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
                Standby::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data standby berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus standby: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data standby.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->alasan = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        // Query utama dengan filter, pencarian, dan sorting.
        $query = Standby::query()
            ->with(['unit.typeUnit'])
            ->when($this->search, function ($q) {
                $q->where('alasan', 'like', '%' . $this->search . '%')
                    ->orWhereHas('unit', function ($uq) {
                        $uq->where('nomor_lambung', 'like', '%' . $this->search . '%');
                    });
            })
            ->when($this->filterUnitId, fn($q) => $q->whereHas('unit', fn($uq) => $uq->where('id', $this->filterUnitId)))
            ->when($this->filterDate, fn($q) => $q->whereDate('tanggal', $this->filterDate));

        // Sorting support termasuk nomor_lambung via join ke units
        if ($this->sortField === 'nomor_lambung') {
            $query->leftJoin('units', 'units.nomor_lambung', '=', 'standby.unit_id')
                ->orderBy('units.nomor_lambung', $this->sortDirection)
                ->select('standby.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $standbys = $query->paginate($this->perPage);

        return [
            'standbys' => $standbys,
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
                    <a href="#">Data Standby</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Standby</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Standby
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
                                <input type="text" class="form-control" placeholder="Cari (lambung/alasan)..."
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
                            <table class="table table-striped table-hover table-bordered table-sm table-timesheet">
                                <thead class="thead-dark">
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
                                        <th style="cursor: pointer;" wire:click="sortBy('alasan')">Alasan
                                            @if ($sortField === 'alasan')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th class="text-right" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($standbys as $s)
                                    <tr>
                                        <td>{{ optional($s->tanggal)->format('d/m/Y') }}</td>
                                        <td>{{ optional($s->unit)->nomor_lambung }}</td>
                                        <td>{{ optional(optional($s->unit)->typeUnit)->jenis_alat }}</td>
                                        <td>{{ $s->alasan }}</td>
                                        <td class="text-right">
                                            <button class="btn btn-warning btn-sm mb-1" wire:click="edit({{ $s->id }})">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" wire:click="confirmDelete({{ $s->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Tidak ada data.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-3">
                            {{ $standbys->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" id="standbyModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Standby</h5>
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
                            <small class="form-text text-muted">Tanggal standby unit.</small>
                        </div>
                        <div class="form-group">
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
                            <small class="form-text text-muted">Pilih unit berdasarkan nomor lambung; disimpan sebagai nomor lambung.</small>
                        </div>
                        <div class="form-group">
                            <label for="alasan">Alasan</label>
                            <textarea id="alasan" class="form-control" rows="3" wire:model.live="alasan" placeholder="Masukkan alasan standby"></textarea>
                            @error('alasan') <small class="text-danger">{{ $message }}</small> @enderror
                            <small class="form-text text-muted">Deskripsikan alasan unit dalam kondisi standby.</small>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Konfirmasi Hapus -->
    @if($showDeleteModal)
    <div class="modal fade show" id="deleteStandbyModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="close text-white" wire:click="closeDeleteModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <p class="text-center">
                        Apakah Anda yakin ingin menghapus data standby ini?<br>
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
</div>

<!-- Inisialisasi Select2 dan sinkronisasi dengan Livewire -->
<script>
    document.addEventListener('livewire:initialized', () => {
        function initSelect2() {
            const $modal = window.jQuery ? jQuery('#standbyModal') : null;
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
                return; // Select2 not available
            }
            // Find nearest Livewire component root
            const compRoot = document.getElementById('standbyModal')?.closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) :
                null;

            // Unit
            const $u = jQuery('#unit_id');
            if ($u.length && !$u.hasClass('select2-hidden-accessible')) {
                $u.select2({
                    width: '100%',
                    dropdownParent: $modal,
                    placeholder: 'Pilih unit',
                    allowClear: true
                });
                $u.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('unit_id', val);
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
                    $f.select2({
                        width: '100%',
                        placeholder: 'Semua unit',
                        allowClear: true
                    });
                    $f.on('change', function() {
                        const val = jQuery(this).val();
                        comp && comp.set('filterUnitId', val);
                    });
                }
            }
        }

        function destroySelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            const $u = jQuery('#unit_id');
            if ($u.length && $u.hasClass('select2-hidden-accessible')) $u.select2('destroy');
        }

        if (window.Livewire && typeof Livewire.on === 'function') {
            Livewire.on('standby-modal-opened', () => setTimeout(initSelect2, 50));
            Livewire.on('standby-modal-closed', () => destroySelect2());
        }

        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('message.processed', () => {
                const modalVisible = document.getElementById('standbyModal');
                if (modalVisible) setTimeout(initSelect2, 50);
                // Re-init filter Unit after Livewire processes DOM
                setTimeout(initSelect2Filter, 50);
            });
        }

        // Initial filter Unit initialization when page ready
        setTimeout(initSelect2Filter, 0);
    });
</script>