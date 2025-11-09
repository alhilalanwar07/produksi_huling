<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model TypeUnit
 * Menyimpan jenis alat (tipe unit) untuk operasi hauling.
 */
class TypeUnit extends Model
{
    use HasFactory;

    protected $table = 'type_units';

    protected $fillable = [
        'jenis_alat',
    ];

    protected $casts = [
        'jenis_alat' => 'string',
    ];

    /**
     * Aturan validasi dasar untuk TypeUnit
     */
    public static function rules(?int $id = null): array
    {
        $unique = 'unique:type_units,jenis_alat';
        if ($id) {
            $unique .= ',' . $id;
        }
        return [
            'jenis_alat' => 'required|string|max:100|' . $unique,
        ];
    }

    /**
     * Pesan validasi dalam Bahasa Indonesia
     */
    public static function messages(): array
    {
        return [
            'jenis_alat.required' => 'Jenis alat wajib diisi.',
            'jenis_alat.string' => 'Jenis alat harus berupa teks.',
            'jenis_alat.max' => 'Jenis alat maksimal 100 karakter.',
            'jenis_alat.unique' => 'Jenis alat sudah ada.',
        ];
    }
}