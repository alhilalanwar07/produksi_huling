<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HaulingPendekPerusda extends Model
{
    use HasFactory;

    protected $table = 'hauling_pendek_perusdas';

    protected $fillable = [
        'tanggal',
        'unit_id',
        'karyawan_id',
        'mitra_id',
        'material_id',
        'jumlah_retase',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'jumlah_retase' => 'integer',
    ];

    // Relationships
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class);
    }

    public function mitra()
    {
        return $this->belongsTo(Site::class, 'mitra_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    // Scopes
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    public function scopeByMitra($query, $mitraId)
    {
        return $query->where('mitra_id', $mitraId);
    }

    public function scopeByUnit($query, $unitId)
    {
        return $query->where('unit_id', $unitId);
    }

    public function scopeByMaterial($query, $materialId)
    {
        return $query->where('material_id', $materialId);
    }
}