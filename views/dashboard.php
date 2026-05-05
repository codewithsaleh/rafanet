<?php
// File: views/dashboard.php
session_start();

// Proteksi halaman: tendang kembali ke login jika tidak ada sesi
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Mini WiFi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        neon: '#00f2fe',
                        darkbg: '#0f172a'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-darkbg text-slate-300 font-sans antialiased min-h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-cyan-600/20 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-blue-600/20 rounded-full blur-[120px] pointer-events-none"></div>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto relative z-10">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold text-white mb-1">Overview System</h2>
                <p class="text-slate-400">Selamat datang kembali, pantau jaringan Anda hari ini.</p>
            </div>
            
            <div class="bg-white/5 backdrop-blur-md border border-white/10 px-5 py-3 rounded-2xl flex items-center gap-4">
                <div class="w-2 h-2 bg-neon rounded-full animate-pulse"></div>
                <span class="text-sm font-medium">Sisa Kuota: <span id="quota-display" class="text-neon font-bold">Mengambil data...</span></span>
            </div>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-2xl hover:border-neon/50 transition duration-300 group">
                <div class="w-12 h-12 bg-blue-500/20 text-blue-400 flex items-center justify-center rounded-xl mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <h3 class="text-slate-400 text-sm mb-1">Total Klien Aktif</h3>
                <p class="text-3xl font-bold text-white">0 <span class="text-sm font-normal text-slate-500">Klien</span></p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-2xl hover:border-green-500/50 transition duration-300 group">
                <div class="w-12 h-12 bg-green-500/20 text-green-400 flex items-center justify-center rounded-xl mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <h3 class="text-slate-400 text-sm mb-1">Pemasukan Bulan Ini</h3>
                <p class="text-3xl font-bold text-white">Rp 0</p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-6 rounded-2xl hover:border-purple-500/50 transition duration-300 group">
                <div class="w-12 h-12 bg-purple-500/20 text-purple-400 flex items-center justify-center rounded-xl mb-4 group-hover:scale-110 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                </div>
                <h3 class="text-slate-400 text-sm mb-1">Status Balik Modal (ROI)</h3>
                <p class="text-3xl font-bold text-white">Rp 0</p>
            </div>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 min-h-[300px]">
            <h3 class="text-xl font-semibold text-white mb-4">Aktivitas Terbaru</h3>
            <p class="text-slate-500">Belum ada data jaringan atau transaksi yang ditambahkan.</p>
        </div>

    </main>

    <script>
        setTimeout(() => {
            document.getElementById('quota-display').innerText = '150 GB';
        }, 1500);
    </script>
<?php require_once 'templates/footer.php'; ?>