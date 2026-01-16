<?php
/**
 * Database Configuration & Connection
 * SQLite3 Database Handler
 */

class Database {
    private static $instance = null;
    private $db;
    private $db_path = __DIR__ . '/../database/distribusi.db';

    private function __construct() {
        try {
            // Pastikan folder database ada
            $db_dir = dirname($this->db_path);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }

            // Koneksi ke SQLite
            $this->db = new SQLite3($this->db_path);
            $this->db->busyTimeout(5000); // Timeout 5 detik untuk menghindari locked database
            
            // Enable foreign keys
            $this->db->exec('PRAGMA foreign_keys = ON;');
            
        } catch (Exception $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->db;
    }

    // Helper: Execute Query (SELECT)
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($params) {
                foreach ($params as $key => $value) {
                    if (is_int($key)) {
                        $stmt->bindValue($key + 1, $value);
                    } else {
                        $stmt->bindValue(':' . $key, $value);
                    }
                }
            }
            
            $result = $stmt->execute();
            $data = [];
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $row;
            }
            
            return $data;
            
        } catch (Exception $e) {
            error_log("Query Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper: Execute Query (INSERT/UPDATE/DELETE)
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($params) {
                foreach ($params as $key => $value) {
                    if (is_int($key)) {
                        $stmt->bindValue($key + 1, $value);
                    } else {
                        $stmt->bindValue(':' . $key, $value);
                    }
                }
            }
            
            $result = $stmt->execute();
            return $result !== false;
            
        } catch (Exception $e) {
            error_log("Execute Error: " . $e->getMessage());
            return false;
        }
    }

    // Helper: Get Last Insert ID
    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }

    // Helper: Escape String (untuk keamanan)
    public function escape($string) {
        return SQLite3::escapeString($string);
    }

    // Helper: Begin Transaction
    public function beginTransaction() {
        return $this->db->exec('BEGIN TRANSACTION;');
    }

    // Helper: Commit Transaction
    public function commit() {
        return $this->db->exec('COMMIT;');
    }

    // Helper: Rollback Transaction
    public function rollback() {
        return $this->db->exec('ROLLBACK;');
    }
}
