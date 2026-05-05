<?php
// File: classes/Transaction.php

class Transaction {
    private $conn;
    private $table_name = "transactions";

    // Properti untuk transaksi manual
    public $id;
    public $jenis_transaksi;
    public $kategori;
    public $nama_item;
    public $qty;
    public $harga_satuan;
    public $total_harga;
    public $keterangan;
    public $tanggal;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ========================================================================
    // 1. KALKULASI OTOMATIS (DARI TOPOLOGI)
    // ========================================================================

    // Hitung Total Modal Server (Semua aset di network_id IS NULL)
    public function getServerInfrastructureTotal() {
        $query = "SELECT SUM(a.harga) as total 
                  FROM network_devices nd 
                  JOIN assets a ON nd.asset_id = a.id 
                  WHERE nd.network_id IS NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // Hitung Total Modal Klien (Semua aset yang terhubung ke network_id klien)
    public function getClientInfrastructureTotal() {
        $query = "SELECT SUM(a.harga) as total 
                  FROM network_devices nd 
                  JOIN assets a ON nd.asset_id = a.id 
                  WHERE nd.network_id IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // Hitung Total Pemasukan Registrasi (Klien yang sudah punya topologi x 300.000)
    public function getRegistrationIncomeTotal() {
        $query = "SELECT COUNT(DISTINCT network_id) as total_klien 
                  FROM network_devices 
                  WHERE network_id IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_klien = $row['total_klien'] ?? 0;
        return $total_klien * 300000;
    }

    // Ambil jumlah klien untuk informasi di UI
    public function getCountClientWithTopology() {
        $query = "SELECT COUNT(DISTINCT network_id) as jml FROM network_devices WHERE network_id IS NOT NULL";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['jml'] ?? 0;
    }

    // ========================================================================
    // 2. CRUD TRANSAKSI MANUAL
    // ========================================================================

    // Ambil data transaksi manual berdasarkan jenis (Pemasukan/Pengeluaran)
    public function readManual($jenis) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE jenis_transaksi = ? 
                  ORDER BY tanggal DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $jenis);
        $stmt->execute();
        return $stmt;
    }

    // Simpan transaksi manual
    public function createManual() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET jenis_transaksi=:jenis, kategori=:kategori, nama_item=:nama, 
                      qty=:qty, harga_satuan=:harga, total_harga=:total, keterangan=:ket";
        
        $stmt = $this->conn->prepare($query);

        $this->total_harga = $this->qty * $this->harga_satuan;

        $stmt->bindParam(":jenis", $this->jenis_transaksi);
        $stmt->bindParam(":kategori", $this->kategori);
        $stmt->bindParam(":nama", $this->nama_item);
        $stmt->bindParam(":qty", $this->qty);
        $stmt->bindParam(":harga", $this->harga_satuan);
        $stmt->bindParam(":total", $this->total_harga);
        $stmt->bindParam(":ket", $this->keterangan);

        return $stmt->execute();
    }

    // Hapus transaksi manual
    public function deleteManual() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    // Total Nominal per Jenis (Untuk ringkasan di Header)
    public function getTotalManual($jenis) {
        $query = "SELECT SUM(total_harga) as total FROM " . $this->table_name . " WHERE jenis_transaksi = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $jenis);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    // Update transaksi manual
    public function updateManual() {
        $query = "UPDATE " . $this->table_name . " 
                  SET kategori=:kategori, nama_item=:nama, 
                      qty=:qty, harga_satuan=:harga, total_harga=:total, keterangan=:ket
                  WHERE id=:id";
        
        $stmt = $this->conn->prepare($query);
        $this->total_harga = $this->qty * $this->harga_satuan;

        $stmt->bindParam(":kategori", $this->kategori);
        $stmt->bindParam(":nama", $this->nama_item);
        $stmt->bindParam(":qty", $this->qty);
        $stmt->bindParam(":harga", $this->harga_satuan);
        $stmt->bindParam(":total", $this->total_harga);
        $stmt->bindParam(":ket", $this->keterangan);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }
}