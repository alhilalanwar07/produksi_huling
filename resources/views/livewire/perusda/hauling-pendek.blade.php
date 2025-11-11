<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{HaulingPendekPerusda, Unit, Karyawan, Site, Material};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Form fields
    public $tanggal = '';
    public $unit_id = null;
    public $karyawan_id = null;
    public $mitra_id = null;
    public $material_id = null;
    public $jumlah_retase = 1;

    // Dropdown data
    public $units = [];
    public $karyawans = [];
    public $mitras = [];
    public $materials = [];

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
        $this->units = Unit::query()->orderBy('nomor_lambung')->get(['id', 'nomor_lambung']);
        $this->karyawans = Karyawan::query()->orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $this->mitras = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
        $this->materials = Material::query()->orderBy('nama_material')->get(['id', 'nama_material']);
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
        $this->tanggal = now()->toDateString();
        $this->jumlah_retase = 1;
        $this->showModal = true;
        $this->dispatch('hauling-pendek-modal-opened');
    }

    public function edit($id)
    {
        $hp = HaulingPendekPerusda::findOrFail($id);
        $this->editingId = $id;
        $this->tanggal = $hp->tanggal?->toDateString();
        $this->unit_id = $hp->unit_id;
        $this->karyawan_id = $hp->karyawan_id;
        $this->mitra_id = $hp->mitra_id;
        $this->material_id = $hp->material_id;
        $this->jumlah_retase = $hp->jumlah_retase;
        $this->showModal = true;
        $this->dispatch('hauling-pendek-modal-opened');
    }

    public function save()
    {
        $validated = $this->validate($this->rules(), $this->messages());
        try {
            if ($this->editingId) {
                HaulingPendekPerusda::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data hauling pendek berhasil diperbarui.');
            } else {
                HaulingPendekPerusda::create($validated);
                session()->flash('message', 'Data hauling pendek berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('hauling-pendek-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan hauling pendek: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('hauling-pendek-modal-closed');
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
                HaulingPendekPerusda::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data hauling pendek berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus hauling pendek: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->karyawan_id = null;
        $this->mitra_id = null;
        $this->material_id = null;
        $this->jumlah_retase = 1;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'unit_id' => ['required', 'exists:units,id'],
            'karyawan_id' => ['required', 'exists:karyawans,id'],
            'mitra_id' => ['required', 'exists:sites,id'],
            'material_id' => ['required', 'exists:materials,id'],
            'jumlah_retase' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',
            'karyawan_id.required' => 'Driver wajib dipilih.',
            'karyawan_id.exists' => 'Driver tidak valid.',
            'mitra_id.required' => 'Mitra wajib dipilih.',
            'mitra_id.exists' => 'Mitra tidak valid.',
            'material_id.required' => 'Material wajib dipilih.',
            'material_id.exists' => 'Material tidak valid.',
            'jumlah_retase.required' => 'Jumlah retase wajib diisi.',
            'jumlah_retase.integer' => 'Jumlah retase harus angka.',
            'jumlah_retase.min' => 'Jumlah retase minimal 1.',
        ];
    }

    public function with(): array
    {
        $records = HaulingPendekPerusda::query()
            ->with(['unit', 'karyawan', 'mitra', 'material'])
            ->when($this->search, function ($q) {
                $q->whereHas('unit', function ($q2) {
                    $q2->where('nomor_lambung', 'like', '%' . $this->search . '%');
                })->orWhereHas('karyawan', function ($q3) {
                    $q3->where('nama_karyawan', 'like', '%' . $this->search . '%');
                })->orWhereHas('mitra', function ($q4) {
                    $q4->where('nama_site', 'like', '%' . $this->search . '%');
                })->orWhereHas('material', function ($q5) {
                    $q5->where('nama_material', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'records' => $records,
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
                    <a href="#">Perusda</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Hauling Pendek</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Data Hauling Pendek Perusda</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Data
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

                        <!-- Search and PerPage -->
                        <div class="row mb-3 justify-content-between">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari (unit/driver/mitra/material)..."
                                        wire:model.live.debounce.300ms="search">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control" wire:model.live="perPage">
                                    <option value="10">10 per halaman</option>
                                    <option value="25">25 per halaman</option>
                                    <option value="50">50 per halaman</option>
                                    <option value="100">100 per halaman</option>
                                </select>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th wire:click="sortBy('tanggal')" style="cursor: pointer;">
                                            Tanggal
                                            @if($sortField === 'tanggal')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Unit</th>
                                        <th>Driver</th>
                                        <th>Mitra</th>
                                        <th>Material</th>
                                        <th class="text-right">Retase</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($records as $r)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}</td>
                                        <td class="text-uppercase">{{ $r->unit->nomor_lambung ?? '-' }}</td>
                                        <td>{{ $r->karyawan->nama_karyawan ?? '-' }}</td>
                                        <td>{{ $r->mitra->nama_site ?? '-' }}</td>
                                        <td class="text-uppercase">{{ $r->material->nama_material ?? '-' }}</td>
                                        <td class="text-right">{{ number_format($r->jumlah_retase) }}</td>
                                        <td>
                                            <button wire:click="edit({{ $r->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $r->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data hauling pendek.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="justify-content-between mt-3">
                            {{ $records->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div id="haulingPendekModal" class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Hauling Pendek</h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal</label>
                                    <input type="date" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" wire:model="tanggal">
                                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unit_id">Unit</label>
                                    <div wire:ignore>
                                        <select id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" wire:model="unit_id">
                                            <option value="">-</option>
                                            @foreach($units as $u)
                                            <option value="{{ $u->id }}">{{ $u->nomor_lambung }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="karyawan_id">Driver</label>
                                    <div wire:ignore>
                                        <select id="karyawan_id" class="form-control @error('karyawan_id') is-invalid @enderror" wire:model="karyawan_id">
                                            <option value="">-</option>
                                            @foreach($karyawans as $d)
                                            <option value="{{ $d->id }}">{{ $d->nama_karyawan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('karyawan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="mitra_id">Mitra</label>
                                    <div wire:ignore>
                                        <select id="mitra_id" class="form-control @error('mitra_id') is-invalid @enderror" wire:model="mitra_id">
                                            <option value="">-</option>
                                            @foreach($mitras as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama_site }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('mitra_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="material_id">Material</label>
                                    <div wire:ignore>
                                        <select id="material_id" class="form-control @error('material_id') is-invalid @enderror" wire:model="material_id">
                                            <option value="">-</option>
                                            @foreach($materials as $mat)
                                            <option value="{{ $mat->id }}">{{ strtoupper($mat->nama_material) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('material_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jumlah_retase">Jumlah Retase</label>
                                    <input type="number" min="1" id="jumlah_retase" class="form-control @error('jumlah_retase') is-invalid @enderror" wire:model="jumlah_retase">
                                    @error('jumlah_retase')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
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
                        Apakah Anda yakin ingin menghapus data ini?<br>
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

    <!-- Select2 init seperti halaman Unit -->
<script>
    // Pastikan jQuery dan Select2 sudah dimuat di layout Anda
    document.addEventListener('livewire:initialized', () => {
        function initSelect2() {
            const $modal = window.jQuery ? jQuery('#haulingPendekModal') : null;
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
                // Select2 belum tersedia; lewati tanpa error
                return;
            }
            // Temukan root komponen Livewire terdekat (hindari komponen lain seperti navigation)
            const compRoot = document.getElementById('haulingPendekModal')?.closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) :
                null;

            // Unit
            const $u = jQuery('#unit_id');
            if ($u.length && !$u.hasClass('select2-hidden-accessible')) {
                $u.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $u.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('unit_id', val);
                });
            }

            // Karyawan
            const $k = jQuery('#karyawan_id');
            if ($k.length && !$k.hasClass('select2-hidden-accessible')) {
                $k.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $k.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('karyawan_id', val);
                });
            }

            // Mitra
            const $m = jQuery('#mitra_id');
            if ($m.length && !$m.hasClass('select2-hidden-accessible')) {
                $m.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $m.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('mitra_id', val);
                });
            }

            // Material
            const $mat = jQuery('#material_id');
            if ($mat.length && !$mat.hasClass('select2-hidden-accessible')) {
                $mat.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $mat.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('material_id', val);
                });
            }
        }

        function destroySelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            const $u = jQuery('#unit_id');
            const $k = jQuery('#karyawan_id');
            const $m = jQuery('#mitra_id');
            const $mat = jQuery('#material_id');
            if ($u.length && $u.hasClass('select2-hidden-accessible')) $u.select2('destroy');
            if ($k.length && $k.hasClass('select2-hidden-accessible')) $k.select2('destroy');
            if ($m.length && $m.hasClass('select2-hidden-accessible')) $m.select2('destroy');
            if ($mat.length && $mat.hasClass('select2-hidden-accessible')) $mat.select2('destroy');
        }

        // Event dari Livewire untuk open/close modal
        if (window.Livewire && typeof Livewire.on === 'function') {
            Livewire.on('hauling-pendek-modal-opened', () => {
                // Sedikit tunda untuk memastikan DOM siap
                setTimeout(initSelect2, 50);
            });
            Livewire.on('hauling-pendek-modal-closed', () => {
                destroySelect2();
            });
        }

        // Re-init setelah DOM Livewire diproses (mis. setelah validasi/gagal simpan)
        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('message.processed', () => {
                const modalVisible = document.getElementById('haulingPendekModal');
                if (modalVisible) {
                    setTimeout(initSelect2, 50);
                }
            });
        }
    });
</script>
</div>
