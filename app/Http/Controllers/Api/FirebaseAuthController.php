<?php

namespace App\Http\Controllers;

use App\Models\User;
use Hash;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class FirebaseAuthController extends Controller
{
    protected $auth;
    protected $database;

    public function __construct()
    {
        $this->auth = app('firebase.auth');
    }

    public function sendOTP(Request $request)
    {
        try {
            $request->validate([
                'nomor_hp' => 'required|string'
            ]);

            $phoneNumber = $request->nomor_hp;
            if (!str_starts_with($phoneNumber, '+')) {
                $phoneNumber = '+62' . ltrim($phoneNumber, '0');
            }

            $verification = $this->auth->signInWithPhoneNumber($phoneNumber);

            return response()->json([
                'status' => true,
                'message' => 'OTP berhasil dikirim',
                'verification_id' => $verification->verificationId()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengirim OTP',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        try {
            $request->validate([
                'verification_id' => 'required|string',
                'otp_code' => 'required|string'
            ]);

            $verification = $this->auth->verifyPhoneNumber(
                $request->verification_id,
                $request->otp_code
            );

            if ($verification->phoneNumber()) {
                return response()->json([
                    'status' => true,
                    'message' => 'OTP valid',
                    'nomor_hp' => $verification->phoneNumber()
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'OTP tidak valid',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'nomor_hp' => 'required|string',
                'verification_id' => 'required|string',
                'otp_code' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed'
            ]);

            $verification = $this->auth->verifyPhoneNumber(
                $request->verification_id,
                $request->otp_code
            );

            if (!$verification->phoneNumber()) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP tidak valid'
                ], 400);
            }

            $user = User::where('nomor_hp', $verification->phoneNumber())->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password berhasil direset'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}