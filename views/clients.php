<?php
// File: views/clients.php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/Database.php';
require_once '../classes/Client.php';

$database = new Database();
$db = $database->getConnection();
$client = new Client($db);

$message = '';

// Proses Tambah Data
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_klien'])) {
    $client->nama = $_POST['nama'];
    $client->alamat = $_POST['alamat'];
    $client->no_whatsapp = $_POST['no_whatsapp'];
    $client->tanggal_bergabung = date('Y-m-d'); 
    $client->status = $_POST['status'];

    if($client->create()) {
        $message = "<div class='bg-green-500/20 border border-green-500 text-green-400 p-3 rounded-xl mb-4'>Berhasil menambahkan klien baru!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal menambahkan klien.</div>";
    }
}

// Proses Edit (Update) Data
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_klien'])) {
    $client->id = $_POST['id'];
    $client->nama = $_POST['nama'];
    $client->alamat = $_POST['alamat'];
    $client->no_whatsapp = $_POST['no_whatsapp'];
    $client->status = $_POST['status'];

    if($client->update()) {
        $message = "<div class='bg-blue-500/20 border border-blue-500 text-blue-400 p-3 rounded-xl mb-4'>Data klien berhasil diperbarui!</div>";
    } else {
        $message = "<div class='bg-red-500/20 border border-red-500 text-red-400 p-3 rounded-xl mb-4'>Gagal memperbarui data klien.</div>";
    }
}

// Proses Hapus Data
if(isset($_GET['delete_id'])) {
    $client->id = $_GET['delete_id'];
    if($client->delete()) {
        header("Location: clients.php"); 
        exit;
    }
}

// Ambil semua data
$stmt = $client->read();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Klien - Mini WiFi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { neon: '#00f2fe', darkbg: '#0f172a' } }
            }
        }
    </script>
