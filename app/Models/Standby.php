<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Standby extends Model
{
    use HasFactory;

    protected $table = 'standby';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'alasan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'nomor_lambung');
    }

    public static function rules(): array
    {
        return [
            'tanggal' => ['required', 'date'],
            'unit_id' => ['required', 'string', 'max:100', 'exists:units,nomor_lambung'],
            'alasan'  => ['required', 'string'],
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $data = $model->only(['tanggal', 'unit_id', 'alasan']);
            $validator = Validator::make($data, self::rules());
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }
}