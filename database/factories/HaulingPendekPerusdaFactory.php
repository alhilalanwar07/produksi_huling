<?php

namespace Database\Factories;

use App\Models\HaulingPendekPerusda;
use App\Models\Unit;
use App\Models\Karyawan;
use App\Models\Site;
use App\Models\Material;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HaulingPendekPerusda>
 */
class HaulingPendekPerusdaFactory extends Factory
{
    protected $model = HaulingPendekPerusda::class;

    public function definition(): array
    {
        // Ensure related records exist or create minimal ones
        $unit = Unit::query()->inRandomOrder()->first() ?? Unit::factory()->create();
        $karyawan = Karyawan::query()->inRandomOrder()->first() ?? Karyawan::factory()->create();
        $mitra = Site::query()->where('status', 'MITRA')->inRandomOrder()->first()
            ?? Site::create(['nama_site' => 'MITRA DUMMY', 'status' => 'MITRA']);
        $material = Material::query()->inRandomOrder()->first()
            ?? Material::create(['nama_material' => 'ore']);

        return [
            'tanggal' => $this->faker->date(),
            'unit_id' => $unit->id,
            'karyawan_id' => $karyawan->id,
            'mitra_id' => $mitra->id,
            'material_id' => $material->id,
            'jumlah_retase' => $this->faker->numberBetween(1, 50),
        ];
    }
}