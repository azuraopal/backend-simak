<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Karyawan;
use App\Models\Upah;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UpahController extends Controller
{
    public function index()
    {
        try {
            if (Auth::user()->role === UserRole::Admin) {
                $upah = Upah::with('karyawan.user')->get();
            } else {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data karyawan tidak ditemukan'
                    ], 404);
                }
                $upah = Upah::with('karyawan.user')
                    ->where('id_karyawan', $karyawan->id)
                    ->get();
            }

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data upah',
                'error' => $e->getMessage()
            ], 500);
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

        $validator = Validator::make($request->all(), [
            'id_karyawan' => 'required|exists:karyawan,id',
            'minggu_ke' => 'required|integer|min:1',
            'total_dikerjakan' => 'required|integer|min:0',
            'total_upah' => 'required|integer|min:0',
            'periode_mulai' => 'required|date',
            'periode_selesai' => 'required|date|after_or_equal:periode_mulai'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $upah = Upah::create($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil ditambahkan',
                'data' => $upah
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $upah = Upah::with('karyawan.user')->find($id);

            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            if (Auth::user()->role !== UserRole::Admin) {
                $karyawan = Karyawan::where('users_id', Auth::id())->first();
                if (!$karyawan || $upah->id_karyawan !== $karyawan->id) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Unauthorized access'
                    ], 403);
                }
            }

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diambil',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data upah',
                'error' => $e->getMessage()
            ], 500);
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

        $validator = Validator::make($request->all(), [
            'id_karyawan' => 'required|exists:karyawan,id',
            'minggu_ke' => 'required|integer|min:1',
            'total_dikerjakan' => 'required|integer|min:0',
            'total_upah' => 'required|integer|min:0',
            'periode_mulai' => 'required|date',
            'periode_selesai' => 'required|date|after_or_equal:periode_mulai'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $upah = Upah::find($id);

            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            $upah->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil diupdate',
                'data' => $upah
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengupdate data upah',
                'error' => $e->getMessage()
            ], 500);
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
            $upah = Upah::find($id);

            if (!$upah) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data upah tidak ditemukan'
                ], 404);
            }

            $upah->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data upah berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data upah',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
