<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\{Unit, Karyawan, TypeUnit, JenisUnit, Site};

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        // Pastikan foreign keys tersedia; jika tidak, kembalikan null (akan diatasi di Seeder)
        $karyawanId = Karyawan::query()->inRandomOrder()->value('id');
        $typeUnitId = TypeUnit::query()->inRandomOrder()->value('id');
        $jenisUnitId = JenisUnit::query()->inRandomOrder()->value('id');
        $siteId = Site::query()->inRandomOrder()->value('id');

        return [
            'nomor_lambung' => strtoupper($this->faker->unique()->bothify('LB-####')),
            'karyawan_id' => $karyawanId,
            'type_unit_id' => $typeUnitId,
            'jenis_unit_id' => $jenisUnitId,
            'site_id' => $siteId,
            'nomor_polisi' => strtoupper($this->faker->unique()->bothify('KT #### ??')),
            'nomor_rangka' => strtoupper($this->faker->unique()->bothify('??##??##??##??##')),
            'nomor_mesin' => strtoupper($this->faker->unique()->bothify('??##??##??##')),
        ];
    }
}