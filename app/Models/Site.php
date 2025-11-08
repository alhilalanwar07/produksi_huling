<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = [
        'nama_site',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function getTotalRetaseAttribute(): int
    {
        return (int) $this->deposits()->sum('jumlah_retase');
    }

    public function getTotalDepositAttribute(): int
    {
        return (int) $this->deposits()->sum('jumlah_deposit');
    }

    public function getSisaRetaseAttribute(): int
    {
        return $this->total_retase - $this->total_deposit;
    }
}
