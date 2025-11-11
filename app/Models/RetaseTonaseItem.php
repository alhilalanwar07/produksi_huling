<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetaseTonaseItem extends Model
{
    protected $table = 'retase_tonase_items';

    protected $fillable = [
        'pms_id',
        'site_id',
        'material_id',
        'tonase',
    ];

    protected $casts = [
        'tonase' => 'decimal:2',
    ];

    public function pms(): BelongsTo
    {
        return $this->belongsTo(RetaseTonasePms::class, 'pms_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}