<?php

namespace App\Helpers;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\Log;

class EmailHelper
{
    public static function sendUserCredentials($email, $nama_lengkap, $password)
    {
        require_once base_path('vendor/autoload.php');

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = env('MAIL_USERNAME');
            $mail->Password = env('MAIL_PASSWORD');
            $mail->SMTPSecure = env('MAIL_ENCRYPTION');
            $mail->Port = env('MAIL_PORT');


            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($email, $nama_lengkap);
            $mail->isHTML(true);
            $mail->Subject = 'Kredensial Akun Anda';
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
            return true;
        } catch (Exception $e) {
            Log::error('Email gagal dikirim: ' . $e->getMessage());
            return false;
        }
    }
}