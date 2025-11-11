<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{TimeSheet, Unit, Karyawan, Lokasi, Site, TypeUnit, JenisUnit};

class TimeSheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan dependensi dasar tersedia (buat yang wajib terlebih dahulu)
        if (Site::count() === 0) {
            foreach (['SITE A', 'SITE B', 'MITRA C'] as $nama) {
                Site::firstOrCreate(['nama_site' => $nama], ['status' => $nama === 'MITRA C' ? 'MITRA' : 'SITE']);
            }
        }

        if (TypeUnit::count() === 0) {
            TypeUnit::firstOrCreate(['jenis_alat' => 'DUMP TRUCK']);
        }

        if (JenisUnit::count() === 0) {
            JenisUnit::firstOrCreate(['nama_jenis' => 'DT']);
        }

        if (Karyawan::count() === 0) {
            Karyawan::factory(10)->create();
        }

        if (Lokasi::count() === 0) {
            foreach (['LOKASI A', 'LOKASI B', 'LOKASI C'] as $i => $nama) {
                Lokasi::firstOrCreate(['nama_lokasi' => $nama, 'kode_lokasi' => 'L' . ($i + 1)]);
            }
        }

        // Buat unit setelah dependensi tersedia agar FK tidak null
        if (Unit::count() === 0) {
            \App\Models\Unit::factory(5)->create();
        }

        // Buat 12 data time sheet
        TimeSheet::factory(12)->create();
    }
}