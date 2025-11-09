<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JenisUnit extends Model
{
    use HasFactory;

    protected $table = 'jenis_units';

    protected $fillable = [
        'nama_jenis',
    ];

    protected $casts = [
        'nama_jenis' => 'string',
    ];

    public static function rules(?int $id = null): array
    {
        $unique = 'unique:jenis_units,nama_jenis';
        if ($id) {
            $unique .= ',' . $id;
        }
        return [
            'nama_jenis' => 'required|string|max:100|' . $unique,
        ];
    }

    public static function messages(): array
    {
        return [
            'nama_jenis.required' => 'Nama jenis unit wajib diisi.',
            'nama_jenis.string' => 'Nama jenis unit harus berupa teks.',
            'nama_jenis.max' => 'Nama jenis unit maksimal 100 karakter.',
            'nama_jenis.unique' => 'Nama jenis unit sudah ada.',
        ];
    }
}