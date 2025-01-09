<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Karyawan::create([ 
            'users_id'=> 2,
            'nama'=> 'Milki Dwi',
            'tanggal_lahir'=> '26-12-2024',
            'pekerjaan'=> 'Lem Atas',
            'alamat'=> 'Jl. Pintu',
            'telepon'=> '08123456789'
        ]);
    }
}
