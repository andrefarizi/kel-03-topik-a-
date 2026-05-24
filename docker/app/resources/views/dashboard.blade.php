<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Manajemen Server - KSJ Kelompok 3</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: '#0ea5e9', // Sky 500
                        success: '#10b981', // Emerald 500
                        danger: '#ef4444',  // Red 500
                        warning: '#f59e0b', // Amber 500
                        darkbg: '#0f172a',  // Slate 900
                        cardbg: 'rgba(30, 41, 59, 0.7)', // Slate 800 with opacity
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glow-text { text-shadow: 0 0 10px rgba(14, 165, 233, 0.5); }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }
        .pulse-dot-danger {
            animation: pulse-danger 2s infinite;
        }
        @keyframes pulse-danger {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-darkbg text-slate-300 min-h-screen relative overflow-x-hidden selection:bg-primary selection:text-white">

    <!-- Background Decoration -->
    <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-primary/10 to-transparent -z-10"></div>
    <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] rounded-full bg-primary/20 blur-[120px] -z-10"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] rounded-full bg-success/10 blur-[120px] -z-10"></div>

    <!-- Navbar -->
    <nav class="glass-panel border-b border-white/10 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded bg-gradient-to-br from-primary to-blue-600 flex items-center justify-center shadow-lg shadow-primary/30">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                    </div>
                    <span class="text-xl font-bold text-white tracking-wide glow-text">KSJ<span class="text-primary">.</span>Kelompok 3</span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium px-3 py-1 rounded-full bg-white/5 border border-white/10 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $db_connected ? 'bg-success pulse-dot' : 'bg-danger pulse-dot-danger' }}"></span>
                        DB: {{ $db_connected ? 'Connected' : 'Disconnected' }}
                    </span>
                    <button class="bg-white/10 hover:bg-white/20 border border-white/10 px-4 py-2 rounded-md text-sm font-semibold transition-all duration-300 shadow-lg flex items-center gap-2 text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar -->
        <div class="w-full lg:w-64 flex-shrink-0">
            <div class="glass-panel rounded-xl p-4 sticky top-24">
                <div class="mb-6 px-2">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Pengguna Aktif</p>
                    <p class="text-white font-medium flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-gradient-to-tr from-slate-700 to-slate-600 flex items-center justify-center text-xs border border-slate-500">AD</span>
                        {{ $nama_admin ?? 'Admin' }}
                    </p>
                </div>
                <ul class="space-y-1">
                    <li>
                        <a href="#" class="flex items-center gap-3 text-white font-medium p-3 rounded-lg bg-primary/20 border border-primary/30 transition-all shadow-[0_0_15px_rgba(14,165,233,0.15)]">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 text-slate-400 font-medium p-3 rounded-lg hover:bg-white/5 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Firewall & Keamanan
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 text-slate-400 font-medium p-3 rounded-lg hover:bg-white/5 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                            Container Docker
                        </a>
                    </li>
                    <li>
                        <a href="#" class="flex items-center gap-3 text-slate-400 font-medium p-3 rounded-lg hover:bg-white/5 hover:text-white transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            Pengaturan Sistem
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 space-y-6">
            
            <div class="glass-panel p-6 rounded-xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none">
                    <svg class="w-32 h-32 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Ikhtisar Infrastruktur Web App</h1>
                <p class="text-slate-400 text-sm max-w-2xl leading-relaxed">
                    Arsitektur 3-kontainer dengan Nginx reverse proxy, App Server (Laravel), dan MariaDB/PostgreSQL. Sistem di-deploy menggunakan Docker Compose dengan integrasi UFW dan Fail2Ban.
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="glass-panel p-5 rounded-xl border-t-2 border-t-primary hover:bg-white/5 transition duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Status Enkripsi</p>
                            <h3 class="text-xl font-bold text-white">SSL/TLS Aktif</h3>
                        </div>
                        <div class="p-2 bg-primary/20 rounded-lg text-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">Port 443 via Nginx Volume</p>
                </div>

                <div class="glass-panel p-5 rounded-xl border-t-2 border-t-success hover:bg-white/5 transition duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Network Tier</p>
                            <h3 class="text-xl font-bold text-white">Bridge Terisolasi</h3>
                        </div>
                        <div class="p-2 bg-success/20 rounded-lg text-success">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">Koneksi DB tertutup dari Host</p>
                </div>

                <div class="glass-panel p-5 rounded-xl border-t-2 {{ $db_connected ? 'border-t-primary' : 'border-t-danger' }} hover:bg-white/5 transition duration-300">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-1">Koneksi Database</p>
                            <h3 class="text-xl font-bold {{ $db_connected ? 'text-white' : 'text-danger' }}">
                                {{ $db_connected ? 'Online' : 'Offline' }}
                            </h3>
                        </div>
                        <div class="p-2 {{ $db_connected ? 'bg-primary/20 text-primary' : 'bg-danger/20 text-danger' }} rounded-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-4">
                        {{ $db_connected ? 'Data berhasil di-load via Laravel Model' : 'Pastikan container MySQL berjalan' }}
                    </p>
                </div>
            </div>

            <!-- Table Section -->
            <div class="glass-panel rounded-xl overflow-hidden">
                <div class="p-5 border-b border-white/10 flex justify-between items-center bg-white/5">
                    <h3 class="text-lg font-bold text-white">Status Layanan & Container</h3>
                    <button class="text-xs bg-primary/20 text-primary hover:bg-primary/30 px-3 py-1.5 rounded transition-colors font-medium border border-primary/30">Refresh</button>
                </div>
                
                @if(!$db_connected)
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-danger/20 text-danger rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <h4 class="text-white font-semibold text-lg mb-2">Database Belum Terkoneksi</h4>
                    <p class="text-slate-400 text-sm max-w-md mx-auto">Silakan jalankan migrasi <code class="bg-black/30 px-1 py-0.5 rounded text-primary">php artisan migrate --seed</code> jika database sudah nyala, atau pastikan .env Anda sudah benar.</p>
                </div>
                @elseif(count($services) == 0)
                <div class="p-12 text-center text-slate-400">
                    <p>Database terkoneksi, tapi tabel service_statuses masih kosong.</p>
                    <p class="text-sm mt-2">Jalankan <code class="bg-black/30 px-1 py-0.5 rounded text-primary">php artisan db:seed</code></p>
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="text-xs text-slate-400 uppercase bg-black/20 border-b border-white/10">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Nama Layanan</th>
                                <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Port Terbuka</th>
                                <th scope="col" class="px-6 py-4 font-semibold tracking-wider">Deskripsi Peran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($services as $service)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4 font-medium text-white flex items-center gap-3">
                                    <div class="w-8 h-8 rounded bg-white/5 flex items-center justify-center border border-white/10 group-hover:border-primary/50 transition-colors">
                                        <svg class="w-4 h-4 text-slate-300 group-hover:text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path></svg>
                                    </div>
                                    {{ $service->name }}
                                </td>
                                <td class="px-6 py-4">
                                    @if(strtolower($service->status) == 'active')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-success/10 text-success border border-success/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success"></span> Active
                                        </span>
                                    @elseif(strtolower($service->status) == 'warning')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-warning/10 text-warning border border-warning/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-warning"></span> Warning
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-danger/10 text-danger border border-danger/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-danger"></span> Error
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-300 font-mono text-xs">
                                    {{ $service->port ?? 'Internal Only' }}
                                </td>
                                <td class="px-6 py-4 text-slate-400 text-xs truncate max-w-xs" title="{{ $service->description }}">
                                    {{ $service->description }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>
    </div>

</body>
</html>