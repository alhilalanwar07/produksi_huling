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
        foreach (['ore', 'boulder'] as $name) {
            Material::firstOrCreate(['nama_material' => $name]);
        }
    }
}