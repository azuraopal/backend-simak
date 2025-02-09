<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Upah extends Model
{
    use HasFactory;

    protected $table = 'upah';

    protected $fillable = [
        'staff_produksi_id',
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

    public function StaffProduksi()
    {
        return $this->belongsTo(StaffProduksi::class, 'staff_produksi_id');
    }

    public function detailPerhitungan()
    {
        return $this->hasMany(BarangHarian::class, 'staff_produksi_id', 'staff_produksi_id')
            ->join('barang as b', 'b.id', '=', 'barang_harian.barang_id')
            ->select(
                'barang_harian.*',
                'b.nama as nama_barang',
                'b.upah as upah_per_kodi'
            );
    }

    public function recalculateTotal()
    {
        $result = DB::table('barang_harian as bh')
            ->join('barang as b', 'b.id', '=', 'bh.barang_id')
            ->where('bh.staff_produksi_id', $this->staff_produksi_id)
            ->whereBetween('bh.tanggal', [$this->periode_mulai, $this->periode_selesai])
            ->select(
                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan), 0) as total_dikerjakan'),
                DB::raw('COALESCE(SUM(bh.jumlah_dikerjakan * b.upah), 0) as total_upah')
            )
            ->first();

        if ($result) {
            $this->update([
                'total_dikerjakan' => $result->total_dikerjakan,
                'total_upah' => $result->total_upah
            ]);
        }
    }
}