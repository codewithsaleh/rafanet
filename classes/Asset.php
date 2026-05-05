<?php
// File: classes/Asset.php

class Asset {
    private $conn;
    private $table_name = "assets";

    public $id;
    public $merek;
    public $nama;
    public $jenis;
    public $detail_jenis;
    public $layer;
    public $mac_address;
    public $harga;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Menampilkan semua aset
    public function read() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Menambah aset baru
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET merek=:merek, nama=:nama, jenis=:jenis, detail_jenis=:detail_jenis, layer=:layer, mac_address=:mac_address, harga=:harga";
        
        $stmt = $this->conn->prepare($query);

        // Menggunakan bindValue karena kita mengoper nilai hasil dari fungsi htmlspecialchars
        $stmt->bindValue(":merek", htmlspecialchars(strip_tags($this->merek)));
        $stmt->bindValue(":nama", htmlspecialchars(strip_tags($this->nama)));
        $stmt->bindValue(":jenis", htmlspecialchars(strip_tags($this->jenis)));
        $stmt->bindValue(":detail_jenis", htmlspecialchars(strip_tags($this->detail_jenis ?? '')));
        $stmt->bindValue(":layer", htmlspecialchars(strip_tags($this->layer)));
        $stmt->bindValue(":mac_address", htmlspecialchars(strip_tags($this->mac_address ?? '')));
        $stmt->bindValue(":harga", htmlspecialchars(strip_tags($this->harga)));

        return $stmt->execute();
    }

    // Mengubah data aset
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET merek=:merek, nama=:nama, jenis=:jenis, detail_jenis=:detail_jenis, layer=:layer, mac_address=:mac_address, harga=:harga 
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindValue(":merek", htmlspecialchars(strip_tags($this->merek)));
        $stmt->bindValue(":nama", htmlspecialchars(strip_tags($this->nama)));
        $stmt->bindValue(":jenis", htmlspecialchars(strip_tags($this->jenis)));
        $stmt->bindValue(":detail_jenis", htmlspecialchars(strip_tags($this->detail_jenis ?? '')));
        $stmt->bindValue(":layer", htmlspecialchars(strip_tags($this->layer)));
        $stmt->bindValue(":mac_address", htmlspecialchars(strip_tags($this->mac_address ?? '')));
        $stmt->bindValue(":harga", htmlspecialchars(strip_tags($this->harga)));
        $stmt->bindValue(":id", htmlspecialchars(strip_tags($this->id)));

        return $stmt->execute();
    }

    // Menghapus data aset
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }
}
?>