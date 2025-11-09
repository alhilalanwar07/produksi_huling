<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Tongkang
 * Menyimpan data tongkang dan kapasitasnya.
 */
class Tongkang extends Model
{
    use HasFactory;

    protected $table = 'tongkangs';

    protected $fillable = [
        'nama_tongkang',
        'kapasitas',
    ];

    protected $casts = [
        'nama_tongkang' => 'string',
        'kapasitas' => 'integer',
    ];

    /**
     * Aturan validasi dasar untuk Tongkang
     */
    public static function rules(?int $id = null): array
    {
        return [
            'nama_tongkang' => 'required|string|max:255',
            'kapasitas' => 'required|integer|min:0',
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia
     */
    public static function messages(): array
    {
        return [
            'nama_tongkang.required' => 'Nama tongkang wajib diisi.',
            'nama_tongkang.string' => 'Nama tongkang harus berupa teks.',
            'nama_tongkang.max' => 'Nama tongkang maksimal 255 karakter.',
            'kapasitas.required' => 'Kapasitas wajib diisi.',
            'kapasitas.integer' => 'Kapasitas harus berupa angka bulat.',
            'kapasitas.min' => 'Kapasitas minimal 0.',
        ];
    }
}