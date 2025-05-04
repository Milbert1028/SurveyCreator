<?php
/**
 * Two-Factor Authentication Handler
 * 
 * Implements TOTP (Time-based One-Time Password) authentication
 * Requires the PHPGangsta/GoogleAuthenticator library
 * Install with composer: composer require phpgangsta/googleauthenticator
 */

class TwoFactorAuth {
    private $db;
    private $ga;
    
    public function __construct() {
        $this->db = Database::getInstance();
        
        // Initialize Google Authenticator library
        require_once __DIR__ . '/../vendor/autoload.php';
        $this->ga = new PHPGangsta_GoogleAuthenticator();
        
        // Create the 2FA table if it doesn't exist
        $this->createTwoFactorTable();
    }
    
    /**
     * Generate a new secret key for a user
     * 
     * @param int $user_id User ID
     * @param string $username Username for the QR code
     * @return array Array containing secret key and QR code URL
     */
    public function generateSecret($user_id, $username) {
        // Generate a new secret key
        $secret = $this->ga->createSecret();
        
        // Save the secret key to the database
        $user_id = (int)$user_id;
        $secret_escaped = $this->db->escape($secret);
        
        // Check if user already has 2FA
        $check_query = $this->db->query("
            SELECT id FROM two_factor_auth WHERE user_id = $user_id
        ");
        
        if ($check_query && $check_query->num_rows > 0) {
            // Update existing record
            $this->db->query("
                UPDATE two_factor_auth
                SET secret_key = '$secret_escaped', is_activated = 0, updated_at = NOW()
                WHERE user_id = $user_id
            ");
        } else {
            // Insert new record
            $this->db->query("
                INSERT INTO two_factor_auth (user_id, secret_key, created_at, updated_at)
                VALUES ($user_id, '$secret_escaped', NOW(), NOW())
            ");
        }
        
        // Generate QR code URL
        $app_name = defined('APP_NAME') ? APP_NAME : 'SurveyCreator';
        $qrCodeUrl = $this->ga->getQRCodeGoogleUrl($app_name . ':' . $username, $secret);
        
        return [
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl
        ];
    }
    
    /**
     * Verify a TOTP code
     * 
     * @param int $user_id User ID
     * @param string $code The verification code
     * @return bool True if code is valid, false otherwise
     */
    public function verifyCode($user_id, $code) {
        $user_id = (int)$user_id;
        
        // Get user's secret key
        $query = $this->db->query("
            SELECT secret_key
            FROM two_factor_auth
            WHERE user_id = $user_id
            AND is_activated = 1
        ");
        
        if ($query && $row = $query->fetch_assoc()) {
            $secret = $row['secret_key'];
            
            // Verify the code with a window of 1 (allows for slight time drift)
            return $this->ga->verifyCode($secret, $code, 1);
        }
        
        return false;
    }
    
    /**
     * Activate 2FA for a user after verification
     * 
     * @param int $user_id User ID
     * @param string $code Verification code to confirm setup
     * @return bool True if activated successfully, false otherwise
     */
    public function activateForUser($user_id, $code) {
        $user_id = (int)$user_id;
        
        // Get user's secret key
        $query = $this->db->query("
            SELECT secret_key
            FROM two_factor_auth
            WHERE user_id = $user_id
        ");
        
        if ($query && $row = $query->fetch_assoc()) {
            $secret = $row['secret_key'];
            
            // Verify the code
            if ($this->ga->verifyCode($secret, $code, 1)) {
                // Activate 2FA for the user
                $this->db->query("
                    UPDATE two_factor_auth
                    SET is_activated = 1, updated_at = NOW()
                    WHERE user_id = $user_id
                ");
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Deactivate 2FA for a user
     * 
     * @param int $user_id User ID
     * @return bool True if deactivated successfully
     */
    public function deactivateForUser($user_id) {
        $user_id = (int)$user_id;
        
        $this->db->query("
            UPDATE two_factor_auth
            SET is_activated = 0, updated_at = NOW()
            WHERE user_id = $user_id
        ");
        
        return true;
    }
    
    /**
     * Check if 2FA is active for a user
     * 
     * @param int $user_id User ID
     * @return bool True if 2FA is active, false otherwise
     */
    public function isActivated($user_id) {
        $user_id = (int)$user_id;
        
        $query = $this->db->query("
            SELECT is_activated
            FROM two_factor_auth
            WHERE user_id = $user_id
        ");
        
        if ($query && $row = $query->fetch_assoc()) {
            return (bool)$row['is_activated'];
        }
        
        return false;
    }
    
    /**
     * Generate backup codes for a user
     * 
     * @param int $user_id User ID
     * @param int $count Number of backup codes to generate
     * @return array Array of backup codes
     */
    public function generateBackupCodes($user_id, $count = 8) {
        $user_id = (int)$user_id;
        $backup_codes = [];
        
        // Generate random backup codes
        for ($i = 0; $i < $count; $i++) {
            $backup_codes[] = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
        }
        
        // Hash the backup codes before storing them
        $hashed_codes = [];
        foreach ($backup_codes as $code) {
            $hashed_codes[] = password_hash($code, PASSWORD_DEFAULT);
        }
        
        // Store the hashed backup codes in the database
        $this->db->query("
            UPDATE two_factor_auth
            SET backup_codes = '" . $this->db->escape(json_encode($hashed_codes)) . "',
                updated_at = NOW()
            WHERE user_id = $user_id
        ");
        
        return $backup_codes;
    }
    
    /**
     * Verify a backup code for a user
     * 
     * @param int $user_id User ID
     * @param string $code The backup code to verify
     * @return bool True if code is valid, false otherwise
     */
    public function verifyBackupCode($user_id, $code) {
        $user_id = (int)$user_id;
        
        // Get user's backup codes
        $query = $this->db->query("
            SELECT backup_codes
            FROM two_factor_auth
            WHERE user_id = $user_id
            AND is_activated = 1
        ");
        
        if ($query && $row = $query->fetch_assoc() && !empty($row['backup_codes'])) {
            $backup_codes = json_decode($row['backup_codes'], true);
            
            // Check each backup code
            foreach ($backup_codes as $index => $hashed_code) {
                if (password_verify($code, $hashed_code)) {
                    // Remove the used backup code
                    unset($backup_codes[$index]);
                    
                    // Update the database with the remaining codes
                    $this->db->query("
                        UPDATE two_factor_auth
                        SET backup_codes = '" . $this->db->escape(json_encode(array_values($backup_codes))) . "',
                            updated_at = NOW()
                        WHERE user_id = $user_id
                    ");
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Create the two_factor_auth table if it doesn't exist
     * 
     * @return void
     */
    private function createTwoFactorTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS two_factor_auth (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                secret_key VARCHAR(64) NOT NULL,
                is_activated TINYINT(1) NOT NULL DEFAULT 0,
                backup_codes TEXT,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE KEY unique_user_id (user_id),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
}
?> 