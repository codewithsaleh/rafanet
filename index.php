<?php
// File: index.php
session_start();

// Panggil file konfigurasi dan class
require_once 'config/Database.php';
require_once 'classes/User.php';

// Jika admin sudah login, cegah akses ke halaman login dan lempar ke dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: views/dashboard.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$login_error = '';

// Cek apakah form disubmit
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Jalankan fungsi login dari Class User
    if($user->login($username, $password)) {
        header("Location: views/dashboard.php");
        exit;
    } else {
        $login_error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Mini WiFi Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-900 to-slate-800 min-h-screen flex items-center justify-center p-4">
    
    <div class="bg-white/10 backdrop-blur-lg border border-white/20 shadow-2xl rounded-2xl p-8 w-full max-w-md">
        <h2 class="text-3xl font-bold text-white text-center mb-6">Mini WiFi Login</h2>
        
        <?php if($login_error): ?>
            <div class="bg-red-500/50 border border-red-500 text-white px-4 py-2 rounded-lg mb-4 text-center">
                <?= $login_error; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-5">
            <div>
                <label class="block text-white/80 mb-1 text-sm">Username</label>
                <input type="text" name="username" required 
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" 
                       placeholder="Masukkan username">
            </div>
            <div>
                <label class="block text-white/80 mb-1 text-sm">Password</label>
                <input type="password" name="password" required 
                       class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-3 text-white placeholder-white/30 focus:outline-none focus:ring-2 focus:ring-blue-400 transition" 
                       placeholder="Masukkan password">
            </div>
            <button type="submit" 
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg transition duration-300 shadow-lg mt-4">
                Masuk ke Dashboard
            </button>
        </form>
    </div>

</body>
</html>