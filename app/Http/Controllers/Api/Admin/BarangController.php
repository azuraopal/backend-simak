<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Models\Barang;
use App\Models\Stock;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class BarangController extends Controller
{
    public function index()
    {
        $barang = Barang::with(['kategori', 'stock'])->get()->map(fn($item) => [
            'id' => $item->id,
            'nama' => $item->nama,
            'deskripsi' => $item->deskripsi,
            'kategori' => $item->kategori_nama,
            'stok' => $item->jumlah_stock,
            'upah' => $item->upah,
        ]);

        if ($barang->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'Data barang tidak ditemukan',
                'data' => [],
            ], 404);
        }

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
                    'added_by_name' => $request->user()->nama_lengkap,
                    'nama_barang' => $barang->nama,
                    'stok_awal' => $request->stok_awal,
                ])
                ->log("Barang '{$barang->nama}' berhasil ditambahkan dengan stok awal {$request->stok_awal} oleh {$request->user()->nama_lengkap}");

            return response()->json([
                'status' => true,
                'message' => "Barang '{$barang->nama}' berhasil ditambahkan oleh {$request->user()->nama_lengkap}",
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

    public function show(Request $request, $id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => 'required|integer|exists:barang,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $barang = Barang::with(['kategori', 'stock'])->find($id);

        if (!$barang) {
            return response()->json([
                'status' => false,
                'message' => 'Barang tidak ditemukan',
                'data' => [],
            ], 404);
        }

        $data = [
            'id' => $barang->id,
            'nama' => $barang->nama,
            'deskripsi' => $barang->deskripsi,
            'kategori' => $barang->kategori_nama,
            'stok' => $barang->jumlah_stock,
            'upah' => $barang->upah,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Detail barang berhasil diambil',
            'data' => $data,
        ], 200);
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
                        'updated_by_name' => $request->user()->nama_lengkap,
                        'updated_fields' => array_merge($updatedData, $request->only('stok_awal')),
                    ])
                    ->log("Barang '{$barang->nama}' berhasil diperbarui oleh {$request->user()->nama_lengkap}");
            }

            return response()->json([
                'status' => true,
                'message' => "Barang '{$barang->nama}' berhasil diperbarui oleh {$request->user()->nama_lengkap}",
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

    public function destroy(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        if ($barang->stock > 0) {
            return response()->json([
                'status' => false,
                'message' => 'Barang tidak bisa dihapus karena masih memiliki stok',
            ], 400);
        }

        $barang->delete();

        $user = $request->user();

        activity()
            ->causedBy(auth()->user())
            ->log("Menghapus barang: {$barang->nama} oleh " . ($user->nama_lengkap ?: $user->email) . ".");

        return response()->json([
            'status' => true,
            'message' => 'Data barang berhasil dihapus',
        ], 200);
    }

    public function getTotalStockFromBarang()
    {
        try {
            $barangs = Barang::with('stock')->get();

            $totalStock = $barangs->sum(fn($barang) => $barang->stock->stock ?? 0);

            return response()->json([
                'status' => true,
                'message' => 'Total stok dari semua barang berhasil dijumlahkan',
                'total_stok' => $totalStock,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil stok',
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

            $barang->stock()->updateOrCreate(
                ['id' => $barang->stock_id],
                ['stock' => max(0, $stokAwal + $request->stok)]
            );

            $user = $request->user();

            if ($barang instanceof \Illuminate\Database\Eloquent\Model) {
                activity()
                    ->causedBy($user)
                    ->performedOn($barang)
                    ->withProperties([
                        'action' => 'addStock',
                        'added_stock' => $request->stok,
                        'previous_stock' => $stokAwal,
                        'new_stock' => $stokAwal + $request->stok,
                        'added_by' => $user->id,
                        'added_by_name' => $user->nama_lengkap ?: $user->email,
                    ])
                    ->log("Stok barang '{$barang->nama}' berhasil ditambahkan oleh " . ($user->nama_lengkap ?: $user->email) . ".");
            }

            return response()->json([
                'status' => true,
                'message' => 'Stok barang berhasil ditambahkan',
                'data' => [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'stok' => $stokAwal + $request->stok,
                    'added_by' => $user->name ?? $user->email,
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

    public function reduceStock(Request $request, $id)
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

            if ($stokAwal <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Stok habis',
                ], 400);
            }

            if ($stokAwal - $request->stok < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak bisa mengurangi stok di bawah 0',
                ], 400);
            }

            $barang->stock()->updateOrCreate(
                ['id' => $barang->stock_id],
                ['stock' => max(0, $stokAwal - $request->stok)]
            );

            $user = $request->user();

            if ($barang instanceof \Illuminate\Database\Eloquent\Model) {
                activity()
                    ->causedBy($user)
                    ->performedOn($barang)
                    ->withProperties([
                        'action' => 'reduceStock',
                        'reduced_stock' => $request->stok,
                        'previous_stock' => $stokAwal,
                        'new_stock' => max(0, $stokAwal - $request->stok),
                        'reduced_by' => $user->id,
                        'reduced_by_name' => $user->nama_lengkap ?: $user->email,
                    ])
                    ->log("Stok barang '{$barang->nama}' berhasil dikurangi sebanyak {$request->stok} oleh " . ($user->nama_lengkap ?: $user->email) . ".");
            }
            return response()->json([
                'status' => true,
                'message' => 'Stok barang berhasil dikurangi',
                'data' => [
                    'id' => $barang->id,
                    'nama' => $barang->nama,
                    'stok' => max(0, $stokAwal - $request->stok),
                    'reduced_by' => $user->name ?? $user->email,
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
}
