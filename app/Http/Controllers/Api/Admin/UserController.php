<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Helpers\EmailHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use DB;
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
        $currentRole = $request->user()->role;


        if ($currentRole instanceof UserRole) {
            $currentRole = $currentRole->value;
        }

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
            DB::beginTransaction();

            $password = Str::random(8);
            $roleValue = $request->role instanceof UserRole ? $request->role->value : $request->role;
            $defaultFotoProfile = config('constants.default_profile_picture', 'default.jpg');

            try {
                $emailSent = EmailHelper::sendUserCredentials(
                    $request->email,
                    $request->nama_lengkap,
                    $password
                );

                if (!$emailSent) {
                    throw new Exception("Gagal mengirim email kredensial");
                }
            } catch (Exception $emailError) {
                DB::rollBack();
                \Log::error("Error saat mengirim email: " . $emailError->getMessage());

                return response()->json([
                    'status' => false,
                    'message' => 'Gagal membuat user karena email tidak dapat dikirim',
                    'error' => $emailError->getMessage()
                ], 500);
            }

            $userData = [
                'nama_lengkap' => $request->nama_lengkap,
                'email' => $request->email,
                'password' => Hash::make($password),
                'role' => $roleValue,
                'foto_profile' => $request->foto_profile ?? $defaultFotoProfile,
                'email_verified_at' => now(),
            ];

            if (!$this->validateUserData($userData)) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Data user tidak valid',
                ], 422);
            }

            $user = User::create($userData);

            if ($currentRole === UserRole::Staff->value) {
                try {
                    $userCreating = $request->user();
                    activity()
                        ->causedBy($userCreating)
                        ->performedOn($user)
                        ->withProperties([
                            'action' => 'store',
                            'added_by' => $userCreating->id,
                            'added_by_name' => $userCreating->nama_lengkap ?: $userCreating->email,
                            'user_id' => $user->id,
                            'user_email' => $user->email,
                            'user_role' => $roleValue,
                        ])
                        ->log("Staff created a new user with role {$roleValue} oleh " . ($userCreating->nama_lengkap ?: $userCreating->email));
                } catch (Exception $logError) {
                    \Log::warning("Failed to log activity: " . $logError->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "User dengan peran {$roleValue} berhasil didaftarkan oleh {$currentRole}",
                'data' => $user,
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error("Error saat membuat user: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Server Error',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * 
     * @param array $userData
     * @return bool
     */
    private function validateUserData(array $userData): bool
    {
        $requiredFields = ['nama_lengkap', 'email', 'password', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                \Log::error("Missing required field: {$field}");
                return false;
            }
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            \Log::error("Invalid email format: {$userData['email']}");
            return false;
        }

        if (strlen($userData['password']) < 8) {
            \Log::error("Password too short");
            return false;
        }

        $validRoles = array_map(fn($role) => $role->value, UserRole::cases());
        if (!in_array($userData['role'], $validRoles)) {
            \Log::error("Invalid role: {$userData['role']}");
            return false;
        }

        return true;
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

            if (!$user instanceof \Illuminate\Database\Eloquent\Model) {
                \Log::error("performedOn() menerima tipe data yang salah: " . get_class($user));
                return response()->json(['message' => 'Server Error'], 500);
            }

            if ($request->user()->role === UserRole::Staff->value) {
                $userUpdating = $request->user();
                activity()
                    ->causedBy($userUpdating)
                    ->performedOn($user)
                    ->withProperties([
                        'action' => 'update',
                        'updated_fields' => array_keys($validated),
                        'updated_by' => $userUpdating->id,
                        'updated_by_name' => $userUpdating->nama_lengkap ?: $userUpdating->email,
                    ])
                    ->log("Staff updated user with ID {$user->id} oleh " . ($userUpdating->nama_lengkap ?: $userUpdating->email));
            }


            return response()->json([
                'status' => true,
                'message' => 'User berhasil diupdate',
                'data' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        } catch (Exception $e) {
            \Log::error("Error saat mengupdate user ID {$id}: {$e->getMessage()}");
            return response()->json(['message' => 'Server Error'], 500);
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
