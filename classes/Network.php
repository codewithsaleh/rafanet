<?php
// File: classes/Network.php

class Network {
    private $conn;
    private $table_name = "networks";
    private $table_devices = "network_devices";

    public $id;
    public $client_id;
    public $ip_address;
    public $ssid;
    public $password;
    public $bandwidth;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. READ: Menghitung TOTAL MODAL HANYA DARI LAYER 3 (a.layer = 3)
    public function read() {
        $query = "SELECT n.*, c.nama as nama_klien,
                         (SELECT COALESCE(SUM(a.harga), 0) 
                          FROM " . $this->table_devices . " nd 
                          JOIN assets a ON nd.asset_id = a.id 
                          WHERE nd.network_id = n.id AND a.layer = 3) as total_modal
                  FROM " . $this->table_name . " n
                  LEFT JOIN clients c ON n.client_id = c.id 
                  ORDER BY n.id DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET client_id=:client_id, ip_address=:ip_address, ssid=:ssid, password=:password, bandwidth=:bandwidth";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":client_id", htmlspecialchars(strip_tags($this->client_id)));
        $stmt->bindValue(":ip_address", htmlspecialchars(strip_tags($this->ip_address)));
        $stmt->bindValue(":ssid", htmlspecialchars(strip_tags($this->ssid ?? '')));
        $stmt->bindValue(":password", htmlspecialchars(strip_tags($this->password ?? '')));
        $stmt->bindValue(":bandwidth", htmlspecialchars(strip_tags($this->bandwidth ?? '')));
        return $stmt->execute();
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET client_id=:client_id, ip_address=:ip_address, ssid=:ssid, password=:password, bandwidth=:bandwidth 
                  WHERE id=:id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":client_id", htmlspecialchars(strip_tags($this->client_id)));
        $stmt->bindValue(":ip_address", htmlspecialchars(strip_tags($this->ip_address)));
        $stmt->bindValue(":ssid", htmlspecialchars(strip_tags($this->ssid ?? '')));
        $stmt->bindValue(":password", htmlspecialchars(strip_tags($this->password ?? '')));
        $stmt->bindValue(":bandwidth", htmlspecialchars(strip_tags($this->bandwidth ?? '')));
        $stmt->bindValue(":id", htmlspecialchars(strip_tags($this->id)));
        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        return $stmt->execute();
    }

    // ========================================================================
    // LOGIKA TOPOLOGI BARU
    // ========================================================================

    // Mengambil data topologi, dipisah berdasarkan layer nanti di frontend
    // Mencari baris 80-an di classes/Network.php
    public function getTopology($network_id) {
        $query = "SELECT nd.id as device_id, nd.parent_device_id, nd.device_port, 
                         a.id as asset_id, a.nama, a.merek, a.jenis, a.layer, a.harga
                  FROM network_devices nd
                  JOIN assets a ON nd.asset_id = a.id
                  WHERE nd.network_id = :network_id
                  ORDER BY a.layer ASC, nd.id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":network_id", $network_id);
        $stmt->execute();
        return $stmt;
    }

    // Ambil data topologi pusat (Server) - Menggunakan network_id = 0
    // Ambil data topologi pusat (Server) - Menggunakan kondisi IS NULL
    public function getServerTopology() {
        $query = "SELECT nd.id as device_id, nd.parent_device_id, nd.device_port, 
                         a.id as asset_id, a.nama, a.merek, a.jenis, a.layer, a.harga
                  FROM " . $this->table_devices . " nd
                  JOIN assets a ON nd.asset_id = a.id
                  WHERE nd.network_id IS NULL
                  ORDER BY a.layer ASC, nd.id ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Mengambil Aset yang BELUM TERPAKAI
    // Mengambil Aset yang TERSEDIA (Khusus Mikrotik bisa 4 kali pakai)
    // Mengambil Aset yang TERSEDIA (Mikrotik max 4 Port, Switch max 5 Port)
    public function getAvailableAssets($layer_condition) {
        $query = "SELECT a.*, 
                         COUNT(nd.id) as used_count, 
                         GROUP_CONCAT(nd.device_port) as used_ports 
                  FROM assets a 
                  LEFT JOIN network_devices nd ON a.id = nd.asset_id 
                  WHERE a.layer IN ($layer_condition) 
                  GROUP BY a.id 
                  HAVING (a.jenis = 'Mikrotik' AND used_count < 4) 
                      OR (a.jenis = 'Switch' AND used_count < 5)
                      OR (a.jenis NOT IN ('Mikrotik', 'Switch') AND used_count = 0) 
                  ORDER BY a.layer ASC, a.id DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Menambah Perangkat (Otomatis deteksi Server vs Client)
    public function addDevice($network_id, $asset_id, $parent_id = null, $device_port = null) {
        $query = "INSERT INTO " . $this->table_devices . " 
                  SET network_id=:network_id, asset_id=:asset_id, parent_device_id=:parent_id, device_port=:device_port";
        
        $stmt = $this->conn->prepare($query);
        
        // Jika network_id adalah 0 (Zona Server), kirim NULL ke database
        if($network_id == 0 || empty($network_id)) {
            $stmt->bindValue(":network_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":network_id", $network_id);
        }

        $stmt->bindValue(":asset_id", $asset_id);
        
        if(empty($parent_id)) {
            $stmt->bindValue(":parent_id", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":parent_id", $parent_id);
        }

        if(empty($device_port)) {
            $stmt->bindValue(":device_port", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":device_port", $device_port);
        }

        return $stmt->execute();
    }

    // Menghapus seluruh topologi untuk satu klien (Reset)
    public function resetClientTopology($network_id) {
        $query = "DELETE FROM " . $this->table_devices . " WHERE network_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $network_id);
        return $stmt->execute();
    }

    

    public function removeDevice($device_id) {
        $query = "DELETE FROM " . $this->table_devices . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $device_id);
        return $stmt->execute();
    }

    
}
?>