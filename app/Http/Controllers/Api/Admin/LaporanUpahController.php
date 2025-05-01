<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Upah;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Validator;

class LaporanUpahController extends Controller
{
    public function printAll()
    {
        if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $upahList = Upah::with(['staff_produksi.user:id,nama_lengkap,email,created_at'])
                ->orderBy('periode_mulai', 'desc')
                ->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.laporanUpah', compact('upahList'));

            return $pdf->download('laporan_upah_semua.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function printFiltered(Request $request)
    {
        if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'periode_mulai' => 'required|date',
                'periode_selesai' => 'required|date|after_or_equal:periode_mulai',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $upahList = Upah::with(['staff_produksi.user:id,nama_lengkap,email,created_at'])
                ->whereBetween('periode_mulai', [$request->periode_mulai, $request->periode_selesai])
                ->orderBy('periode_mulai', 'desc')
                ->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.laporanUpah', compact('upahList', 'request'));

            return $pdf->download('laporan_upah_filtered.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
