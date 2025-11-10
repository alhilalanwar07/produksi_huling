<?php

namespace Database\Seeders;

use App\Models\{Barging, Unit, Karyawan, JenisBarging, Site};
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

        for ($i = 0; $i < 12; $i++) {
            Barging::create([
                'tanggal' => Carbon::today()->subDays(random_int(0, 30)),
                'unit_id' => Arr::random($unitIds),
                'karyawan_id' => Arr::random($karyawanIds),
                'jenis_barging_id' => Arr::random($jenisIds),
                'tongkang_id' => 'TB-' . str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT),
                'site_id' => Arr::random($siteIds),
            ]);
        }
    }
}