<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tongkang;

class TongkangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_tongkang' => 'TB-01 Mahakam', 'kapasitas' => 5000],
            ['nama_tongkang' => 'TB-02 Barito', 'kapasitas' => 6500],
            ['nama_tongkang' => 'TB-03 Kapuas', 'kapasitas' => 7000],
            ['nama_tongkang' => 'TB-04 Karimata', 'kapasitas' => 8000],
            ['nama_tongkang' => 'TB-05 Selat Makassar', 'kapasitas' => 9000],
        ];

        foreach ($data as $item) {
            Tongkang::firstOrCreate([
                'nama_tongkang' => $item['nama_tongkang'],
            ], $item);
        }
    }
}