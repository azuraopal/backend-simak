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
            'email' => 'muhammadnovals334@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'Admin',
        ]);

        User::create([ 
            'nama_lengkap' => 'Staff Produksi',
            'email' => 'muhammadnovalsupriyadi1@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'StaffProduksi',
        ]);
        User::create([ 
            'nama_lengkap' => 'Staff',
            'email' => 'simakpdjaya@gmail.com',
            'password' => Hash::make('nopal123'),
            'role' => 'Staff',
        ]);
    }
}
