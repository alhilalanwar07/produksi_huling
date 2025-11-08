<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agama extends Model
{
    use HasFactory;

    protected $table = 'agamas';

    protected $fillable = [
        'nama_agama',
    ];

    protected $casts = [
        'nama_agama' => 'string',
    ];

    /**
     * Validasi dasar untuk Agama
     */
    public static function rules(?int $id = null): array
    {
        $unique = 'unique:agamas,nama_agama';
        if ($id) {
            $unique .= ',' . $id;
        }

        return [
            'nama_agama' => 'required|string|max:100|' . $unique,
        ];
    }

    public static function messages(): array
    {
        return [
            'nama_agama.required' => 'Nama agama tidak boleh kosong.',
            'nama_agama.string' => 'Nama agama harus berupa teks.',
            'nama_agama.max' => 'Nama agama maksimal 100 karakter.',
            'nama_agama.unique' => 'Nama agama sudah digunakan.',
        ];
    }
}