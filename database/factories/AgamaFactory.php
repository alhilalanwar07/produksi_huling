<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Agama;

/**
 * @extends Factory<Agama>
 */
class AgamaFactory extends Factory
{
    protected $model = Agama::class;

    public function definition(): array
    {
        $options = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];
        return [
            'nama_agama' => $this->faker->unique()->randomElement($options),
        ];
    }
}