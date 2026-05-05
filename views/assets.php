<?php
// File: views/assets.php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/Database.php';
require_once '../classes/Asset.php';

$database = new Database();
$db = $database->getConnection();
$asset = new Asset($db);

$message = '';

// Proses Simpan Tambah Data
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_aset'])) {
    $asset->merek = $_POST['merek'];
    $asset->nama = $_POST['nama'];
    $asset->jenis = $_POST['jenis'];
    $asset->detail_jenis = $_POST['detail_jenis'] ?? '';
    $asset->layer = $_POST['layer'];
    $asset->mac_address = $_POST['mac_address'];
    // Membersihkan format Rp dan titik sebelum disimpan ke tipe DECIMAL
    $asset->harga = str_replace(['Rp', '.', ' '], '', $_POST['harga']); 

    if($asset->create()) {
        $message = "<div class='bg-green-500/20 border border-green-500 text-green-400 p-3 rounded-xl mb-4'>Berhasil menambahkan aset perangkat!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal menambahkan aset.</div>";
    }
}

// Proses Simpan Edit Data
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_aset'])) {
    $asset->id = $_POST['id'];
    $asset->merek = $_POST['merek'];
    $asset->nama = $_POST['nama'];
    $asset->jenis = $_POST['jenis'];
    $asset->detail_jenis = $_POST['detail_jenis'] ?? '';
    $asset->layer = $_POST['layer'];
    $asset->mac_address = $_POST['mac_address'];
    $asset->harga = str_replace(['Rp', '.', ' '], '', $_POST['harga']);

    if($asset->update()) {
        $message = "<div class='bg-blue-500/20 border border-blue-500 text-blue-400 p-3 rounded-xl mb-4'>Data aset berhasil diperbarui!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal memperbarui data aset.</div>";
    }
}

// Proses Hapus Data
if(isset($_GET['delete_id'])) {
    $asset->id = $_GET['delete_id'];
    if($asset->delete()) {
        header("Location: assets.php");
        exit;
    }
}

// Persiapan Variabel untuk Ringkasan Card
$stmt = $asset->read();
$all_assets = [];
$total_aset_harga = 0;
$aset_termahal_nama = '-';
$aset_termahal_harga = 0;
$aset_terbaru_nama = 'Belum ada data';

