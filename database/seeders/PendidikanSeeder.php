<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pendidikan;

class PendidikanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['tingkat_pendidikan' => 'SD', 'singkatan' => 'SD'],
            ['tingkat_pendidikan' => 'SMP', 'singkatan' => 'SMP'],
            ['tingkat_pendidikan' => 'SMA/SMK', 'singkatan' => 'SMA/SMK'],
            ['tingkat_pendidikan' => 'D3', 'singkatan' => 'D3'],
            ['tingkat_pendidikan' => 'S1', 'singkatan' => 'S1'],
            ['tingkat_pendidikan' => 'S2', 'singkatan' => 'S2'],
        ];

        foreach ($data as $item) {
            Pendidikan::firstOrCreate(['tingkat_pendidikan' => $item['tingkat_pendidikan']], $item);
        }
    }
}