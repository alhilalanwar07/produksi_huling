<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{RetaseTonasePms, RetaseTonaseItem, Unit, Karyawan, Site, Material};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Dropdown data
    public $units = [];
    public $drivers = [];
    public $sites = [];
    public $materials = [];
    // Track baris yang sedang dibuka (untuk persist saat navigasi/paginate)
    public $openRows = [];

    // Modal state
    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;

    // Header fields
    public $tanggal;
    public $unit_id = null;
    public $driver_id = null;
    public $jumlah_retase_per_tonase = 10;
    public $catatan = '';

    // Detail inputs (per item)
    public $detail_site_id = null;
    public $detail_material_id = null;
    public $detail_tonase = null;
    public $items = [];

    public function mount()
    {
        $this->loadDropdowns();
        $this->tanggal = now()->toDateString();
        $this->jumlah_retase_per_tonase = 10;
    }

    private function loadDropdowns(): void
    {
        $this->units = Unit::query()->orderBy('nomor_lambung')->get(['id', 'nomor_lambung']);
        $this->drivers = Karyawan::query()->orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $this->sites = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
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
        $this->showModal = true;
        $this->dispatch('pms-modal-opened');
    }

    // Toggle collapse baris (persist state di server)
    public function toggleRow($id)
    {
        $id = (int) $id;
        if (in_array($id, $this->openRows, true)) {
            $this->openRows = array_values(array_diff($this->openRows, [$id]));
        } else {
            $this->openRows[] = $id;
        }
    }

    public function edit($id)
    {
        $pms = RetaseTonasePms::with(['items.site', 'items.material', 'unit', 'driver'])->findOrFail($id);
        $this->editingId = $id;
        $this->tanggal = optional($pms->tanggal)->format('Y-m-d');
        $this->unit_id = $pms->unit_id;
        $this->driver_id = $pms->driver_id;
        $this->jumlah_retase_per_tonase = (int) $pms->jumlah_retase_per_tonase;
        $this->catatan = $pms->catatan ?? '';
        $this->items = $pms->items->map(function ($it) {
            return [
                'site_id' => $it->site_id,
                'site_name' => optional($it->site)->nama_site,
                'material_id' => $it->material_id,
                'material_name' => optional($it->material)->nama_material,
                'tonase' => (float) $it->tonase,
            ];
        })->toArray();
        $this->showModal = true;
        $this->dispatch('pms-modal-opened');
    }

    public function save()
    {
        // Validasi input dengan pesan kustom
        $messages = [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal harus berupa tanggal yang valid.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'unit_id.exists' => 'Unit yang dipilih tidak valid.',
            'driver_id.required' => 'Driver wajib dipilih.',
            'driver_id.exists' => 'Driver yang dipilih tidak valid.',
            'jumlah_retase_per_tonase.required' => 'Jumlah retase per tonase wajib diisi.',
            'jumlah_retase_per_tonase.integer' => 'Jumlah retase per tonase harus berupa angka bulat.',
            'jumlah_retase_per_tonase.min' => 'Jumlah retase per tonase minimal 1.',
            'catatan.string' => 'Catatan harus berupa teks.',
            'items.required' => 'Minimal harus ada satu item tonase.',
            'items.array' => 'Daftar item harus berupa array.',
            'items.min' => 'Minimal harus ada satu item tonase.',
            'items.*.site_id.required' => 'Penyewa pada setiap item wajib dipilih.',
            'items.*.site_id.exists' => 'Penyewa pada item tidak valid.',
            'items.*.material_id.required' => 'Material pada setiap item wajib dipilih.',
            'items.*.material_id.exists' => 'Material pada item tidak valid.',
            'items.*.tonase.required' => 'Tonase pada setiap item wajib diisi.',
            'items.*.tonase.numeric' => 'Tonase pada item harus berupa angka.',
            'items.*.tonase.min' => 'Tonase pada item minimal 0,01 ton.',
        ];

        $validated = $this->validate([
            'tanggal' => ['required', 'date'],
            'unit_id' => ['required', 'exists:units,id'],
            'driver_id' => ['required', 'exists:karyawans,id'],
            'jumlah_retase_per_tonase' => ['required', 'integer', 'min:1'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.site_id' => ['required', 'exists:sites,id'],
            'items.*.material_id' => ['required', 'exists:materials,id'],
            'items.*.tonase' => ['required', 'numeric', 'min:0.01'],
        ], $messages);

        try {
            if ($this->editingId) {
                $pms = RetaseTonasePms::findOrFail($this->editingId);
                $pms->update([
                    'tanggal' => $this->tanggal,
                    'unit_id' => $this->unit_id,
                    'driver_id' => $this->driver_id,
                    'jumlah_retase_per_tonase' => $this->jumlah_retase_per_tonase,
                    'catatan' => $this->catatan,
                ]);
                $pms->items()->delete();
            } else {
                $pms = RetaseTonasePms::create([
                    'tanggal' => $this->tanggal,
                    'unit_id' => $this->unit_id,
                    'driver_id' => $this->driver_id,
                    'jumlah_retase_per_tonase' => $this->jumlah_retase_per_tonase,
                    'catatan' => $this->catatan,
                ]);
            }

            foreach ($this->items as $it) {
                RetaseTonaseItem::create([
                    'pms_id' => $pms->id,
                    'site_id' => $it['site_id'],
                    'material_id' => $it['material_id'],
                    'tonase' => $it['tonase'],
                ]);
            }

            session()->flash('message', 'Data PMS retase berhasil disimpan.');
            $this->showModal = false;
            $this->dispatch('pms-modal-closed');
            $this->resetForm();
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan PMS retase: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('pms-modal-closed');
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
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
                RetaseTonasePms::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data PMS retase berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus PMS retase: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = now()->toDateString();
        $this->unit_id = null;
        $this->driver_id = null;
        $this->jumlah_retase_per_tonase = 10;
        $this->catatan = '';
        $this->detail_site_id = null;
        $this->detail_material_id = null;
        $this->detail_tonase = null;
        $this->items = [];
    }

    public function addItem()
    {
        $messages = [
            'detail_site_id.required' => 'Penyewa wajib dipilih.',
            'detail_site_id.exists' => 'Penyewa yang dipilih tidak valid.',
            'detail_material_id.required' => 'Material wajib dipilih.',
            'detail_material_id.exists' => 'Material yang dipilih tidak valid.',
            'detail_tonase.required' => 'Tonase wajib diisi.',
            'detail_tonase.numeric' => 'Tonase harus berupa angka.',
            'detail_tonase.min' => 'Tonase minimal 0,01 ton.',
            'jumlah_retase_per_tonase.required' => 'Jumlah retase per tonase wajib diisi.',
            'jumlah_retase_per_tonase.integer' => 'Jumlah retase per tonase harus berupa angka bulat.',
            'jumlah_retase_per_tonase.min' => 'Jumlah retase per tonase minimal 1.',
        ];

        $this->validate([
            'detail_site_id' => ['required', 'exists:sites,id'],
            'detail_material_id' => ['required', 'exists:materials,id'],
            'detail_tonase' => ['required', 'numeric', 'min:0.01'],
            'jumlah_retase_per_tonase' => ['required', 'integer', 'min:1'],
        ], $messages);

        $site = collect($this->sites)->firstWhere('id', (int) $this->detail_site_id);
        $material = collect($this->materials)->firstWhere('id', (int) $this->detail_material_id);
        $siteName = $site['nama_site'] ?? null;
        $materialName = $material['nama_material'] ?? null;

        $this->items[] = [
            'site_id' => (int) $this->detail_site_id,
            'site_name' => $siteName,
            'material_id' => (int) $this->detail_material_id,
            'material_name' => $materialName,
            'tonase' => (float) $this->detail_tonase,
        ];

        $this->detail_site_id = null;
        $this->detail_material_id = null;
        $this->detail_tonase = null;

        $this->dispatch('item-added-reset-select2');
    }

    public function resetItems()
    {
        $this->items = [];
    }

    public function removeItem($index)
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function getTotalRetaseProperty()
    {
        $per = (int) ($this->jumlah_retase_per_tonase ?? 0);
        return $per * count($this->items);
    }

    public function getTotalTonaseProperty()
    {
        $per = (int) ($this->jumlah_retase_per_tonase ?? 0);
        $sumTonase = array_sum(array_map(fn($i) => (float) ($i['tonase'] ?? 0), $this->items));
        return $sumTonase * $per;
    }

    public function with(): array
    {
        $list = RetaseTonasePms::query()
            ->with(['unit', 'driver', 'items.site', 'items.material'])
            ->when($this->search, function ($q) {
                $q->where('tanggal', 'like', '%' . $this->search . '%')
                    ->orWhereHas('unit', fn($uq) => $uq->where('nomor_lambung', 'like', '%' . $this->search . '%'))
                    ->orWhereHas('driver', fn($dq) => $dq->where('nama_karyawan', 'like', '%' . $this->search . '%'));
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return [
            'pmsList' => $list,
        ];
    }
}; ?>

<div x-data>
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
                    <a href="#">Hauling Panjang PMS</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Retase per Tonase</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Data
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session()->has('message'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('message') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif
                        @if (session()->has('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        @endif

                        <div class="row mb-3 justify-content-between">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari (tanggal/lambung/driver)..."
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

                        <div class="table-responsive table-container position-relative" aria-live="polite" aria-busy="false">
                            <!-- Loading overlay -->
                            <div class="loading-overlay" wire:loading.class="show" wire:target="search,sortBy,perPage,create,edit,confirmDelete,delete,save,toggleRow">
                                <div class="spinner-border text-secondary" role="status" aria-live="polite">
                                    <span class="visually-hidden">Memuat...</span>
                                </div>
                            </div>
                            {{-- Tabel utama dengan hover highlight, spacing konsisten, dan visual hierarchy --}}
                            <table class="table table-bordered table-hover align-middle shadow-sm" role="table" aria-describedby="pms-table-desc">
                                <thead class="thead-dark">
                                    <tr>
                                        {{-- 1. HEADER IKON KECIL --}}
                                        <th style="width: 60px;" scope="col" class="text-center">Detail</th>

                                        <th
                                            role="columnheader"
                                            scope="col"
                                            class="sortable text-md-center"
                                            wire:click="sortBy('tanggal')"
                                            wire:keydown.enter="sortBy('tanggal')"
                                            tabindex="0"
                                            aria-sort="{{ $sortField === 'tanggal' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                            style="cursor: pointer;">
                                            Tanggal
                                            @if($sortField === 'tanggal')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th
                                            role="columnheader"
                                            scope="col"
                                            class="sortable text-md-center"
                                            wire:click="sortBy('unit_id')"
                                            wire:keydown.enter="sortBy('unit_id')"
                                            tabindex="0"
                                            aria-sort="{{ $sortField === 'unit_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                            style="cursor: pointer;">
                                            Unit
                                            @if($sortField === 'unit_id')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th
                                            role="columnheader"
                                            scope="col"
                                            class="sortable text-md-center"
                                            wire:click="sortBy('driver_id')"
                                            wire:keydown.enter="sortBy('driver_id')"
                                            tabindex="0"
                                            aria-sort="{{ $sortField === 'driver_id' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}"
                                            style="cursor: pointer;">
                                            Driver
                                            @if($sortField === 'driver_id')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th scope="col" class="text-md-center">Retase/Tonase</th>
                                        <th scope="col" class="text-md-end">Total Tonase</th>
                                        <th scope="col" class="text-md-end">Total Retase</th>
                                        <th scope="col" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($pmsList as $pms)
                                    {{-- 2. TAMBAHKAN align-middle --}}
                                    <tr class="align-middle">

                                        {{-- 3. TOMBOL IKON BARU --}}
                                        <td>
                                            <button
                                                id="toggle-{{ $pms->id }}"
                                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
                                                type="button"
                                                aria-expanded="{{ in_array($pms->id, $openRows) ? 'true' : 'false' }}"
                                                aria-controls="pms-items-{{ $pms->id }}"
                                                wire:click="toggleRow({{ $pms->id }})"
                                                title="Lihat Detail">
                                                <i class="fas {{ in_array($pms->id, $openRows) ? 'fa-chevron-down' : 'fa-chevron-right' }} toggle-icon"></i>
                                                <span class="sr-only">Tampilkan/sembunyikan item</span>
                                            </button>
                                        </td>

                                        <td>{{ optional($pms->tanggal)->format('Y-m-d') ?? $pms->tanggal }}</td>
                                        <td class="text-uppercase">{{ $pms->unit->nomor_lambung ?? '-' }}</td>
                                        <td>{{ $pms->driver->nama_karyawan ?? '-' }}</td>
                                        <td class="text-md-center"><span class="badge badge-info">{{ $pms->jumlah_retase_per_tonase }}</span></td>
                                        <td class="text-md-end">{{ number_format($pms->total_tonase, 2) }}</td>
                                        <td class="text-md-end">{{ number_format($pms->total_retase, 0) }}</td>
                                        <td class="text-center">
                                            <button wire:click="edit({{ $pms->id }})" class="btn btn-warning btn-sm mr-1 mb-1" aria-label="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $pms->id }})" class="btn btn-danger btn-sm" aria-label="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    <tr class="collapse-row">
                                        @if(in_array($pms->id, $openRows))
                                        <td colspan="8">
                                            <div
                                                id="pms-items-{{ $pms->id }}"
                                                role="region"
                                                aria-labelledby="toggle-{{ $pms->id }}"
                                                x-show="$wire.openRows.includes({{ $pms->id }})"
                                                x-collapse>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered mb-0">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th colspan="3">Penyewa</th>
                                                                <th colspan="2">Material</th>
                                                                <th>Tonase</th>
                                                                <th>Retase</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse($pms->items as $it)
                                                            <tr>
                                                                <td colspan="3">{{ optional($it->site)->nama_site ?? '-' }}</td>
                                                                <td colspan="2">{{ optional($it->material)->nama_material ?? '-' }}</td>
                                                                <td class="text-end">{{ number_format((float) $it->tonase, 2) }}</td>
                                                                <td class="text-end">{{ number_format((float) $it->tonase * (int)$pms->jumlah_retase_per_tonase, 0) }}</td>
                                                            </tr>
                                                            @empty
                                                            <tr>
                                                                <td colspan="3" class="text-center text-muted py-3">
                                                                    <i class="fas fa-inbox me-2"></i> Tidak ada item untuk entri ini.
                                                                </td>
                                                            </tr>
                                                            @endforelse
                                                            <!-- Total rows -->
                                                            <tr class="table-light fw-bold">
                                                                <td colspan="5" class="text-end">Total:</td>
                                                                <td class="text-end">{{ number_format($pms->items->sum('tonase'), 2) }}</td>
                                                                <td class="text-end">{{ number_format($pms->items->sum('tonase') * (int)$pms->jumlah_retase_per_tonase, 2) }}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <i class="fas fa-inbox me-2"></i>
                                                <span>Tidak ada data PMS retase yang ditemukan.</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3 gap-2">
                            <div class="text-muted small">
                                Menampilkan {{ $pmsList->firstItem() ?? 0 }}–{{ $pmsList->lastItem() ?? 0 }} dari {{ $pmsList->total() }} entri
                            </div>
                            <div>
                                {{ $pmsList->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="modal fade show" id="pmsModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Input Retase dan Tonase</h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal <span class="text-danger">*</span></label>
                                    <input type="date" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" wire:model="tanggal">
                                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="unit_id">Pilih Unit <span class="text-danger">*</span></label>
                                    <div wire:ignore>
                                        <select id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" wire:model="unit_id">
                                            <option value="">- Pilih Unit -</option>
                                            @foreach($units as $u)
                                            <option value="{{ $u->id }}">{{ $u->nomor_lambung }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="driver_id">Pilih Driver <span class="text-danger">*</span></label>
                                    <div wire:ignore>
                                        <select id="driver_id" class="form-control @error('driver_id') is-invalid @enderror" wire:model="driver_id">
                                            <option value="">- Pilih Driver -</option>
                                            @foreach($drivers as $d)
                                            <option value="{{ $d->id }}">{{ $d->nama_karyawan }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('driver_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="jumlah_retase_per_tonase">Retase per Tonase <span class="text-danger">*</span></label>
                                    <input type="number" min="1" id="jumlah_retase_per_tonase" class="form-control @error('jumlah_retase_per_tonase') is-invalid @enderror" wire:model="jumlah_retase_per_tonase">
                                    @error('jumlah_retase_per_tonase')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="detail_site_id">Pilih Penyewa <span class="text-danger">*</span></label>
                                    <div wire:ignore>
                                        <select id="detail_site_id" class="form-control @error('detail_site_id') is-invalid @enderror" wire:model="detail_site_id">
                                            <option value="">- Pilih Penyewa -</option>
                                            @foreach($sites as $s)
                                            <option value="{{ $s->id }}">{{ $s->nama_site }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('detail_site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="detail_material_id">Material <span class="text-danger">*</span></label>
                                    <div wire:ignore>
                                        <select id="detail_material_id" class="form-control @error('detail_material_id') is-invalid @enderror" wire:model="detail_material_id">
                                            <option value="">- Pilih Material -</option>
                                            @foreach($materials as $m)
                                            <option value="{{ $m->id }}">{{ $m->nama_material }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('detail_material_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="detail_tonase">Tonase (Ton) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" id="detail_tonase" class="form-control @error('detail_tonase') is-invalid @enderror" wire:model="detail_tonase" placeholder="Masukkan tonase">
                                    @error('detail_tonase')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div>
                                    <button class="btn btn-success mb-2" type="button" wire:click="addItem"><i class="fas fa-plus"></i> Tambah</button>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Ringkasan</label>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="form-control d-flex justify-content-between align-items-center">
                                                Jumlah Retase: <b>{{ $this->totalRetase }}</b>
                                            </div>
                                        </div>
                                        <div class="col-md-5 ">
                                            <div class="form-control d-flex justify-content-between align-items-center">
                                                Total Tonase: <b>{{ number_format($this->totalTonase, 2) }} ton</b>
                                            </div>
                                        </div>
                                        <div class="col-md-2 text-end">
                                            <button class="btn btn-danger" type="button" wire:click="resetItems">Reset Semua</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                            <div class="form-group">
                                <h6>Daftar Tonase</h6>
                                <div class="table-responsive mb-3">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Penyewa</th>
                                                <th>Material</th>
                                                <th>Tonase</th>
                                                <th>Subtotal Tonase (x {{ $jumlah_retase_per_tonase }})</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($items as $idx => $it)
                                            <tr>
                                                <td>{{ $it['site_name'] ?? '-' }}</td>
                                                <td>{{ $it['material_name'] ?? '-' }}</td>
                                                <td>{{ number_format($it['tonase'], 2) }}</td>
                                                <td>{{ number_format(($it['tonase'] ?? 0) * $jumlah_retase_per_tonase, 2) }}</td>
                                                <td>
                                                    <button class="btn btn-danger btn-sm" type="button" wire:click="removeItem({{ $idx }})"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center">Belum ada item.</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">Tutup</button>
                    <button type="button" class="btn btn-primary" wire:click="save">Simpan Data</button>
                </div>
            </div>
        </div>
    </div>
    @endif

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
                        Apakah Anda yakin ingin menghapus data PMS retase ini?<br>
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
    <style>
        /* Visual hierarchy, spacing, hover shadow, and focus for sortable headers */
        .table-container {
            border-radius: .25rem;
            padding: .5rem;
        }

        .table thead th {
            border-bottom: 2px solid rgba(0, 0, 0, .1);
            letter-spacing: .02em;
        }

        .table tbody tr:hover {
            box-shadow: 0 1px 6px rgba(0, 0, 0, .08);
            transition: box-shadow .15s ease;
        }

        .sortable:focus {
            outline: 2px dashed #6c757d;
            outline-offset: 2px;
        }

        /* Loading overlay */
        .loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .7);
            z-index: 5;
            opacity: 0;
            pointer-events: none;
            /* klik tetap jalan saat overlay tidak aktif */
            transition: opacity .2s ease;
        }

        .loading-overlay.show {
            opacity: 1;
            pointer-events: auto;
            /* hanya blok interaksi ketika loading aktif */
        }

        /* Collapse detail row background to separate from main rows */
        .collapse-row {
            background-color: #f8f9fa;
        }
    </style>
</div>


<script>
    document.addEventListener('livewire:initialized', () => {
        function initSelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
                return;
            }
            const $modal = window.jQuery ? jQuery('#pmsModal') : null;
            const compRoot = document.getElementById('pmsModal')?.closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) : null;

            const pairs = [
                ['#unit_id', 'unit_id'],
                ['#driver_id', 'driver_id'],
                ['#detail_site_id', 'detail_site_id'],
                ['#detail_material_id', 'detail_material_id']
            ];
            pairs.forEach(([selector, prop]) => {
                const $el = jQuery(selector);
                if ($el.length && !$el.hasClass('select2-hidden-accessible')) {
                    $el.select2({
                        width: '100%',
                        dropdownParent: $modal
                    });
                    $el.on('change', function() {
                        const val = jQuery(this).val();
                        comp && comp.set(prop, val);
                    });
                }
            });
        }

        function destroySelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            ['#unit_id', '#driver_id', '#detail_site_id', '#detail_material_id'].forEach(function(sel) {
                const $el = jQuery(sel);
                if ($el.length && $el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
            });
        }

        function resetDetailSelects() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            jQuery('#detail_site_id').val(null).trigger('change');
            jQuery('#detail_material_id').val(null).trigger('change');
        }

        if (window.Livewire && typeof Livewire.on === 'function') {
            Livewire.on('pms-modal-opened', () => {
                setTimeout(initSelect2, 50);
            });
            Livewire.on('pms-modal-closed', () => {
                destroySelect2();
            });
            Livewire.on('item-added-reset-select2', resetDetailSelects);
        }

        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('message.processed', () => {
                const modalVisible = document.getElementById('pmsModal');
                if (modalVisible) {
                    setTimeout(initSelect2, 50);
                }
            });
        }
    });
</script>