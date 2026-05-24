<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\ServiceStatus::insert([
            [
                'name' => 'Nginx Reverse Proxy',
                'status' => 'active',
                'port' => '80, 443',
                'description' => 'Meneruskan request ke aplikasi dan menangani SSL/TLS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'App Server (Laravel)',
                'status' => 'active',
                'port' => '9000',
                'description' => 'Container backend aplikasi utama',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Database (MariaDB)',
                'status' => 'active',
                'port' => '3306',
                'description' => 'Menyimpan data, tertutup dari public host',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'UFW Firewall',
                'status' => 'active',
                'port' => null,
                'description' => 'Mengamankan port server (hanya membuka 22, 80, 443)',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fail2Ban',
                'status' => 'warning',
                'port' => null,
                'description' => 'Mencegah serangan brute force pada SSH',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
