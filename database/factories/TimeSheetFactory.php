<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{TimeSheet, Unit, Karyawan, Lokasi, Site};

/**
 * @extends Factory<TimeSheet>
 */
class TimeSheetFactory extends Factory
{
    protected $model = TimeSheet::class;

    public function definition(): array
    {
        $tanggal = $this->faker->dateTimeBetween('-7 days', 'now')->format('Y-m-d');

        $unit = Unit::query()->inRandomOrder()->first();
        $unitId = $unit?->id;
        $driverId = $unit?->karyawan_id;

        $operatorId = $driverId ?: Karyawan::query()->inRandomOrder()->value('id');
        $lokasiId = Lokasi::query()->inRandomOrder()->value('id');
        $siteId = Site::query()->inRandomOrder()->value('id');

        $s1Awal = $this->faker->randomFloat(2, 0, 1000);
        $s1Akhir = $s1Awal + $this->faker->randomFloat(2, 0.1, 8.0);

        // 60% ada shift 2
        $hasShift2 = $this->faker->boolean(60);
        $s2Awal = $hasShift2 ? $this->faker->randomFloat(2, 0, 1000) : null;
        $s2Akhir = $hasShift2 && $s2Awal !== null ? $s2Awal + $this->faker->randomFloat(2, 0.1, 6.0) : null;

        $totalHm = round(max(0, $s1Akhir - $s1Awal), 2);
        $totalLembur = $hasShift2 && $s2Awal !== null && $s2Akhir !== null
            ? round(max(0, $s2Akhir - $s2Awal), 2)
            : 0.0;

        return [
            'tanggal' => $tanggal,
            'unit_id' => $unitId,
            'karyawan_id' => $operatorId,
            'shift1_hm_awal' => $s1Awal,
            'shift1_hm_akhir' => $s1Akhir,
            'shift2_hm_awal' => $s2Awal,
            'shift2_hm_akhir' => $s2Akhir,
            'total_hm' => $totalHm,
            'total_lembur' => $totalLembur,
            'keterangan' => $this->faker->optional()->sentence(6),
            'lokasi_id' => $lokasiId,
            'site_id' => $siteId,
        ];
    }
}