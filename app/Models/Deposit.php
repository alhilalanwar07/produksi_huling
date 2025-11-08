<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    protected $table = 'deposits';

    protected $fillable = [
        'site_id',
        'jumlah_retase',
        'jumlah_deposit',
    ];

    protected $casts = [
        'jumlah_retase' => 'integer',
        'jumlah_deposit' => 'integer',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
