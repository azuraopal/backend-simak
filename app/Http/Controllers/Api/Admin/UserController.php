<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Helpers\EmailHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();
        $totalUsers = $users->count();

        return response()->json([
            'status' => true,
            'message' => 'Data pengguna berhasil diambil',
            'total_users' => $totalUsers,
            'data' => $users,
        ], 200);
    }
    public function store(Request $request)
    {
        $currentRole = $request->user()->role->value;

        $allowedRoles = $this->getAllowedRoles($currentRole);
        if (empty($allowedRoles)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to create user with this role',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => [
                'required',
                new Enum(UserRole::class),
                Rule::in($allowedRoles),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $password = Str::random(8);

            $emailSent = EmailHelper::sendUserCredentials(
                $request->email,
                $request->nama_lengkap,
                $password
            );

            if (!$emailSent) {
                \Log::error('Email gagal dikirim untuk user: ' . $request->email);
                return response()->json([
                    'status' => false,
                    'message' => 'Gagal mengirim kredensial email. User tidak dibuat',
                ], 500);
            }

            $defaultFotoProfile = config('constants.default_profile_picture', 'default.jpg');

            // Buat user baru
            $user = User::create([
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => $request->role,
                'foto_profile' => $request->foto_profile ?? $defaultFotoProfile,
                'email_verified_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => "User dengan peran {$request->role} berhasil didaftarkan oleh {$currentRole}",
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error saat membuat user: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function getAllowedRoles(string $currentRole): array
    {
        if ($currentRole === UserRole::Admin->value) {
            return [UserRole::Admin->value, UserRole::Staff->value, UserRole::Karyawan->value];
        }

        if ($currentRole === UserRole::Staff->value) {
            return [UserRole::Karyawan->value];
        }

        return [];
    }

    public function show(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            return response()->json($user, 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    }

    public function update(Request $request, $id)
    {

        try {
            $user = User::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nama_lengkap' => 'sometimes|required|string|max:100',
                'email' => "sometimes|required|email|unique:users,email,{$user->id}",
                'password' => 'nullable|string|min:8',
                'role' => ['sometimes', new Enum(UserRole::class)],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'User berhasil diupdate',
                'data' => $user,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json(['message' => 'User berhasil dihapus'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }
    }
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'foto_profile' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $user = $request->user();

        if ($user->foto_profile && Storage::exists("public/{$user->foto_profile}")) {
            Storage::delete("public/{$user->foto_profile}");
        }

        $fileName = uniqid() . '.' . $request->foto_profile->extension();
        $request->foto_profile->storeAs('public/profile_photos', $fileName);

        $user->foto_profile = "profile_photos/{$fileName}";
        $user->save();

        return response()->json([
            'message' => 'Foto profil berhasil diunggah',
            'foto_profile_url' => asset("storage/{$user->foto_profile}"),
        ]);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Password lama tidak sesuai',
            ], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password berhasil diperbarui',
        ], 200);
    }

}
