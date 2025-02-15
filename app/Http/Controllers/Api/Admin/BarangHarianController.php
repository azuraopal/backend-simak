<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\BarangHarian;
use App\Models\StaffProduksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BarangHarianController extends Controller
{
    private function handleException(\Exception $e)
    {
        return response()->json([
            'status' => false,
            'message' => 'Terjadi kesalahan sistem',
            'error' => $e->getMessage()
        ], 500);
    }

    public function index()
    {
        try {
            $query = BarangHarian::with(['barang', 'staff_produksi.user']);

            if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
                $staffAdministrasi = StaffProduksi::where('users_id', Auth::id())->first();
                if (!$staffAdministrasi) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data Staff Produksi tidak ditemukan'
                    ], 404);
                }
                $query->where('staff_produksi_id', $staffAdministrasi->id);
            }

            $barangHarian = $query->orderBy('tanggal', 'desc')->get();

            if ($barangHarian->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diambil',
                'data' => $barangHarian
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }


    public function store(Request $request)
    {
        if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'staff_produksi_id' => 'required|exists:staff_produksi,id',
                'barang_id' => 'required|exists:barang,id',
                'tanggal' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend() && config('app.env') === 'production') {
                            $fail('Tanggal tidak boleh di akhir pekan.');
                        }
                        if ($date->gt(Carbon::now())) {
                            $fail('Tanggal tidak boleh lebih dari hari ini.');
                        }
                    },
                ],
                'jumlah_dikerjakan' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barangHarian = BarangHarian::with('staff_produksi.user')->create($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil ditambahkan',
                'data' => $barangHarian->load(['barang', 'staff_produksi.user'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan sistem',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $barangHarian = BarangHarian::with(['barang', 'staff_produksi.user'])->find($id);

            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            if (!in_array(Auth::user()->role, [UserRole::Admin, UserRole::StaffAdministrasi])) {
                $staff_produksi = StaffProduksi::where('users_id', Auth::id())->first();
                if (!$staff_produksi || $barangHarian->staff_produksi_id !== $staff_produksi->id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diambil',
                'data' => $barangHarian
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, $id)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'staff_produksi_id' => [
                    'required',
                    'exists:staff_produksi,id',
                    function ($attribute, $value, $fail) {
                        $staff_produksi = StaffProduksi::find($value);
                        if (!$staff_produksi || !$staff_produksi->user) {
                            $fail('Data staff_produksi tidak lengkap atau tidak valid.');
                        }
                    },
                ],
                'barang_id' => 'required|exists:barang,id',
                'tanggal' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend() && config('app.env') === 'production') {
                            $fail('Tanggal tidak boleh di akhir pekan.');
                        }
                        if ($date->gt(Carbon::now())) {
                            $fail('Tanggal tidak boleh lebih dari hari ini.');
                        }
                    },
                ],
                'jumlah_dikerjakan' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barangHarian = BarangHarian::find($id);
            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            $barangHarian->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil diupdate',
                'data' => $barangHarian->load(['barang', 'staff_produksi.user'])
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id)
    {
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $barangHarian = BarangHarian::find($id);
            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            $barangHarian->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}