<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Barging extends Model
{
    use HasFactory;

    protected $table = 'bargings';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'karyawan_id',
        'jenis_barging_id',
        'tongkang_id',
        'site_id',
        'retase',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'retase' => 'decimal:2',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function jenisBarging(): BelongsTo
    {
        return $this->belongsTo(JenisBarging::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public static function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'unit_id' => ['required', 'exists:units,id'],
            'karyawan_id' => ['required', 'exists:karyawans,id'],
            'jenis_barging_id' => ['required', 'exists:jenis_bargings,id'],
            // Simpan sebagai ID tongkang (dropdown Select2 menggunakan ID)
            'tongkang_id' => ['required', 'exists:tongkangs,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'retase' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $data = $model->only(['tanggal', 'unit_id', 'karyawan_id', 'jenis_barging_id', 'tongkang_id', 'site_id', 'retase']);
            $validator = Validator::make($data, self::rules());
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }
}