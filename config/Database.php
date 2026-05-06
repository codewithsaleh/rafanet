<?php

class Database {
    // Properti di-set private (Enkapsulasi) agar hanya bisa diakses dari dalam class ini
    private $host = "localhost";
    private $db_name = "db_miniwifi"; // GANTI dengan nama database di Laragon
    private $username = "root"; // Default username Laragon
    private $password = "";     // Default password Laragon kosong
    public $conn;

    // Method untuk mendapatkan koneksi database
    public function getConnection() {
        $this->conn = null;

        try {
            // Membuat instance PDO baru
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            
            // Set mode error PDO menjadi Exception agar error mudah dilacak
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set default fetch mode ke Associative Array agar data yang ditarik berbentuk array asosiatif (bukan index angka)
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
        } catch(PDOException $exception) {
            // Menangkap error jika koneksi gagal tanpa membocorkan kredensial database
            echo "Koneksi Database Gagal: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>