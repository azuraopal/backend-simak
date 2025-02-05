<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Models\Karyawan;
use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class KaryawanController extends Controller
{
    public function index()
    {
        try {
            $karyawan = Karyawan::with('user')->get();

            if ($karyawan->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data karyawan tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => $karyawan
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
            'telepon' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::find($request->users_id);
        if (!$user || $user->role !== UserRole::Karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'ID karyawan tidak valid atau User bukan Karyawan'
            ], 422);
        }

        if (Karyawan::where('users_id', $request->users_id)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'Informasi karyawan sudah tersedia'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $selectedUser = User::findOrFail($request->users_id);

            $karyawan = Karyawan::create([
                'users_id' => $request->users_id,
                'nama' => $selectedUser->nama_lengkap,
                'tanggal_lahir' => $request->tanggal_lahir,
                'pekerjaan' => $request->pekerjaan,
                'alamat' => $request->alamat,
                'telepon' => $request->telepon,
                'email' => $selectedUser->email,
            ]);

            if ($request->user()->role === UserRole::Staff) {
                activity()
                    ->causedBy($request->user())
                    ->performedOn($karyawan)
                    ->withProperties([
                        'action' => 'store',
                        'added_by_name' => $request->user()->nama_lengkap,
                        'karyawan_data' => [
                            'nama' => $karyawan->nama,
                            'email' => $karyawan->email,
                            'pekerjaan' => $karyawan->pekerjaan,
                            'tanggal_lahir' => $karyawan->tanggal_lahir,
                        ]
                    ])
                    ->log("Staff '{$request->user()->nama_lengkap}' menambahkan data karyawan baru '{$karyawan->nama}'");
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Informasi karyawan berhasil dibuat',
                'data' => $karyawan->load('user')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat informasi karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function show($id)
    {
        $karyawan = Karyawan::with('user')->find($id);

        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $karyawan
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

        $karyawan = Karyawan::find($id);

        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Karyawan not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'string|max:100',
            'tanggal_lahir' => 'date',
            'pekerjaan' => 'string|max:255',
            'alamat' => 'string',
            'telepon' => 'string|max:20',
            'email' => "email|unique:users,email,{$karyawan->users_id}"
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
                'nama' => $karyawan->nama,
                'tanggal_lahir' => $karyawan->tanggal_lahir,
                'pekerjaan' => $karyawan->pekerjaan,
                'alamat' => $karyawan->alamat,
                'telepon' => $karyawan->telepon,
                'email' => $karyawan->user->email
            ];

            $karyawan->update($request->all());

            if ($request->has('email') || $request->has('nama')) {
                $karyawan->user->update([
                    'email' => $request->email ?? $karyawan->user->email,
                    'nama_lengkap' => $request->nama ?? $karyawan->user->nama_lengkap
                ]);
            }

            $changes = array_diff_assoc(array_intersect_key($request->all(), $oldData), $oldData);

            if ($request->user()->role === UserRole::Staff) {
                if ($karyawan instanceof Karyawan) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($karyawan)
                        ->withProperties([
                            'action' => 'update',
                            'updated_by_name' => $request->user()->nama_lengkap,
                            'updated_by_role' => $request->user()->role,
                            'karyawan' => $karyawan->nama,
                            'old_data' => $oldData,
                            'changes' => $changes
                        ])
                        ->log("Staff '{$request->user()->nama_lengkap}' memperbarui data karyawan '{$karyawan->nama}'");
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Karyawan updated successfully',
                'data' => $karyawan->load('user')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update karyawan',
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

        $karyawan = Karyawan::find($id);

        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Karyawan not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $karyawanData = [
                'nama' => $karyawan->nama,
                'email' => $karyawan->email,
                'pekerjaan' => $karyawan->pekerjaan
            ];

            if ($request->user()->role === UserRole::Staff) {
                if ($karyawan instanceof Karyawan) {
                    activity()
                        ->causedBy($request->user())
                        ->performedOn($karyawan)
                        ->withProperties([
                            'action' => 'delete',
                            'deleted_by_name' => $request->user()->nama_lengkap,
                            'deleted_by_role' => $request->user()->role,
                            'karyawan_data' => $karyawanData
                        ])
                        ->log("{$request->user()->role} '{$request->user()->nama_lengkap}' menghapus data karyawan '{$karyawan->nama}'");
                }
            }

            $karyawan->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Karyawan information deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete karyawan',
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
        $karyawan = Karyawan::where('nama', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->with('user')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $karyawan
        ]);
    }
}