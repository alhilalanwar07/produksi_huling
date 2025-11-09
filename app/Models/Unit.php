<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'nomor_lambung',
        'karyawan_id',
        'type_unit_id',
        'jenis_unit_id',
        'site_id',
        'nomor_polisi',
        'nomor_rangka',
        'nomor_mesin',
    ];

    protected $casts = [
        'nomor_lambung' => 'string',
        'nomor_polisi' => 'string',
        'nomor_rangka' => 'string',
        'nomor_mesin' => 'string',
        'karyawan_id' => 'integer',
        'type_unit_id' => 'integer',
        'jenis_unit_id' => 'integer',
        'site_id' => 'integer',
    ];

    // Relasi
    public function driver()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function typeUnit()
    {
        return $this->belongsTo(TypeUnit::class, 'type_unit_id');
    }

    public function jenisUnit()
    {
        return $this->belongsTo(JenisUnit::class, 'jenis_unit_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public static function rules(?int $id = null): array
    {
        $uniqueLambung = 'unique:units,nomor_lambung';
        $uniquePolisi = 'unique:units,nomor_polisi';
        $uniqueRangka = 'unique:units,nomor_rangka';
        $uniqueMesin = 'unique:units,nomor_mesin';
        if ($id) {
            $uniqueLambung .= ',' . $id;
            $uniquePolisi .= ',' . $id;
            $uniqueRangka .= ',' . $id;
            $uniqueMesin .= ',' . $id;
        }

        return [
            'nomor_lambung' => 'required|string|max:100|' . $uniqueLambung,
            'karyawan_id' => 'required|exists:karyawans,id',
            'type_unit_id' => 'required|exists:type_units,id',
            'jenis_unit_id' => 'required|exists:jenis_units,id',
            'site_id' => 'required|exists:sites,id',
            'nomor_polisi' => 'required|string|max:50|' . $uniquePolisi,
            'nomor_rangka' => 'required|string|max:100|' . $uniqueRangka,
            'nomor_mesin' => 'required|string|max:100|' . $uniqueMesin,
        ];
    }

    public static function messages(): array
    {
        return [
            'nomor_lambung.required' => 'Nomor lambung wajib diisi.',
            'nomor_lambung.string' => 'Nomor lambung harus berupa teks.',
            'nomor_lambung.max' => 'Nomor lambung maksimal 100 karakter.',
            'nomor_lambung.unique' => 'Nomor lambung sudah digunakan.',

            'karyawan_id.required' => 'Driver wajib dipilih.',
            'karyawan_id.exists' => 'Driver tidak valid.',

            'type_unit_id.required' => 'Tipe unit wajib dipilih.',
            'type_unit_id.exists' => 'Tipe unit tidak valid.',

            'jenis_unit_id.required' => 'Jenis unit wajib dipilih.',
            'jenis_unit_id.exists' => 'Jenis unit tidak valid.',

            'site_id.required' => 'Site wajib dipilih.',
            'site_id.exists' => 'Site tidak valid.',

            'nomor_polisi.required' => 'Nomor polisi wajib diisi.',
            'nomor_polisi.string' => 'Nomor polisi harus berupa teks.',
            'nomor_polisi.max' => 'Nomor polisi maksimal 50 karakter.',
            'nomor_polisi.unique' => 'Nomor polisi sudah digunakan.',

            'nomor_rangka.required' => 'Nomor rangka wajib diisi.',
            'nomor_rangka.string' => 'Nomor rangka harus berupa teks.',
            'nomor_rangka.max' => 'Nomor rangka maksimal 100 karakter.',
            'nomor_rangka.unique' => 'Nomor rangka sudah digunakan.',

            'nomor_mesin.required' => 'Nomor mesin wajib diisi.',
            'nomor_mesin.string' => 'Nomor mesin harus berupa teks.',
            'nomor_mesin.max' => 'Nomor mesin maksimal 100 karakter.',
            'nomor_mesin.unique' => 'Nomor mesin sudah digunakan.',
        ];
    }
}