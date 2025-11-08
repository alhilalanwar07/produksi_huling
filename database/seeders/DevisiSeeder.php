<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Devisi;

class DevisiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_devisi' => 'Produksi', 'kode_devisi' => 'PRD'],
            ['nama_devisi' => 'Logistik', 'kode_devisi' => 'LOG'],
            ['nama_devisi' => 'Maintenance', 'kode_devisi' => 'MNT'],
            ['nama_devisi' => 'HRD', 'kode_devisi' => 'HRD'],
        ];

        foreach ($data as $item) {
            Devisi::firstOrCreate(['nama_devisi' => $item['nama_devisi']], $item);
        }
    }
}