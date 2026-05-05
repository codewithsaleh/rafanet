<?php
// File: views/topology.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

require_once '../config/Database.php';
require_once '../classes/Network.php';
require_once '../classes/Asset.php';

$database = new Database();
$db = $database->getConnection();
$network = new Network($db);
$asset = new Asset($db);

$network_id = $_GET['id'] ?? null;
if (!$network_id) {
    header("Location: networks.php");
    exit;
}

// Info Jaringan Klien
$stmt_info = $db->prepare("SELECT n.*, c.nama as nama_klien FROM networks n JOIN clients c ON n.client_id = c.id WHERE n.id = ?");
$stmt_info->execute([$network_id]);
$info = $stmt_info->fetch(PDO::FETCH_ASSOC);

// Proses Form Tambah
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_device'])) {
    $target_net_id = isset($_POST['is_server']) ? 0 : $network_id;
    $network->addDevice($target_net_id, $_POST['asset_id'], $_POST['parent_id'], $_POST['device_port']);
    header("Location: topology.php?id=" . $network_id);
    exit;
}

// Proses Hapus
// Proses Hapus 1 Perangkat
if (isset($_GET['remove_device'])) {
    $network->removeDevice($_GET['remove_device']);
    header("Location: topology.php?id=" . $network_id);
    exit;
}

// Proses RESET Seluruh Topologi Klien
if (isset($_GET['action']) && $_GET['action'] == 'reset_client') {
    $network->resetClientTopology($network_id);
    header("Location: topology.php?id=" . $network_id);
    exit;
}

// Data Topologi Mentah
$server_topology_raw = $network->getServerTopology()->fetchAll(PDO::FETCH_ASSOC);
$client_topology_raw = $network->getTopology($network_id)->fetchAll(PDO::FETCH_ASSOC);

// Map Semua Node untuk mempermudah pencarian Parent
$all_nodes_map = [];
foreach (array_merge($server_topology_raw, $client_topology_raw) as $nd) {
    $all_nodes_map[$nd['device_id']] = $nd;
}

// Aset Tersedia
$available_server_assets = $network->getAvailableAssets("0,1,2")->fetchAll(PDO::FETCH_ASSOC);
$available_client_assets = $network->getAvailableAssets("0,3")->fetchAll(PDO::FETCH_ASSOC);

// =================================================================================
// 1. FUNGSI PEMBENTUK TREE DASAR
// =================================================================================
function buildTree(array $elements, $parentId = null) {
    $branch = array();
    foreach ($elements as $element) {
        if ($element['parent_device_id'] == $parentId) {
            $children = buildTree($elements, $element['device_id']);
            if ($children) $element['children'] = $children;
            $branch[] = $element;
        }
    }
    return $branch;
}

// =================================================================================
// 2. LOGIKA DISTRIBUSI ZONA KIRI & KANAN (Seperti Sebelumnya)
// =================================================================================
$server_topology = [];
$client_topology = [];
foreach ($server_topology_raw as $st) $server_topology[] = $st; // Server tetap utuh di kiri
foreach ($client_topology_raw as $ct) $client_topology[] = $ct; // Klien tetap utuh di kanan

$server_tree = buildTree($server_topology_raw);

$client_tree = [];
$client_device_ids = array_column($client_topology_raw, 'device_id');
$client_roots = [];
foreach ($client_topology_raw as $item) {
    if (!in_array($item['parent_device_id'], $client_device_ids)) {
        $client_roots[] = $item['parent_device_id'];
    }
}
foreach (array_unique($client_roots) as $root_id) {
    $client_tree = array_merge($client_tree, buildTree($client_topology_raw, $root_id));
}

// =================================================================================
// 3. LOGIKA BARU: BACKTRACKING UNTUK FULL PATH TOPOLOGY (Horizontal)
// =================================================================================
// Tujuannya: Hanya ambil node Server yang jalurnya berakhir di jaringan klien ini.
$active_server_node_ids = [];
$current_parents = [];

// Mulai dari parent perangkat klien (colokan pertama di server)
foreach ($client_topology_raw as $c) {
    if ($c['parent_device_id']) $current_parents[] = $c['parent_device_id'];
}

