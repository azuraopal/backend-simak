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
    public function index()
    {
        $barang = Barang::all();

        return response()->json([
            'status' => true,
            'message' => 'Data barang berhasil diambil',
            'data' => $barang,
        ], 200);
    }

    public function store(Request $request)
    {
        if (!$this->isAdminOrStaff($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or Staff can perform this action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'deskripsi' => 'required|string|max:1000',
            'kategori_barang' => 'required|integer|exists:kategori,id',
            'stok_awal' => 'required|integer|min:1',
            'upah' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $barang = Barang::create([
                'nama' => $request->nama,
                'deskripsi' => $request->deskripsi,
                'kategori_barang' => $request->kategori_barang,
                'upah' => $request->upah,
            ]);

            $barang->stock()->updateOrCreate(
                ['id' => $barang->stock_id],
                ['stock' => $request->stok_awal]
            );

            activity()
                ->causedBy($request->user())
                ->performedOn($barang)
                ->withProperties([
                    'action' => 'store',
                    'nama' => $barang->nama,
                    'stok_awal' => $request->stok_awal,
                ])
                ->log("Barang '{$barang->nama}' berhasil ditambahkan dengan stok awal {$request->stok_awal}");

            return response()->json([
                'status' => true,
                'message' => 'Barang berhasil ditambahkan',
                'data' => $barang,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (!$this->isAdminOrStaff($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or Staff can perform this action.',
            ], 403);
        }

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'status' => false,
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|string|max:255',
            'deskripsi' => 'sometimes|string|max:1000',
            'kategori_barang' => 'sometimes|integer|exists:kategori,id',
            'upah' => 'sometimes|numeric|min:0',
            'stok_awal' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $updatedData = $request->only(['nama', 'deskripsi', 'kategori_barang', 'upah']);
            $barang->update($updatedData);

            if ($request->has('stok_awal')) {
                $barang->stock()->update(['stock' => $request->stok_awal]);
            }

            if ($barang instanceof \Illuminate\Database\Eloquent\Model) {
                activity()
                    ->causedBy($request->user())
                    ->performedOn($barang)
                    ->withProperties([
                        'action' => 'update',
                        'updated_fields' => array_merge($updatedData, $request->only('stok_awal')),
                    ])
                    ->log("Barang '{$barang->nama}' berhasil diperbarui");
            }

            return response()->json([
                'status' => true,
                'message' => 'Barang berhasil diperbarui',
                'data' => $barang,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addStock(Request $request, $id)
    {
        if (!$this->isAdminOrStaff($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or Staff can perform this action.',
            ], 403);
        }

        $barang = Barang::find($id);

        if (!$barang) {
            return response()->json([
                'status' => false,
                'message' => 'Barang tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'stok' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $stokAwal = $barang->stock ? $barang->stock->stock : 0;

            // Update stok
            $barang->stock()->updateOrCreate(
                ['id' => $barang->stock_id],
                ['stock' => $stokAwal + $request->stok]
            );

            // Log aktivitas
            if ($barang instanceof \Illuminate\Database\Eloquent\Model) {
                activity()
                    ->causedBy($request->user())
                    ->performedOn($barang)
                    ->withProperties([
                        'action' => 'addStock',
                        'added_stock' => $request->stok,
                        'previous_stock' => $stokAwal,
                        'new_stock' => $stokAwal + $request->stok,
                    ])
                    ->log("Stok barang '{$barang->nama}' berhasil ditambahkan sebanyak {$request->stok}");
            }

            return response()->json([
                'status' => true,
                'message' => 'Stok barang berhasil ditambahkan',
                'data' => [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'stok' => $stokAwal + $request->stok,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function isAdminOrStaff($request): bool
    {
        $role = $request->user()->role;
        return in_array($role->value, ['Admin', 'Staff']);
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
