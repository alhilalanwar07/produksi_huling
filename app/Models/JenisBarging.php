<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class JenisBarging extends Model
{
    use HasFactory;

    protected $table = 'jenis_bargings';

    protected $fillable = [
        'nama_jenis_barging',
    ];

    public static function rules(): array
    {
        return [
            'nama_jenis_barging' => ['required', 'string', 'max:255'],
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $data = $model->only(['nama_jenis_barging']);
            $validator = Validator::make($data, self::rules());
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        });
    }
}