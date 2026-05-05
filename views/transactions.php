<?php
// File: views/transactions.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/Database.php';
require_once '../classes/Transaction.php';

$database = new Database();
$db = $database->getConnection();
$trans = new Transaction($db);

$message = '';

// =================================================================================
// 1. PROSES CRUD TRANSAKSI MANUAL
// =================================================================================

// Tambah Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_transaksi'])) {
    $trans->jenis_transaksi = $_POST['jenis_transaksi'];

    $kategori = $_POST['kategori'];
    if ($kategori == 'Lainnya' && !empty($_POST['kategori_manual'])) {
        $kategori = htmlspecialchars($_POST['kategori_manual']);
    }
    $trans->kategori = $kategori;
    $trans->nama_item = $_POST['nama_item'];
    $trans->qty = $_POST['qty'];
    $trans->harga_satuan = str_replace('.', '', $_POST['harga_satuan']);
    $trans->keterangan = $_POST['keterangan'];

    if ($trans->createManual()) {
        $message = "<div class='bg-green-500/20 border border-green-500 text-green-400 p-3 rounded-xl mb-4'>Transaksi berhasil dicatat!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal mencatat transaksi.</div>";
    }
}

// Edit Transaksi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_transaksi'])) {
    $trans->id = $_POST['id'];

    $kategori = $_POST['kategori'];
    if ($kategori == 'Lainnya' && !empty($_POST['kategori_manual'])) {
        $kategori = htmlspecialchars($_POST['kategori_manual']);
    }
    $trans->kategori = $kategori;
    $trans->nama_item = $_POST['nama_item'];
    $trans->qty = $_POST['qty'];
    $trans->harga_satuan = str_replace('.', '', $_POST['harga_satuan']);
    $trans->keterangan = $_POST['keterangan'];

    if ($trans->updateManual()) {
        $message = "<div class='bg-blue-500/20 border border-blue-500 text-blue-400 p-3 rounded-xl mb-4'>Transaksi berhasil diperbarui!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal memperbarui transaksi.</div>";
    }
}

// Hapus Transaksi Manual
if (isset($_GET['delete_id'])) {
    $trans->id = $_GET['delete_id'];
    if ($trans->deleteManual()) {
        header("Location: transactions.php");
        exit;
    }
}

// =================================================================================
// 2. PENGAMBILAN DATA
// =================================================================================
$auto_modal_server = $trans->getServerInfrastructureTotal();
$auto_modal_klien = $trans->getClientInfrastructureTotal();
$auto_registrasi = $trans->getRegistrationIncomeTotal();

$query_client_details = "SELECT c.nama, SUM(a.harga) as total_modal 
                         FROM network_devices nd 
                         JOIN networks n ON nd.network_id = n.id 
                         JOIN clients c ON n.client_id = c.id 
                         JOIN assets a ON nd.asset_id = a.id 
                         GROUP BY c.id";
$stmt_cd = $db->prepare($query_client_details);
$stmt_cd->execute();
$client_details = $stmt_cd->fetchAll(PDO::FETCH_ASSOC);

$stmt_pengeluaran = $trans->readManual('Pengeluaran');
$stmt_pemasukan = $trans->readManual('Pemasukan');

$total_pengeluaran = $auto_modal_server + $auto_modal_klien + $trans->getTotalManual('Pengeluaran');
$total_pemasukan = $auto_registrasi + $trans->getTotalManual('Pemasukan');
$laba_rugi = $total_pemasukan - $total_pengeluaran;
$warna_laba = $laba_rugi >= 0 ? 'text-green-400' : 'text-red-400';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Keuangan & Transaksi - WIFI.NET</title>
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
        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2300f2fe' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19.5 8.25l-7.5 7.5-7.5-7.5' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.2rem;
        }

        select option {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 12px;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .custom-scroll::-webkit-scrollbar {
            height: 8px;
            width: 8px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
        }

        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 242, 254, 0.5);
        }
    </style>
</head>

