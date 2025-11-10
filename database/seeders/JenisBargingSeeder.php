<?php

namespace Database\Seeders;

use App\Models\JenisBarging;
use Illuminate\Database\Seeder;

class JenisBargingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            'Coal Loading',
            'Overburden Transfer',
            'Sand Barging',
            'Stockpile Transfer',
            'Jetty Loading',
            'River Barging',
            'Night Shift Barging',
            'Port Transfer',
            'Long Distance Barging',
            'Shallow Water Barging',
            'Special Operation',
            'Emergency Support',
        ];

        foreach ($items as $name) {
            JenisBarging::firstOrCreate(['nama_jenis_barging' => $name]);
        }
    }
}