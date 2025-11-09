<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Breakdown extends Model
{
    use HasFactory;

    protected $table = 'breakdowns';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'unit_id' => 'integer',
        'status' => 'string',
        'keterangan' => 'string',
    ];

    /**
     * Relasi ke Unit.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Atribut bantuan untuk menampilkan nomor lambung dari unit terkait.
     */
    protected $appends = ['nomor_lambung'];

    public function getNomorLambungAttribute(): ?string
    {
        return $this->relationLoaded('unit') && $this->unit ? $this->unit->nomor_lambung : null;
    }

    /**
     * Rules dan messages untuk validasi.
     */
    public static function rules(?int $id = null): array
    {
        return [
            'tanggal' => 'required|date',
            'unit_id' => 'required|exists:units,id',
            'status' => 'required|string|max:100',
            'keterangan' => 'nullable|string',
        ];
    }

    public static function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Tanggal tidak valid.',
            'unit_id.required' => 'Unit wajib dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',
            'status.required' => 'Status wajib diisi.',
            'status.string' => 'Status harus berupa teks.',
            'status.max' => 'Status maksimal 100 karakter.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
        ];
    }
}