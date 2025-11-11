<?php

namespace Database\Seeders;

use App\Models\{Barging, Unit, Karyawan, JenisBarging, Site, Tongkang};
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class BargingSeeder extends Seeder
{
    public function run(): void
    {
        if (JenisBarging::count() < 12) {
            (new JenisBargingSeeder())->run();
        }

        if (Site::count() === 0) {
            Site::create(['nama_site' => 'SITE A', 'status' => 'SITE']);
            Site::create(['nama_site' => 'MITRA B', 'status' => 'MITRA']);
        }

        if (Unit::count() === 0) {
            Unit::factory()->count(6)->create();
        }

        if (Karyawan::count() === 0) {
            Karyawan::factory()->count(6)->create();
        }

        $unitIds = Unit::query()->pluck('id')->all();
        $karyawanIds = Karyawan::query()->pluck('id')->all();
        $jenisIds = JenisBarging::query()->pluck('id')->all();
        $siteIds = Site::query()->pluck('id')->all();
        $tongkangIds = Tongkang::query()->pluck('id')->all();

        // Pastikan ada tongkang untuk foreign key
        if (empty($tongkangIds)) {
            (new TongkangSeeder())->run();
            $tongkangIds = Tongkang::query()->pluck('id')->all();
        }

        for ($i = 0; $i < 12; $i++) {
            Barging::create([
                'tanggal' => Carbon::today()->subDays(random_int(0, 30)),
                'unit_id' => Arr::random($unitIds),
                'karyawan_id' => Arr::random($karyawanIds),
                'jenis_barging_id' => Arr::random($jenisIds),
                // gunakan ID tongkang yang valid (FK ke tongkangs.id)
                'tongkang_id' => Arr::random($tongkangIds),
                'site_id' => Arr::random($siteIds),
                // retase wajib numerik dan >= 0
                'retase' => random_int(10, 250),
            ]);
        }
    }
}