// Hitung data untuk Card Ringkasan
if($stmt->rowCount() > 0) {
    $all_assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Perbaikan: Pastikan array index 0 ada dan gunakan '??' agar aman dari error "Undefined array key"
    if (!empty($all_assets) && isset($all_assets)) {
        $nama_terbaru = $all_assets['nama'] ?? 'Perangkat';
        $merek_terbaru = $all_assets['merek'] ?? 'Merek';
        $aset_terbaru_nama = htmlspecialchars($nama_terbaru . ' (' . $merek_terbaru . ')');
    }
    
    foreach($all_assets as $item) {
        // Ambil nilai dengan pengaman (fallback)
        $harga = $item['harga'] ?? 0;
        $nama = $item['nama'] ?? 'Tanpa Nama';

        $total_aset_harga += $harga;
        
        if($harga > $aset_termahal_harga) {
            $aset_termahal_harga = $harga;
            $aset_termahal_nama = htmlspecialchars($nama);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Inventaris Aset - Mini WiFi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { neon: '#00f2fe', darkbg: '#0f172a' } } } }
    </script>
    <style>
        /* Sembunyikan scrollbar bawaan agar UI tetap clean */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-darkbg text-slate-300 font-sans antialiased min-h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-cyan-600/20 rounded-full blur-[120px] pointer-events-none"></div>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="flex-1 p-8 relative z-10 flex flex-col h-screen overflow-hidden">
        <header class="flex justify-between items-center mb-6 shrink-0">
            <div>
                <h2 class="text-3xl font-bold text-white mb-1">Inventaris Aset</h2>
                <p class="text-slate-400">Kelola perangkat keras jaringan Anda.</p>
            </div>
            <button onclick="toggleModal('modalTambah')" class="bg-gradient-to-r from-neon to-blue-500 hover:from-cyan-400 hover:to-blue-400 text-darkbg font-bold py-2 px-6 rounded-xl shadow-[0_0_15px_rgba(0,242,254,0.3)] transition duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Aset
            </button>
        </header>

        <div class="shrink-0">
            <?= $message; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 shrink-0">
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-5 rounded-2xl hover:border-green-500/50 transition duration-300 group flex items-center gap-4">
                <div class="w-12 h-12 bg-green-500/20 text-green-400 flex items-center justify-center rounded-xl group-hover:scale-110 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                    <h3 class="text-slate-400 text-xs uppercase tracking-wider mb-1">Total Nilai Aset</h3>
                    <p class="text-2xl font-bold text-white">Rp <?= number_format($total_aset_harga, 0, ',', '.'); ?></p>
                </div>
            </div>

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-5 rounded-2xl hover:border-purple-500/50 transition duration-300 group flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-500/20 text-purple-400 flex items-center justify-center rounded-xl group-hover:scale-110 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                </div>
                <div>
                    <h3 class="text-slate-400 text-xs uppercase tracking-wider mb-1">Aset Termahal</h3>
                    <p class="text-lg font-bold text-white truncate max-w-[150px]"><?= $aset_termahal_nama; ?></p>
                    <p class="text-xs text-purple-400">Rp <?= number_format($aset_termahal_harga, 0, ',', '.'); ?></p>
                </div>
            </div>

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 p-5 rounded-2xl hover:border-neon/50 transition duration-300 group flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-500/20 text-neon flex items-center justify-center rounded-xl group-hover:scale-110 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <h3 class="text-slate-400 text-xs uppercase tracking-wider mb-1">Ditambahkan Terbaru</h3>
                    <p class="text-lg font-bold text-white truncate max-w-[150px]"><?= $aset_terbaru_nama; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden flex-1 relative flex flex-col min-h-0 shadow-xl">
            <div class="overflow-y-auto overflow-x-auto h-full no-scrollbar">
                <table id="assetTable" class="w-full text-left border-collapse relative">
                    <thead class="sticky top-0 z-20 bg-slate-900/95 backdrop-blur-md shadow-md border-b border-white/10">
                        <tr class="text-slate-300">
                            <th class="p-4 font-semibold cursor-pointer hover:text-neon transition group select-none" onclick="sortTable(0)">
                                Nama & Merek <span class="text-slate-500 group-hover:text-neon text-xs ml-1">↕</span>
                            </th>
                            <th class="p-4 font-semibold cursor-pointer hover:text-neon transition group select-none" onclick="sortTable(1)">
                                Jenis (Detail) <span class="text-slate-500 group-hover:text-neon text-xs ml-1">↕</span>
                            </th>
                            <th class="p-4 font-semibold cursor-pointer hover:text-neon transition group select-none text-center" onclick="sortTable(2)">
                                Layer <span class="text-slate-500 group-hover:text-neon text-xs ml-1">↕</span>
                            </th>
                            <th class="p-4 font-semibold">MAC Address</th>
                            <th class="p-4 font-semibold cursor-pointer hover:text-neon transition group select-none" onclick="sortTable(4)">
                                Harga <span class="text-slate-500 group-hover:text-neon text-xs ml-1">↕</span>
                            </th>
                            <th class="p-4 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/10 relative z-0">
                        <?php if(!empty($all_assets)): ?>
                            <?php foreach ($all_assets as $row): ?>
                            <tr class="hover:bg-white/10 transition">
                                <td class="p-4">
                                    <p class="font-bold text-white"><?= htmlspecialchars($row['nama']); ?></p>
                                    <p class="text-xs text-slate-400"><?= htmlspecialchars($row['merek']); ?></p>
                                </td>
                                <td class="p-4">
                                    <span class="text-neon"><?= htmlspecialchars($row['jenis']); ?></span>
                                    <?php if(!empty($row['detail_jenis'])): ?>
                                        <span class="text-xs text-slate-400 ml-1">(<?= htmlspecialchars($row['detail_jenis']); ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($row['layer'] == 0): ?>
                                        <span class="px-2 py-1 bg-slate-500/20 text-slate-400 rounded border border-slate-500/30 text-sm">Tanpa Layer</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 bg-blue-500/20 text-blue-300 rounded border border-blue-500/30 text-sm">L-<?= $row['layer']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 font-mono text-sm text-slate-400"><?= htmlspecialchars($row['mac_address']) ?: '-'; ?></td>
                                <td class="p-4 text-green-400 font-medium">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></td>
                                <td class="p-4 text-center flex justify-center gap-3">
                                    <button onclick="openEditModal(this)" 
                                            data-id="<?= $row['id']; ?>"
                                            data-merek="<?= htmlspecialchars($row['merek']); ?>"
                                            data-nama="<?= htmlspecialchars($row['nama']); ?>"
                                            data-jenis="<?= htmlspecialchars($row['jenis']); ?>"
                                            data-detail="<?= htmlspecialchars($row['detail_jenis']); ?>"
                                            data-layer="<?= $row['layer']; ?>"
                                            data-mac="<?= htmlspecialchars($row['mac_address']); ?>"
                                            data-harga="<?= intval($row['harga']); ?>" 
                                            class="text-neon hover:text-cyan-300 transition" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <a href="assets.php?delete_id=<?= $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus aset ini?')" class="text-red-400 hover:text-red-300 transition" title="Hapus">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="p-6 text-center text-slate-500">Belum ada aset terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/80 backdrop-blur-sm" onclick="toggleModal('modalTambah')"></div>
        <div class="relative bg-slate-900 border border-white/20 shadow-2xl rounded-2xl w-full max-w-md p-6 z-10 max-h-[90vh] overflow-y-auto no-scrollbar">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white">Tambah Aset Perangkat</h3>
                <button onclick="toggleModal('modalTambah')" class="text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="assets.php" method="POST" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Merek</label>
                        <input type="text" name="merek" placeholder="ZTE / TP-Link" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-neon outline-none">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Nama Seri</label>
                        <input type="text" name="nama" placeholder="F609 / WR840N" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-neon outline-none">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Jenis Perangkat</label>
                    <select name="jenis" onchange="renderDynamicFields(this.value, 'dynamic_container_add')" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white focus:border-neon outline-none">
                        <option value="">-- Pilih Jenis --</option>
                        <option value="Modem">Modem</option>
                        <option value="Router">Router</option>
                        <option value="Switch">Switch</option>
                        <option value="Mikrotik">Mikrotik</option>
                        <option value="HTB">HTB</option>
                        <option value="Cable">Cable</option>
                        <option value="Antenna">Antenna</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div id="dynamic_container_add" class="hidden"></div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Pilih Layer</label>
                        <select name="layer" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-white focus:border-neon outline-none">
                            <option value="0">0 - Tanpa Layer</option>
                            <option value="1">1 - Layer 1 (Inti/Modem)</option>
                            <option value="2">2 - Layer 2 (Mikrotik)</option>
                            <option value="3">3 - Layer 3 (Distribusi)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">MAC (Opsional)</label>
                        <input type="text" name="mac_address" placeholder="00:00:00:00:00:00" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white font-mono text-sm focus:border-neon outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-300 mb-1">Harga Perangkat (Rp)</label>
                    <input type="number" name="harga" placeholder="150000" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:border-neon outline-none">
                </div>

                <div class="pt-2">
                    <button type="submit" name="tambah_aset" class="w-full bg-neon text-darkbg font-bold py-2 rounded-lg hover:bg-cyan-400 transition shadow-[0_0_10px_rgba(0,242,254,0.3)]">Simpan Aset</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/80 backdrop-blur-sm" onclick="toggleModal('modalEdit')"></div>
        <div class="relative bg-slate-900 border border-white/20 shadow-2xl rounded-2xl w-full max-w-md p-6 z-10 max-h-[90vh] overflow-y-auto no-scrollbar">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-white">Edit Aset Perangkat</h3>
                <button onclick="toggleModal('modalEdit')" class="text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="assets.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Merek</label>
                        <input type="text" name="merek" id="edit_merek" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white outline-none focus:border-neon">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Nama Seri</label>
                        <input type="text" name="nama" id="edit_nama" required class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white outline-none focus:border-neon">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Jenis Perangkat</label>
                    <select name="jenis" id="edit_jenis" onchange="renderDynamicFields(this.value, 'dynamic_container_edit')" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">
                        <option value="Modem">Modem</option>
                        <option value="Router">Router</option>
                        <option value="Switch">Switch</option>
                        <option value="Mikrotik">Mikrotik</option>
                        <option value="HTB">HTB</option>
                        <option value="Cable">Cable</option>
                        <option value="Antenna">Antenna</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div id="dynamic_container_edit" class="hidden"></div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">Pilih Layer</label>
                        <select name="layer" id="edit_layer" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-3 py-2 text-white outline-none focus:border-neon">
                            <option value="0">0 - Tanpa Layer</option>
                            <option value="1">1 - Layer 1 (Inti/Modem)</option>
                            <option value="2">2 - Layer 2 (Mikrotik)</option>
                            <option value="3">3 - Layer 3 (Distribusi)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-300 mb-1">MAC Address</label>
                        <input type="text" name="mac_address" id="edit_mac" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white font-mono text-sm outline-none focus:border-neon">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-slate-300 mb-1">Harga Perangkat (Rp)</label>
                    <input type="number" name="harga" id="edit_harga" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">
                </div>

                <div class="pt-2">
                    <button type="submit" name="edit_aset" class="w-full bg-blue-500 text-white font-bold py-2 rounded-lg hover:bg-blue-400 transition shadow-[0_0_10px_rgba(59,130,246,0.3)]">Perbarui Aset</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(modalID) {
            document.getElementById(modalID).classList.toggle('hidden');
        }

        // Render Field Dinamis sesuai Jenis Perangkat
        function renderDynamicFields(jenis, containerId, existingValue = '') {
            const container = document.getElementById(containerId);
            container.innerHTML = ''; 
            let html = '';
            
            if (jenis === 'HTB') {
                html = `
                    <label class="block text-sm text-slate-300 mb-1">Sisi HTB (Side)</label>
                    <select name="detail_jenis" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">
                        <option value="Side A" ${existingValue === 'Side A' ? 'selected' : ''}>Side A</option>
                        <option value="Side B" ${existingValue === 'Side B' ? 'selected' : ''}>Side B</option>
                    </select>`;
            } else if (jenis === 'Cable') {
                html = `
                    <label class="block text-sm text-slate-300 mb-1">Panjang Kabel</label>
                    <input type="text" name="detail_jenis" value="${existingValue}" placeholder="100 Meter" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">`;
            } else if (jenis === 'Antenna') {
                html = `
                    <label class="block text-sm text-slate-300 mb-1">Tipe Penempatan</label>
                    <select name="detail_jenis" required class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">
                        <option value="Indoor" ${existingValue === 'Indoor' ? 'selected' : ''}>Indoor</option>
                        <option value="Outdoor" ${existingValue === 'Outdoor' ? 'selected' : ''}>Outdoor</option>
                    </select>`;
            } else if (jenis === 'Lainnya') {
                html = `
                    <label class="block text-sm text-slate-300 mb-1">Detail Spesifikasi Manual</label>
                    <input type="text" name="detail_jenis" value="${existingValue}" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white outline-none focus:border-neon">`;
            }

            if (html !== '') {
                container.innerHTML = html;
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }

        // Buka Modal Edit dan lempar data
        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_merek').value = btn.getAttribute('data-merek');
            document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
            document.getElementById('edit_jenis').value = btn.getAttribute('data-jenis');
            document.getElementById('edit_layer').value = btn.getAttribute('data-layer');
            document.getElementById('edit_mac').value = btn.getAttribute('data-mac');
            document.getElementById('edit_harga').value = btn.getAttribute('data-harga');

            renderDynamicFields(btn.getAttribute('data-jenis'), 'dynamic_container_edit', btn.getAttribute('data-detail'));
            toggleModal('modalEdit');
        }

        // Fitur Sorting Tabel
        let currentSortCol = -1;
        let sortAsc = true;

        function sortTable(colIdx) {
            const table = document.getElementById("assetTable");
            const tbody = table.querySelector("tbody");
            
            if(tbody.rows.length === 1 && tbody.rows.cells.length === 1) return;

            const rows = Array.from(tbody.querySelectorAll("tr"));
            sortAsc = (currentSortCol === colIdx) ? !sortAsc : true;
            currentSortCol = colIdx;

            rows.sort((a, b) => {
                let textA = a.cells[colIdx].innerText.trim();
                let textB = b.cells[colIdx].innerText.trim();
                
                // Parsing untuk angka (layer/harga)
                let numA = parseFloat(textA.replace(/[^0-9,-]+/g,""));
                let numB = parseFloat(textB.replace(/[^0-9,-]+/g,""));
                
                let isNum = !isNaN(numA) && !isNaN(numB) && textA.match(/\d/) && textB.match(/\d/);

                if(isNum) {
                    return sortAsc ? numA - numB : numB - numA;
                } else {
                    return sortAsc ? textA.localeCompare(textB) : textB.localeCompare(textA);
                }
            });

            tbody.append(...rows);
        }
    </script>
<?php require_once 'templates/footer.php'; ?>