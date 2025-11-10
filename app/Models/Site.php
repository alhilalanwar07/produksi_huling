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

    public function getTotalRetaseAttribute(): float
    {
        // Total retase kini bersumber dari tabel bargings berdasarkan site_id
        return (float) Barging::where('site_id', $this->id)->sum('retase');
    }

    public function getTotalDepositAttribute(): int
    {
        return (int) $this->deposits()->sum('jumlah_deposit');
    }

    public function getSisaRetaseAttribute(): float
    {
        // Sisa retase dihitung dari total retase barging dikurangi total deposit
        return (float) ($this->total_retase - $this->total_deposit);
    }
}
