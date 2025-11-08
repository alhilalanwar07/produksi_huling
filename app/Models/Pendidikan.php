<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendidikan extends Model
{
    use HasFactory;

    protected $table = 'pendidikans';

    protected $fillable = [
        'tingkat_pendidikan',
        'singkatan',
    ];

    protected $casts = [
        'tingkat_pendidikan' => 'string',
        'singkatan' => 'string',
    ];

    /**
     * Validasi dasar untuk Pendidikan
     */
    public static function rules(?int $id = null): array
    {
        $unique = 'unique:pendidikans,tingkat_pendidikan';
        if ($id) {
            $unique .= ',' . $id;
        }

        return [
            'tingkat_pendidikan' => 'required|string|max:255|' . $unique,
            'singkatan' => 'nullable|string|max:50',
        ];
    }

    public static function messages(): array
    {
        return [
            'tingkat_pendidikan.required' => 'Tingkat pendidikan tidak boleh kosong.',
            'tingkat_pendidikan.string' => 'Tingkat pendidikan harus berupa teks.',
            'tingkat_pendidikan.max' => 'Tingkat pendidikan maksimal 255 karakter.',
            'tingkat_pendidikan.unique' => 'Tingkat pendidikan sudah digunakan.',
            'singkatan.string' => 'Singkatan harus berupa teks.',
            'singkatan.max' => 'Singkatan maksimal 50 karakter.',
        ];
    }
}