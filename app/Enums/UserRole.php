<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'Admin';
    case Karyawan = 'Karyawan';

    case Staff = 'Staff';
}