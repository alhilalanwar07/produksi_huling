<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Unit, Karyawan, TypeUnit, JenisUnit, Site};

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan dependensi tersedia
        if (Karyawan::count() === 0) {
            Karyawan::factory(10)->create();
        }

        if (TypeUnit::count() === 0) {
            $typeUnits = ['Excavator', 'Bulldozer', 'Dump Truck', 'Wheel Loader', 'Motor Grader'];
            foreach ($typeUnits as $jenis) {
                TypeUnit::firstOrCreate(['jenis_alat' => $jenis]);
            }
        }

        if (JenisUnit::count() === 0) {
            $jenisUnits = ['Alat Berat', 'Kendaraan Angkut', 'Kendaraan Operasional'];
            foreach ($jenisUnits as $nama) {
                JenisUnit::firstOrCreate(['nama_jenis' => $nama]);
            }
        }

        if (Site::count() === 0) {
            $sites = [
                ['nama_site' => 'SITE A', 'status' => 'SITE'],
                ['nama_site' => 'SITE B', 'status' => 'SITE'],
                ['nama_site' => 'MITRA C', 'status' => 'MITRA'],
            ];
            foreach ($sites as $s) {
                Site::firstOrCreate(['nama_site' => $s['nama_site']], $s);
            }
        }

        // Buat 30 unit dummy
        Unit::factory(30)->create();
    }
}