<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Jabatan;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    protected $model = Jabatan::class;

    public function definition(): array
    {
        return [
            'nama_jabatan' => $this->faker->unique()->jobTitle(),
            'deskripsi' => $this->faker->optional()->sentence(),
        ];
    }
}