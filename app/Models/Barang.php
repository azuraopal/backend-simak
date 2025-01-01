<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'barang';

    protected $fillable = [
        'nama',
        'deskripsi',
        'kategori_barang',
        'stok_awal',
        'stok_tersedia',
        'upah'
    ];

    public function barangHarian()
    {
        return $this->hasMany(BarangHarian::class, 'barang_id');
    }
}