// Runut ke atas sampai ke Modem (root)
while (!empty($current_parents)) {
    $next_parents = [];
    foreach ($current_parents as $pid) {
        if (!in_array($pid, $active_server_node_ids)) {
            $active_server_node_ids[] = $pid; // Simpan ID Server yang dipakai
            // Cari parent-nya lagi
            if (isset($all_nodes_map[$pid]) && $all_nodes_map[$pid]['parent_device_id']) {
                $next_parents[] = $all_nodes_map[$pid]['parent_device_id'];
            }
        }
    }
    $current_parents = $next_parents;
}

// Filter Server Topology
$filtered_server_topology = [];
foreach ($server_topology_raw as $s) {
    if (in_array($s['device_id'], $active_server_node_ids)) {
        $filtered_server_topology[] = $s;
    }
}

// Gabungkan Server (yang sudah difilter) dengan Client, lalu bangun satu pohon utuh
$full_path_raw = array_merge($filtered_server_topology, $client_topology_raw);
$full_tree = buildTree($full_path_raw);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Topology Manager - WIFI.NET</title>
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
            -webkit-appearance: none;
            -moz-appearance: none;
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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Animasi garis horizontal */
        .tree-line {
            background: linear-gradient(90deg, #334155 50%, transparent 50%);
            background-size: 8px 2px;
            height: 2px;
        }
    </style>
</head>

<body class="bg-darkbg text-slate-300 font-sans h-screen flex overflow-hidden relative">

    <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-cyan-600/10 rounded-full blur-[120px] pointer-events-none fixed"></div>

    <main class="flex-1 p-8 flex flex-col h-full relative z-10 overflow-y-auto">
        <header class="flex justify-between items-center mb-6 shrink-0">
            <div>
                <h2 class="text-3xl font-bold text-white tracking-tight">Manajer Topologi</h2>
                <p class="text-slate-400">Klien: <span class="text-neon font-bold"><?= $info['nama_klien'] ?></span></p>
            </div>
            <a href="networks.php" class="px-4 py-2 bg-white/5 border border-white/10 rounded-xl hover:bg-white/10 transition flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                </svg>
                Kembali
            </a>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8 h-[450px] shrink-0">
            <section class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-6 flex flex-col shadow-2xl relative overflow-hidden h-full">
                <div class="absolute top-0 right-0 p-4 opacity-10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3" /></svg>
                </div>
                <div class="flex justify-between items-center mb-6 shrink-0 relative z-10">
                    <h3 class="text-xl font-bold text-white flex items-center gap-2 italic">
                        <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span> INFRASTRUKTUR SERVER
                    </h3>
                    <button onclick="toggleModal('modalServer')" class="bg-blue-500/20 text-blue-400 border border-blue-500/40 px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-blue-500 hover:text-white transition">+ Edit Backbone</button>
                </div>
                <div class="flex-1 overflow-y-auto no-scrollbar space-y-4">
                    <?php if (empty($server_tree)) echo "<p class='text-center text-slate-600 mt-10'>Server belum dikonfigurasi.</p>"; ?>
                    <?php renderTreeUI($server_tree, "server"); ?>
                </div>
            </section>

            <section class="bg-white/5 backdrop-blur-xl border border-neon/10 rounded-3xl p-6 flex flex-col shadow-2xl relative overflow-hidden h-full">
                <div class="absolute top-0 right-0 p-4 opacity-10 text-neon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-32 h-32"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                </div>
                <div class="flex justify-between items-center mb-6 shrink-0 relative z-10">
                    <h3 class="text-xl font-bold text-neon flex items-center gap-2 italic">
                        <span class="w-2 h-2 rounded-full bg-neon animate-pulse"></span> DISTRIBUSI KLIEN
                    </h3>
                    <div class="flex items-center gap-3">
                        <a href="?id=<?= $network_id ?>&action=reset_client" onclick="return confirm('Peringatan: Ini akan mengembalikan semua perangkat klien ke inventaris awal dan mereset nilai modal. Lanjutkan?')" class="bg-red-500/10 text-red-400 border border-red-500/30 px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-red-500 hover:text-white transition">
                            Reset Topologi
                        </a>
                        
                        <button onclick="toggleModal('modalClient')" class="bg-neon/20 text-neon border border-neon/40 px-4 py-1.5 rounded-lg text-sm font-bold hover:bg-neon hover:text-darkbg transition">
                            + Pasang Perangkat
                        </button>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto no-scrollbar space-y-4">
                    <?php if (empty($client_tree)) echo "<p class='text-center text-slate-600 mt-10'>Belum ada perangkat klien.</p>"; ?>
                    <?php renderTreeUI($client_tree, "client"); ?>
                </div>
            </section>
        </div>

        <section class="bg-slate-900/50 border border-white/10 rounded-3xl p-8 shadow-2xl shrink-0 mb-10">
            <div class="mb-6">
                <h3 class="text-xl font-bold text-white tracking-widest uppercase flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-purple-400"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    Peta Sambungan Langsung (Active Path)
                </h3>
                <p class="text-sm text-slate-400">Menampilkan jalur murni dari sumber awal ke router klien.</p>
            </div>
            
            <div class="overflow-x-auto pb-6 no-scrollbar">
                <div class="min-w-max pr-10">
                    <?php 
                    if (empty($full_tree)) {
                        echo "<p class='text-slate-600'>Sambungkan perangkat klien ke server untuk melihat jalur aktif.</p>";
                    } else {
                        renderHorizontalTreeUI($full_tree); 
                    }
                    ?>
                </div>
            </div>
        </section>
    </main>

    <div id="modalServer" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalServer')"></div>
        <div class="relative bg-slate-900/90 border border-blue-500/30 shadow-[0_0_50px_rgba(0,0,0,0.5)] backdrop-blur-2xl rounded-[2rem] w-full max-w-md p-8 z-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 text-blue-400"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3" /></svg>
                    Aset Server
                </h3>
                <button onclick="toggleModal('modalServer')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="" method="POST" class="space-y-5">
                <input type="hidden" name="is_server" value="1">
                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Pilih Perangkat L1/L2/L0</label>
                    <div class="relative">
                        <select name="asset_id" onchange="handlePortUI(this, 'server_port_box')" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 cursor-pointer">
                            <option value="" disabled selected>-- Pilih Aset --</option>
                            <?php foreach ($available_server_assets as $a): ?>
                                <option value="<?= $a['id'] ?>" data-jenis="<?= $a['jenis'] ?>" data-used="<?= htmlspecialchars($a['used_ports'] ?? '') ?>">
                                    [L-<?= $a['layer'] ?>] <?= $a['nama'] ?> (<?= $a['merek'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="server_port_box" class="hidden group">
                    <label class="block text-xs font-bold text-yellow-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-yellow-300 transition">Pilih Port Aktif</label>
                    <div class="relative">
                        <select name="device_port" id="server_port_select" class="w-full bg-yellow-500/10 border border-yellow-500/30 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-yellow-400 focus:ring-4 focus:ring-yellow-500/20 transition-all duration-300 cursor-pointer"></select>
                    </div>
                </div>
                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-blue-400 transition">Sambungkan Ke</label>
                    <div class="relative">
                        <select name="parent_id" class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all duration-300 cursor-pointer">
                            <option value="">-- Titik Awal (Modem) --</option>
                            <?php foreach ($server_topology_raw as $s): ?>
                                <option value="<?= $s['device_id'] ?>"><?= $s['nama'] ?> <?= $s['device_port'] ? "({$s['device_port']})" : "" ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" name="add_device" class="w-full bg-gradient-to-r from-blue-600 to-blue-400 text-white font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(59,130,246,0.3)]">Simpan ke Backbone</button>
                </div>
            </form>
        </div>
    </div>

    <div id="modalClient" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-darkbg/90 backdrop-blur-md" onclick="toggleModal('modalClient')"></div>
        <div class="relative bg-slate-900/90 border border-neon/30 shadow-[0_0_50px_rgba(0,0,0,0.5)] backdrop-blur-2xl rounded-[2rem] w-full max-w-md p-8 z-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-7 h-7 text-neon"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                    Alat Klien
                </h3>
                <button onclick="toggleModal('modalClient')" class="text-slate-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form action="" method="POST" class="space-y-5">
                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Pilih Perangkat L3/L0</label>
                    <div class="relative">
                        <select name="asset_id" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300 cursor-pointer">
                            <option value="" disabled selected>-- Pilih Aset --</option>
                            <?php foreach ($available_client_assets as $a): ?>
                                <option value="<?= $a['id'] ?>">[L-<?= $a['layer'] ?>] <?= $a['nama'] ?> (Rp<?= number_format($a['harga'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="group">
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-widest mb-2 ml-1 group-focus-within:text-neon transition">Ambil Sumber Dari</label>
                    <div class="relative">
                        <select name="parent_id" required class="w-full bg-slate-800 border border-white/10 rounded-2xl px-5 py-3.5 text-white outline-none focus:border-neon focus:ring-4 focus:ring-neon/10 transition-all duration-300 cursor-pointer">
                            <option value="" disabled selected>-- Pilih Sumber Sambungan --</option>
                            <optgroup label="DARI SERVER (BACKBONE)" class="text-blue-400 font-bold bg-slate-900">
                                <?php foreach ($server_topology_raw as $s): ?>
                                    <option value="<?= $s['device_id'] ?>" class="text-white font-normal"><?= $s['nama'] ?> <?= $s['device_port'] ? "({$s['device_port']})" : "" ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="DARI KLIEN (ESTAFET)" class="text-green-400 font-bold bg-slate-900">
                                <?php foreach ($client_topology_raw as $c): ?>
                                    <option value="<?= $c['device_id'] ?>" class="text-white font-normal"><?= $c['nama'] ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="pt-4">
                    <button type="submit" name="add_device" class="w-full bg-gradient-to-r from-neon to-green-400 text-darkbg font-black uppercase tracking-widest py-4 rounded-2xl hover:scale-[1.02] active:scale-95 transition-all duration-300 shadow-[0_0_20px_rgba(0,242,254,0.3)]">Pasang ke Klien</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleModal(modalID) { document.getElementById(modalID).classList.toggle('hidden'); }
        function handlePortUI(select, boxId) {
            const box = document.getElementById(boxId);
            const portSelect = box.querySelector('select');
            const selectedOption = select.options[select.selectedIndex];
            const jenis = selectedOption.getAttribute('data-jenis');
            const usedStr = selectedOption.getAttribute('data-used') || '';
            const usedPorts = usedStr.split(',');

            portSelect.innerHTML = '<option value="" disabled selected>-- Pilih Port --</option>';

            if (jenis === 'Mikrotik') {
                ['Port 2', 'Port 3', 'Port 4', 'Port 5'].forEach(p => {
                    if (!usedPorts.includes(p)) portSelect.innerHTML += `<option value="${p}">${p}</option>`;
                });
                portSelect.setAttribute('required', 'required'); box.classList.remove('hidden');
            } else if (jenis === 'Switch') {
                ['Port LAN 2', 'Port LAN 3', 'Port LAN 4', 'Port FO Side A', 'Port FO Side B'].forEach(p => {
                    if (!usedPorts.includes(p)) portSelect.innerHTML += `<option value="${p}">${p}</option>`;
                });
                portSelect.setAttribute('required', 'required'); box.classList.remove('hidden');
            } else {
                portSelect.removeAttribute('required'); box.classList.add('hidden');
            }
        }
    </script>
</body>
</html>

<?php
// =================================================================================
// FUNGSI RENDER UI UNTUK TOPOLOGI (VERTIKAL - GRID)
// =================================================================================
function renderTreeUI($tree, $type, $level = 0) {
    foreach ($tree as $node) {
        $margin = $level * 30;
        $is_server = ($type === "server");
        $accent = $is_server ? "blue-500" : "green-500";
        $icon = getDeviceIcon($node['jenis'], $accent);

        echo "<div class='relative' style='margin-left: {$margin}px'>";
        if ($level > 0) echo "<div class='absolute -left-4 top-5 w-4 h-0.5 bg-white/10'></div>";

        echo "
        <div class='group flex items-center gap-4 bg-white/5 border border-white/10 p-3 rounded-2xl hover:border-{$accent}/50 transition mb-2'>
            <div class='p-2 bg-darkbg rounded-xl shadow-inner'>$icon</div>
            <div class='flex-1'>
                <h4 class='text-sm font-bold text-white'>{$node['nama']}</h4>
                <p class='text-[10px] text-slate-500 uppercase'>{$node['merek']} &bull; L-{$node['layer']}</p>
                " . ($node['device_port'] ? "<span class='text-[9px] bg-{$accent}/20 text-{$accent} px-2 py-0.5 rounded-md font-bold'>{$node['device_port']}</span>" : "") . "
            </div>
            <a href='?id={$_GET['id']}&remove_device={$node['device_id']}' onclick='return confirm(\"Lepas perangkat ini?\")' class='opacity-0 group-hover:opacity-100 p-2 text-red-500 hover:bg-red-500/10 rounded-lg transition'>
                <svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='1.5' stroke='currentColor' class='w-4 h-4'><path stroke-linecap='round' stroke-linejoin='round' d='M6 18L18 6M6 6l12 12' /></svg>
            </a>
        </div>";

        if (isset($node['children'])) renderTreeUI($node['children'], $type, $level + 1);
        echo "</div>";
    }
}

// =================================================================================
// FUNGSI RENDER UI BARU: FULL PATH TOPOLOGY (HORIZONTAL)
// =================================================================================
// =================================================================================
// FUNGSI RENDER UI BARU: FULL PATH TOPOLOGY (HORIZONTAL)
// =================================================================================
function renderHorizontalTreeUI($tree) {
    if (empty($tree)) return;
    echo '<div class="flex flex-col gap-6">';
    
    foreach ($tree as $node) {
        // PERBAIKAN LOGIKA TANPA in_array()
        $is_client = ($node['layer'] == 3 || ($node['layer'] != 1 && $node['layer'] != 2 && empty($node['parent_device_id'])));
        
        $accent = $is_client ? "neon" : "blue-400";
        $border = $is_client ? "neon/50" : "blue-500/30";
        $bg = $is_client ? "bg-cyan-900/20" : "bg-slate-800";
        
        $icon = getDeviceIcon($node['jenis'], $accent);

        echo '<div class="flex flex-row items-center relative">';
        
        // Kotak Node (Alat)
        echo "
        <div class='w-56 shrink-0 z-10 flex items-center gap-3 {$bg} border border-{$border} p-3 rounded-2xl shadow-lg'>
            <div class='p-2 bg-darkbg rounded-xl shadow-inner text-{$accent}'>$icon</div>
            <div class='flex-1 overflow-hidden'>
                <h4 class='text-sm font-bold text-white truncate'>{$node['nama']}</h4>
                <p class='text-[10px] text-slate-400 uppercase truncate'>L-{$node['layer']} &bull; {$node['jenis']}</p>
                " . ($node['device_port'] ? "<span class='inline-block mt-1 text-[9px] bg-{$accent}/10 text-{$accent} border border-{$accent}/30 px-1.5 py-0.5 rounded font-bold'>{$node['device_port']}</span>" : "") . "
            </div>
        </div>";

        // Render Anak-anak (Children) menyamping
        if (!empty($node['children'])) {
            echo '<div class="flex flex-row items-center">';
            // Garis penghubung lurus
            echo '<div class="w-8 tree-line"></div>'; 
            // Kontainer Vertikal untuk anak-anak
            echo '<div class="flex flex-col gap-4 border-l-2 border-slate-600 pl-8 py-2 relative">';
            
            foreach ($node['children'] as $child) {
                echo '<div class="relative flex items-center">';
                // Garis cabang ke anak
                echo '<div class="absolute -left-8 w-8 tree-line"></div>';
                
                // REKURSIF HORIZONTAL
                renderHorizontalTreeUI([$child]); 
                
                echo '</div>';
            }
            echo '</div></div>';
        }
        echo '</div>';
    }
    echo '</div>';
}

// Fungsi Helper Ikon SVG Murni
function getDeviceIcon($jenis, $color) {
    if ($jenis == 'Modem') return '<svg class="w-5 h-5 text-'.$color.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>';
    if ($jenis == 'Mikrotik') return '<svg class="w-5 h-5 text-'.$color.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path></svg>';
    if ($jenis == 'Cable' || $jenis == 'FO') return '<svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>';
    return '<svg class="w-5 h-5 text-'.$color.'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.571 15.355-5.571 21.213 0"></path></svg>';
}
?>