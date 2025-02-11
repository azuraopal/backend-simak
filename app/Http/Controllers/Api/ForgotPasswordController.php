<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class ForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request)
    {
        $request->validate([
            'nomor_hp' => 'required'
        ]);

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        \DB::table('password_resets')->updateOrInsert(
            ['phone' => $request->nomor_hp],
            [
                'token' => Hash::make($code),
                'created_at' => now()
            ]
        );

        $response = Http::post('http://localhost:3000/send-message', [
            'phone' => $request->nomor_hp,
            'message' => "Kode reset password Anda: $code\n\nBerlaku 15 menit."
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
        $request->validate([
            'nomor_hp' => 'required',
            'code' => 'required',
            'password' => 'required|confirmed|min:6'
        ]);

        $reset = \DB::table('password_resets')
            ->where('phone', $request->nomor_hp)
            ->first();

        if (!$reset || !Hash::check($request->code, $reset->token)) {
            return response()->json([
                'message' => 'Kode tidak valid'
            ], 400);
        }

        if (now()->diffInMinutes($reset->created_at) > 15) {
            return response()->json([
                'message' => 'Kode sudah kadaluarsa'
            ], 400);
        }

        User::where('nomor_hp', $request->nomor_hp)
            ->update(['password' => Hash::make($request->password)]);

        \DB::table('password_resets')
            ->where('phone', $request->nomor_hp)
            ->delete();

        return response()->json([
            'message' => 'Password berhasil direset'
        ]);
    }
}