<body class="bg-darkbg text-slate-300 font-sans h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-red-600/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-[-10%] right-[-10%] w-96 h-96 bg-green-600/10 rounded-full blur-[120px] pointer-events-none"></div>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="flex-1 p-8 flex flex-col h-full relative z-10 overflow-hidden">
        <header class="mb-6 shrink-0">
            <h2 class="text-3xl font-bold text-white tracking-tight mb-4">Arus Kas & Keuangan</h2>
            <?= $message; ?>
            <div class="grid grid-cols-3 gap-6">
                <div class="bg-slate-900/50 border border-red-500/30 p-5 rounded-2xl shadow-lg">
                    <p class="text-sm text-slate-400 uppercase tracking-widest mb-1">Total Pengeluaran</p>
                    <h3 class="text-2xl font-black text-red-400">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></h3>
                </div>
                <div class="bg-slate-900/50 border border-green-500/30 p-5 rounded-2xl shadow-lg">
                    <p class="text-sm text-slate-400 uppercase tracking-widest mb-1">Total Pemasukan</p>
                    <h3 class="text-2xl font-black text-green-400">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></h3>
                </div>
                <div class="bg-slate-900/50 border border-white/10 p-5 rounded-2xl shadow-lg">
                    <p class="text-sm text-slate-400 uppercase tracking-widest mb-1">Laba / (Rugi)</p>
                    <h3 class="text-2xl font-black <?= $warna_laba ?>">Rp <?= number_format($laba_rugi, 0, ',', '.') ?></h3>
                </div>
            </div>
        </header>

        <div class="flex-1 grid grid-cols-1 xl:grid-cols-2 gap-8 min-h-0">

            <section class="bg-white/5 backdrop-blur-xl border border-red-500/20 rounded-3xl p-6 flex flex-col shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center mb-6 shrink-0 border-b border-white/10 pb-4">
                    <h3 class="text-xl font-bold text-red-400 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        PENGELUARAN
                    </h3>
                    <button onclick="toggleModal('modalTransaksi', 'Pengeluaran')" class="bg-red-500/20 text-red-400 border border-red-500/40 px-4 py-2 rounded-xl text-sm font-bold hover:bg-red-500 hover:text-white transition whitespace-nowrap">
                        + Catat Manual
                    </button>
                </div>

                <div class="flex-1 overflow-x-auto overflow-y-auto custom-scroll pb-4">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead class="sticky top-0 bg-slate-900/95 backdrop-blur-md z-10">
                            <tr class="text-slate-400 text-sm">
                                <th class="p-3 font-semibold rounded-tl-lg whitespace-nowrap">No</th>
                                <th class="p-3 font-semibold whitespace-nowrap">Item</th>
                                <th class="p-3 font-semibold whitespace-nowrap">Kategori</th>
                                <th class="p-3 font-semibold text-center whitespace-nowrap">Qty</th>
                                <th class="p-3 font-semibold text-right whitespace-nowrap">Harga Satuan</th>
                                <th class="p-3 font-semibold text-right whitespace-nowrap">Total (Rp)</th>
                                <th class="p-3 font-semibold whitespace-nowrap min-w-[150px]">Keterangan</th>
                                <th class="p-3 font-semibold text-center rounded-tr-lg whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 text-sm">
                            <?php $no_pengeluaran = 1; ?>

                            <?php if ($auto_modal_server > 0): ?>
                                <tr class="bg-blue-900/10 hover:bg-blue-900/20 transition text-slate-300">
                                    <td class="p-3"><?= $no_pengeluaran++; ?></td>
                                    <td class="p-3 font-bold text-blue-400">Infrastruktur Server [Auto]</td>
                                    <td class="p-3"><span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs">Backbone</span></td>
                                    <td class="p-3 text-center font-mono">1 Lot</td>
                                    <td class="p-3 text-right font-mono"><?= number_format($auto_modal_server, 0, ',', '.') ?></td>
                                    <td class="p-3 text-right font-bold text-red-400"><?= number_format($auto_modal_server, 0, ',', '.') ?></td>
                                    <td class="p-3 text-xs text-slate-400">Total nilai backbone pusat (Sekali pakai)</td>
                                    <td class="p-3 text-center">
                                        <a href="networks.php" class="inline-flex items-center gap-1.5 text-[10px] bg-slate-800 border border-slate-600/50 text-slate-300 px-2.5 py-1.5 rounded-lg hover:bg-slate-700 hover:text-white hover:border-slate-500 transition-all shadow-sm" title="Ubah via Topologi">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Cek Topologi
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($client_details as $cd): ?>
                                <tr class="bg-neon/5 hover:bg-neon/10 transition text-slate-300">
                                    <td class="p-3"><?= $no_pengeluaran++; ?></td>
                                    <td class="p-3 font-bold text-neon">Infra: <?= htmlspecialchars($cd['nama']) ?> [Auto]</td>
                                    <td class="p-3"><span class="bg-cyan-500/20 text-neon px-2 py-1 rounded text-xs">Aset Klien</span></td>
                                    <td class="p-3 text-center font-mono">1 Set</td>
                                    <td class="p-3 text-right font-mono"><?= number_format($cd['total_modal'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-right font-bold text-red-400"><?= number_format($cd['total_modal'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-xs text-slate-400">Terbuat dari builder topologi klien</td>
                                    <td class="p-3 text-center">
                                        <a href="networks.php" class="inline-flex items-center gap-1.5 text-[10px] bg-slate-800 border border-slate-600/50 text-slate-300 px-2.5 py-1.5 rounded-lg hover:bg-slate-700 hover:text-white hover:border-slate-500 transition-all shadow-sm" title="Ubah via Topologi">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Cek Topologi
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php while ($rp = $stmt_pengeluaran->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-white/5 transition text-slate-300">
                                    <td class="p-3"><?= $no_pengeluaran++; ?></td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($rp['nama_item']) ?></td>
                                    <td class="p-3"><span class="bg-white/10 px-2 py-1 rounded text-xs"><?= htmlspecialchars($rp['kategori']) ?></span></td>
                                    <td class="p-3 text-center font-mono"><?= $rp['qty'] ?></td>
                                    <td class="p-3 text-right font-mono"><?= number_format($rp['harga_satuan'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-right font-bold text-red-400"><?= number_format($rp['total_harga'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-xs text-slate-400"><?= htmlspecialchars($rp['keterangan'] ?: '-') ?></td>
                                    <td class="p-3 text-center flex justify-center gap-2">
                                        <button onclick="openEditModal(this)"
                                            data-id="<?= $rp['id'] ?>" data-jenis="Pengeluaran" data-nama="<?= htmlspecialchars($rp['nama_item']) ?>"
                                            data-kategori="<?= htmlspecialchars($rp['kategori']) ?>" data-qty="<?= $rp['qty'] ?>"
                                            data-harga="<?= $rp['harga_satuan'] ?>" data-ket="<?= htmlspecialchars($rp['keterangan']) ?>"
                                            class="text-blue-400 hover:text-blue-300 transition" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <a href="?delete_id=<?= $rp['id'] ?>" onclick="return confirm('Hapus transaksi ini?')" class="text-red-500 hover:text-red-400 transition" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="bg-white/5 backdrop-blur-xl border border-green-500/20 rounded-3xl p-6 flex flex-col shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center mb-6 shrink-0 border-b border-white/10 pb-4">
                    <h3 class="text-xl font-bold text-green-400 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        PEMASUKAN
                    </h3>
                    <button onclick="toggleModal('modalTransaksi', 'Pemasukan')" class="bg-green-500/20 text-green-400 border border-green-500/40 px-4 py-2 rounded-xl text-sm font-bold hover:bg-green-500 hover:text-white transition whitespace-nowrap">
                        + Catat Manual
                    </button>
                </div>

                <div class="flex-1 overflow-x-auto overflow-y-auto custom-scroll pb-4">
                    <table class="w-full text-left border-collapse min-w-[1000px]">
                        <thead class="sticky top-0 bg-slate-900/95 backdrop-blur-md z-10">
                            <tr class="text-slate-400 text-sm">
                                <th class="p-3 font-semibold rounded-tl-lg whitespace-nowrap">No</th>
                                <th class="p-3 font-semibold whitespace-nowrap">Item</th>
                                <th class="p-3 font-semibold whitespace-nowrap">Kategori</th>
                                <th class="p-3 font-semibold text-center whitespace-nowrap">Qty</th>
                                <th class="p-3 font-semibold text-right whitespace-nowrap">Harga Satuan</th>
                                <th class="p-3 font-semibold text-right whitespace-nowrap">Total (Rp)</th>
                                <th class="p-3 font-semibold whitespace-nowrap min-w-[150px]">Keterangan</th>
                                <th class="p-3 font-semibold text-center rounded-tr-lg whitespace-nowrap">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10 text-sm">
                            <?php $no_pemasukan = 1; ?>

                            <?php foreach ($client_details as $cd): ?>
                                <tr class="bg-purple-900/10 hover:bg-purple-900/20 transition text-slate-300">
                                    <td class="p-3"><?= $no_pemasukan++; ?></td>
                                    <td class="p-3 font-bold text-purple-400">Registrasi: <?= htmlspecialchars($cd['nama']) ?> [Auto]</td>
                                    <td class="p-3"><span class="bg-purple-500/20 text-purple-400 px-2 py-1 rounded text-xs">Registrasi</span></td>
                                    <td class="p-3 text-center font-mono">1 Org</td>
                                    <td class="p-3 text-right font-mono">300.000</td>
                                    <td class="p-3 text-right font-bold text-green-400">300.000</td>
                                    <td class="p-3 text-xs text-slate-400">Otomatis dari build topologi</td>
                                    <td class="p-3 text-center">
                                        <a href="networks.php" class="inline-flex items-center gap-1.5 text-[10px] bg-slate-800 border border-slate-600/50 text-slate-300 px-2.5 py-1.5 rounded-lg hover:bg-slate-700 hover:text-white hover:border-slate-500 transition-all shadow-sm" title="Ubah via Topologi">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3 h-3">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                            Cek Topologi
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php while ($rm = $stmt_pemasukan->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="hover:bg-white/5 transition text-slate-300">
                                    <td class="p-3"><?= $no_pemasukan++; ?></td>
                                    <td class="p-3 font-bold text-white"><?= htmlspecialchars($rm['nama_item']) ?></td>
                                    <td class="p-3"><span class="bg-green-500/20 text-green-400 px-2 py-1 rounded text-xs"><?= htmlspecialchars($rm['kategori']) ?></span></td>
                                    <td class="p-3 text-center font-mono"><?= $rm['qty'] ?></td>
                                    <td class="p-3 text-right font-mono"><?= number_format($rm['harga_satuan'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-right font-bold text-green-400"><?= number_format($rm['total_harga'], 0, ',', '.') ?></td>
                                    <td class="p-3 text-xs text-slate-400"><?= htmlspecialchars($rm['keterangan'] ?: '-') ?></td>
                                    <td class="p-3 text-center flex justify-center gap-2">
                                        <button onclick="openEditModal(this)"
                                            data-id="<?= $rm['id'] ?>" data-jenis="Pemasukan" data-nama="<?= htmlspecialchars($rm['nama_item']) ?>"
                                            data-kategori="<?= htmlspecialchars($rm['kategori']) ?>" data-qty="<?= $rm['qty'] ?>"
                                            data-harga="<?= $rm['harga_satuan'] ?>" data-ket="<?= htmlspecialchars($rm['keterangan']) ?>"
                                            class="text-blue-400 hover:text-blue-300 transition" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <a href="?delete_id=<?= $rm['id'] ?>" onclick="return confirm('Hapus transaksi ini?')" class="text-red-500 hover:text-red-400 transition" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <div id="modalTransaksi" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalTransaksi')"></div>
        <div id="modalContainer" class="relative bg-slate-900/90 border shadow-[0_0_50px_rgba(0,0,0,0.5)] backdrop-blur-2xl rounded-[2rem] w-full max-w-lg p-8 z-10 transition-colors duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 id="modalTitle" class="text-2xl font-bold text-white tracking-tight">Catat Transaksi</h3>
                <button onclick="toggleModal('modalTransaksi')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="jenis_transaksi" id="inputJenisTransaksi">

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-white transition">Nama Item / Kegiatan</label>
                    <input type="text" name="nama_item" required placeholder="Contoh: Bayar Tagihan Biznet" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-white focus:ring-4 focus:ring-white/10 transition-all duration-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-white transition">Kategori</label>
                        <div class="relative">
                            <select name="kategori" onchange="checkKategori(this, 'kategoriManualBox', 'inputKategoriManual')" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-white focus:ring-4 focus:ring-white/10 transition-all duration-300 cursor-pointer">
                                <option value="" disabled selected>-- Pilih --</option>
                                <option value="Perangkat">Perangkat Hardware</option>
                                <option value="Kuota">Beli Kuota/Bandwidth</option>
                                <option value="Listrik">Biaya Listrik</option>
                                <option value="Aset">Aset Lainnya</option>
                                <option value="Lainnya">Lainnya (Ketik Manual)</option>
                            </select>
                        </div>
                    </div>
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-white transition">Qty (Jumlah)</label>
                        <input type="number" name="qty" value="1" min="1" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-white focus:ring-4 focus:ring-white/10 transition-all duration-300">
                    </div>
                </div>

                <div id="kategoriManualBox" class="hidden group">
                    <label class="block text-xs font-semibold text-yellow-400 uppercase tracking-widest mb-1 ml-1">Ketik Kategori Manual</label>
                    <input type="text" name="kategori_manual" id="inputKategoriManual" placeholder="Contoh: Transportasi" class="w-full bg-yellow-500/10 border border-yellow-500/30 rounded-2xl px-5 py-3 text-white outline-none focus:border-yellow-400 focus:ring-4 focus:ring-yellow-500/20 transition-all duration-300">
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-white transition">Harga Satuan (Rp)</label>
                    <input type="number" name="harga_satuan" required placeholder="150000" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-white focus:ring-4 focus:ring-white/10 transition-all duration-300">
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-white transition">Keterangan Opsional</label>
                    <textarea name="keterangan" rows="2" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-white focus:ring-4 focus:ring-white/10 transition-all duration-300"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="tambah_transaksi" id="btnSubmitModal" class="w-full bg-white text-darkbg font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-lg">
                        Simpan Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalEdit')"></div>
        <div class="relative bg-slate-900/90 border border-blue-500/50 shadow-[0_0_50px_rgba(59,130,246,0.3)] backdrop-blur-2xl rounded-[2rem] w-full max-w-lg p-8 z-10 transition-colors duration-300">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white tracking-tight">Edit Transaksi</h3>
                <button onclick="toggleModal('modalEdit')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit_id">

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-blue-400 transition">Nama Item / Kegiatan</label>
                    <input type="text" name="nama_item" id="edit_nama" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-blue-400 transition">Kategori</label>
                        <div class="relative">
                            <select name="kategori" id="edit_kategori" onchange="checkKategori(this, 'editKategoriBox', 'editKategoriManual')" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 cursor-pointer">
                                <option value="Perangkat">Perangkat Hardware</option>
                                <option value="Kuota">Beli Kuota/Bandwidth</option>
                                <option value="Listrik">Biaya Listrik</option>
                                <option value="Aset">Aset Lainnya</option>
                                <option value="Lainnya">Lainnya (Ketik Manual)</option>
                            </select>
                        </div>
                    </div>
                    <div class="group">
                        <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-blue-400 transition">Qty (Jumlah)</label>
                        <input type="number" name="qty" id="edit_qty" min="1" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                    </div>
                </div>

                <div id="editKategoriBox" class="hidden group">
                    <label class="block text-xs font-semibold text-yellow-400 uppercase tracking-widest mb-1 ml-1">Ketik Kategori Manual</label>
                    <input type="text" name="kategori_manual" id="editKategoriManual" class="w-full bg-yellow-500/10 border border-yellow-500/30 rounded-2xl px-5 py-3 text-white outline-none focus:border-yellow-400 focus:ring-4 focus:ring-yellow-500/20 transition-all duration-300">
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-blue-400 transition">Harga Satuan (Rp)</label>
                    <input type="number" name="harga_satuan" id="edit_harga" required class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300">
                </div>

                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-1 ml-1 group-focus-within:text-blue-400 transition">Keterangan Opsional</label>
                    <textarea name="keterangan" id="edit_ket" rows="2" class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-3 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300"></textarea>
                </div>

                <div class="pt-4">
                    <button type="submit" name="edit_transaksi" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(59,130,246,0.4)]">
                        Perbarui Transaksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(modalID, jenisTransaksi = null) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('hidden');

            if (jenisTransaksi && modalID === 'modalTransaksi') {
                document.getElementById('inputJenisTransaksi').value = jenisTransaksi;
                document.getElementById('modalTitle').innerText = 'Catat ' + jenisTransaksi;

                const container = document.getElementById('modalContainer');
                const btnSubmit = document.getElementById('btnSubmitModal');

                if (jenisTransaksi === 'Pengeluaran') {
                    container.className = 'relative bg-slate-900/90 border border-red-500/50 shadow-[0_0_50px_rgba(220,38,38,0.3)] backdrop-blur-2xl rounded-[2rem] w-full max-w-lg p-8 z-10 transition-colors duration-300';
                    btnSubmit.className = 'w-full bg-gradient-to-r from-red-600 to-red-400 text-white font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(220,38,38,0.4)]';
                } else {
                    container.className = 'relative bg-slate-900/90 border border-green-500/50 shadow-[0_0_50px_rgba(34,197,94,0.3)] backdrop-blur-2xl rounded-[2rem] w-full max-w-lg p-8 z-10 transition-colors duration-300';
                    btnSubmit.className = 'w-full bg-gradient-to-r from-green-600 to-green-400 text-white font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(34,197,94,0.4)]';
                }
            }
        }

        function checkKategori(select, boxId, inputId) {
            const box = document.getElementById(boxId);
            const input = document.getElementById(inputId);
            if (select.value === 'Lainnya') {
                box.classList.remove('hidden');
                input.setAttribute('required', 'required');
            } else {
                box.classList.add('hidden');
                input.removeAttribute('required');
            }
        }

        function openEditModal(btn) {
            document.getElementById('edit_id').value = btn.getAttribute('data-id');
            document.getElementById('edit_nama').value = btn.getAttribute('data-nama');
            document.getElementById('edit_qty').value = btn.getAttribute('data-qty');
            document.getElementById('edit_harga').value = btn.getAttribute('data-harga');
            document.getElementById('edit_ket').value = btn.getAttribute('data-ket');

            const kat = btn.getAttribute('data-kategori');
            const selectKat = document.getElementById('edit_kategori');

            // Cek apakah kategori ada di dropdown bawaan
            let exists = false;
            for (let i = 0; i < selectKat.options.length; i++) {
                if (selectKat.options[i].value === kat) {
                    exists = true;
                    break;
                }
            }

            if (exists) {
                selectKat.value = kat;
                document.getElementById('editKategoriBox').classList.add('hidden');
                document.getElementById('editKategoriManual').removeAttribute('required');
            } else {
                selectKat.value = 'Lainnya';
                document.getElementById('editKategoriBox').classList.remove('hidden');
                document.getElementById('editKategoriManual').value = kat;
                document.getElementById('editKategoriManual').setAttribute('required', 'required');
            }

            toggleModal('modalEdit');
        }
    </script>
</body>

</html>