<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\TypeUnit;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'jenis_alat';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Form field
    public $jenis_alat = '';

    // Modal state
    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;

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
    }

    public function edit($id)
    {
        $t = TypeUnit::findOrFail($id);
        $this->editingId = $id;
        $this->jenis_alat = $t->jenis_alat;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate(TypeUnit::rules($this->editingId), TypeUnit::messages());
        try {
            if ($this->editingId) {
                TypeUnit::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data tipe unit berhasil diperbarui.');
            } else {
                TypeUnit::create($validated);
                session()->flash('message', 'Data tipe unit berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan tipe unit: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
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
                TypeUnit::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data tipe unit berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus tipe unit: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->jenis_alat = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $typeUnits = TypeUnit::query()
            ->when($this->search, function ($q) {
                $q->where('jenis_alat', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'typeUnits' => $typeUnits,
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
                    <a href="#">Data Tipe Unit</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Tipe Unit</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Tipe Unit
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
                                    <input type="text" class="form-control" placeholder="Cari tipe unit..."
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
                            <table class="table table-striped table-hover table-bordered table-timesheet">
                                <thead class="thead-dark">
                                    <tr>
                                        <th wire:click="sortBy('jenis_alat')" style="cursor: pointer;">
                                            Jenis Alat
                                            @if($sortField === 'jenis_alat')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($typeUnits as $tu)
                                    <tr>
                                        <td class="text-uppercase">{{ $tu->jenis_alat }}</td>
                                        <td>
                                            <button wire:click="edit({{ $tu->id }})" class="btn btn-warning btn-sm mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $tu->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Tidak ada data tipe unit yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="justify-content-between mt-3">
                                {{ $typeUnits->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Tipe Unit</h5>
                    <button type="button" class="close" wire:click="$set('showModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="form-group">
                            <label for="jenis_alat">Jenis Alat</label>
                            <input type="text" id="jenis_alat" class="form-control @error('jenis_alat') is-invalid @enderror" wire:model="jenis_alat">
                            @error('jenis_alat')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Batal</button>
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
                        Apakah Anda yakin ingin menghapus data tipe unit ini?<br>
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
