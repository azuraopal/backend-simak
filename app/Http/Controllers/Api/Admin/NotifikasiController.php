<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use App\Http\Controllers\Controller;

class NotifikasiController extends Controller
{

    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()->latest()->get();

        return response()->json([
            'data' => $notifications
        ]);
    }

    public function read($id)
    {
        $notifikasi = Auth::user()->notifications()->where('id', $id)->firstOrFail();

        $notifikasi->markAsRead();

        $url = $notifikasi->data['url'] ?? '/dashboard';

        return redirect($url);
    }
}
