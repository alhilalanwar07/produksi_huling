<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Karyawan extends Model
{
    use HasFactory;

    protected $table = 'karyawans';

    protected $fillable = [
        'nama_karyawan',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'jabatan_id',
        'devisi_id',
        'site_id',
        'pendidikan_id',
        'agama_id',
        'tanggal_masuk',
        'gaji_pokok',
        'status',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'tanggal_masuk' => 'date',
        'gaji_pokok' => 'decimal:2',
    ];

    // Relasi
    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function devisi()
    {
        return $this->belongsTo(Devisi::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function pendidikan()
    {
        return $this->belongsTo(Pendidikan::class);
    }

    public function agama()
    {
        return $this->belongsTo(Agama::class);
    }

    // Accessor umur
    public function getUmurAttribute(): ?int
    {
        if (!$this->tanggal_lahir) {
            return null;
        }
        return Carbon::parse($this->tanggal_lahir)->age;
    }

    /**
     * Validasi dasar untuk Karyawan
     */
    public static function rules(?int $id = null): array
    {
        return [
            'nama_karyawan' => 'required|string|min:3|max:100',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'tempat_lahir' => 'required|string|min:3|max:50',
            'tanggal_lahir' => 'required|date',
            'jabatan_id' => 'nullable|exists:jabatans,id',
            'devisi_id' => 'nullable|exists:devisis,id',
            'site_id' => 'nullable|exists:sites,id',
            'pendidikan_id' => 'nullable|exists:pendidikans,id',
            'agama_id' => 'nullable|exists:agamas,id',
            'tanggal_masuk' => 'required|date',
            'gaji_pokok' => 'required|numeric|min:0',
            'status' => 'required|in:aktif,non-aktif',
        ];
    }

    public static function messages(): array
    {
        return [
            'nama_karyawan.required' => 'Nama karyawan wajib diisi.',
            'nama_karyawan.min' => 'Nama karyawan minimal 3 karakter.',
            'nama_karyawan.max' => 'Nama karyawan maksimal 100 karakter.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'tempat_lahir.required' => 'Tempat lahir wajib diisi.',
            'tempat_lahir.min' => 'Tempat lahir minimal 3 karakter.',
            'tempat_lahir.max' => 'Tempat lahir maksimal 50 karakter.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'jabatan_id.exists' => 'Jabatan tidak ditemukan.',
            'devisi_id.exists' => 'Devisi tidak ditemukan.',
            'site_id.exists' => 'Site tidak ditemukan.',
            'pendidikan_id.exists' => 'Pendidikan tidak ditemukan.',
            'agama_id.exists' => 'Agama tidak ditemukan.',
            'tanggal_masuk.required' => 'Tanggal masuk wajib diisi.',
            'tanggal_masuk.date' => 'Format tanggal masuk tidak valid.',
            'gaji_pokok.required' => 'Gaji pokok wajib diisi.',
            'gaji_pokok.numeric' => 'Gaji pokok harus berupa angka.',
            'gaji_pokok.min' => 'Gaji pokok minimal 0.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];
    }
}