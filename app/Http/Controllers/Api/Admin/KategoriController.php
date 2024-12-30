<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Enums\UserRole;

class KategoriController extends Controller
{
    private function validateRole(Request $request): void
    {
        if ($request->user()?->role !== UserRole::Admin) {
            abort(403, 'Unauthorized.');
        }
    }

    public function index(Request $request)
    {

        return response()->json([
            'success' => true,
            'message' => 'Kategori fetched successfully.',
            'data' => Kategori::all()
        ]);
    }

    public function store(Request $request)
    {
        $this->validateRole($request);

        $validated = $request->validate([
            'nama' => 'required|string|max:70',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori = Kategori::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori created successfully.',
            'data' => $kategori
        ], 201);
    }

    public function show(Request $request, $id)
    {

        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori not found.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kategori details fetched successfully.',
            'data' => $kategori
        ]);
    }


    public function update(Request $request, $id)
    {
        $this->validateRole($request);

        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori not found.',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
        ]);

        $kategori->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori updated successfully.',
            'data' => $kategori
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $this->validateRole($request);

        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori not found.',
                'data' => null
            ], 404);
        }

        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori deleted successfully.',
        ], 200);
    }

}
