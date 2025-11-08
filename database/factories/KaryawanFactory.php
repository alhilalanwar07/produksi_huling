<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Karyawan;
use App\Models\{Jabatan, Devisi, Site, Pendidikan, Agama};

/**
 * @extends Factory<Karyawan>
 */
class KaryawanFactory extends Factory
{
    protected $model = Karyawan::class;

    public function definition(): array
    {
        $jenisKelamin = $this->faker->randomElement(['Laki-laki', 'Perempuan']);
        return [
            'nama_karyawan' => $this->faker->name(),
            'jenis_kelamin' => $jenisKelamin,
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'jabatan_id' => Jabatan::query()->inRandomOrder()->value('id'),
            'devisi_id' => Devisi::query()->inRandomOrder()->value('id'),
            'site_id' => Site::query()->inRandomOrder()->value('id'),
            'pendidikan_id' => Pendidikan::query()->inRandomOrder()->value('id'),
            'agama_id' => Agama::query()->inRandomOrder()->value('id'),
            'tanggal_masuk' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'gaji_pokok' => $this->faker->randomFloat(2, 2500000, 15000000),
            'status' => $this->faker->randomElement(['aktif', 'non-aktif']),
        ];
    }
}