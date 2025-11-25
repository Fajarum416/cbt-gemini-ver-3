<?php
// includes/Database.php

class Database {
    private $host = DB_HOST;
    private $user = DB_USERNAME;
    private $pass = DB_PASSWORD;
    private $dbname = DB_NAME;
    
    public $conn;

    public function __construct() {
        // Matikan error reporting default mysqli agar bisa ditangkap try-catch
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            $this->conn->set_charset("utf8");
            $this->conn->query("SET time_zone = '+07:00'");
        } catch (mysqli_sql_exception $e) {
            // Jika mode debug, tampilkan error asli. Jika tidak, tampilkan pesan umum.
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Koneksi Database Gagal: " . $e->getMessage());
            } else {
                die("Maaf, sistem sedang dalam pemeliharaan (DB Connection).");
            }
        }
    }

    // Fungsi Query Utama
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Gagal Prepare: " . $this->conn->error);
            }

            if (!empty($params)) {
                $types = "";
                foreach ($params as $param) {
                    if (is_int($param)) $types .= "i";
                    elseif (is_float($param)) $types .= "d";
                    else $types .= "s";
                }
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Gagal Eksekusi: " . $stmt->error);
            }

            return $stmt;
        } catch (Exception $e) {
            // Lempar error agar bisa ditangkap oleh try-catch di file pemanggil (seperti process_test_wizard.php)
            throw $e; 
        }
    }

    // Ambil 1 Baris
    public function single($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            return null;
        }
    }

    // Ambil Banyak Baris
    public function all($sql, $params = []) {
        try {
            $stmt = $this->query($sql, $params);
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    // --- INI FUNGSI YANG TADI HILANG ---
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    // -----------------------------------

    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
// Tanpa tag penutup PHP