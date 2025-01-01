<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\BarangHarian;
use App\Models\Karyawan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
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
            $barang = Barang::all();

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => $barang
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
                'nama' => 'required|string|max:100',
                'deskripsi' => 'required|string',
                'kategori_barang' => 'required|integer|exists:kategori,id',
                'stok_awal' => 'required|integer|min:0',
                'stok_tersedia' => 'required|integer|min:0',
                'upah' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barang = Barang::create($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil ditambahkan',
                'data' => $barang
            ], 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $barang = Barang::find($id);

            if (!$barang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => $barang
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
                'nama' => 'required|string|max:100',
                'deskripsi' => 'required|string',
                'kategori_barang' => 'required|integer|exists:kategori,id',
                'stok_awal' => 'required|integer|min:0',
                'stok_tersedia' => 'required|integer|min:0',
                'upah' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $barang = Barang::find($id);
            if (!$barang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

            $barang->update($request->all());

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diupdate',
                'data' => $barang
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
            $barang = Barang::find($id);
            if (!$barang) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data barang tidak ditemukan'
                ], 404);
            }

            $barang->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}