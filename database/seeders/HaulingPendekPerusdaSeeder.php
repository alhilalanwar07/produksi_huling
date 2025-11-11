<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HaulingPendekPerusda;
use App\Models\Unit;
use App\Models\Karyawan;
use App\Models\Site;
use App\Models\Material;

class HaulingPendekPerusdaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure base related data
        $unit = Unit::query()->first() ?? Unit::factory()->create();
        $karyawan = Karyawan::query()->first() ?? Karyawan::factory()->create();
        $mitra = Site::query()->where('status', 'MITRA')->first()
            ?? Site::create(['nama_site' => 'MITRA DUMMY', 'status' => 'MITRA']);
        $material = Material::firstOrCreate(['nama_material' => 'ore']);

        // Create multiple records
        HaulingPendekPerusda::factory()
            ->count(10)
            ->create([
                'unit_id' => $unit->id,
                'karyawan_id' => $karyawan->id,
                'mitra_id' => $mitra->id,
                'material_id' => $material->id,
            ]);
    }
}