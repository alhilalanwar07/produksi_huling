<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Breakdown, Unit};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    // Pencarian, sorting, pagination
    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Form fields
    public $tanggal = '';
    public $unit_id = null;
    public $status = '';
    public $keterangan = '';

    // Dropdown data
    public $units = [];

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
        $this->units = Unit::query()
            ->with('typeUnit')
            ->orderBy('nomor_lambung')
            ->get(['id', 'nomor_lambung', 'type_unit_id']);
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
        $this->dispatch('breakdown-modal-opened');
    }

    public function edit($id)
    {
        $b = Breakdown::with('unit')->findOrFail($id);
        $this->editingId = $id;
        $this->tanggal = $b->tanggal ? $b->tanggal->format('Y-m-d') : '';
        $this->unit_id = $b->unit_id;
        $this->status = $b->status;
        $this->keterangan = $b->keterangan ?? '';
        $this->showModal = true;
        $this->dispatch('breakdown-modal-opened');
    }

    public function save()
    {
        $validated = $this->validate(Breakdown::rules($this->editingId), Breakdown::messages());
        try {
            if ($this->editingId) {
                Breakdown::findOrFail($this->editingId)->update($validated);
                session()->flash('message', 'Data breakdown berhasil diperbarui.');
            } else {
                Breakdown::create($validated);
                session()->flash('message', 'Data breakdown berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan breakdown: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('breakdown-modal-closed');
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
                Breakdown::findOrFail($this->deletingId)->delete();
                session()->flash('message', 'Data breakdown berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus breakdown: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->status = '';
        $this->keterangan = '';
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $query = Breakdown::query()->with(['unit'])
            ->when($this->search, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('status', 'like', '%' . $this->search . '%')
                        ->orWhere('keterangan', 'like', '%' . $this->search . '%')
                        ->orWhereHas('unit', function ($uq) {
                            $uq->where('nomor_lambung', 'like', '%' . $this->search . '%');
                        });
                });
            });

        if ($this->sortField === 'nomor_lambung') {
            $query->leftJoin('units', 'breakdowns.unit_id', '=', 'units.id')
                ->select('breakdowns.*')
                ->orderBy('units.nomor_lambung', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $breakdowns = $query->paginate($this->perPage);

        return [
            'breakdowns' => $breakdowns,
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
                    <a href="#">Unit</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Data Breakdown</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Breakdown</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Breakdown
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
                                    <input type="text" class="form-control" placeholder="Cari breakdown (lambung/status/keterangan)..."
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
                                        <th wire:click="sortBy('tanggal')" style="cursor: pointer;">
                                            Tanggal
                                            @if($sortField === 'tanggal')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th wire:click="sortBy('nomor_lambung')" style="cursor: pointer;">
                                            Nomor Lambung
                                            @if($sortField === 'nomor_lambung')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th wire:click="sortBy('status')" style="cursor: pointer;">
                                            Status
                                            @if($sortField === 'status')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Keterangan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($breakdowns as $bd)
                                    <tr>
                                        <td>{{ optional($bd->tanggal)->format('Y-m-d') ?? $bd->tanggal }}</td>
                                        <td class="text-uppercase">{{ $bd->unit->nomor_lambung ?? '-' }}</td>
                                        <td>{{ $bd->status }}</td>
                                        <td>{{ $bd->keterangan }}</td>
                                        <td>
                                            <button wire:click="edit({{ $bd->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $bd->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Tidak ada data breakdown yang ditemukan.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="justify-content-between mt-3">
                            {{ $breakdowns->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah/Edit -->
    @if($showModal)
    <div class="modal fade show" id="breakdownModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Breakdown</h5>
                    <button type="button" class="close" wire:click="closeModal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="tanggal">Tanggal</label>
                                    <input type="date" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror" wire:model="tanggal">
                                    @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="unit_id">Unit (Nomor Lambung — Tipe Unit)</label>
                                    <div wire:ignore>
                                        <select id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" wire:model="unit_id">
                                            <option value="">Pilih Nomor Lambung</option>
                                            @foreach($units as $u)
                                            <option value="{{ $u->id }}">{{ $u->nomor_lambung }} — {{ $u->typeUnit->jenis_alat ?? '-' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('unit_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <input type="text" id="status" class="form-control @error('status') is-invalid @enderror" wire:model="status" placeholder="Status breakdown">
                                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="keterangan">Keterangan</label>
                                    <textarea id="keterangan" rows="3" class="form-control @error('keterangan') is-invalid @enderror" wire:model="keterangan" placeholder="Keterangan breakdown"></textarea>
                                    @error('keterangan')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        Apakah Anda yakin ingin menghapus data breakdown ini?<br>
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
            const $modal = window.jQuery ? jQuery('#breakdownModal') : null;
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
                // Select2 belum tersedia; lewati tanpa error
                return;
            }
            // Temukan root komponen Livewire terdekat (hindari komponen lain seperti navigation)
            const compRoot = document.getElementById('breakdownModal')?.closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) :
                null;
            // Unit
            const $t = jQuery('#unit_id');
            if ($t.length && !$t.hasClass('select2-hidden-accessible')) {
                $t.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $t.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('unit_id', val);
                });
            }
        }

        function destroySelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            const $t = jQuery('#unit_id');
            if ($t.length && $t.hasClass('select2-hidden-accessible')) $t.select2('destroy');
        }

        // Event dari Livewire untuk open/close modal
        if (window.Livewire && typeof Livewire.on === 'function') {
            Livewire.on('breakdown-modal-opened', () => {
                // Sedikit tunda untuk memastikan DOM siap
                setTimeout(initSelect2, 50);
            });
            Livewire.on('breakdown-modal-closed', () => {
                destroySelect2();
            });
        }

        // Re-init setelah DOM Livewire diproses (mis. setelah validasi/gagal simpan)
        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('message.processed', () => {
                const modalVisible = document.getElementById('breakdownModal');
                if (modalVisible) {
                    setTimeout(initSelect2, 50);
                }
            });
        }
    });
</script>