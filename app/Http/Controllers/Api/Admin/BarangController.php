<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function addStock(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'stok_tambahan' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $barang = Barang::findOrFail($id);
        $stock = $barang->stock;

        if ($stock) {
            $stock->stock += $request->stok_tambahan;
            $stock->save();

            activity()
                ->causedBy(auth()->user())
                ->withProperties(['stok_tambahan' => $request->stok_tambahan])
                ->log("Menambahkan stok barang: {$barang->nama}");
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Barang tidak memiliki stok awal',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Stok berhasil ditambahkan',
            'data' => $barang->load('stock'),
        ], 200);
    }

    public function index()
    {
        try {
            $barang = Barang::with('stock', 'kategori')->get();

            activity()
                ->causedBy(auth()->user())
                ->log('Melihat daftar barang.');

            return response()->json([
                'status' => true,
                'message' => 'Data barang berhasil diambil',
                'data' => $barang,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat mengambil data barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'deskripsi' => 'required|string',
            'kategori_barang' => 'required|integer|exists:kategori,id',
            'stok_awal' => 'required|integer|min:0',
            'upah' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $stock = Stock::create(['stock' => $request->stok_awal]);

            $barang = Barang::create([
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'kategori_barang' => $request->kategori_barang,
                'stock_id' => $stock->id,
                'upah' => $request->upah,
            ]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($barang)
                ->withProperties($barang->toArray())
                ->log('Menambahkan barang baru.');

            return response()->json([
                'status' => true,
                'message' => 'Barang berhasil ditambahkan dengan stok awal',
                'data' => $barang->load('stock'),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $barang = Barang::with('stock', 'kategori')->findOrFail($id);

        activity()
            ->causedBy(auth()->user())
            ->log("Melihat detail barang: {$barang->nama}");

        return response()->json([
            'status' => true,
            'message' => 'Data barang berhasil diambil',
            'data' => $barang,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:100',
            'deskripsi' => 'required|string',
            'kategori_barang' => 'required|integer|exists:kategori,id',
            'upah' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $barang = Barang::findOrFail($id);
        $barang->update($request->all());

        activity()
            ->causedBy(auth()->user())
            ->withProperties($request->all())
            ->log("Mengupdate data barang: {$barang->nama}");

        return response()->json([
            'status' => true,
            'message' => 'Data barang berhasil diupdate',
            'data' => $barang,
        ], 200);
    }

    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete();

        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus barang: {$barang->nama}");

        return response()->json([
            'status' => true,
            'message' => 'Data barang berhasil dihapus',
        ], 200);
    }
}
