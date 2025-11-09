<?php

use App\Models\Deposit;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    use WithPagination;

    // Properties untuk search dan filter
    public $search = '';
    public $sortField = 'nama_site';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Properties untuk form Site
    public $nama_site = '';
    public $status = 'SITE';

    // Properties untuk modal Deposit
    public $depositSiteId = null;
    public $depositModal = false;
    public $aksiRetase = 'tambah'; // tambah | kurangi
    public $aksiDeposit = 'tambah'; // tambah | kurangi
    public $depositJumlahRetase = 0;
    public $depositJumlahDeposit = 0;

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
        $site = Site::findOrFail($id);
        $this->editingId = $id;
        $this->nama_site = $site->nama_site;
        $this->status = $site->status;
        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate([
            'nama_site' => 'required|string|max:255',
            'status' => 'required|in:MITRA,SITE',
        ]);

        if ($this->editingId) {
            Site::findOrFail($this->editingId)->update($validated);
            session()->flash('message', 'Data site/mitra berhasil diperbarui.');
        } else {
            Site::create($validated);
            session()->flash('message', 'Data site/mitra berhasil ditambahkan.');
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
            Site::findOrFail($this->deletingId)->delete();
            session()->flash('message', 'Data site/mitra berhasil dihapus.');
            $this->showDeleteModal = false;
            $this->deletingId = null;
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->nama_site = '';
        $this->status = 'SITE';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $sites = Site::query()
            ->when($this->search, function ($query) {
                $query->where('nama_site', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'sites' => $sites,
        ];
    }

    // Deposit actions
    public function openDepositModal($siteId)
    {
        $this->depositSiteId = $siteId;
        $this->aksiDeposit = 'tambah';
        $this->depositJumlahRetase = 0;
        $this->depositJumlahDeposit = 0;
        $this->depositModal = true;
    }

    public function saveDeposit()
    {
        $this->validate([
            'aksiRetase' => 'required|in:tambah,kurangi',
            'aksiDeposit' => 'required|in:tambah,kurangi',
            'depositJumlahRetase' => 'nullable|integer|min:0',
            'depositJumlahDeposit' => 'nullable|integer|min:0',
        ], [
            'aksiRetase.required' => 'Pilih aksi retase.',
            'aksiRetase.in' => 'Aksi retase tidak valid.',
            'aksiDeposit.required' => 'Pilih aksi deposit.',
            'aksiDeposit.in' => 'Aksi deposit tidak valid.',
            'depositJumlahRetase.integer' => 'Jumlah retase harus berupa angka bulat.',
            'depositJumlahRetase.min' => 'Jumlah retase minimal 0.',
            'depositJumlahDeposit.integer' => 'Jumlah deposit harus berupa angka bulat.',
            'depositJumlahDeposit.min' => 'Jumlah deposit minimal 0.',
        ]);

        // Minimal salah satu diisi
        if ((int)$this->depositJumlahRetase <= 0 && (int)$this->depositJumlahDeposit <= 0) {
            session()->flash('error', 'Masukkan jumlah retase atau deposit (minimal salah satu).');
            return;
        }

        $site = Site::findOrFail($this->depositSiteId);

        $plannedRetase = $this->aksiRetase === 'tambah'
            ? (int)$this->depositJumlahRetase
            : -(int)$this->depositJumlahRetase;

        $plannedDeposit = $this->aksiDeposit === 'tambah'
            ? (int)$this->depositJumlahDeposit
            : -(int)$this->depositJumlahDeposit;

        $newTotalRetase = $site->total_retase + $plannedRetase;
        $newTotalDeposit = $site->total_deposit + $plannedDeposit;

        // Aturan:
        // - total retase >= 0
        // - total deposit >= 0 (tidak boleh minus)
        // - sisa retase = total_retase - total_deposit >= 0
        if ($newTotalRetase < 0) {
            session()->flash('error', 'Total retase tidak boleh kurang dari 0.');
            return;
        }
        if ($newTotalDeposit < 0) {
            session()->flash('error', 'Total deposit tidak boleh kurang dari 0.');
            return;
        }
        if (($newTotalRetase - $newTotalDeposit) < 0) {
            session()->flash('error', 'Sisa retase tidak boleh kurang dari 0.');
            return;
        }

        Deposit::create([
            'site_id' => $this->depositSiteId,
            'jumlah_retase' => $plannedRetase,
            'jumlah_deposit' => $plannedDeposit,
        ]);

        session()->flash('message', 'Perubahan deposit/retase berhasil disimpan.');
        $this->depositModal = false;
        $this->depositSiteId = null;
        $this->aksiRetase = 'tambah';
        $this->aksiDeposit = 'tambah';
        $this->depositJumlahRetase = 0;
        $this->depositJumlahDeposit = 0;
        $this->resetPage();
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
                    <a href="#">Data Site & Mitra</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Site & Mitra</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Site/Mitra
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
                                    <input type="text" class="form-control" placeholder="Cari site/mitra..."
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
                                        <th wire:click="sortBy('nama_site')" style="cursor: pointer;">
                                            Nama Site/Mitra
                                            @if($sortField === 'nama_site')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th class="text-center">Total Retase</th>
                                        <th class="text-center">Total Deposit</th>
                                        <th class="text-center">Sisa Retase</th>
                                        <th class="text-center" wire:click="sortBy('status')" style="cursor: pointer;">
                                            Status
                                            @if($sortField === 'status')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($sites as $site)
                                    <tr>
                                        <td class="text-uppercase">{{ $site->nama_site }}</td>
                                        <td class="text-center">{{ number_format($site->total_retase, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($site->total_deposit, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($site->sisa_retase, 0, ',', '.') }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-{{ $site->status === 'MITRA' ? 'primary' : 'black' }}">
                                                {{ $site->status }}
                                            </span>
                                        </td>
                                        <td>
                                            <button wire:click="edit({{ $site->id }})"
                                                class="btn btn-warning btn-sm mr-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="openDepositModal({{ $site->id }})"
                                                class="btn btn-success btn-sm mr-1">
                                                <i class="fas fa-wallet"></i> Deposit
                                            </button>
                                            <button wire:click="confirmDelete({{ $site->id }})"
                                                class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data mitra yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="justify-content-between mt-3">
                            {{ $sites->links() }}
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
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Site/Mitra</h5>
                    <button type="button" class="close" wire:click="$set('showModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="nama_site">Nama Site/Mitra</label>
                                    <input type="text" class="form-control @error('nama_site') is-invalid @enderror"
                                        id="nama_site" wire:model="nama_site">
                                    @error('nama_site')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select class="form-control @error('status') is-invalid @enderror"
                                        id="status" wire:model="status">
                                        <option value="SITE">SITE</option>
                                        <option value="MITRA">MITRA</option>
                                    </select>
                                    @error('status')
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
                        Apakah Anda yakin ingin menghapus data site/mitra ini?<br>
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

    <!-- Deposit Modal -->
    @if($depositModal)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Kelola Deposit</h5>
                    <button type="button" class="close text-white" wire:click="$set('depositModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (session()->has('error'))
                    <div class="alert alert-danger" role="alert">
                        {{ session('error') }}
                    </div>
                    @endif
                    <div class="form-group">
                        <label>Aksi Retase</label>
                        <div class="d-flex">
                            <div class="form-check mr-3">
                                <input class="form-check-input" type="radio" id="aksi_retase_tambah" value="tambah" wire:model="aksiRetase">
                                <label class="form-check-label" for="aksi_retase_tambah">Tambah</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="aksi_retase_kurangi" value="kurangi" wire:model="aksiRetase">
                                <label class="form-check-label" for="aksi_retase_kurangi">Kurangi</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="depositJumlahRetase">Jumlah Retase</label>
                        <input type="number" id="depositJumlahRetase" class="form-control @error('depositJumlahRetase') is-invalid @enderror" wire:model="depositJumlahRetase" min="0">
                        @error('depositJumlahRetase')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Isi angka retase untuk ditambah/dikurangi.</small>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label>Aksi Deposit</label>
                        <div class="d-flex">
                            <div class="form-check mr-3">
                                <input class="form-check-input" type="radio" id="aksi_deposit_tambah" value="tambah" wire:model="aksiDeposit">
                                <label class="form-check-label" for="aksi_deposit_tambah">Tambah</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" id="aksi_deposit_kurangi" value="kurangi" wire:model="aksiDeposit">
                                <label class="form-check-label" for="aksi_deposit_kurangi">Kurangi</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="depositJumlahDeposit">Jumlah Deposit</label>
                        <input type="number" id="depositJumlahDeposit" class="form-control @error('depositJumlahDeposit') is-invalid @enderror" wire:model="depositJumlahDeposit" min="0">
                        @error('depositJumlahDeposit')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Isi angka deposit untuk ditambah/dikurangi.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="$set('depositModal', false)">Batal</button>
                    <button type="button" class="btn btn-success" wire:click="saveDeposit">Simpan</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>