<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use Illuminate\Http\Request;
use App\Enums\UserRole;
use Validator;

class KategoriController extends Controller
{
    private function validateRole(Request $request, array $allowedRoles): bool
    {
        $userRole = $request->user()?->role;

        if (is_string($userRole)) {
            $userRole = strtolower($userRole);
            $allowedRoles = array_map(function ($role) {
                return strtolower($role->value);
            }, $allowedRoles);
        }

        return in_array($userRole, $allowedRoles);
    }

    public function index(Request $request)
    {
        $this->validateRole($request, [UserRole::Admin, UserRole::StaffAdministrasi]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diambil',
            'data' => Kategori::all()
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->validateRole($request, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or StaffAdministrasi can perform this action.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:70',
            'deskripsi' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $kategori = Kategori::create($validator->validated());

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                activity()
                    ->causedBy($request->user())
                    ->performedOn($kategori)
                    ->withProperties([
                        'action' => 'store',
                        'added_by_name' => $request->user()->nama_lengkap,
                        'nama_kategori' => $kategori->nama,
                        'deskripsi' => $kategori->deskripsi,
                    ])
                    ->log("Kategori '{$kategori->nama}' berhasil ditambahkan oleh {$request->user()->nama_lengkap}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dibuat',
                'data' => $kategori
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
        $this->validateRole($request, [UserRole::Admin, UserRole::StaffAdministrasi]);

        $kategori = Kategori::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail kategori berhasil diambil.',
            'data' => $kategori
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->validateRole($request, [UserRole::Admin, UserRole::StaffAdministrasi])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin can perform this action.',
            ], 403);
        }

        try {
            $kategori = Kategori::find($id);

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:100',
                'deskripsi' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $oldData = $kategori->toArray();

            $kategori->update($validator->validated());

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                if ($kategori instanceof Kategori) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($kategori)
                        ->withProperties([
                            'action' => 'update',
                            'updated_by_name' => $request->user()->nama_lengkap,
                            'old_data' => $oldData,
                            'new_data' => $kategori->toArray(),
                        ])
                        ->log("Kategori '{$kategori->nama}' diupdate oleh {$request->user()->nama_lengkap}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diupdate',
                'data' => $kategori
            ]);
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
        if (!$this->validateRole($request, [UserRole::Admin])) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin can perform this action.',
            ], 403);
        }

        try {
            $kategori = Kategori::find($id);

            if (!$kategori) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan',
                    'data' => null
                ], 404);
            }

            $kategoriData = $kategori->toArray();

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                if ($kategori instanceof Kategori) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($kategori)
                        ->withProperties([
                            'action' => 'delete',
                            'deleted_by_name' => $request->user()->nama_lengkap,
                            'kategori_data' => $kategoriData,
                        ])
                        ->log("Kategori '{$kategori->nama}' dihapus oleh {$request->user()->nama_lengkap}");
                }
            }

            $kategori->delete();

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
