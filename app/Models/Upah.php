<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upah extends Model
{
    use HasFactory;

    protected $table = 'upah';

    protected $fillable = [
        'id_karyawan',
        'minggu_ke',
        'total_dikerjakan',
        'total_upah',
        'periode_mulai',
        'periode_selesai'
    ];

    protected $casts = [
        'periode_mulai' => 'date',
        'periode_selesai' => 'date'
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}