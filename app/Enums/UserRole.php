<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'Admin';
    case StaffProduksi = 'StaffProduksi';
    case StaffAdministrasi = 'StaffAdministrasi';
}