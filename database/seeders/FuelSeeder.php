<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FuelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil daftar Unit dan Karyawan aktif untuk foreign key
        $unitIds = Unit::query()->pluck('id')->all();
        $driverIds = Karyawan::query()
            ->where('status', 'aktif')
            ->pluck('id')
            ->all();

        if (empty($unitIds) || empty($driverIds)) {
            // Jika belum ada master data yang diperlukan, jangan gagal — cukup abaikan
            // Jalankan seeder master terlebih dahulu: UnitSeeder & KaryawanSeeder
            return;
        }

        $now = now();
        $rows = [];

        // Generate data BBM realistis 45–90 entri dalam 60 hari terakhir
        $total = random_int(45, 90);
        for ($i = 0; $i < $total; $i++) {
            $tanggal = $now->copy()->subDays(random_int(0, 60))->toDateString();
            $unitId = $unitIds[array_rand($unitIds)];
            $driverId = $driverIds[array_rand($driverIds)];

            // Jumlah pengisian 50.00–300.00 liter
            $jumlah = random_int(5000, 30000) / 100; // dua desimal

            // Odometer KM 1,000–80,000 (integer)
            $km = random_int(1000, 80000);

            $rows[] = [
                'tanggal' => $tanggal,
                'unit_id' => $unitId,
                'karyawan_id' => $driverId,
                'jumlah_pengisian' => $jumlah,
                'km' => $km,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('fuel')->insert($rows);
    }
}