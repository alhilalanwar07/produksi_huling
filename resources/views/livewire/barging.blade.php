<?php

use Livewire\Volt\Component;
use Livewire\WithPagination;
use App\Models\{Barging, Unit, Karyawan, JenisBarging, Site, Tongkang};
use Illuminate\Support\Facades\Log;

new class extends Component {
    use WithPagination;

    public $search = '';
    public $sortField = 'tanggal';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $tanggal = '';
    public $unit_id = null;
    public $karyawan_id = null;
    public $jenis_barging_id = null;
    public $tongkang_id = '';
    public $site_id = null;
    public $retase = 0;

    public $units = [];
    public $drivers = [];
    public $jenisBargings = [];
    public $sites = [];
    public $tongkangs = [];

    public $editingId = null;
    public $showModal = false;
    public $deletingId = null;
    public $showDeleteModal = false;
    // Tidak diperlukan lagi untuk sinkronisasi deposit

    public function mount()
    {
        $this->loadDropdowns();
    }

    private function loadDropdowns(): void
    {
        $this->units = Unit::query()->orderBy('nomor_lambung')->get(['id', 'nomor_lambung']);
        $this->drivers = Karyawan::query()->orderBy('nama_karyawan')->get(['id', 'nama_karyawan']);
        $this->jenisBargings = JenisBarging::query()->orderBy('nama_jenis_barging')->get(['id', 'nama_jenis_barging']);
        $this->sites = Site::query()->orderBy('nama_site')->get(['id', 'nama_site']);
        $this->tongkangs = Tongkang::query()->orderBy('nama_tongkang')->get(['id', 'nama_tongkang']);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

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

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->dispatch('barging-modal-opened');
    }

    public function edit($id)
    {
        $b = Barging::findOrFail($id);
        $this->editingId = $id;
        $this->tanggal = optional($b->tanggal)->format('Y-m-d');
        $this->unit_id = $b->unit_id;
        $this->karyawan_id = $b->karyawan_id;
        $this->jenis_barging_id = $b->jenis_barging_id;
        $this->tongkang_id = $b->tongkang_id;
        $this->site_id = $b->site_id;
        $this->retase = (float) ($b->retase ?? 0);
        $this->showModal = true;
        $this->dispatch('barging-modal-opened');
    }

    public function save()
    {
        // Validasi server-side (tongkang_id wajib ID yang ada, retase angka bulat >=1)
        $rules = Barging::rules();
        $rules['tongkang_id'] = ['required', 'exists:tongkangs,id'];
        $rules['retase'] = ['required', 'numeric', 'min:0'];
        $messages = [
            'tongkang_id.required' => 'Pilih tongkang.',
            'tongkang_id.exists' => 'Tongkang tidak ditemukan.',
            'retase.required' => 'Retase wajib diisi.',
            'retase.numeric' => 'Retase harus berupa angka.',
            'retase.min' => 'Retase minimal 0.',
        ];

        $validated = $this->validate($rules, $messages);
        try {
            if ($this->editingId) {
                $existing = Barging::findOrFail($this->editingId);
                $existing->update($validated);
                session()->flash('message', 'Data barging berhasil diperbarui.');
            } else {
                Barging::create($validated);
                session()->flash('message', 'Data barging berhasil ditambahkan.');
            }
            $this->resetForm();
            $this->showModal = false;
            $this->dispatch('barging-modal-closed');
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan barging: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->dispatch('barging-modal-closed');
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
                $existing = Barging::findOrFail($this->deletingId);
                $existing->delete();
                session()->flash('message', 'Data barging berhasil dihapus.');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                $this->resetPage();
            }
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus barging: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat menghapus data.');
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->tanggal = '';
        $this->unit_id = null;
        $this->karyawan_id = null;
        $this->jenis_barging_id = null;
        $this->tongkang_id = '';
        $this->site_id = null;
        $this->retase = 0;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function with(): array
    {
        $query = Barging::query()
            ->with(['unit', 'karyawan', 'jenisBarging', 'site'])
            ->when($this->search, function ($q) {
                $q->where('tongkang_id', 'like', '%' . $this->search . '%')
                  ->orWhereHas('unit', function ($uq) {
                      $uq->where('nomor_lambung', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('karyawan', function ($kq) {
                      $kq->where('nama_karyawan', 'like', '%' . $this->search . '%');
                  });
            });

        if ($this->sortField === 'nomor_lambung') {
            $query->leftJoin('units', 'units.id', '=', 'bargings.unit_id')
                ->orderBy('units.nomor_lambung', $this->sortDirection)
                ->select('bargings.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $bargings = $query->paginate($this->perPage);

        return [
            'bargings' => $bargings,
        ];
    }
    
    // Tidak ada lagi sinkronisasi ke tabel deposits
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
                    <a href="#">Data Barging</a>
                </li>
            </ul>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="card-title">Manajemen Data Barging</h4>
                            <button wire:click="create" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Tambah Barging
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
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

                        <div class="row mb-3 justify-content-between">
                            <div class="col-md-6 mb-2">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Cari (lambung/karyawan/tongkang)..."
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

                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered table-timesheet">
                                <thead class="thead-dark">
                                    <tr>
                                        <th wire:click="sortBy('tanggal')" style="cursor: pointer;">Tanggal
                                            @if($sortField === 'tanggal')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th wire:click="sortBy('nomor_lambung')" style="cursor: pointer;">Nomor Lambung
                                            @if($sortField === 'nomor_lambung')
                                            <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @endif
                                        </th>
                                        <th>Karyawan</th>
                                        <th>Jenis Barging</th>
                                        <th>Tongkang</th>
                                        <th>Site</th>
                                        <th>Retase</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($bargings as $b)
                                    <tr>
                                        <td>{{ optional($b->tanggal)->format('d/m/Y') }}</td>
                                        <td>{{ $b->unit->nomor_lambung ?? '-' }}</td>
                                        <td>{{ $b->karyawan->nama_karyawan ?? '-' }}</td>
                                        <td>{{ $b->jenisBarging->nama_jenis_barging ?? '-' }}</td>
                                        <td>
                                            {{ optional(collect($tongkangs)->firstWhere('id', $b->tongkang_id))->nama_tongkang ?? $b->tongkang_id }}
                                        </td>
                                        <td>{{ $b->site->nama_site ?? '-' }}</td>
                                        <td>{{ number_format((float)$b->retase, 2) }}</td>
                                        <td>
                                            <button wire:click="edit({{ $b->id }})" class="btn btn-warning btn-sm mr-1 mb-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="confirmDelete({{ $b->id }})" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data barging.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="justify-content-between mt-3">
                            {{ $bargings->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showModal)
    <div class="modal fade show" id="bargingModal" style="display: block; background-color: rgba(0,0,0,0.5);" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $editingId ? 'Edit' : 'Tambah' }} Barging</h5>
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
                                    <label for="karyawan_id">Karyawan</label>
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
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="jenis_barging_id">Jenis Barging</label>
                                    <div wire:ignore>
                                        <select id="jenis_barging_id" class="form-control @error('jenis_barging_id') is-invalid @enderror" wire:model="jenis_barging_id">
                                            <option value="">-</option>
                                            @foreach($jenisBargings as $jb)
                                            <option value="{{ $jb->id }}">{{ $jb->nama_jenis_barging }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('jenis_barging_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tongkang_id">Tongkang</label>
                                    <div wire:ignore>
                                        <select id="tongkang_id" class="form-control @error('tongkang_id') is-invalid @enderror" wire:model="tongkang_id" required>
                                            <option value="">-</option>
                                            @foreach($tongkangs as $t)
                                            <option value="{{ $t->id }}">{{ $t->nama_tongkang }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('tongkang_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="site_id">Site</label>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="retase">Retase</label>
                                    <input type="number" id="retase" class="form-control @error('retase') is-invalid @enderror" wire:model="retase" min="0" step="0.01" placeholder="0.00">
                                    @error('retase')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                        Apakah Anda yakin ingin menghapus data barging ini?<br>
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

<script>
    document.addEventListener('livewire:initialized', () => {
        function initSelect2() {
            const $modal = window.jQuery ? jQuery('#bargingModal') : null;
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') {
                // Select2 belum tersedia; lewati tanpa error
                return;
            }
            // Temukan root komponen Livewire terdekat (hindari komponen lain seperti navigation)
            const compRoot = document.getElementById('bargingModal')?.closest('[wire\\:id]');
            const comp = (compRoot && window.Livewire && typeof Livewire.find === 'function') ?
                Livewire.find(compRoot.getAttribute('wire:id')) :
                null;

            // Unit
            const $unit = jQuery('#unit_id');
            if ($unit.length && !$unit.hasClass('select2-hidden-accessible')) {
                $unit.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $unit.on('change', function() {
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

            // Jenis Barging
            const $jb = jQuery('#jenis_barging_id');
            if ($jb.length && !$jb.hasClass('select2-hidden-accessible')) {
                $jb.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $jb.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('jenis_barging_id', val);
                });
            }

            // Tongkang
            const $t = jQuery('#tongkang_id');
            if ($t.length && !$t.hasClass('select2-hidden-accessible')) {
                $t.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $t.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('tongkang_id', val);
                });
            }

            // Site
            const $s = jQuery('#site_id');
            if ($s.length && !$s.hasClass('select2-hidden-accessible')) {
                $s.select2({
                    width: '100%',
                    dropdownParent: $modal
                });
                $s.on('change', function() {
                    const val = jQuery(this).val();
                    comp && comp.set('site_id', val);
                });
            }
        }

        function destroySelect2() {
            if (!window.jQuery || typeof jQuery.fn.select2 === 'undefined') return;
            const $unit = jQuery('#unit_id');
            const $k = jQuery('#karyawan_id');
            const $jb = jQuery('#jenis_barging_id');
            const $t = jQuery('#tongkang_id');
            const $s = jQuery('#site_id');
            if ($unit.length && $unit.hasClass('select2-hidden-accessible')) $unit.select2('destroy');
            if ($k.length && $k.hasClass('select2-hidden-accessible')) $k.select2('destroy');
            if ($jb.length && $jb.hasClass('select2-hidden-accessible')) $jb.select2('destroy');
            if ($t.length && $t.hasClass('select2-hidden-accessible')) $t.select2('destroy');
            if ($s.length && $s.hasClass('select2-hidden-accessible')) $s.select2('destroy');
        }

        // Event dari Livewire untuk open/close modal
        if (window.Livewire && typeof Livewire.on === 'function') {
            Livewire.on('barging-modal-opened', () => {
                // Sedikit tunda untuk memastikan DOM siap
                setTimeout(initSelect2, 50);
            });
            Livewire.on('barging-modal-closed', () => {
                destroySelect2();
            });
        }

        // Re-init setelah DOM Livewire diproses (mis. setelah validasi/gagal simpan)
        if (window.Livewire && typeof Livewire.hook === 'function') {
            Livewire.hook('message.processed', () => {
                const modalVisible = document.getElementById('bargingModal');
                if (modalVisible) {
                    setTimeout(initSelect2, 50);
                }
            });
        }
    });
</script>
