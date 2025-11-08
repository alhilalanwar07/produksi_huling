<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Devisi extends Model
{
    use HasFactory;

    protected $table = 'devisis';

    protected $fillable = [
        'nama_devisi',
        'kode_devisi',
    ];

    protected $casts = [
        'nama_devisi' => 'string',
        'kode_devisi' => 'string',
    ];

    /**
     * Validasi dasar untuk Devisi
     */
    public static function rules(?int $id = null): array
    {
        $uniqueNama = 'unique:devisis,nama_devisi';
        $uniqueKode = 'unique:devisis,kode_devisi';
        if ($id) {
            $uniqueNama .= ',' . $id;
            $uniqueKode .= ',' . $id;
        }

        return [
            'nama_devisi' => 'required|string|max:255|' . $uniqueNama,
            'kode_devisi' => 'nullable|string|max:50|' . $uniqueKode,
        ];
    }

    public static function messages(): array
    {
        return [
            'nama_devisi.required' => 'Nama devisi tidak boleh kosong.',
            'nama_devisi.string' => 'Nama devisi harus berupa teks.',
            'nama_devisi.max' => 'Nama devisi maksimal 255 karakter.',
            'nama_devisi.unique' => 'Nama devisi sudah digunakan.',
            'kode_devisi.string' => 'Kode devisi harus berupa teks.',
            'kode_devisi.max' => 'Kode devisi maksimal 50 karakter.',
            'kode_devisi.unique' => 'Kode devisi sudah digunakan.',
        ];
    }
}