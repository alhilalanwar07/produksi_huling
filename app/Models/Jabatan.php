<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatans';

    protected $fillable = [
        'nama_jabatan',
        'deskripsi',
    ];

    protected $casts = [
        'nama_jabatan' => 'string',
        'deskripsi' => 'string',
    ];

    /**
     * Validasi dasar untuk Jabatan
     */
    public static function rules(?int $id = null): array
    {
        $unique = 'unique:jabatans,nama_jabatan';
        if ($id) {
            $unique .= ',' . $id;
        }

        return [
            'nama_jabatan' => 'required|string|max:255|' . $unique,
            'deskripsi' => 'nullable|string',
        ];
    }

    public static function messages(): array
    {
        return [
            'nama_jabatan.required' => 'Nama jabatan tidak boleh kosong.',
            'nama_jabatan.string' => 'Nama jabatan harus berupa teks.',
            'nama_jabatan.max' => 'Nama jabatan maksimal 255 karakter.',
            'nama_jabatan.unique' => 'Nama jabatan sudah digunakan.',
            'deskripsi.string' => 'Deskripsi harus berupa teks.',
        ];
    }
}