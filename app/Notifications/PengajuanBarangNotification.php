<?php

namespace App\Notifications;

use App\Enums\UserRole;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PengajuanBarangNotification extends Notification
{
    use Queueable;

    protected $barangHarian;

    public function __construct($barangHarian)
    {
        $this->barangHarian = $barangHarian;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        $role = $notifiable->role;

        $prefix = match ($role) {
            UserRole::Admin => 'admin',
            UserRole::StaffAdministrasi => 'staff-administrasi',
            default => 'user',
        };

        return [
            'barang_harian_id' => $this->barangHarian->id,
            'message' => 'Pengajuan barang baru dari ' . $this->barangHarian->staff_produksi->nama,
            'tanggal_pengajuan' => $this->barangHarian->tanggal_pengajuan,
            'url' => url("/{$prefix}/pengajuan/" . $this->barangHarian->id),
        ];
    }


    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
