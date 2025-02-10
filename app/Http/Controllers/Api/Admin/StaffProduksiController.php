<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\StaffProduksi;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StaffProduksiController extends Controller
{
    public function index()
    {
        try {
            $staffProduksi = StaffProduksi::with('user')->get();

            if ($staffProduksi->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data Staff Produksi tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $staffProduksi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
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
            'users_id' => 'required|exists:users,id',
            'tanggal_lahir' => 'required|date',
            'pekerjaan' => 'required|string|max:255',
            'alamat' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->users_id);
        if (!$user || $user->role !== UserRole::StaffProduksi) {
            return response()->json([
                'status' => false,
                'message' => 'ID Staff Produksi tidak valid atau User bukan Staff Produksi'
            ], 422);
        }

        if (StaffProduksi::where('users_id', $request->users_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Informasi Staff Produksi sudah tersedia'
            ], 422);
        }

        if (!$user->nomor_hp) {
            return response()->json([
                'status' => false,
                'message' => 'Nomor HP belum tersedia pada data pengguna'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $selectedUser = User::findOrFail($request->users_id);

            $staffProduksi = StaffProduksi::create([
                'users_id' => $request->users_id,
                'nama' => $selectedUser->nama_lengkap,
                'tanggal_lahir' => $request->tanggal_lahir,
                'pekerjaan' => $request->pekerjaan,
                'alamat' => $request->alamat,
                'telepon' => $user->nomor_hp,
                'email' => $selectedUser->email,
            ]);

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                activity()
                    ->causedBy($request->user())
                    ->performedOn($staffProduksi)
                    ->withProperties([
                        'action' => 'store',
                        'added_by_name' => $request->user()->nama_lengkap,
                        'staffProduksi_data' => [
                            'nama' => $staffProduksi->nama,
                            'email' => $staffProduksi->email,
                            'pekerjaan' => $staffProduksi->pekerjaan,
                            'tanggal_lahir' => $staffProduksi->tanggal_lahir,
                        ]
                    ])
                    ->log("Staff '{$request->user()->nama_lengkap}' menambahkan data Staff baru '{$staffProduksi->nama}'");
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Informasi Staff Produksi berhasil dibuat',
                'data' => $staffProduksi->load('user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat informasi Staff Produksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $staffProduksi = StaffProduksi::with('user')->find($id);

        if (!$staffProduksi) {
            return response()->json([
                'status' => false,
                'message' => 'Staff Produksi tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $staffProduksi
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!$this->isAdminOrStaff($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or Staff can perform this action.',
            ], 403);
        }

        $staffProduksi = StaffProduksi::find($id);

        if (!$staffProduksi) {
            return response()->json([
                'status' => false,
                'message' => 'Staff Produksi not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'string|max:100',
            'tanggal_lahir' => 'date',
            'pekerjaan' => 'string|max:255',
            'alamat' => 'string',
            'telepon' => 'string|max:20',
            'email' => "email|unique:users,email,{$staffProduksi->users_id}"
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $oldData = [
                'nama' => $staffProduksi->nama,
                'tanggal_lahir' => $staffProduksi->tanggal_lahir,
                'pekerjaan' => $staffProduksi->pekerjaan,
                'alamat' => $staffProduksi->alamat,
                'telepon' => $staffProduksi->telepon,
                'email' => $staffProduksi->user->email
            ];

            $staffProduksi->update($request->all());

            if ($request->has('email') || $request->has('nama')) {
                $staffProduksi->user->update([
                    'email' => $request->email ?? $staffProduksi->user->email,
                    'nama_lengkap' => $request->nama ?? $staffProduksi->user->nama_lengkap
                ]);
            }

            $changes = array_diff_assoc(array_intersect_key($request->all(), $oldData), $oldData);

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                if ($staffProduksi instanceof StaffProduksi) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($staffProduksi)
                        ->withProperties([
                            'action' => 'update',
                            'updated_by_name' => $request->user()->nama_lengkap,
                            'updated_by_role' => $request->user()->role,
                            'staffProduksi' => $staffProduksi->nama,
                            'old_data' => $oldData,
                            'changes' => $changes
                        ])
                        ->log("Staff '{$request->user()->nama_lengkap}' memperbarui data Staff Produksi '{$staffProduksi->nama}'");
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Staff Produksi updated successfully',
                'data' => $staffProduksi->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update Staff Produksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(Request $request, $id)
    {
        if (!$this->isAdminOrStaff($request)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only Admin or Staff can perform this action.',
            ], 403);
        }

        $staffProduksi = StaffProduksi::find($id);

        if (!$staffProduksi) {
            return response()->json([
                'status' => false,
                'message' => 'Staff Produksi not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $staffProduksiData = [
                'nama' => $staffProduksi->nama,
                'email' => $staffProduksi->email,
                'pekerjaan' => $staffProduksi->pekerjaan
            ];

            if ($request->user()->role === UserRole::StaffAdministrasi) {
                if ($staffProduksi instanceof StaffProduksi) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($staffProduksi)
                        ->withProperties([
                            'action' => 'delete',
                            'deleted_by_name' => $request->user()->nama_lengkap,
                            'deleted_by_role' => $request->user()->role,
                            'staff_produksi_data' => $staffProduksiData
                        ])
                        ->log("{$request->user()->role} '{$request->user()->nama_lengkap}' menghapus data Staff Produksi '{$staffProduksi->nama}'");
                }
            }

            $staffProduksi->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Staff Produksi information deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete Staff Produksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    private function isAdminOrStaff($request): bool
    {
        $role = $request->user()->role;
        return in_array($role->value, ['Admin', 'Staff']);
    }

    public function search(Request $request)
    {
        $search = $request->get('search');
        $staffProduksi = StaffProduksi::where('nama', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->with('user')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $staffProduksi
        ]);
    }
}