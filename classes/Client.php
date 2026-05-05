<?php
// File: classes/Client.php

class Client {
    private $conn;
    private $table_name = "clients";

    // Properti yang sesuai dengan kolom tabel
    public $id;
    public $nama;
    public $alamat;
    public $no_whatsapp;
    public $tanggal_bergabung;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fungsi READ: Menampilkan semua data klien
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Fungsi CREATE: Menambah klien baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nama=:nama, alamat=:alamat, no_whatsapp=:no_whatsapp, tanggal_bergabung=:tanggal_bergabung, status=:status";
        
        $stmt = $this->conn->prepare($query);

        // Membersihkan data (Sanitasi)
        $this->nama = htmlspecialchars(strip_tags($this->nama));
        $this->alamat = htmlspecialchars(strip_tags($this->alamat));
        $this->no_whatsapp = htmlspecialchars(strip_tags($this->no_whatsapp));
        
        // Binding data
        $stmt->bindParam(":nama", $this->nama);
        $stmt->bindParam(":alamat", $this->alamat);
        $stmt->bindParam(":no_whatsapp", $this->no_whatsapp);
        $stmt->bindParam(":tanggal_bergabung", $this->tanggal_bergabung);
        $stmt->bindParam(":status", $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Fungsi UPDATE: Mengubah data klien
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nama=:nama, alamat=:alamat, no_whatsapp=:no_whatsapp, status=:status 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        // Sanitasi input agar aman
        $this->nama = htmlspecialchars(strip_tags($this->nama));
        $this->alamat = htmlspecialchars(strip_tags($this->alamat));
        $this->no_whatsapp = htmlspecialchars(strip_tags($this->no_whatsapp));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Binding data ke query
        $stmt->bindParam(":nama", $this->nama);
        $stmt->bindParam(":alamat", $this->alamat);
        $stmt->bindParam(":no_whatsapp", $this->no_whatsapp);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Fungsi DELETE: Menghapus klien
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>