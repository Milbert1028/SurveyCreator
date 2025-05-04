<?php
/**
 * Brute Force Protection for Login
 * 
 * This file provides IP-based login attempt tracking and blocking
 */

class LoginProtection {
    private $db;
    private $ip;
    private $max_attempts = 5; // Maximum failed attempts before temporary block
    private $block_minutes = 15; // Block duration in minutes
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->ip = $this->getClientIP();
        
        // Create the login_attempts table if it doesn't exist
        $this->createAttemptsTable();
    }
    
    /**
     * Check if the current IP is blocked from login attempts
     * 
     * @return bool True if blocked, false otherwise
     */
    public function isBlocked() {
        // Clean up old records first
        $this->cleanupAttempts();
        
        // Count recent failed attempts
        $ip = $this->db->escape($this->ip);
        $block_time = date('Y-m-d H:i:s', strtotime("-{$this->block_minutes} minutes"));
        
        $query = $this->db->query("
            SELECT COUNT(*) as attempts 
            FROM login_attempts 
            WHERE ip_address = '$ip' 
            AND attempt_time > '$block_time'
            AND is_successful = 0
        ");
        
        if ($query && $row = $query->fetch_assoc()) {
            return (int)$row['attempts'] >= $this->max_attempts;
        }
        
        return false;
    }
    
    /**
     * Record a login attempt
     * 
     * @param bool $success Whether the attempt was successful
     * @return void
     */
    public function recordAttempt($success) {
        $ip = $this->db->escape($this->ip);
        $success_value = $success ? 1 : 0;
        
        $this->db->query("
            INSERT INTO login_attempts (ip_address, is_successful, attempt_time)
            VALUES ('$ip', $success_value, NOW())
        ");
    }
    
    /**
     * Reset login attempts for an IP after successful login
     * 
     * @return void
     */
    public function resetAttempts() {
        $ip = $this->db->escape($this->ip);
        
        $this->db->query("
            UPDATE login_attempts
            SET is_successful = 1
            WHERE ip_address = '$ip'
        ");
    }
    
    /**
     * Remove old login attempt records
     * 
     * @return void
     */
    private function cleanupAttempts() {
        // Remove attempts older than 24 hours
        $this->db->query("
            DELETE FROM login_attempts
            WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
    }
    
    /**
     * Get the client's IP address
     * 
     * @return string
     */
    private function getClientIP() {
        // Get IP address considering proxies and load balancers
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP format
        $ip = filter_var($ip, FILTER_VALIDATE_IP);
        return $ip ? $ip : '0.0.0.0';
    }
    
    /**
     * Create the login_attempts table if it doesn't exist
     * 
     * @return void
     */
    private function createAttemptsTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS login_attempts (
                id INT PRIMARY KEY AUTO_INCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                is_successful TINYINT(1) NOT NULL DEFAULT 0,
                attempt_time DATETIME NOT NULL,
                INDEX (ip_address),
                INDEX (attempt_time)
            )
        ");
    }
    
    /**
     * Get remaining time of block in minutes
     * 
     * @return int Minutes until block expires
     */
    public function getBlockTimeRemaining() {
        $ip = $this->db->escape($this->ip);
        
        // Get the most recent failed attempt time
        $query = $this->db->query("
            SELECT attempt_time
            FROM login_attempts
            WHERE ip_address = '$ip'
            AND is_successful = 0
            ORDER BY attempt_time DESC
            LIMIT 1
        ");
        
        if ($query && $row = $query->fetch_assoc()) {
            $last_attempt = strtotime($row['attempt_time']);
            $block_until = $last_attempt + ($this->block_minutes * 60);
            $now = time();
            
            if ($now < $block_until) {
                return ceil(($block_until - $now) / 60);
            }
        }
        
        return 0;
    }
}
?> 