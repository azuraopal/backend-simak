<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User; // Pastikan Anda mengimpor model User

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([ 
            'nama_lengkap' => 'Admin',
            'email' => 'admin@mail.test',
            'password' => Hash::make('12345678'),
            'role' => 'Admin',
        ]);
    }
}
