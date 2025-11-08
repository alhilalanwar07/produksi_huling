<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Agama;

class AgamaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_agama' => 'Islam'],
            ['nama_agama' => 'Kristen'],
            ['nama_agama' => 'Katolik'],
            ['nama_agama' => 'Hindu'],
            ['nama_agama' => 'Buddha'],
            ['nama_agama' => 'Konghucu'],
        ];

        foreach ($data as $item) {
            Agama::firstOrCreate(['nama_agama' => $item['nama_agama']], $item);
        }
    }
}