<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetaseTonasePms extends Model
{
    protected $table = 'retase_tonase_pms';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'driver_id',
        'jumlah_retase_per_tonase',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_retase_per_tonase' => 'integer',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'driver_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RetaseTonaseItem::class, 'pms_id');
    }
    // Aggregates
    public function getTotalRetaseAttribute(): int
    {

        return (int) ($this->items->sum('tonase') * $this->jumlah_retase_per_tonase);
    }

    public function getTotalTonaseAttribute(): float
    {
        return (float) ($this->items->sum('tonase'));
    }
}