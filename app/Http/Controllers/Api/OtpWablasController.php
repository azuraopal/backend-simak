<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OtpWablasController extends Controller
{
    public function sendOTP(Request $request)
    {
        $request->validate([
            'nomor_hp' => 'required|string'
        ]);

        $phoneNumber = $request->nomor_hp;
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+62' . ltrim($phoneNumber, '0');
        }

        $otp = rand(100000, 999999);
        Cache::put("otp_{$phoneNumber}", $otp, now()->addMinutes(5));

        try {
            $response = Http::post(env('WABLAS_API_URL'), [
                'phone' => $phoneNumber,
                'message' => "Kode OTP Anda: $otp",
                'token' => env('WABLAS_API_TOKEN')
            ]);

            return response()->json([
                'status' => true,
                'message' => 'OTP berhasil dikirim melalui WhatsApp'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim OTP via WhatsApp',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'nomor_hp' => 'required|string',
            'otp_code' => 'required|string'
        ]);

        $phoneNumber = $request->nomor_hp;
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+62' . ltrim($phoneNumber, '0');
        }

        $cachedOtp = Cache::get("otp_{$phoneNumber}");

        if ($cachedOtp && $cachedOtp == $request->otp_code) {
            Cache::forget("otp_{$phoneNumber}");
            return response()->json([
                'status' => true,
                'message' => 'OTP valid'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'OTP tidak valid atau sudah kadaluarsa'
        ], 400);
    }
}
