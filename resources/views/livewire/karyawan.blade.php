<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Karyawan, Jabatan, Devisi, Site, Pendidikan, Agama};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Properties untuk search dan filter
    public $search = '';
    public $statusFilter = '';
    public $sortField = 'nama_karyawan';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Properties untuk form Karyawan
    public $nama_karyawan = '';
    public $jenis_kelamin = 'Laki-laki';
    public $tempat_lahir = '';
    public $tanggal_lahir = '';
    public $jabatan_id = null;
    public $devisi_id = null;
    public $site_id = null;
    public $pendidikan_id = null;
    public $agama_id = null;
    public $tanggal_masuk = '';
    public $gaji_pokok = 0;
    public $status = 'aktif';

    // Data list untuk dropdown
    public $jabatans = [];
    public $devisis = [];
    public $sites = [];
    public $pendidikans = [];
    public $agamas = [];

    // Properties untuk edit
    public $editingId = null;
    public $showModal = false;

    // Properties untuk delete
    public $deletingId = null;
    public $showDeleteModal = false;

    public function mount()
    {
        $this->loadDropdowns();
    }

    private function ensureAdmin(): bool
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'admin') {
            session()->flash('error', 'Aksi tidak diizinkan.');
            return false;
        }
        return true;
    }

    private function loadDropdowns(): void
    {
        $this->jabatans = Jabatan::query()->orderBy('nama_jabatan')->get(['id', 'nama_jabatan']);
        $this->devisis = Devisi::query()->orderBy('nama_devisi')->get(['id', 'nama_devisi']);
        $this->sites = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
        $this->pendidikans = Pendidikan::query()->orderBy('tingkat_pendidikan')->get(['id', 'tingkat_pendidikan']);
        $this->agamas = Agama::query()->orderBy('nama_agama')->get(['id', 'nama_agama']);
    }

    // Reset pagination ketika search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
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
        if (!$this->ensureAdmin()) {
            return;
        }
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        if (!$this->ensureAdmin()) {
            return;
        }
        $k = Karyawan::findOrFail($id);
        $this->editingId = $id;
        $this->nama_karyawan = $k->nama_karyawan;
        $this->jenis_kelamin = $k->jenis_kelamin;
        $this->tempat_lahir = $k->tempat_lahir;
        $this->tanggal_lahir = $k->tanggal_lahir?->format('Y-m-d');
        $this->jabatan_id = $k->jabatan_id;
        $this->devisi_id = $k->devisi_id;
        $this->site_id = $k->site_id;
        $this->pendidikan_id = $k->pendidikan_id;
        $this->agama_id = $k->agama_id;
        $this->tanggal_masuk = $k->tanggal_masuk?->format('Y-m-d');
        $this->gaji_pokok = $k->gaji_pokok;
        $this->status = $k->status;
        $this->showModal = true;
    }

    public function save()
    {
        if (!$this->ensureAdmin()) {
            return;
        }
        $validated = $this->validate(Karyawan::rules(), Karyawan::messages());
        try {
            if ($this->editingId) {
                Karyawan::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data karyawan berhasil diperbarui.');
            } else {
                Karyawan::create($validated);
                session()->flash('message', 'Data karyawan berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan karyawan: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function confirmDelete($id)
    {
        if (!$this->ensureAdmin()) {
            return;
        }
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!$this->ensureAdmin()) {
            return;
        }
        try {
            if ($this->deletingId) {
                Karyawan::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data karyawan berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus karyawan: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->nama_karyawan = '';
        $this->jenis_kelamin = 'Laki-laki';
        $this->tempat_lahir = '';
        $this->tanggal_lahir = '';
        $this->jabatan_id = null;
        $this->devisi_id = null;
        $this->site_id = null;
        $this->pendidikan_id = null;
        $this->agama_id = null;
        $this->tanggal_masuk = '';
        $this->gaji_pokok = 0;
        $this->status = 'aktif';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $karyawans = Karyawan::query()
            ->with(['jabatan', 'devisi', 'site', 'pendidikan', 'agama'])
            ->when($this->search, function ($query) {
                $query->where('nama_karyawan', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'karyawans' => $karyawans,
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
                    <a href="#">Data Karyawan</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Karyawan</h4>
                            @if(auth()->user()->role === 'admin')
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Karyawan
                            </button>
                            @endif
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

                        <!-- Search and Filter -->
                        <div class="row mb-3 justify-content-between">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari karyawan..."
                                        wire:model.live.debounce.300ms="search">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" wire:model.live="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="aktif">Aktif</option>
                                    <option value="non-aktif">Non-aktif</option>
                                </select>
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

                        <div class="">
                            <!-- Table -->
                            <div class="table-responsive" style="max-height: 100vh; overflow-y: auto;">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th wire:click="sortBy('nama_karyawan')" style="cursor: pointer;">
                                                Nama Karyawan
                                                @if($sortField === 'nama_karyawan')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                                @endif
                                            </th>
                                            <th>J/K</th>
                                            <th>Jabatan</th>
                                            <th>Devisi</th>
                                            <th>Site</th>
                                            <th>Pendidikan</th>
                                            <th>Agama</th>
                                            <th wire:click="sortBy('tanggal_masuk')" style="cursor: pointer;">Tanggal Masuk</th>
                                            <th class="text-right" wire:click="sortBy('gaji_pokok')" style="cursor: pointer;">Gaji Pokok</th>
                                            <th class="text-center" wire:click="sortBy('status')" style="cursor: pointer;">Status
                                                @if($sortField === 'status')
                                                <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                                @endif
                                            </th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($karyawans as $karyawan)
                                        <tr>
                                            <td class="text-uppercase">{{ $karyawan->nama_karyawan }}</td>
                                            <td>{{ $karyawan->jenis_kelamin }}</td>
                                            <td>{{ $karyawan->jabatan->nama_jabatan ?? '-' }}</td>
                                            <td>{{ $karyawan->devisi->nama_devisi ?? '-' }}</td>
                                            <td>{{ $karyawan->site->nama_site ?? '-' }}</td>
                                            <td>{{ $karyawan->pendidikan->tingkat_pendidikan ?? '-' }}</td>
                                            <td>{{ $karyawan->agama->nama_agama ?? '-' }}</td>
                                            <td>{{ $karyawan->tanggal_masuk?->format('Y-m-d') }}</td>
                                            <td class="text-right">Rp. {{ number_format($karyawan->gaji_pokok, 2, ',', '.') }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-{{ $karyawan->status === 'aktif' ? 'success' : 'secondary' }}">
                                                    {{ ucfirst($karyawan->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if(auth()->user()->role === 'admin')
                                                <button wire:click="edit({{ $karyawan->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button wire:click="confirmDelete({{ $karyawan->id }})" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                @else
                                                <span class="text-muted">Tidak ada aksi</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="11" class="text-center">Tidak ada data karyawan yang ditemukan.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="justify-content-between mt-3">
                                    {{ $karyawans->links() }}
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
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Karyawan</h5>
                    <button type="button" class="close" wire:click="$set('showModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nama_karyawan">Nama Karyawan</label>
                                    <input type="text" id="nama_karyawan"
                                        class="form-control @error('nama_karyawan') is-invalid @enderror"
                                        wire:model="nama_karyawan">
                                    @error('nama_karyawan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jenis_kelamin">Jenis Kelamin</label>
                                    <select id="jenis_kelamin" class="form-control @error('jenis_kelamin') is-invalid @enderror" wire:model="jenis_kelamin">
                                        <option value="Laki-laki">Laki-laki</option>
                                        <option value="Perempuan">Perempuan</option>
                                    </select>
                                    @error('jenis_kelamin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tempat_lahir">Tempat Lahir</label>
                                    <input type="text" id="tempat_lahir" class="form-control @error('tempat_lahir') is-invalid @enderror" wire:model="tempat_lahir">
                                    @error('tempat_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_lahir">Tanggal Lahir</label>
                                    <input type="date" id="tanggal_lahir" class="form-control @error('tanggal_lahir') is-invalid @enderror" wire:model="tanggal_lahir">
                                    @error('tanggal_lahir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="jabatan_id">Jabatan</label>
                                    <select id="jabatan_id" class="form-control @error('jabatan_id') is-invalid @enderror" wire:model="jabatan_id">
                                        <option value="">-</option>
                                        @foreach($jabatans as $j)
                                        <option value="{{ $j->id }}">{{ $j->nama_jabatan }}</option>
                                        @endforeach
                                    </select>
                                    @error('jabatan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="devisi_id">Devisi</label>
                                    <select id="devisi_id" class="form-control @error('devisi_id') is-invalid @enderror" wire:model="devisi_id">
                                        <option value="">-</option>
                                        @foreach($devisis as $d)
                                        <option value="{{ $d->id }}">{{ $d->nama_devisi }}</option>
                                        @endforeach
                                    </select>
                                    @error('devisi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="pendidikan_id">Pendidikan</label>
                                    <select id="pendidikan_id" class="form-control @error('pendidikan_id') is-invalid @enderror" wire:model="pendidikan_id">
                                        <option value="">-</option>
                                        @foreach($pendidikans as $p)
                                        <option value="{{ $p->id }}">{{ $p->tingkat_pendidikan }}</option>
                                        @endforeach
                                    </select>
                                    @error('pendidikan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="agama_id">Agama</label>
                                    <select id="agama_id" class="form-control @error('agama_id') is-invalid @enderror" wire:model="agama_id">
                                        <option value="">-</option>
                                        @foreach($agamas as $a)
                                        <option value="{{ $a->id }}">{{ $a->nama_agama }}</option>
                                        @endforeach
                                    </select>
                                    @error('agama_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tanggal_masuk">Tanggal Masuk</label>
                                    <input type="date" id="tanggal_masuk" class="form-control @error('tanggal_masuk') is-invalid @enderror" wire:model="tanggal_masuk">
                                    @error('tanggal_masuk')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gaji_pokok">Gaji Pokok</label>
                                    <input type="number" step="0.01" id="gaji_pokok" class="form-control @error('gaji_pokok') is-invalid @enderror" wire:model="gaji_pokok" min="0">
                                    @error('gaji_pokok')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" class="form-control @error('status') is-invalid @enderror" wire:model="status">
                                        <option value="aktif">Aktif</option>
                                        <option value="non-aktif">Non-aktif</option>
                                    </select>
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        Apakah Anda yakin ingin menghapus data karyawan ini?<br>
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