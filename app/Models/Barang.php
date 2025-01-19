<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Barang extends Model
{
    use LogsActivity;

    protected $table = 'barang';

    protected $fillable = [
        'nama',
        'deskripsi',
        'kategori_barang',
        'stock_id',
        'upah'
    ];

    public function barangHarian()
    {
        return $this->hasMany(BarangHarian::class, 'barang_id');
    }

    public function stock()
    {
        return $this->hasOne(Stock::class, 'id', 'stock_id');
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_barang', 'id');
    }

    public function getShortDescriptionAttribute()
    {
        return substr($this->deskripsi, 0, 50) . '...';
    }

    public function setNamaAttribute($value)
    {
        $this->attributes['nama'] = ucwords(strtolower($value));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'deskripsi', 'kategori_barang', 'upah'])
            ->useLogName('barang')
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted()
    {
        static::creating(function ($barang) {
            if (!$barang->stock_id) {
                $stock = Stock::create(['stock' => 0]);
                $barang->stock_id = $stock->id;
            }
        });

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

        static::deleted(function ($barang) {
            activity()
                ->causedBy(auth()->user())
                ->performedOn($barang)
                ->log("Barang '{$barang->nama}' telah dihapus");
        });
    }
}
