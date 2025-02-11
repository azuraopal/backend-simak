<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request)
    {
        $nomor_hp = $this->normalizePhoneNumber($request->nomor_hp);
        $nomor_hp_alt = str_replace('+62', '0', $nomor_hp);

        $user = User::where('nomor_hp', $nomor_hp)
            ->orWhere('nomor_hp', $nomor_hp_alt)
            ->orWhere('nomor_hp', 'LIKE', '%' . substr($nomor_hp, 3))
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Nomor HP tidak ditemukan'
            ], 404);
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_resets')->updateOrInsert(
            ['phone' => $nomor_hp],
            [
                'token' => Hash::make($code),
                'created_at' => now()
            ]
        );

        $response = Http::post('http://localhost:3000/send-message', [
            'phone' => $nomor_hp,
            'message' => "🔐 *Kode Verifikasi Reset Password*\n\n" .
                "Kode rahasia Anda: *{$code}*\n" .
                "Kode ini hanya berlaku selama *15 menit*.\n\n" .
                "Jangan berikan kode ini kepada siapa pun demi keamanan akun Anda.\n\n" .
                "Terima kasih,\nSIMAK"
        ]);

        if (!$response->successful()) {
            return response()->json([
                'message' => 'Gagal mengirim kode'
            ], 500);
        }

        return response()->json([
            'message' => 'Kode reset password telah dikirim via WhatsApp'
        ]);
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->headers->set('Accept', 'application/json');

            $validator = Validator::make($request->all(), [
                'nomor_hp' => 'required',
                'code' => 'required',
                'password' => 'required|confirmed|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $nomor_hp = $this->normalizePhoneNumber($request->nomor_hp);
            $nomor_hp_alt = str_replace('+62', '0', $nomor_hp);

            $user = User::where('nomor_hp', $nomor_hp)
                ->orWhere('nomor_hp', $nomor_hp_alt)
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $reset = DB::table('password_resets')
                ->where('phone', $nomor_hp)
                ->orWhere('phone', $nomor_hp_alt)
                ->first();

            if (!$reset || !Hash::check($request->code, $reset->token)) {
                return response()->json([
                    'message' => 'Kode tidak valid'
                ], 400);
            }

            if (Carbon::parse($reset->created_at)->diffInMinutes(now()) > 15) {
                return response()->json([
                    'message' => 'Kode sudah kadaluarsa'
                ], 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            DB::table('password_resets')->where('phone', $nomor_hp)->delete();

            return response()->json([
                'message' => 'Password berhasil direset'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function normalizePhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 2) == '62') {
            $formatted = "+{$phone}";
        } elseif (substr($phone, 0, 1) == '0') {
            $formatted = "+62" . substr($phone, 1);
        } else {
            $formatted = "+62{$phone}";
        }

        return $formatted;
    }
}
