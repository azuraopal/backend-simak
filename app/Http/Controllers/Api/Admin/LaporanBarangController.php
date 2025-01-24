<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Stock;
use App\Models\Barang;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class LaporanBarangController extends Controller
{
    public function generateLaporanBarang(Request $request)
    {
        if ($request->user()->role->value !== UserRole::Admin->value) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak diizinkan. Hanya Admin yang dapat membuat laporan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Kesalahan Validasi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->endOfDay();

        $pergerakanStok = Stock::with('barang')
            ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
            ->get();

        $laporanPemasukan = $pergerakanStok->filter(fn($pergerakan) => $pergerakan->stock > 0);
        $laporanPengeluaran = $pergerakanStok->filter(fn($pergerakan) => $pergerakan->stock < 0);

        $pdf = Pdf::loadView('laporan.pergerakan_barang', [
            'laporanPemasukan' => $laporanPemasukan,
            'laporanPengeluaran' => $laporanPengeluaran,
            'tanggalMulai' => $tanggalMulai,
            'tanggalSelesai' => $tanggalSelesai,
        ]);

        $namaFile = "laporan_pergerakan_barang_{$tanggalMulai->format('Y-m-d')}_{$tanggalSelesai->format('Y-m-d')}.pdf";
        return $pdf->download($namaFile);
    }

    public function ringkasanPergerakanBarang(Request $request)
    {
        if ($request->user()->role->value !== UserRole::Admin->value) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak diizinkan. Hanya Admin yang dapat melihat ringkasan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Kesalahan Validasi',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tanggalMulai = Carbon::parse($request->tanggal_mulai)->startOfDay();
        $tanggalSelesai = Carbon::parse($request->tanggal_selesai)->endOfDay();

        $pergerakanStok = Stock::with('barang')
            ->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai])
            ->get();

        $ringkasan = [
            'total_barang_masuk' => $pergerakanStok->filter(fn($m) => $m->stock > 0)->count(),
            'total_barang_keluar' => $pergerakanStok->filter(fn($m) => $m->stock < 0)->count(),
            'total_stok_ditambah' => $pergerakanStok->where('stock', '>', 0)->sum('stock'),
            'total_stok_dikurangi' => abs($pergerakanStok->where('stock', '<', 0)->sum('stock')),
        ];

        return response()->json([
            'status' => true,
            'message' => 'Ringkasan pergerakan barang berhasil diambil',
            'data' => $ringkasan,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);
    }
}