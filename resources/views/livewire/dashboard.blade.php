<?php

use Livewire\Volt\Component;
use Carbon\Carbon;
use App\Models\RetaseTonasePms;
use App\Models\RetaseTonaseItem;
use App\Models\Barging;
use App\Models\Fuel;
use App\Models\Standby;
use App\Models\TimeSheet;
use App\Models\Site;
use App\Models\Material;
use Illuminate\Support\Facades\DB;

new class extends Component {
    public string $startDate;
    public string $endDate;
    public array $metrics = [];
    public array $chart = [];
    public array $chartOps = [];
    public ?int $siteId = null;
    public ?int $materialId = null;
    public array $sites = [];
    public array $materials = [];
    public array $deltas = [];
    public array $topUnits = [];

    public function mount(): void
    {
        // Tetapkan default tanggal
        $this->endDate = Carbon::now()->toDateString();
        $this->startDate = Carbon::now()->subDays(13)->toDateString();
        
        // Muat filter
        $this->sites = Site::orderBy('nama_site')->get(['id', 'nama_site'])->map(fn($s) => ['id' => $s->id, 'name' => $s->nama_site])->all();
        $this->materials = Material::orderBy('nama_material')->get(['id', 'nama_material'])->map(fn($m) => ['id' => $m->id, 'name' => $m->nama_material])->all();
        
        // Muat data saat komponen pertama kali di-load
        $this->loadData();
    }

    // Gunakan atribut #[On] untuk me-refresh data saat filter berubah
    // Ini lebih bersih daripada method updated() terpisah
    #[Livewire\Attributes\On('filters-updated')]
    public function loadData(): void
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // == 1. PERHITUNGAN METRICS (K-Cards) ==
        // Query ini sudah cukup optimal (agregat)
        $fuelTotal = (float) Fuel::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->whereHas('unit', fn($qq) => $qq->where('site_id', $this->siteId)))
            ->sum('jumlah_pengisian');
        $bargingRet = (float) Barging::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->sum('retase');
        $tonaseTotal = (float) RetaseTonaseItem::whereHas('pms', fn($q) => $q->whereBetween('tanggal', [$start, $end]))
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->when($this->materialId, fn($q) => $q->where('material_id', $this->materialId))
            ->sum('tonase');
        $unitHours = (float) TimeSheet::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->sum('total_hm');
        $standbyCount = (int) Standby::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->whereHas('unit', fn($qq) => $qq->where('site_id', $this->siteId)))
            ->count();

        $this->metrics = [
            'unit_hours' => $unitHours,
            'standby_count' => $standbyCount, // Anda menghitung ini, tapi tidak menampilkannya di view?
            'barging_ret' => $bargingRet,
            'tonase_pms' => $tonaseTotal,
            'fuel_usage' => $fuelTotal,
        ];

        // == 2. PERBAIKAN: PERSIAPAN DATA CHART (MENGHINDARI N+1) ==
        // Ambil semua data yang relevan dalam satu kali query, jangan di dalam loop
        
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end->copy()->addDay());

        // A. Data untuk Chart Tonase (Bar)
        $tonasePerDay = RetaseTonaseItem::whereHas('pms', fn($q) => $q->whereBetween('tanggal', [$start, $end]))
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->when($this->materialId, fn($q) => $q->where('material_id', $this->materialId))
            ->join('retase_tonase_pms', 'retase_tonase_items.pms_id', '=', 'retase_tonase_pms.id')
            ->groupBy('date')
            ->select(DB::raw('DATE(retase_tonase_pms.tanggal) as date'), DB::raw('SUM(tonase) as total'))
            ->get()
            ->pluck('total', 'date');

        // B. Data untuk Chart Retase (Line) & Top Units
        // Eager load 'items' dan 'unit' untuk diproses di PHP
        $allPms = RetaseTonasePms::with(['items', 'unit'])
            ->whereBetween('tanggal', [$start, $end])
            ->get();
            
        $retasePerDay = [];
        $unitsAgg = [];

        foreach ($allPms as $pms) {
            $date = $pms->tanggal->toDateString();

            // Filter 'items' yang sudah di-load (collection filtering, bukan DB query)
            $filteredItems = $pms->items
                ->when($this->siteId, fn($c) => $c->where('site_id', $this->siteId))
                ->when($this->materialId, fn($c) => $c->where('material_id', $this->materialId));

            $sumTon = (float) $filteredItems->sum('tonase');

            // Kalkulasi Retase harian
            if ($sumTon > 0) {
                $retasePerDay[$date] = ($retasePerDay[$date] ?? 0) + ($sumTon * $pms->jumlah_retase_per_tonase);
            }
            
            // Kalkulasi Agregat Top Unit
            if ($sumTon > 0) {
                $label = $pms->unit ? ($pms->unit->nomor_lambung ?: ('#' . $pms->unit->id)) : ('#' . $pms->unit_id);
                $unitsAgg[$label] = ($unitsAgg[$label] ?? 0) + $sumTon;
            }
        }

        // C. Data untuk Chart Ops (HM & Fuel)
        $hmPerDay = TimeSheet::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->groupBy('date')
            ->select(DB::raw('DATE(tanggal) as date'), DB::raw('SUM(total_hm) as total'))
            ->get()
            ->pluck('total', 'date');
            
        $fuelPerDay = Fuel::whereBetween('tanggal', [$start, $end])
            ->when($this->siteId, fn($q) => $q->whereHas('unit', fn($qq) => $qq->where('site_id', $this->siteId)))
            ->groupBy('date')
            ->select(DB::raw('DATE(tanggal) as date'), DB::raw('SUM(jumlah_pengisian) as total'))
            ->get()
            ->pluck('total', 'date');

        // == 3. PROSES DATA UNTUK CHART ==
        // Sekarang looping harian hanya memproses data dari array/collection (sangat cepat)
        $labels = [];
        $barTonase = [];
        $lineRetase = [];
        $labelsOps = [];
        $lineHM = [];
        $lineFuel = [];

        foreach ($period as $day) {
            $d = Carbon::instance($day);
            $dateString = $d->toDateString();
            $labelFormat = $d->format('d M');
            
            // Chart 1
            $labels[] = $labelFormat;
            $barTonase[] = $tonasePerDay->get($dateString) ?? 0;
            $lineRetase[] = $retasePerDay[$dateString] ?? 0;
            
            // Chart 2
            $labelsOps[] = $labelFormat;
            $lineHM[] = $hmPerDay->get($dateString) ?? 0;
            $lineFuel[] = $fuelPerDay->get($dateString) ?? 0;
        }

        $this->chart = [
            'labels' => $labels,
            'barTonase' => $barTonase,
            'lineRetase' => $lineRetase,
            'title' => 'Tonase Hauling Ore PMS (' . Carbon::parse($this->startDate)->format('d F Y') . ' - ' . Carbon::parse($this->endDate)->format('d F Y') . ')',
        ];
        $this->chartOps = [
            'labels' => $labelsOps,
            'hm' => $lineHM,
            'fuel' => $lineFuel,
            'title' => 'HM vs Fuel (' . Carbon::parse($this->startDate)->format('d F Y') . ' - ' . Carbon::parse($this->endDate)->format('d F Y') . ')',
        ];

        // == 4. PERHITUNGAN DELTA ==
        // Logika ini sudah OK
        $days = Carbon::parse($this->startDate)->diffInDays(Carbon::parse($this->endDate)) + 1;
        $prevStart = Carbon::parse($this->startDate)->subDays($days)->startOfDay();
        $prevEnd = Carbon::parse($this->startDate)->subDay()->endOfDay();
        $prevFuel = (float) Fuel::whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($this->siteId, fn($q) => $q->whereHas('unit', fn($qq) => $qq->where('site_id', $this->siteId)))
            ->sum('jumlah_pengisian');
        $prevBarge = (float) Barging::whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->sum('retase');
        $prevTonase = (float) RetaseTonaseItem::whereHas('pms', fn($q) => $q->whereBetween('tanggal', [$prevStart, $prevEnd]))
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->when($this->materialId, fn($q) => $q->where('material_id', $this->materialId))
            ->sum('tonase');
        $prevHM = (float) TimeSheet::whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($this->siteId, fn($q) => $q->where('site_id', $this->siteId))
            ->sum('total_hm');

        $this->deltas = [
            'unit_hours' => $prevHM > 0 ? (($unitHours - $prevHM) / $prevHM) * 100 : null,
            'barging_ret' => $prevBarge > 0 ? (($bargingRet - $prevBarge) / $prevBarge) * 100 : null,
            'tonase_pms' => $prevTonase > 0 ? (($tonaseTotal - $prevTonase) / $prevTonase) * 100 : null,
            'fuel_usage' => $prevFuel > 0 ? (($fuelTotal - $prevFuel) / $prevFuel) * 100 : null,
        ];

        // == 5. TOP UNITS ==
        // Data $unitsAgg sudah dihitung di langkah #2
        arsort($unitsAgg);
        $this->topUnits = [];
        foreach (array_slice($unitsAgg, 0, 5, true) as $u => $v) {
            $this->topUnits[] = ['unit' => $u, 'tonase' => $v];
        }

        // == 6. DISPATCH EVENT UNTUK UPDATE CHART JS ==
        // Ini cara Livewire 3 modern untuk memberitahu JS agar me-render ulang chart
        $this->dispatch('data-updated', chart: $this->chart, chartOps: $this->chartOps);
    }
    
    // Fungsi ini akan dipanggil oleh view untuk men-trigger update
    public function updateData()
    {
        $this->dispatch('filters-updated');
    }
}; ?>

