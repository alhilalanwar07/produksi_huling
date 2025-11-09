<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Buat akun admin default jika belum ada
        User::query()->firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'role' => 'admin',
                'password' => bcrypt('12345678'),
            ]
        );

        // Panggil seeders untuk master data
        $this->call([
            JabatanSeeder::class,
            DevisiSeeder::class,
            PendidikanSeeder::class,
            AgamaSeeder::class,
            KaryawanSeeder::class,
            TongkangSeeder::class,
            TypeUnitSeeder::class,
            JenisUnitSeeder::class,
            TimeSheetSeeder::class,
            UnitSeeder::class,
        ]);
    }
}
