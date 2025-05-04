<?php
/**
 * Admin Activity Logger
 * 
 * Logs all admin actions for security and auditing
 */

class ActivityLogger {
    private $db;
    private $user_id;
    
    /**
     * Initialize the logger
     * 
     * @param int $user_id Current user ID (null if not logged in)
     */
    public function __construct($user_id = null) {
        $this->db = Database::getInstance();
        $this->user_id = $user_id;
        
        // Create the activity_logs table if it doesn't exist
        $this->createLogsTable();
    }
    
    /**
     * Log an activity
     * 
     * @param string $action The action performed
     * @param string $module The module where the action was performed
     * @param string $description Description of the activity
     * @param mixed $data Additional data to store (will be JSON encoded)
     * @return bool True if logged successfully
     */
    public function log($action, $module, $description = '', $data = null) {
        // Get current user ID from session if not provided
        $user_id = $this->user_id;
        if ($user_id === null && isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
        }
        
        // Sanitize inputs
        $user_id = $user_id ? (int)$user_id : 0;
        $action = $this->db->escape($action);
        $module = $this->db->escape($module);
        $description = $this->db->escape($description);
        
        // JSON encode additional data if provided
        $data_json = $data !== null ? $this->db->escape(json_encode($data)) : 'NULL';
        if ($data_json !== 'NULL') {
            $data_json = "'" . $data_json . "'";
        }
        
        // Get IP address and user agent
        $ip_address = $this->getClientIP();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $this->db->escape($_SERVER['HTTP_USER_AGENT']) : '';
        
        // Insert log entry
        $result = $this->db->query("
            INSERT INTO activity_logs 
            (user_id, action, module, description, ip_address, user_agent, additional_data, created_at)
            VALUES 
            ($user_id, '$action', '$module', '$description', '$ip_address', '$user_agent', $data_json, NOW())
        ");
        
        return (bool)$result;
    }
    
    /**
     * Get logs with pagination
     * 
     * @param int $page Page number (1-based)
     * @param int $per_page Records per page
     * @param array $filters Optional filters (user_id, action, module, date_from, date_to)
     * @return array Array of logs and pagination info
     */
    public function getLogs($page = 1, $per_page = 20, $filters = []) {
        $page = max(1, (int)$page);
        $per_page = max(1, (int)$per_page);
        $offset = ($page - 1) * $per_page;
        
        // Build where clause from filters
        $where_clauses = [];
        
        if (!empty($filters['user_id'])) {
            $user_id = (int)$filters['user_id'];
            $where_clauses[] = "user_id = $user_id";
        }
        
        if (!empty($filters['action'])) {
            $action = $this->db->escape($filters['action']);
            $where_clauses[] = "action = '$action'";
        }
        
        if (!empty($filters['module'])) {
            $module = $this->db->escape($filters['module']);
            $where_clauses[] = "module = '$module'";
        }
        
        if (!empty($filters['date_from'])) {
            $date_from = $this->db->escape($filters['date_from']);
            $where_clauses[] = "created_at >= '$date_from'";
        }
        
        if (!empty($filters['date_to'])) {
            $date_to = $this->db->escape($filters['date_to']);
            $where_clauses[] = "created_at <= '$date_to 23:59:59'";
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Get total count
        $count_query = $this->db->query("
            SELECT COUNT(*) as total FROM activity_logs $where_sql
        ");
        
        $total = 0;
        if ($count_query && $count_row = $count_query->fetch_assoc()) {
            $total = (int)$count_row['total'];
        }
        
        // Get logs with join to users table
        $logs_query = $this->db->query("
            SELECT 
                l.*,
                u.username
            FROM 
                activity_logs l
            LEFT JOIN 
                users u ON l.user_id = u.id
            $where_sql
            ORDER BY 
                l.created_at DESC
            LIMIT 
                $offset, $per_page
        ");
        
        $logs = [];
        if ($logs_query) {
            while ($row = $logs_query->fetch_assoc()) {
                // Parse JSON data if present
                if ($row['additional_data']) {
                    $row['additional_data'] = json_decode($row['additional_data'], true);
                }
                $logs[] = $row;
            }
        }
        
        // Calculate pagination info
        $total_pages = ceil($total / $per_page);
        $has_next = $page < $total_pages;
        $has_prev = $page > 1;
        
        return [
            'logs' => $logs,
            'pagination' => [
                'total' => $total,
                'per_page' => $per_page,
                'current_page' => $page,
                'total_pages' => $total_pages,
                'has_next' => $has_next,
                'has_prev' => $has_prev
            ]
        ];
    }
    
    /**
     * Get actions for a specific user
     * 
     * @param int $user_id User ID
     * @param int $limit Maximum number of logs to retrieve
     * @return array Array of user activity logs
     */
    public function getUserActions($user_id, $limit = 10) {
        $user_id = (int)$user_id;
        $limit = (int)$limit;
        
        $query = $this->db->query("
            SELECT * FROM activity_logs
            WHERE user_id = $user_id
            ORDER BY created_at DESC
            LIMIT $limit
        ");
        
        $logs = [];
        if ($query) {
            while ($row = $query->fetch_assoc()) {
                if ($row['additional_data']) {
                    $row['additional_data'] = json_decode($row['additional_data'], true);
                }
                $logs[] = $row;
            }
        }
        
        return $logs;
    }
    
    /**
     * Get the client's IP address
     * 
     * @return string
     */
    private function getClientIP() {
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
     * Create the activity_logs table if it doesn't exist
     * 
     * @return void
     */
    private function createLogsTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT,
                action VARCHAR(50) NOT NULL,
                module VARCHAR(50) NOT NULL,
                description TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                additional_data TEXT,
                created_at DATETIME NOT NULL,
                INDEX (user_id),
                INDEX (action),
                INDEX (module),
                INDEX (created_at)
            )
        ");
    }
}
?> 