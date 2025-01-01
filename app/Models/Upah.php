<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Upah extends Model
{
    use HasFactory;

    protected $table = 'upah';

    protected $fillable = [
        'karyawan_id',
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
        return $this->belongsTo(Karyawan::class, 'karyawan_id');
    }

    public function detailPerhitungan()
    {
        return $this->hasManyThrough(
            BarangHarian::class,
            Karyawan::class,
            'id',
            'barang_id',
            'karyawan_id',
            'id'
        )->join('barang', 'barang.id', '=', 'barang_harian.barang_id')
            ->select('barang_harian.*', 'barang.nama as nama_barang', 'barang.upah as upah_per_kodi');
    }
}