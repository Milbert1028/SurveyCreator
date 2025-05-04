<?php
require_once __DIR__ . '/config.php';

class Database {
    private $connection;
    private static $instance = null;

    public function __construct() {
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
} 