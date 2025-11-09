<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fuel extends Model
{
    use HasFactory;

    protected $table = 'fuel';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'karyawan_id',
        'jumlah_pengisian',
        'km',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_pengisian' => 'decimal:2',
        'km' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class);
    }
}