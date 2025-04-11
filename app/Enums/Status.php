<?php

namespace App\Enums;

enum Status
{
    case Menunggu = 'menunggu';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match($this) {
            self::Menunggu => 'menunggu',
            self::Disetujui => 'disetujui',
            self::Ditolak => 'ditolak',
        };
    }
}
