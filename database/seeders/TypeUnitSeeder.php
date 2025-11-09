<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TypeUnit;

class TypeUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['jenis_alat' => 'Excavator'],
            ['jenis_alat' => 'Bulldozer'],
            ['jenis_alat' => 'Dump Truck'],
            ['jenis_alat' => 'Wheel Loader'],
            ['jenis_alat' => 'Motor Grader'],
        ];

        foreach ($data as $item) {
            TypeUnit::firstOrCreate(['jenis_alat' => $item['jenis_alat']], $item);
        }
    }
}