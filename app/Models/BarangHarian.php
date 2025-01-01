<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangHarian extends Model
{
    protected $table = 'barang_harian';

    protected $fillable = [
        'karyawan_id',
        'barang_id',
        'tanggal',
        'jumlah_dikerjakan'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'id');
    }

    public function karyawan()
    {
        return $this->belongsTo(User::class, 'karyawan_id', 'id');
    }
}
