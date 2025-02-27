<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use Illuminate\Support\Facades\Log;

class EmailHelper
{
    public static function sendUserCredentials($email, $nama_lengkap, $password)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) {
                Log::info("SMTP Debug: $str");
            };

            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');

            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($email, $nama_lengkap);

            $mail->isHTML(true);
            $mail->Subject = 'Kredensial Akun SIMAK';
            $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9;'>
                <h1 style='color: #333; text-align: center;'>Selamat Datang, $nama_lengkap!</h1>
                <p style='font-size: 16px; color: #555;'>Terima kasih telah bergabung dengan platform kami. Berikut adalah kredensial akun Anda:</p>
                <table style='width: 100%; margin: 20px 0; font-size: 16px; color: #555;'>
                    <tr>
                        <td style='font-weight: bold; width: 30%;'>Email</td>
                        <td>: $email</td>
                    </tr>
                    <tr>
                        <td style='font-weight: bold;'>Password</td>
                        <td>: <span style='font-family: monospace; color: #000;'>$password</span></td>
                    </tr>
                </table>
                <p style='font-size: 16px; color: #555;'>Silakan gunakan kredensial ini untuk masuk ke akun Anda. Kami sangat menyarankan Anda untuk mengganti password Anda segera setelah login pertama demi keamanan akun Anda.</p>
                <p style='text-align: center; font-size: 12px; color: #aaa; margin-top: 20px;'>Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi tim dukungan kami.</p>
            </div>
        ";

            $mail->send();
            Log::info("Email berhasil dikirim ke $email");
            return true;
        } catch (Exception $e) {
            Log::error("Email gagal dikirim: {$mail->ErrorInfo}");
            return false;
        }
    }

    public static function sendPasswordResetLink($email, $nama_lengkap, $resetLink)
    {
        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = function ($str, $level) {
                Log::info("SMTP Debug: $str");
            };

            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');

            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($email, $nama_lengkap);

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Akun Anda';
            $mail->Body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; background-color: #f9f9f9;'>
            <h1 style='color: #333; text-align: center;'>Lupa Password?</h1>
            <p style='font-size: 16px; color: #555;'>Jika Anda merasa ini adalah permintaan yang sah, klik link berikut untuk mengatur ulang password Anda:</p>
            <p style='text-align: center; font-size: 16px;'>
                <a href='$resetLink' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
            </p>
            <p style='font-size: 14px; color: #777;'>Jika Anda tidak merasa ini adalah permintaan yang sah, abaikan email ini.</p>
        </div>
    ";

            $mail->send();
            Log::info("Email reset password berhasil dikirim ke $email");
            return true;
        } catch (Exception $e) {
            Log::error("Email gagal dikirim: {$mail->ErrorInfo}");
            return false;
        }
    }

}