<div class="container-fluid p-3 p-md-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <div class="text-muted small">Dashboard</div>
            <h4 class="mb-0 fw-semibold">Produksi Hauling</h4>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <input type="date" wire:model.lazy="startDate" @change="$wire.updateData()" class="form-control form-control-sm" style="width: auto;">
            <span class="text-muted">–</span>
            <input type="date" wire:model.lazy="endDate" @change="$wire.updateData()" class="form-control form-control-sm" style="width: auto;">
            <select wire:model.lazy="siteId" @change="$wire.updateData()" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Site</option>
                @foreach($sites as $s)
                    <option value="{{ $s['id'] }}">{{ $s['name'] }}</option>
                @endforeach
            </select>
            <select wire:model.lazy="materialId" @change="$wire.updateData()" class="form-select form-select-sm" style="width: auto;">
                <option value="">Semua Material</option>
                @foreach($materials as $m)
                    <option value="{{ $m['id'] }}">{{ $m['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Unit Hours (HM)</div>
                    <div class="h3 fw-bold my-1">{{ number_format($metrics['unit_hours'] ?? 0, 1) }} Jam</div>
                    <div class="small {{ ($deltas['unit_hours'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @php $dh = $deltas['unit_hours'] ?? null; @endphp
                        {{ is_null($dh) ? '–' : ( ($dh>=0?'+':'') . number_format($dh,1) . '% vs prev') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Barge</div>
                    <div class="h3 fw-bold my-1">{{ number_format($metrics['barging_ret'] ?? 0, 1) }} Ret</div>
                    <div class="small {{ ($deltas['barging_ret'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @php $db = $deltas['barging_ret'] ?? null; @endphp
                        {{ is_null($db) ? '–' : ( ($db>=0?'+':'') . number_format($db,1) . '% vs prev') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Tonase PMS</div>
                    <div class="h3 fw-bold my-1">{{ number_format($metrics['tonase_pms'] ?? 0, 1) }} Ton</div>
                    <div class="small {{ ($deltas['tonase_pms'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        @php $dt = $deltas['tonase_pms'] ?? null; @endphp
                        {{ is_null($dt) ? '–' : ( ($dt>=0?'+':'') . number_format($dt,1) . '% vs prev') }}
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Fuel Usage</div>
                    <div class="h3 fw-bold my-1">{{ number_format($metrics['fuel_usage'] ?? 0, 1) }} Liter</div>
                    <div class="small {{ ($deltas['fuel_usage'] ?? 0) >= 0 ? 'text-danger' : 'text-danger' }}"> @php $df = $deltas['fuel_usage'] ?? null; @endphp
                        {{ is_null($df) ? '–' : ( ($df>=0?'+':'') . number_format($df,1) . '% vs prev') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted small">{{ $chart['title'] ?? 'Tonase Hauling Ore PMS' }}</h6>
                    <div wire:ignore style="height: 300px;">
                        <canvas id="pmsChart"></canvas>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadCSV('pmsChart', window.pmsChart.config.data)">
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="card-title text-muted small mb-3">Top Unit Tonase</h6>
                    <div class="table-responsive" style="max-height: 320px;">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th class="p-2">Unit</th>
                                    <th class="p-2 text-end">Tonase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topUnits as $tu)
                                    <tr>
                                        <td class="p-2">{{ $tu['unit'] }}</td>
                                        <td class="p-2 text-end">{{ number_format($tu['tonase'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="p-2" colspan="2">Tidak ada data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-muted small">{{ $chartOps['title'] ?? 'HM vs Fuel' }}</h6>
                    <div wire:ignore style="height: 300px;">
                        <canvas id="opsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading wire:target="loadData" class="position-fixed top-0 start-0 end-0 bottom-0 bg-white bg-opacity-75" style="z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Memuat data...</span>
            </div>
            <span class="ms-2">Memuat data...</span>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Fungsi untuk download CSV (diambil dari kode Anda, sudah bagus)
    function downloadCSV(name, data) {
        const rows = [['Tanggal', ...data.datasets.map(d => d.label)]];
        const labels = data.labels;
        
        labels.forEach((label, index) => {
            const row = [label];
            data.datasets.forEach(dataset => {
                row.push(dataset.data[index] ?? 0);
            });
            rows.push(row);
        });

        const csv = rows.map(r => r.join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = name + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    // Inisialisasi Chart saat Livewire 3 selesai memuat
    document.addEventListener('livewire:initialized', () => {

        // --- Chart 1: PMS Chart ---
        const ctxPms = document.getElementById('pmsChart').getContext('2d');
        window.pmsChart = new Chart(ctxPms, {
            data: {
                labels: @js($chart['labels'] ?? []),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Tonase',
                        data: @js($chart['barTonase'] ?? []),
                        backgroundColor: '#0d6efd', // Bootstrap Primary
                    },
                    {
                        type: 'line',
                        label: 'Retase',
                        data: @js($chart['lineRetase'] ?? []),
                        borderColor: '#198754', // Bootstrap Success
                        backgroundColor: '#198754',
                        tension: 0.3,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } },
            }
        });

        // --- Chart 2: Ops Chart ---
        const ctxOps = document.getElementById('opsChart').getContext('2d');
        window.opsChart = new Chart(ctxOps, {
            type: 'line',
            data: {
                labels: @js($chartOps['labels'] ?? []),
                datasets: [
                    {
                        label: 'HM',
                        data: @js($chartOps['hm'] ?? []),
                        borderColor: '#0dcaf0', // Bootstrap Info
                        backgroundColor: '#0dcaf0',
                        tension: 0.3,
                    },
                    {
                        label: 'Fuel',
                        data: @js($chartOps['fuel'] ?? []),
                        borderColor: '#ffc107', // Bootstrap Warning
                        backgroundColor: '#ffc107',
                        tension: 0.3,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });

        // --- Listener untuk event 'data-updated' dari PHP ---
        window.addEventListener('data-updated', event => {
            const chartData = event.detail.chart;
            const opsData = event.detail.chartOps;

            // Update Chart 1
            if (window.pmsChart && chartData) {
                window.pmsChart.data.labels = chartData.labels;
                window.pmsChart.data.datasets[0].data = chartData.barTonase;
                window.pmsChart.data.datasets[1].data = chartData.lineRetase;
                window.pmsChart.config.data = window.pmsChart.data; // Simpan data untuk CSV
                window.pmsChart.update();
            }

            // Update Chart 2
            if (window.opsChart && opsData) {
                window.opsChart.data.labels = opsData.labels;
                window.opsChart.data.datasets[0].data = opsData.hm;
                window.opsChart.data.datasets[1].data = opsData.fuel;
                window.opsChart.update();
            }
        });

    });
</script>
@endpush