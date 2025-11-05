<?php
/**
 * User Model
 * Handles user-related database operations
 */

class User {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (first_name, last_name, email, password, phone, role, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'], 
            $data['email'],
            $data['password'],
            $data['phone'] ?? null,
            $data['role'] ?? 'customer',
            $data['is_active'] ?? 1
        ]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Find user by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all customers
     */
    public function getAllCustomers() {
        $sql = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update user status
     */
    public function updateStatus($userId, $status) {
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $userId]);
    }
    
    /**
     * Verify user login
     */
    public function verifyLogin($email, $password) {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}
?>