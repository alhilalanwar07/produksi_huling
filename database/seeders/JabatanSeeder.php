<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jabatan;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            ['nama_jabatan' => 'Operator', 'deskripsi' => null],
            ['nama_jabatan' => 'Supervisor', 'deskripsi' => null],
            ['nama_jabatan' => 'Manager', 'deskripsi' => null],
            ['nama_jabatan' => 'Direktur', 'deskripsi' => null],
        ];

        foreach ($data as $item) {
            Jabatan::firstOrCreate(['nama_jabatan' => $item['nama_jabatan']], $item);
        }
    }
}