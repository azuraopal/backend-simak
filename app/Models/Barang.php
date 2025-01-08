<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected static function booted()
    {
        static::updated(function ($barang) {
            if ($barang->isDirty('upah')) {
                $barangHarians = DB::table('barang_harian')
                    ->where('barang_id', $barang->id)
                    ->get();

                foreach ($barangHarians as $bh) {
                    $upah = Upah::where('karyawan_id', $bh->karyawan_id)
                        ->where('periode_mulai', '<=', $bh->tanggal)
                        ->where('periode_selesai', '>=', $bh->tanggal)
                        ->first();

                    if ($upah) {
                        $totalBH = DB::table('barang_harian as bh')
                            ->join('barang as b', 'b.id', '=', 'bh.barang_id')
                            ->where('bh.karyawan_id', $upah->karyawan_id)
                            ->whereBetween('bh.tanggal', [$upah->periode_mulai, $upah->periode_selesai])
                            ->select(
                                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan), 0) as total_dikerjakan'),
                                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan * b.upah), 0) as total_upah')
                            )
                            ->first();

                        $upah->update([
                            'total_dikerjakan' => $totalBH->total_dikerjakan,
                            'total_upah' => $totalBH->total_upah
                        ]);
                    }
                }
            }
        });
    }
}