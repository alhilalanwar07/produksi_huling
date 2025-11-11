<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{TimeSheet, Unit, Karyawan, Lokasi, Site};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $filterDate = '';

    // Form fields
    public $tanggal = '';
    public $unit_id = null;
    public $karyawan_id = null;
    public $shift1_hm_awal = 0;
    public $shift1_hm_akhir = 0;
    public $shift2_hm_awal = null;
    public $shift2_hm_akhir = null;
    public $total_hm = 0;
    public $total_lembur = 0;
    public $keterangan = '';
    public $lokasi_id = null;
    public $site_id = null;

    // Dropdown data
    public $units = [];
    public $operators = [];
    public $lokasis = [];
    public $sites = [];

    // Modal state
    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;

    public function mount()
    {
        $this->loadDropdowns();
        $this->filterDate = date('Y-m-d');
    }

    private function loadDropdowns(): void
    {
        $this->units = Unit::query()
            ->with(['driver:id,nama_karyawan'])
            ->orderBy('nomor_lambung')
            ->get(['id', 'nomor_lambung', 'karyawan_id']);
        $this->operators = Karyawan::query()->orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $this->lokasis = Lokasi::query()->orderBy('nama_lokasi')->get(['id', 'nama_lokasi']);
        $this->sites = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
    }

    // Reset pagination ketika search berubah
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterDate()
    {
        $this->resetPage();
    }

    // Hitung total HM (Shift 1) otomatis
    private function computeTotalHm(): float
    {
        return round(max(0, (float)$this->shift1_hm_akhir - (float)$this->shift1_hm_awal), 2);
    }

    // Hitung total lembur (Shift 2) otomatis
    private function computeTotalLembur(): float
    {
        if ($this->shift2_hm_awal === null || $this->shift2_hm_akhir === null) {
            return 0.0;
        }
        return round(max(0, (float)$this->shift2_hm_akhir - (float)$this->shift2_hm_awal), 2);
    }

    // Recompute totals when inputs change
    public function updatedShift1HmAwal()
    {
        $this->total_hm = $this->computeTotalHm();
    }

    public function updatedShift1HmAkhir()
    {
        $this->total_hm = $this->computeTotalHm();
    }

    public function updatedShift2HmAwal()
    {
        $this->total_lembur = $this->computeTotalLembur();
    }

    public function updatedShift2HmAkhir()
    {
        $this->total_lembur = $this->computeTotalLembur();
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
        $this->dispatch('timesheet-modal-opened');
    }

    public function edit($id)
    {
        $ts = TimeSheet::findOrFail($id);
        $this->editingId = $id;
        $this->tanggal = $ts->tanggal?->format('Y-m-d');
        $this->unit_id = $ts->unit_id;
        $this->karyawan_id = $ts->karyawan_id;
        $this->shift1_hm_awal = $ts->shift1_hm_awal;
        $this->shift1_hm_akhir = $ts->shift1_hm_akhir;
        $this->shift2_hm_awal = $ts->shift2_hm_awal;
        $this->shift2_hm_akhir = $ts->shift2_hm_akhir;
        $this->total_hm = $ts->total_hm;
        $this->total_lembur = $ts->total_lembur;
        $this->keterangan = $ts->keterangan;
        $this->lokasi_id = $ts->lokasi_id;
        $this->site_id = $ts->site_id;
        $this->showModal = true;
        $this->dispatch('timesheet-modal-opened');
    }

    public function save()
    {
        // Hitung total HM (shift 1) dan total lembur (shift 2)
        $this->total_hm = $this->computeTotalHm();
        $this->total_lembur = $this->computeTotalLembur();

        $validated = $this->validate(TimeSheet::rules($this->editingId), TimeSheet::messages());
        try {
            if ($this->editingId) {
                TimeSheet::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data time sheet berhasil diperbarui.');
            } else {
                TimeSheet::create($validated);
                session()->flash('message', 'Data time sheet berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('timesheet-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan time sheet: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('timesheet-modal-closed');
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
                TimeSheet::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data time sheet berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus time sheet: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->karyawan_id = null;
        $this->shift1_hm_awal = 0;
        $this->shift1_hm_akhir = 0;
        $this->shift2_hm_awal = null;
        $this->shift2_hm_akhir = null;
        $this->total_hm = 0;
        $this->total_lembur = 0;
        $this->keterangan = '';
        $this->lokasi_id = null;
        $this->site_id = null;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $timeSheets = TimeSheet::query()
            ->with(['unit', 'operator', 'lokasi', 'mitra'])
            ->when($this->filterDate, function ($q) {
                $q->whereDate('tanggal', $this->filterDate);
            })
            ->when($this->search, function ($q) {
                $q->where('keterangan', 'like', '%' . $this->search . '%')
                  ->orWhereHas('unit', function ($u) {
                      $u->where('nomor_lambung', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('operator', function ($o) {
                      $o->where('nama_karyawan', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('lokasi', function ($l) {
                      $l->where('nama_lokasi', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('mitra', function ($m) {
                      $m->where('nama_site', 'like', '%' . $this->search . '%');
                  });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'timeSheets' => $timeSheets,
        ];
    }
}; ?>

<div>
    <style>
        /* Perkecil ukuran teks header tabel Time Sheet */
        .table-timesheet thead th {
            font-size: 0.75rem;
            padding: 0.5rem 0.6rem;
            text-align: center;
        }
        .table-timesheet thead tr:nth-child(2) th {
            font-size: 0.7rem;
        }
    </style>
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
                    <a href="#">Data Time Sheet</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Time Sheet</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Time Sheet
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
                            <div class="col-md-7 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari (lambung/operator/lokasi/mitra/keterangan)..."
                                        wire:model.live.debounce.300ms="search">
                                </div>
                            </div>
                            <div class="col-md-3 mb-2">
                                <select class="form-control" wire:model.live="perPage">
                                    <option value="10">10 per halaman</option>
                                    <option value="25">25 per halaman</option>
                                    <option value="50">50 per halaman</option>
                                    <option value="100">100 per halaman</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <div class="input-group">
                                    <input type="date" class="form-control" wire:model.live="filterDate">
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered table-timesheet">
                                <thead class="thead-dark">
                                    <tr>
                                        <th rowspan="2" class="text-center" style="vertical-align: middle;">no</th>
                                        <th rowspan="2" style="vertical-align: middle; cursor: pointer;" wire:click="sortBy('tanggal')">tanggal
                                            @if($sortField === 'tanggal')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th rowspan="2" style="vertical-align: middle;">no lambung</th>
                                        <th rowspan="2" style="vertical-align: middle;">opr</th>
                                        <th rowspan="2" style="vertical-align: middle;">mitra</th>
                                        <th colspan="3" class="text-center">shift 1</th>
                                        <th colspan="3" class="text-center">shift 2</th>
                                        <th rowspan="2" class="text-center" style="vertical-align: middle;">total hm</th>
                                        <th rowspan="2" style="vertical-align: middle;">aksi</th>
                                    </tr>
                                    <tr>
                                        <th class="text-center">hm awal</th>
                                        <th class="text-center">hm akhir</th>
                                        <th class="text-center">total hm</th>
                                        <th class="text-center">hm awal</th>
                                        <th class="text-center">hm akhir</th>
                                        <th class="text-center">total lembur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($timeSheets as $ts)
                                    <tr>
                                        <td class="text-center">{{ ($timeSheets->currentPage()-1) * $timeSheets->perPage() + $loop->iteration }}</td>
                                        <td class="text-nowrap">{{ $ts->tanggal?->format('Y-m-d') }}</td>
                                        <td class="text-uppercase">{{ $ts->unit->nomor_lambung ?? '-' }}</td>
                                        <td>{{ $ts->operator->nama_karyawan ?? '-' }}</td>
                                        <td>{{ $ts->mitra->nama_site ?? '-' }}</td>
                                        <td class="text-center">{{ number_format($ts->shift1_hm_awal, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($ts->shift1_hm_akhir, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($ts->total_hm, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($ts->shift2_hm_awal ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($ts->shift2_hm_akhir ?? 0, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format($ts->total_lembur, 2, ',', '.') }}</td>
                                        <td class="text-center">{{ number_format(($ts->total_hm + $ts->total_lembur), 2, ',', '.') }}</td>
                                        <td>
                                            <button wire:click="edit({{ $ts->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $ts->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="13" class="text-center">Tidak ada data time sheet yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class=" justify-content-between mt-3">
                            {{ $timeSheets->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" id="timesheetModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-xl" >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Time Sheet</h5>
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
                                    <label for="unit_id">No Lambung</label>
                                    <div wire:ignore>
                                        <select id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" wire:model="unit_id">
                                            <option value="">-</option>
                                            @foreach($units as $u)
                                                <option value="{{ $u->id }}">{{ $u->nomor_lambung }} — {{ $u->driver->nama_karyawan ?? '-' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="karyawan_id">Operator</label>
                                    <div wire:ignore>
                                        <select id="karyawan_id" class="form-control @error('karyawan_id') is-invalid @enderror" wire:model="karyawan_id">
                                            <option value="">-</option>
                                            @foreach($operators as $op)
                                                <option value="{{ $op->id }}">{{ $op->nama_karyawan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('karyawan_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12"><h6>Shift 1</h6></div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="shift1_hm_awal">HM Awal</label>
                                    <input type="number" step="0.01" id="shift1_hm_awal" class="form-control @error('shift1_hm_awal') is-invalid @enderror" wire:model.live="shift1_hm_awal" min="0">
                                    @error('shift1_hm_awal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="shift1_hm_akhir">HM Akhir</label>
                                    <input type="number" step="0.01" id="shift1_hm_akhir" class="form-control @error('shift1_hm_akhir') is-invalid @enderror" wire:model.live="shift1_hm_akhir" min="0">
                                    @error('shift1_hm_akhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="total_hm">Total HM</label>
                                    <input type="number" step="0.01" id="total_hm" class="form-control @error('total_hm') is-invalid @enderror" wire:model.live="total_hm" min="0" readonly>
                                    @error('total_hm')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <br>
                        <div class="row">
                            <div class="col-md-12"><h6>Shift 2</h6></div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="shift2_hm_awal">HM Awal</label>
                                    <input type="number" step="0.01" id="shift2_hm_awal" class="form-control @error('shift2_hm_awal') is-invalid @enderror" wire:model.live="shift2_hm_awal" min="0">
                                    @error('shift2_hm_awal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="shift2_hm_akhir">HM Akhir</label>
                                    <input type="number" step="0.01" id="shift2_hm_akhir" class="form-control @error('shift2_hm_akhir') is-invalid @enderror" wire:model.live="shift2_hm_akhir" min="0">
                                    @error('shift2_hm_akhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="total_lembur">Total Lembur</label>
                                    <input type="number" step="0.01" id="total_lembur" class="form-control @error('total_lembur') is-invalid @enderror" wire:model.live="total_lembur" min="0" readonly>
                                    @error('total_lembur')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="keterangan">Keterangan/Kegiatan</label>
                                    <input type="text" id="keterangan" class="form-control @error('keterangan') is-invalid @enderror" wire:model="keterangan">
                                    @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="lokasi_id">Lokasi</label>
                                    <div wire:ignore>
                                        <select id="lokasi_id" class="form-control @error('lokasi_id') is-invalid @enderror" wire:model="lokasi_id">
                                            <option value="">-</option>
                                            @foreach($lokasis as $l)
                                                <option value="{{ $l->id }}">{{ $l->nama_lokasi }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('lokasi_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="site_id">Mitra/Penerima</label>
                                    <div wire:ignore>
                                        <select id="site_id" class="form-control @error('site_id') is-invalid @enderror" wire:model="site_id">
                                            <option value="">-</option>
                                            @foreach($sites as $s)
                                                <option value="{{ $s->id }}">{{ $s->nama_site }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <!-- total_hm + total_lembur = total_hm_akhir -->
                             <div class="col-md-2">
                                <div class="form-group">
                                    <label for="total_hm_akhir">Total HM Akhir</label>
                                    <input type="number" step="0.01" id="total_hm_akhir" class="form-control @error('total_hm_akhir') is-invalid @enderror" value="{{ number_format($total_hm + $total_lembur, 2, '.', '') }}" min="0" readonly>
                                    @error('total_hm_akhir')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        Apakah Anda yakin ingin menghapus data time sheet ini?<br>
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

<!-- Inisialisasi Select2 untuk dropdown di modal Time Sheet -->
<script>
document.addEventListener('livewire:initialized', () => {
    function initSelect2() {
        const $modal = window.jQuery ? jQuery('#timesheetModal') : null;
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
            return;
        }
        const compRoot = document.getElementById('timesheetModal')?.closest('[wire\\:id]');
        const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function')
            ? Livewire.find(compRoot.getAttribute('wire:id'))
            : null;
        const ids = ['unit_id', 'karyawan_id', 'lokasi_id', 'site_id'];
        ids.forEach((id) => {
            const $el = jQuery('#' + id);
            if ($el.length && !$el.hasClass('select2-hidden-accessible')) {
                $el.select2({ width: '100%', dropdownParent: $modal });
                $el.on('change', function () {
                    const val = jQuery(this).val();
                    comp && comp.set(id, val);
                });
            }
        });
    }

    function destroySelect2() {
        if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
        ['unit_id', 'karyawan_id', 'lokasi_id', 'site_id'].forEach((id) => {
            const $el = jQuery('#' + id);
            if ($el.length && $el.hasClass('select2-hidden-accessible')) $el.select2('destroy');
        });
    }

    if (window.Livewire && typeof Livewire.on === 'function') {
        Livewire.on('timesheet-modal-opened', () => {
            setTimeout(initSelect2, 50);
        });
        Livewire.on('timesheet-modal-closed', () => {
            destroySelect2();
        });
    }

    if (window.Livewire && typeof Livewire.hook === 'function') {
        Livewire.hook('message.processed', () => {
            const modalVisible = document.getElementById('timesheetModal');
            if (modalVisible) {
                setTimeout(initSelect2, 50);
            }
        });
    }
});
</script>



