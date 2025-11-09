<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{TimeSheet, Unit, Karyawan, Lokasi, Site};

class TimeSheetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan dependensi dasar tersedia
        if (Unit::count() === 0) {
            // Jika unit belum ada, buat minimal beberapa unit menggunakan factory bawaan
            // Catatan: UnitSeeder biasanya sudah menambahkan unit beserta relasi
            \App\Models\Unit::factory(5)->create();
        }

        if (Karyawan::count() === 0) {
            Karyawan::factory(10)->create();
        }

        if (Lokasi::count() === 0) {
            foreach (['LOKASI A', 'LOKASI B', 'LOKASI C'] as $i => $nama) {
                Lokasi::firstOrCreate(['nama_lokasi' => $nama, 'kode_lokasi' => 'L' . ($i + 1)]);
            }
        }

        if (Site::count() === 0) {
            foreach (['SITE A', 'SITE B', 'MITRA C'] as $nama) {
                Site::firstOrCreate(['nama_site' => $nama], ['status' => $nama === 'MITRA C' ? 'MITRA' : 'SITE']);
            }
        }

        // Buat 12 data time sheet
        TimeSheet::factory(12)->create();
    }
}