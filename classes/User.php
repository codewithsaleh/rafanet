<?php
// File: classes/User.php

class User {
    private $conn;
    private $table_name = "users";

    // Constructor menerima koneksi database dari luar (Dependency Injection)
    public function __construct($db) {
        $this->conn = $db;
    }

    // Method untuk proses login
    public function login($username, $password) {
        // Gunakan prepared statement untuk mencegah SQL Injection
        $query = "SELECT id, username, password, nama_lengkap, role FROM " . $this->table_name . " WHERE username = :username LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        // Cek apakah username ditemukan
        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verifikasi password (mencocokkan input dengan hash di database)
            if(password_verify($password, $row['password'])) {
                // Password cocok, simpan data ke session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['nama'] = $row['nama_lengkap'];
                return true;
            }
        }
        return false;
    }
}
?>