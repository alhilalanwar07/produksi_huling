<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Devisi;

/**
 * @extends Factory<Devisi>
 */
class DevisiFactory extends Factory
{
    protected $model = Devisi::class;

    public function definition(): array
    {
        return [
            'nama_devisi' => strtoupper($this->faker->unique()->word()),
            'kode_devisi' => strtoupper($this->faker->lexify('???')),
        ];
    }
}