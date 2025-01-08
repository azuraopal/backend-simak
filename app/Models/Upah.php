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
        return $this->hasMany(BarangHarian::class, 'karyawan_id', 'karyawan_id')
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
            ->where('bh.karyawan_id', $this->karyawan_id)
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