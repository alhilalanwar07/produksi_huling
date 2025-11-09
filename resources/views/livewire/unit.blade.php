<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Unit, Karyawan, TypeUnit, JenisUnit, Site};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'nomor_lambung';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Form fields
    public $nomor_lambung = '';
    public $karyawan_id = null;
    public $type_unit_id = null;
    public $jenis_unit_id = null;
    public $site_id = null;
    public $nomor_polisi = '';
    public $nomor_rangka = '';
    public $nomor_mesin = '';

    // Dropdown data
    public $drivers = [];
    public $typeUnits = [];
    public $jenisUnits = [];
    public $sites = [];

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
        $this->drivers = Karyawan::query()->orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $this->typeUnits = TypeUnit::query()->orderBy('jenis_alat')->get(['id', 'jenis_alat']);
        $this->jenisUnits = JenisUnit::query()->orderBy('nama_jenis')->get(['id', 'nama_jenis']);
        $this->sites = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
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
        $this->dispatch('unit-modal-opened');
    }

    public function edit($id)
    {
        $u = Unit::findOrFail($id);
        $this->editingId = $id;
        $this->nomor_lambung = $u->nomor_lambung;
        $this->karyawan_id = $u->karyawan_id;
        $this->type_unit_id = $u->type_unit_id;
        $this->jenis_unit_id = $u->jenis_unit_id;
        $this->site_id = $u->site_id;
        $this->nomor_polisi = $u->nomor_polisi;
        $this->nomor_rangka = $u->nomor_rangka;
        $this->nomor_mesin = $u->nomor_mesin;
        $this->showModal = true;
        $this->dispatch('unit-modal-opened');
    }

    public function save()
    {
        $validated = $this->validate(Unit::rules($this->editingId), Unit::messages());
        try {
            if ($this->editingId) {
                Unit::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data unit berhasil diperbarui.');
            } else {
                Unit::create($validated);
                session()->flash('message', 'Data unit berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('unit-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan unit: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('unit-modal-closed');
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
                Unit::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data unit berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus unit: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->nomor_lambung = '';
        $this->karyawan_id = null;
        $this->type_unit_id = null;
        $this->jenis_unit_id = null;
        $this->site_id = null;
        $this->nomor_polisi = '';
        $this->nomor_rangka = '';
        $this->nomor_mesin = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $units = Unit::query()
            ->with(['driver', 'typeUnit', 'jenisUnit', 'site'])
            ->when($this->search, function ($q) {
                $q->where('nomor_lambung', 'like', '%' . $this->search . '%')
                  ->orWhere('nomor_polisi', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'units' => $units,
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
                    <a href="#">Data Unit</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Unit</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Unit
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
                                    <input type="text" class="form-control" placeholder="Cari unit (lambung/polisi)..."
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
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th wire:click="sortBy('nomor_lambung')" style="cursor: pointer;">
                                            Nomor Lambung
                                            @if($sortField === 'nomor_lambung')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Driver</th>
                                        <th>Tipe Unit</th>
                                        <th>Jenis Unit</th>
                                        <th>Site</th>
                                        <th>Nomor Polisi</th>
                                        <th>Nomor Rangka</th>
                                        <th>Nomor Mesin</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($units as $unit)
                                    <tr>
                                        <td class="text-uppercase">{{ $unit->nomor_lambung }}</td>
                                        <td>{{ $unit->driver->nama_karyawan ?? '-' }}</td>
                                        <td>{{ $unit->typeUnit->jenis_alat ?? '-' }}</td>
                                        <td>{{ $unit->jenisUnit->nama_jenis ?? '-' }}</td>
                                        <td>{{ $unit->site->nama_site ?? '-' }}</td>
                                        <td>{{ $unit->nomor_polisi }}</td>
                                        <td>{{ $unit->nomor_rangka }}</td>
                                        <td>{{ $unit->nomor_mesin }}</td>
                                        <td>
                                            <button wire:click="edit({{ $unit->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $unit->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data unit yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="justify-content-between mt-3">
                            {{ $units->links() }}
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" id="unitModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Unit</h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nomor_lambung">Nomor Lambung</label>
                                    <input type="text" id="nomor_lambung" class="form-control @error('nomor_lambung') is-invalid @enderror" wire:model="nomor_lambung">
                                    @error('nomor_lambung')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="karyawan_id">Driver</label>
                                    <div wire:ignore>
                                        <select id="karyawan_id" class="form-control @error('karyawan_id') is-invalid @enderror" wire:model="karyawan_id">
                                            <option value="">-</option>
                                            @foreach($drivers as $d)
                                                <option value="{{ $d->id }}">{{ $d->nama_karyawan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('karyawan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="type_unit_id">Tipe Unit</label>
                                    <div wire:ignore>
                                        <select id="type_unit_id" class="form-control @error('type_unit_id') is-invalid @enderror" wire:model="type_unit_id">
                                            <option value="">-</option>
                                            @foreach($typeUnits as $tu)
                                                <option value="{{ $tu->id }}">{{ $tu->jenis_alat }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('type_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jenis_unit_id">Jenis Unit</label>
                                    <select id="jenis_unit_id" class="form-control @error('jenis_unit_id') is-invalid @enderror" wire:model="jenis_unit_id">
                                        <option value="">-</option>
                                        @foreach($jenisUnits as $ju)
                                            <option value="{{ $ju->id }}">{{ $ju->nama_jenis }}</option>
                                        @endforeach
                                    </select>
                                    @error('jenis_unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="site_id">Site</label>
                                    <select id="site_id" class="form-control @error('site_id') is-invalid @enderror" wire:model="site_id">
                                        <option value="">-</option>
                                        @foreach($sites as $s)
                                            <option value="{{ $s->id }}">{{ $s->nama_site }}</option>
                                        @endforeach
                                    </select>
                                    @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="nomor_polisi">Nomor Polisi</label>
                                    <input type="text" id="nomor_polisi" class="form-control @error('nomor_polisi') is-invalid @enderror" wire:model="nomor_polisi">
                                    @error('nomor_polisi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomor_rangka">Nomor Rangka</label>
                                    <input type="text" id="nomor_rangka" class="form-control @error('nomor_rangka') is-invalid @enderror" wire:model="nomor_rangka">
                                    @error('nomor_rangka')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nomor_mesin">Nomor Mesin</label>
                                    <input type="text" id="nomor_mesin" class="form-control @error('nomor_mesin') is-invalid @enderror" wire:model="nomor_mesin">
                                    @error('nomor_mesin')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        Apakah Anda yakin ingin menghapus data unit ini?<br>
                        <strong>Data yang sudah dihapus tidak dapat dikembalikan.</strong>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDeleteModal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-danger" wire:click="delete">
                        <i class="fas fa-trash"></i> Ya, Hapus Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Inisialisasi Select2 untuk dropdown di modal Unit -->
<script>
// Pastikan jQuery dan Select2 sudah dimuat di layout Anda
document.addEventListener('livewire:initialized', () => {
    function initSelect2() {
        const $modal = window.jQuery ? jQuery('#unitModal') : null;
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
            // Select2 belum tersedia; lewati tanpa error
            return;
        }
        // Temukan root komponen Livewire terdekat (hindari komponen lain seperti navigation)
        const compRoot = document.getElementById('unitModal')?.closest('[wire\\:id]');
        const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function')
            ? Livewire.find(compRoot.getAttribute('wire:id'))
            : null;
        // Karyawan
        const $k = jQuery('#karyawan_id');
        if ($k.length && !$k.hasClass('select2-hidden-accessible')) {
            $k.select2({ width: '100%', dropdownParent: $modal });
            $k.on('change', function () {
                const val = jQuery(this).val();
                comp && comp.set('karyawan_id', val);
            });
        }
        // Type Unit
        const $t = jQuery('#type_unit_id');
        if ($t.length && !$t.hasClass('select2-hidden-accessible')) {
            $t.select2({ width: '100%', dropdownParent: $modal });
            $t.on('change', function () {
                const val = jQuery(this).val();
                comp && comp.set('type_unit_id', val);
            });
        }
    }

    function destroySelect2() {
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
        const $k = jQuery('#karyawan_id');
        const $t = jQuery('#type_unit_id');
        if ($k.length && $k.hasClass('select2-hidden-accessible')) $k.select2('destroy');
        if ($t.length && $t.hasClass('select2-hidden-accessible')) $t.select2('destroy');
    }

    // Event dari Livewire untuk open/close modal
    if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('unit-modal-opened', () => {
            // Sedikit tunda untuk memastikan DOM siap
            setTimeout(initSelect2, 50);
        });
        Livewire.on('unit-modal-closed', () => {
            destroySelect2();
        });
    }

    // Re-init setelah DOM Livewire diproses (mis. setelah validasi/gagal simpan)
    if (window.Livewire && typeof Livewire.hook === 'function') {
        Livewire.hook('message.processed', () => {
            const modalVisible = document.getElementById('unitModal');
            if (modalVisible) {
                setTimeout(initSelect2, 50);
            }
        });
    }
});
</script>
