<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Stock;
use App\Models\BarangHarian;
use App\Models\StaffProduksi;
use App\Models\Upah;
use App\Models\User;
use App\Enums\UserRole;
use Carbon\Carbon;

class SimakSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'nama_lengkap' => 'Administrator',
            'email' => 'admin@example.com',
            'nomor_hp' => '081234567890',
            'password' => bcrypt('password123'),
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        User::create([
            'nama_lengkap' => 'Staff Administrasi',
            'email' => 'administrasi@example.com',
            'nomor_hp' => '081234567891',
            'password' => bcrypt('password123'),
            'role' => UserRole::StaffAdministrasi,
            'email_verified_at' => now(),
        ]);

        $staffUsers = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'nama_lengkap' => "Staff Produksi {$i}",
                'email' => "staff.produksi{$i}@example.com",
                'nomor_hp' => "08123456" . sprintf('%04d', $i),
                'password' => bcrypt('password123'),
                'role' => UserRole::StaffProduksi,
                'email_verified_at' => now(),
            ]);

            $staffProduksi = StaffProduksi::createWithUserData($user->id, [
                'tanggal_lahir' => Carbon::now()->subYears(25)->format('d-m-Y'),
                'pekerjaan' => 'Lem Bawah',
                'alamat' => 'Jalan Pintu Ledeng',
            ]);

            $staffUsers[] = [
                'user' => $user,
                'staff' => $staffProduksi
            ];
        }

        $kategoris = [
            ['nama' => 'Pakaian', 'deskripsi' => 'Berbagai jenis pakaian'],
            ['nama' => 'Aksesoris', 'deskripsi' => 'Berbagai jenis aksesoris'],
            ['nama' => 'Sepatu', 'deskripsi' => 'Berbagai jenis sepatu'],
        ];

        foreach ($kategoris as $kategori) {
            Kategori::create($kategori);
        }

        $barangs = [
            [
                'nama' => 'Kemeja Lengan Panjang',
                'deskripsi' => 'Kemeja formal lengan panjang',
                'kategori_barang' => 1,
                'upah' => 50000
            ],
            [
                'nama' => 'Celana Jeans',
                'deskripsi' => 'Celana jeans premium',
                'kategori_barang' => 1,
                'upah' => 75000
            ],
            [
                'nama' => 'Topi Baseball',
                'deskripsi' => 'Topi baseball casual',
                'kategori_barang' => 2,
                'upah' => 25000
            ],
        ];

        foreach ($barangs as $barang) {
            $stock = Stock::create(['stock' => rand(50, 100)]);
            $barang['stock_id'] = $stock->id;
            Barang::create($barang);
        }

        $startDate = Carbon::now()->subDays(30);

        foreach ($staffUsers as $staffUser) {
            $staffId = $staffUser['staff']->id;

            $upah = Upah::create([
                'staff_produksi_id' => $staffId,
                'minggu_ke' => 1,
                'total_dikerjakan' => 0,
                'total_upah' => 0,
                'periode_mulai' => $startDate,
                'periode_selesai' => Carbon::now(),
            ]);

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);

                BarangHarian::create([
                    'staff_produksi_id' => $staffId,
                    'barang_id' => rand(1, 3),
                    'tanggal' => $date,
                    'jumlah_dikerjakan' => rand(5, 20)
                ]);
            }

            $upah->recalculateTotal();
        }
    }
}
