<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Pendidikan;

/**
 * @extends Factory<Pendidikan>
 */
class PendidikanFactory extends Factory
{
    protected $model = Pendidikan::class;

    public function definition(): array
    {
        $options = [
            ['tingkat' => 'SD', 'singkatan' => 'SD'],
            ['tingkat' => 'SMP', 'singkatan' => 'SMP'],
            ['tingkat' => 'SMA/SMK', 'singkatan' => 'SMA/SMK'],
            ['tingkat' => 'D3', 'singkatan' => 'D3'],
            ['tingkat' => 'S1', 'singkatan' => 'S1'],
            ['tingkat' => 'S2', 'singkatan' => 'S2'],
        ];
        $pick = $this->faker->randomElement($options);

        return [
            'tingkat_pendidikan' => $pick['tingkat'],
            'singkatan' => $pick['singkatan'],
        ];
    }
}