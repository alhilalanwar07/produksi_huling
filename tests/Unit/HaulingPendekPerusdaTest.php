<?php

namespace Tests\Unit;

use App\Models\HaulingPendekPerusda;
use App\Models\Unit;
use App\Models\Karyawan;
use App\Models\Site;
use App\Models\Material;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Database\QueryException;

class HaulingPendekPerusdaTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationships_work(): void
    {
        $unit = Unit::factory()->create();
        $karyawan = Karyawan::factory()->create();
        $mitra = Site::create(['nama_site' => 'MITRA TEST', 'status' => 'MITRA']);
        $material = Material::firstOrCreate(['nama_material' => 'ore']);

        $hpp = HaulingPendekPerusda::create([
            'tanggal' => now()->toDateString(),
            'unit_id' => $unit->id,
            'karyawan_id' => $karyawan->id,
            'mitra_id' => $mitra->id,
            'material_id' => $material->id,
            'jumlah_retase' => 10,
        ]);

        $this->assertEquals($unit->id, $hpp->unit->id);
        $this->assertEquals($karyawan->id, $hpp->karyawan->id);
        $this->assertEquals($mitra->id, $hpp->mitra->id);
        $this->assertEquals($material->id, $hpp->material->id);
    }

    public function test_scopes_work(): void
    {
        $unit = Unit::factory()->create();
        $karyawan = Karyawan::factory()->create();
        $mitra = Site::create(['nama_site' => 'MITRA TEST', 'status' => 'MITRA']);
        $material = Material::firstOrCreate(['nama_material' => 'boulder']);

        HaulingPendekPerusda::create([
            'tanggal' => now()->subDays(2)->toDateString(),
            'unit_id' => $unit->id,
            'karyawan_id' => $karyawan->id,
            'mitra_id' => $mitra->id,
            'material_id' => $material->id,
            'jumlah_retase' => 5,
        ]);

        HaulingPendekPerusda::create([
            'tanggal' => now()->toDateString(),
            'unit_id' => $unit->id,
            'karyawan_id' => $karyawan->id,
            'mitra_id' => $mitra->id,
            'material_id' => $material->id,
            'jumlah_retase' => 7,
        ]);

        $range = HaulingPendekPerusda::query()->byDateRange(now()->subDay()->toDateString(), now()->toDateString())->count();
        $this->assertEquals(1, $range);

        $byMitra = HaulingPendekPerusda::query()->byMitra($mitra->id)->count();
        $this->assertEquals(2, $byMitra);

        $byUnit = HaulingPendekPerusda::query()->byUnit($unit->id)->count();
        $this->assertEquals(2, $byUnit);

        $byMaterial = HaulingPendekPerusda::query()->byMaterial($material->id)->count();
        $this->assertEquals(2, $byMaterial);
    }

    public function test_invalid_negative_retase_fails(): void
    {
        $this->expectException(QueryException::class);

        $unit = Unit::factory()->create();
        $karyawan = Karyawan::factory()->create();
        $mitra = Site::create(['nama_site' => 'MITRA TEST', 'status' => 'MITRA']);
        $material = Material::firstOrCreate(['nama_material' => 'ore']);

        HaulingPendekPerusda::create([
            'tanggal' => now()->toDateString(),
            'unit_id' => $unit->id,
            'karyawan_id' => $karyawan->id,
            'mitra_id' => $mitra->id,
            'material_id' => $material->id,
            'jumlah_retase' => -1,
        ]);
    }
}