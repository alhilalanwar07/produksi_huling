<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisUnit;

class JenisUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_jenis' => 'Alat Berat'],
            ['nama_jenis' => 'Kendaraan Angkut'],
            ['nama_jenis' => 'Kendaraan Operasional'],
        ];

        foreach ($data as $item) {
            JenisUnit::firstOrCreate(['nama_jenis' => $item['nama_jenis']], $item);
        }
    }
}