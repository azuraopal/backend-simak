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
        $karyawan = Karyawan::with('user')->get();
        return response()->json([
            'status' => true,
            'data' => $karyawan
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'users_id' => 'required|exists:users,id',
            'nama' => 'required|string|max:100',
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
            $user = User::findOrFail($request->users_id);

            $karyawan = Karyawan::create([
                'users_id' => $request->users_id,
                'nama' => $request->nama,
                'tanggal_lahir' => $request->tanggal_lahir,
                'pekerjaan' => $request->pekerjaan,
                'alamat' => $request->alamat,
                'telepon' => $request->telepon,
                'email' => $user->email,
            ]);

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
            $karyawan->update($request->all());

            if ($request->has('email') || $request->has('nama')) {
                $karyawan->user->update([
                    'email' => $request->email ?? $karyawan->user->email,
                    'nama_lengkap' => $request->nama ?? $karyawan->user->nama_lengkap
                ]);
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
    public function destroy($id)
    {
        $karyawan = Karyawan::find($id);

        if (!$karyawan) {
            return response()->json([
                'status' => false,
                'message' => 'Karyawan not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
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