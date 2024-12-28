<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            // Menangani pengecualian autentikasi
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);  // Status code 401 untuk Unauthorized
        }

        // Jika bukan AuthenticationException, serahkan ke default handler
        return parent::render($request, $exception);
    }
}
