<?php
// File: views/networks.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/Database.php';
require_once '../classes/Network.php';
require_once '../classes/Client.php';

$database = new Database();
$db = $database->getConnection();

$network = new Network($db);
$client = new Client($db);

$message = '';

// Proses Tambah Jaringan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_jaringan'])) {
    $network->client_id = $_POST['client_id'];
    $network->ip_address = $_POST['ip_address'];
    $network->ssid = $_POST['ssid'];
    $network->password = $_POST['password'];
    $network->bandwidth = $_POST['bandwidth'];

    if ($network->create()) {
        $message = "<div class='bg-green-500/20 border border-green-500 text-green-400 p-3 rounded-xl mb-4'>Berhasil menambahkan data jaringan!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal menambahkan data jaringan.</div>";
    }
}

// Proses Edit Jaringan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_jaringan'])) {
    $network->id = $_POST['id'];
    $network->client_id = $_POST['client_id'];
    $network->ip_address = $_POST['ip_address'];
    $network->ssid = $_POST['ssid'];
    $network->password = $_POST['password'];
    $network->bandwidth = $_POST['bandwidth'];

    if ($network->update()) {
        $message = "<div class='bg-blue-500/20 border border-blue-500 text-blue-400 p-3 rounded-xl mb-4'>Data jaringan berhasil diperbarui!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal memperbarui data jaringan.</div>";
    }
}

// Proses Hapus Jaringan
if (isset($_GET['delete_id'])) {
    $network->id = $_GET['delete_id'];
    if ($network->delete()) {
        header("Location: networks.php");
        exit;
    }
}

// Ambil data jaringan beserta perhitungan total modal
$stmt_networks = $network->read();

// Ambil data klien untuk dropdown form
$stmt_clients = $client->read();
$clients_array = [];
while ($row_client = $stmt_clients->fetch(PDO::FETCH_ASSOC)) {
    $clients_array[] = $row_client;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Jaringan & Router - Mini WiFi</title>
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
    <style>
        /* Menghilangkan panah default pada semua select dan mengganti dengan SVG Custom */
        select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2300f2fe' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2rem;
        }

        /* Memaksa tampilan dropdown option menjadi dark mode */
        select option {
            background-color: #0f172a;
            /* Warna background gelap (sama dengan body) */
            color: #f8fafc;
            /* Warna teks putih/terang */
            padding: 12px;
        }

        /* Sembunyikan scrollbar UI bawaan */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>

