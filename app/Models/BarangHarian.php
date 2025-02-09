<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangHarian extends Model
{
    protected $table = 'barang_harian';

    protected $fillable = [
        'staff_produksi_id',
        'barang_id',
        'tanggal',
        'jumlah_dikerjakan'
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'id');
    }

    public function staff_produksi()
    {
        return $this->belongsTo(StaffProduksi::class, 'staff_produksi_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'staff_produksi_id', 'id')
            ->join('staff_produksi', 'users.id', '=', 'staff_produksi.users_id');
    }

    protected static function booted()
    {
        static::creating(function ($barangHarian) {
            $barang = $barangHarian->barang;
            if ($barang && $barang->stock) {
                $barang->stock->stock -= $barangHarian->jumlah_dikerjakan;
                $barang->stock->save();
            }
        });
    }
}
