<?php

namespace App\Notifications;

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
        return [
            'barang_harian_id' => $this->barangHarian->id,
            'message' => 'Pengajuan barang baru dari ' . $this->barangHarian->staff_produksi->nama,
            'tanggal_pengajuan' => $this->barangHarian->tanggal_pengajuan,
            'url' => url('/admin/pengajuan/' . $this->barangHarian->id),
        ];
    }

    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }
}
