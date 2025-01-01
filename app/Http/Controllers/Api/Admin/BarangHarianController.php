<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\BarangHarian;
use App\Models\Karyawan;
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
            $query = BarangHarian::with(['barang', 'karyawan.user']);

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data karyawan tidak ditemukan'
                    ], 404);
                }
                $query->where('karyawan_id', $karyawan->id);
            }

            $barangHarian = $query->orderBy('tanggal', 'desc')->get();

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
        if (Auth::user()->role !== UserRole::Admin) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {

            $validator = Validator::make($request->all(), [
                'karyawan_id' => 'required|exists:karyawan,id',
                'barang_id' => 'required|exists:barang,id',
                'tanggal' => [
                    'required',
                    'date',
                    'date_format:Y-m-d',
                    function ($attribute, $value, $fail) {
                        $date = Carbon::parse($value);
                        if ($date->isWeekend()) {
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

            $barangHarian = BarangHarian::with('karyawan.user')->create($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data barang harian berhasil ditambahkan',
                'data' => $barangHarian->load(['barang', 'karyawan.user']) 
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
            $barangHarian = BarangHarian::with(['barang', 'karyawan.user'])->find($id);

            if (!$barangHarian) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang harian tidak ditemukan'
                ], 404);
            }

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan || $barangHarian->karyawan_id !== $karyawan->id) {
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
                'karyawan_id' => [
                    'required',
                    'exists:karyawan,id',
                    function ($attribute, $value, $fail) {
                        $karyawan = Karyawan::find($value);
                        if (!$karyawan || !$karyawan->user) {
                            $fail('Data karyawan tidak lengkap atau tidak valid.');
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
                        if ($date->isWeekend()) {
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
                'data' => $barangHarian->load(['barang', 'karyawan.user'])
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