<body class="bg-darkbg text-slate-300 font-sans antialiased min-h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-cyan-600/20 rounded-full blur-[120px] pointer-events-none"></div>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="flex-1 p-8 relative z-10 flex flex-col h-screen overflow-hidden">
        <header class="flex justify-between items-center mb-6 shrink-0">
            <div>
                <h2 class="text-3xl font-bold text-white mb-1">Jaringan Klien</h2>
                <p class="text-slate-400">Kelola administrasi WiFi dan topologi perangkat klien.</p>
            </div>

            <button onclick="toggleModal('modalTambah')" class="bg-gradient-to-r from-neon to-blue-500 hover:from-cyan-400 hover:to-blue-400 text-darkbg font-bold py-2 px-6 rounded-xl shadow-[0_0_15px_rgba(0,242,254,0.3)] transition duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Jaringan
            </button>
        </header>

        <div class="shrink-0">
            <?= $message; ?>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden flex-1 relative flex flex-col min-h-0 shadow-xl">
            <div class="overflow-y-auto overflow-x-auto h-full no-scrollbar">
                <table id="networkTable" class="w-full text-left border-collapse relative">
                    <thead class="sticky top-0 z-20 bg-slate-900/95 backdrop-blur-md shadow-md border-b border-white/10">
                        <tr class="text-slate-300">
                            <th class="p-4 font-semibold">Nama Klien</th>
                            <th class="p-4 font-semibold">IP & Bandwidth</th>
                            <th class="p-4 font-semibold">Kredensial WiFi</th>
                            <th class="p-4 font-semibold">Modal Perangkat</th>
                            <th class="p-4 font-semibold text-center">Manajemen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 relative z-0">
                        <?php if ($stmt_networks->rowCount() > 0): ?>
                            <?php while ($row = $stmt_networks->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-white/10 transition">
                                    <td class="p-4 font-bold text-white"><?= htmlspecialchars($row['nama_klien'] ?? 'Klien Terhapus'); ?></td>
                                    
                                    <td class="p-4">
                                        <p class="text-neon font-mono text-sm"><?= htmlspecialchars($row['ip_address']); ?></p>
                                        <p class="text-xs text-slate-400 mt-1">Bandwidth: <span class="text-white font-medium"><?= htmlspecialchars($row['bandwidth'] ?: '-'); ?></span> Mbps</p>
                                    </td>
                                    
                                    <td class="p-4">
                                        <p class="text-sm text-white">SSID: <span class="font-medium text-neon"><?= htmlspecialchars($row['ssid'] ?: '-'); ?></span></p>
                                        <p class="text-xs text-slate-400 mt-1 flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                            <span class="font-mono text-white"><?= htmlspecialchars($row['password'] ?: 'Tidak diatur'); ?></span>
                                        </p>
                                    </td>
                                    
                                    <td class="p-4">
                                        <?php if ($row['total_modal'] > 0): ?>
                                            <span class="text-green-400 font-medium">Rp <?= number_format($row['total_modal'], 0, ',', '.'); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-500 italic text-sm">Belum ada perangkat</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="p-4 text-center flex justify-center gap-3">
                                        <a href="topology.php?id=<?= $row['id']; ?>" class="px-3 py-1 bg-purple-500/20 text-purple-400 hover:bg-purple-500/40 hover:text-white border border-purple-500/30 rounded-lg flex items-center gap-1 transition text-sm" title="Kelola Topologi & Aset">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25z" />
                                            </svg>
                                            Topologi
                                        </a>

                                        <button onclick="openEditModal(this)"
                                            data-id="<?= $row['id']; ?>"
                                            data-client="<?= $row['client_id']; ?>"
                                            data-ip="<?= htmlspecialchars($row['ip_address']); ?>"
                                            data-ssid="<?= htmlspecialchars($row['ssid']); ?>"
                                            data-pass="<?= htmlspecialchars($row['password']); ?>"
                                            data-bw="<?= htmlspecialchars($row['bandwidth']); ?>"
                                            class="text-neon hover:text-cyan-300 transition p-1" title="Edit Info">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>

                                        <a href="networks.php?delete_id=<?= $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus jaringan klien ini? Semua data topologi alat akan ikut terlepas.')" class="text-red-400 hover:text-red-300 transition p-1" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="p-6 text-center text-slate-500">Belum ada jaringan yang didaftarkan.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalTambah')"></div>
        <div class="relative bg-slate-900/90 border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)] backdrop-blur-2xl rounded-[2rem] w-full max-w-md p-8 z-10">

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white tracking-tight">Tambah Jaringan</h3>
                <button onclick="toggleModal('modalTambah')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="networks.php" method="POST" class="space-y-5">
                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Pemilik Klien</label>
                    <div class="relative">
                        <select name="client_id" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300 cursor-pointer">
                            <option value="" disabled selected>Pilih Klien Pemilik</option>
                            <?php foreach ($clients_array as $c): ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Alokasi IP Address</label>
                    <input type="text" name="ip_address" required placeholder="192.168.10.x" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-mono outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Nama WiFi</label>
                        <input type="text" name="ssid" placeholder="SSID" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300">
                    </div>
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Password</label>
                        <input type="text" name="password" placeholder="Passphrase" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Limit Bandwidth</label>
                    <input type="text" name="bandwidth" placeholder="Contoh: 10 Mbps" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300">
                </div>

                <div class="pt-4">
                    <button type="submit" name="tambah_jaringan" class="w-full bg-gradient-to-r from-neon to-blue-500 text-darkbg font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(0,242,254,0.3)]">
                        Simpan Konfigurasi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalEdit')"></div>
        <div class="relative bg-slate-900/90 border border-white/10 shadow-[0_0_50px_rgba(0,0,0,0.5)] backdrop-blur-2xl rounded-[2rem] w-full max-w-md p-8 z-10">

            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white tracking-tight">Edit Jaringan</h3>
                <button onclick="toggleModal('modalEdit')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="networks.php" method="POST" class="space-y-5">
                <input type="hidden" name="id" id="edit_id">

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Pemilik Klien</label>
                    <div class="relative">
                        <select name="client_id" id="edit_client_id" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 cursor-pointer">
                            <?php foreach ($clients_array as $c): ?>
                                <option value="<?= $c['id']; ?>"><?= htmlspecialchars($c['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Alokasi IP Address</label>
                    <input type="text" name="ip_address" id="edit_ip" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white font-mono outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Nama WiFi</label>
                        <input type="text" name="ssid" id="edit_ssid" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                    </div>
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Password</label>
                        <input type="text" name="password" id="edit_pass" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Limit Bandwidth</label>
                    <input type="text" name="bandwidth" id="edit_bw" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                </div>

                <div class="pt-4">
                    <button type="submit" name="edit_jaringan" class="w-full bg-blue-600 text-white font-black uppercase tracking-widest py-4 rounded-2xl hover:bg-blue-500 hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(37,99,235,0.3)]">
                        Perbarui Jaringan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            document.getElementById(modalID).classList.toggle('hidden');
        }

        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_client_id').value = btn.getAttribute('data-client');
            document.getElementById('edit_ip').value = btn.getAttribute('data-ip');
            document.getElementById('edit_ssid').value = btn.getAttribute('data-ssid');
            document.getElementById('edit_pass').value = btn.getAttribute('data-pass');
            document.getElementById('edit_bw').value = btn.getAttribute('data-bw');
            toggleModal('modalEdit');
        }
    </script>
    <?php require_once 'templates/footer.php'; ?>