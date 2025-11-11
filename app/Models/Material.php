<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    protected $table = 'materials';

    protected $fillable = [
        'nama_material',
    ];

    protected $casts = [
        'nama_material' => 'string',
    ];
}