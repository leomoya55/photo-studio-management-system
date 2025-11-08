<?php
/**
 * Authentication Controller
 * Handles login, logout, and registration
 */

class AuthController {
    private $userModel;
    
    public function __construct($connection) {
        includeModel('User.php');
        $this->userModel = new User($connection);
    }
    
    /**
     * Handle user login
     */
    public function login($email, $password) {
        $user = $this->userModel->verifyLogin($email, $password);
        
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_active'] = $user['is_active'];
            
            return [
                'success' => true,
                'message' => 'Login successful',
                'user' => $user
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    /**
     * Handle user registration
     */
    public function register($data) {
        // Check if user already exists
        if ($this->userModel->findByEmail($data['email'])) {
            return [
                'success' => false,
                'message' => 'Email already exists'
            ];
        }
        
        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Create user
        if ($this->userModel->create($data)) {
            return [
                'success' => true,
                'message' => 'Registration successful'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Registration failed'
        ];
    }
    
    /**
     * Handle user logout
     */
    public function logout() {
        session_start();
        session_destroy();
        return [
            'success' => true,
            'message' => 'Logout successful'
        ];
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>