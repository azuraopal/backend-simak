<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([ 
            'nama_lengkap' => 'Admin',
            'nomor_hp' => '081234567890',
            'email' => 'muhammadnovals334@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'Admin',
        ]);

        User::create([ 
            'nama_lengkap' => 'Staff Produksi',
            'nomor_hp' => '081234567898',
            'email' => 'muhammadnovalsupriyadi1@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'StaffProduksi',
        ]);
        User::create([ 
            'nomor_hp' => '081234567895',
            'nama_lengkap' => 'Staff Administrasi',
            'email' => 'simakpdjaya@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'StaffAdministrasi',
        ]);
    }
}
