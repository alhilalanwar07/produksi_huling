<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSheet extends Model
{
    use HasFactory;

    protected $table = 'time_sheets';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'karyawan_id',
        'shift1_hm_awal',
        'shift1_hm_akhir',
        'shift2_hm_awal',
        'shift2_hm_akhir',
        'total_hm',
        'total_lembur',
        'keterangan',
        'lokasi_id',
        'site_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'shift1_hm_awal' => 'decimal:2',
        'shift1_hm_akhir' => 'decimal:2',
        'shift2_hm_awal' => 'decimal:2',
        'shift2_hm_akhir' => 'decimal:2',
        'total_hm' => 'decimal:2',
        'total_lembur' => 'decimal:2',
    ];

    // Relasi
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function operator()
    {
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function mitra()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public static function rules(?int $id = null): array
    {
        return [
            'tanggal' => 'required|date',
            'unit_id' => 'required|exists:units,id',
            'karyawan_id' => 'required|exists:karyawans,id',
            'shift1_hm_awal' => 'required|numeric|min:0',
            'shift1_hm_akhir' => 'required|numeric|min:0',
            'shift2_hm_awal' => 'nullable|numeric|min:0',
            'shift2_hm_akhir' => 'nullable|numeric|min:0',
            'total_hm' => 'required|numeric|min:0',
            'total_lembur' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string',
            'lokasi_id' => 'nullable|exists:lokasis,id',
            'site_id' => 'nullable|exists:sites,id',
        ];
    }

    public static function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.date' => 'Format tanggal tidak valid.',
            'unit_id.required' => 'No lambung (Unit) wajib dipilih.',
            'unit_id.exists' => 'Unit tidak valid.',
            'karyawan_id.required' => 'Operator wajib dipilih.',
            'karyawan_id.exists' => 'Operator tidak valid.',
            'shift1_hm_awal.required' => 'HM awal shift 1 wajib diisi.',
            'shift1_hm_akhir.required' => 'HM akhir shift 1 wajib diisi.',
            'total_hm.required' => 'Total HM wajib diisi.',
            'total_hm.numeric' => 'Total HM harus berupa angka.',
        ];
    }
}