</head>
<body class="bg-darkbg text-slate-300 font-sans antialiased min-h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-cyan-600/20 rounded-full blur-[120px] pointer-events-none"></div>

    <?php require_once 'templates/sidebar.php'; ?>

    <main class="flex-1 p-8 overflow-y-auto relative z-10">
        <header class="flex justify-between items-center mb-10">
            <div>
                <h2 class="text-3xl font-bold text-white mb-1">Manajemen Klien</h2>
                <p class="text-slate-400">Kelola data identitas pelanggan WiFi Anda.</p>
            </div>
            
            <button onclick="toggleModal('modalTambah')" class="bg-gradient-to-r from-neon to-blue-500 hover:from-cyan-400 hover:to-blue-400 text-darkbg font-bold py-2 px-6 rounded-xl shadow-[0_0_15px_rgba(0,242,254,0.3)] transition duration-300 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Klien
            </button>
        </header>

        <?= $message; ?>

        <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white/5 border-b border-white/10 text-slate-300">
                        <th class="p-4 font-semibold">Nama Klien</th>
                        <th class="p-4 font-semibold">No. WhatsApp</th>
                        <th class="p-4 font-semibold">Tgl Bergabung</th>
                        <th class="p-4 font-semibold">Status</th>
                        <th class="p-4 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <?php if($stmt->rowCount() > 0): ?>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="hover:bg-white/5 transition">
                            <td class="p-4 font-medium text-white"><?= htmlspecialchars($row['nama']); ?></td>
                            <td class="p-4 text-neon"><?= htmlspecialchars($row['no_whatsapp']); ?></td>
                            <td class="p-4"><?= date('d M Y', strtotime($row['tanggal_bergabung'])); ?></td>
                            <td class="p-4">
                                <?php if($row['status'] == 'aktif'): ?>
                                    <span class="px-3 py-1 bg-green-500/20 text-green-400 rounded-full text-xs border border-green-500/30">Aktif</span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-red-500/20 text-red-400 rounded-full text-xs border border-red-500/30">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-center flex justify-center gap-3">
                                
                                <button onclick="openEditModal(this)" 
                                        data-id="<?= $row['id']; ?>"
                                        data-nama="<?= htmlspecialchars($row['nama']); ?>"
                                        data-nowa="<?= htmlspecialchars($row['no_whatsapp']); ?>"
                                        data-alamat="<?= htmlspecialchars($row['alamat']); ?>"
                                        data-status="<?= $row['status']; ?>"
                                        class="text-neon hover:text-cyan-300 transition" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                </button>

                                <a href="clients.php?delete_id=<?= $row['id']; ?>" onclick="return confirm('Yakin ingin menghapus klien ini? Semua data jaringannya akan ikut terhapus.')" class="text-red-400 hover:text-red-300 transition" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-500">Belum ada data klien yang terdaftar.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="modalTambah" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/80 backdrop-blur-sm" onclick="toggleModal('modalTambah')"></div>
        <div class="relative bg-slate-900 border border-white/20 shadow-2xl rounded-2xl w-full max-w-md p-6 z-10 transform transition-all">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-white">Tambah Klien Baru</h3>
                <button onclick="toggleModal('modalTambah')" class="text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="clients.php" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Alamat Pemasangan</label>
                    <textarea name="alamat" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon"></textarea>
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">No. WhatsApp</label>
                    <input type="number" name="no_whatsapp" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon" placeholder="08xxxxxxxx">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Status</label>
                    <select name="status" class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" name="tambah_klien" class="w-full bg-neon text-darkbg font-bold py-2 rounded-lg hover:bg-cyan-400 transition shadow-[0_0_10px_rgba(0,242,254,0.3)]">
                        Simpan Data Klien
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalEdit" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/80 backdrop-blur-sm" onclick="toggleModal('modalEdit')"></div>
        <div class="relative bg-slate-900 border border-white/20 shadow-2xl rounded-2xl w-full max-w-md p-6 z-10 transform transition-all">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-xl font-bold text-white">Edit Data Klien</h3>
                <button onclick="toggleModal('modalEdit')" class="text-slate-400 hover:text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="clients.php" method="POST" class="space-y-4">
                <input type="hidden" name="id" id="edit_id">
                
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama" id="edit_nama" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Alamat Pemasangan</label>
                    <textarea name="alamat" id="edit_alamat" rows="2" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon"></textarea>
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">No. WhatsApp</label>
                    <input type="number" name="no_whatsapp" id="edit_nowa" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon">
                </div>
                <div>
                    <label class="block text-sm text-slate-300 mb-1">Status</label>
                    <select name="status" id="edit_status" class="w-full bg-slate-800 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-neon">
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>
                <div class="pt-2">
                    <button type="submit" name="edit_klien" class="w-full bg-blue-500 text-white font-bold py-2 rounded-lg hover:bg-blue-400 transition shadow-[0_0_10px_rgba(59,130,246,0.3)]">
                        Perbarui Data
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fungsi dasar untuk buka-tutup modal
        function toggleModal(modalID) {
            const modal = document.getElementById(modalID);
            modal.classList.toggle('hidden');
        }

        // Fungsi khusus untuk menangkap data dari tombol Edit dan memasukkannya ke Modal
        function openEditModal(buttonElement) {
            // Mengambil nilai dari atribut data-*
            const id = buttonElement.getAttribute('data-id');
            const nama = buttonElement.getAttribute('data-nama');
            const nowa = buttonElement.getAttribute('data-nowa');
            const alamat = buttonElement.getAttribute('data-alamat');
            const status = buttonElement.getAttribute('data-status');

            // Memasukkan nilai ke dalam input form di modal edit
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_nowa').value = nowa;
            document.getElementById('edit_alamat').value = alamat;
            document.getElementById('edit_status').value = status;

            // Tampilkan modal edit
            toggleModal('modalEdit');
        }
    </script>
<?php require_once 'templates/footer.php'; ?>