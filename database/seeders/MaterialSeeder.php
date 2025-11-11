<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Material;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['ORE', 'BOULDER'] as $name) {
            Material::firstOrCreate(['nama_material' => $name]);
        }
    }
}