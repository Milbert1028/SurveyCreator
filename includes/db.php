<?php
require_once __DIR__ . '/config.php';

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            // Add a log before attempting to connect
            error_log("Attempting database connection to " . DB_HOST . " with user " . DB_USER);
            
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                error_log("Database connection failed: " . $this->connection->connect_error);
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset("utf8mb4");
            error_log("Database connection successful to " . DB_NAME);
        } catch (Exception $e) {
            error_log("Exception in Database constructor: " . $e->getMessage());
            // Re-throw the exception
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                error_log("Failed to get Database instance: " . $e->getMessage());
                throw $e;
            }
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function query($sql) {
        try {
            $result = $this->connection->query($sql);
            if ($result === false) {
                error_log("Query failed: " . $this->connection->error . " in query: " . substr($sql, 0, 100) . "...");
            }
            return $result;
        } catch (Exception $e) {
            error_log("Exception in query: " . $e->getMessage() . " in query: " . substr($sql, 0, 100) . "...");
            throw $e;
        }
    }

    public function prepare($sql) {
        return $this->connection->prepare($sql);
    }

    public function escape($value) {
        return $this->connection->real_escape_string($value);
    }

    public function getLastError() {
        return $this->connection->error;
    }

    public function getLastId() {
        return $this->connection->insert_id;
    }

    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollback();
    }

    public function close() {
        return $this->connection->close();
    }

    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    public function getOne($sql) {
        $result = $this->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }

    public function getAll($sql) {
        $result = $this->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }

    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_values($data);
        $placeholders = str_repeat('?,', count($fields) - 1) . '?';
        
        $sql = "INSERT INTO {$table} (" . implode(',', $fields) . ") VALUES ({$placeholders})";
        $stmt = $this->prepare($sql);
        
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }
        
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result ? $this->getLastId() : false;
    }

    public function update($table, $data, $where) {
        $set = [];
        $values = [];
        $types = '';
        
        foreach ($data as $field => $value) {
            $set[] = "{$field} = ?";
            $values[] = $value;
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }
        
        foreach ($where as $value) {
            $values[] = $value;
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
        }
        
        $sql = "UPDATE {$table} SET " . implode(',', $set) . " WHERE " . key($where) . " = ?";
        $stmt = $this->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    public function delete($table, $where) {
        $sql = "DELETE FROM {$table} WHERE " . key($where) . " = ?";
        $stmt = $this->prepare($sql);
        
        $value = current($where);
        $type = is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        
        $stmt->bind_param($type, $value);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
