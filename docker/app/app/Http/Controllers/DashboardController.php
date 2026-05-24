<?php
// app/Http/Controllers/DashboardController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceStatus;

class DashboardController extends Controller
{
    public function index()
    {
        // LOGIKA BACKEND
        // 1. Ambil data dari database (MySQL via Docker)
        $services = [];
        $dbConnected = false;

        try {
            $services = ServiceStatus::all();
            $dbConnected = true;
        } catch (\Exception $e) {
            // Tangkap error jika DB belum jalan/terkoneksi (penting untuk UI nanti)
            $dbConnected = false;
        }
        
        $namaAdmin = "Admin Kelompok 3"; 

        // 3. Kirim data ke file tampilan
        return view('dashboard', [
            'nama_admin' => $namaAdmin,
            'services' => $services,
            'db_connected' => $dbConnected
        ]);
    }
}