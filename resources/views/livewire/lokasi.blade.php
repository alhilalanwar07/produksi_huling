<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Lokasi;

new class extends Component {
    use WithPagination;

    // Properties untuk search dan filter
    public $search = '';
    public $sortField = 'nama_lokasi';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Properties untuk form Lokasi
    public $nama_lokasi = '';
    public $kode_lokasi = '';

    // Properties untuk edit
    public $editingId = null;
    public $showModal = false;

    // Properties untuk delete
    public $deletingId = null;
    public $showDeleteModal = false;

    // Reset pagination ketika search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    // Sorting function
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

    // CRUD Operations
    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $this->editingId = $id;
        $this->nama_lokasi = $lokasi->nama_lokasi;
        $this->kode_lokasi = $lokasi->kode_lokasi;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'nama_lokasi' => 'required|string|max:255',
            'kode_lokasi' => 'required|string|max:50',
        ], [
            'nama_lokasi.required' => 'Nama lokasi tidak boleh kosong.',
            'nama_lokasi.string' => 'Nama lokasi harus berupa teks.',
            'nama_lokasi.max' => 'Nama lokasi maksimal 255 karakter.',
            'kode_lokasi.required' => 'Kode lokasi tidak boleh kosong.',
            'kode_lokasi.string' => 'Kode lokasi harus berupa teks.',
            'kode_lokasi.max' => 'Kode lokasi maksimal 50 karakter.',
        ]);

        if ($this->editingId) {
            Lokasi::findOrFail($this->editingId)->update($validated);
            session()->flash('message', 'Data lokasi berhasil diperbarui.');
        } else {
            Lokasi::create($validated);
            session()->flash('message', 'Data lokasi berhasil ditambahkan.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function confirmDelete($id)
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if ($this->deletingId) {
            Lokasi::findOrFail($this->deletingId)->delete();
            session()->flash('message', 'Data lokasi berhasil dihapus.');
            $this->showDeleteModal = false;
            $this->deletingId = null;
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->nama_lokasi = '';
        $this->kode_lokasi = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $lokasis = Lokasi::query()
            ->when($this->search, function ($query) {
                $query->where('nama_lokasi', 'like', '%' . $this->search . '%')
                      ->orWhere('kode_lokasi', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'lokasis' => $lokasis,
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
                    <a href="#">Data Lokasi</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Lokasi</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Lokasi
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <!-- Alert Message -->
                        @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif

                        <!-- Search and Filter -->
                        <div class="row mb-3 justify-content-between">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari lokasi..."
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
                        <div class="table-responsive" style="max-height: 100vh; overflow-y: auto;">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th wire:click="sortBy('nama_lokasi')" style="cursor: pointer;">
                                            Nama Lokasi
                                            @if($sortField === 'nama_lokasi')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th wire:click="sortBy('kode_lokasi')" style="cursor: pointer;">
                                            Kode Lokasi
                                            @if($sortField === 'kode_lokasi')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($lokasis as $lokasi)
                                    <tr>
                                        <td class="text-uppercase">{{ $lokasi->nama_lokasi }}</td>
                                        <td class="text-uppercase">{{ $lokasi->kode_lokasi }}</td>
                                        <td>
                                            <button wire:click="edit({{ $lokasi->id }})"
                                                class="btn btn-warning btn-sm mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $lokasi->id }})"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada data lokasi yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div>
                                Menampilkan {{ $lokasis->firstItem() }} sampai {{ $lokasis->lastItem() }} dari {{ $lokasis->total() }} data
                            </div>
                            <div>
                                {{ $lokasis->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Lokasi</h5>
                    <button type="button" class="close" wire:click="$set('showModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama_lokasi">Nama Lokasi</label>
                                    <input type="text" class="form-control @error('nama_lokasi') is-invalid @enderror"
                                        id="nama_lokasi" wire:model="nama_lokasi">
                                    @error('nama_lokasi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="kode_lokasi">Kode Lokasi</label>
                                    <input type="text" class="form-control @error('kode_lokasi') is-invalid @enderror"
                                        id="kode_lokasi" wire:model="kode_lokasi">
                                    @error('kode_lokasi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
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

    <!-- Delete Confirmation Modal -->
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
                        Apakah Anda yakin ingin menghapus data lokasi ini?<br>
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
