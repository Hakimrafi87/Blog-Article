<?php
require_once 'config.php';

class Auth
{
    private $db;
    private $pdo;

    public function __construct()
    {
        $this->db = new Database();
        $this->pdo = $this->db->getConnection();
    }
    /**
     * Authenticate user with username and password (plain text)
     * @param string $username
     * @param string $password
     * @return array|false Returns user data on success, false on failure
     */    public function login($username, $password)
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, password, name, role, created_at 
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debug logging
            error_log("Login attempt for username: " . $username);
            error_log("User found: " . ($user ? 'Yes' : 'No'));

            if ($user) {
                error_log("Stored password: " . $user['password']);
                error_log("Input password: " . $password);
                error_log("Password match result: " . ($password === $user['password'] ? 'true' : 'false'));

                // Compare plain text passwords directly
                if ($password === $user['password']) {
                    // Update last login time (you can add this field to users table if needed)
                    $this->updateLastLogin($user['id']);

                    // Set session data
                    $this->setUserSession($user);

                    return $user;
                }
            }

            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Set user session data
     * @param array $user
     */
    private function setUserSession($user)
    {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Update user's last login timestamp
     * @param int $userId
     */
    private function updateLastLogin($userId)
    {
        try {
            // Note: You may want to add last_login column to users table
            // For now, we'll just log it
            error_log("User ID {$userId} logged in at " . date('Y-m-d H:i:s'));
        } catch (Exception $e) {
            error_log("Error updating last login: " . $e->getMessage());
        }
    }

    /**
     * Logout current user
     */
    public function logout()
    {
        // Clear all session data
        $_SESSION = array();

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Check if current user is admin
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Check if current user is author
     * @return bool
     */
    public function isAuthor()
    {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'author';
    }

    /**
     * Get current user data
     * @return array|null
     */
    public function getCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, name, role, created_at 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting current user: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if current user can edit specific article
     * @param int $articleId
     * @return bool
     */
    public function canEditArticle($articleId)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Admins can edit any article
        if ($this->isAdmin()) {
            return true;
        }

        // Authors can only edit their own articles
        try {
            $stmt = $this->pdo->prepare("SELECT author_id FROM articles WHERE id = ?");
            $stmt->execute([$articleId]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);

            return $article && $article['author_id'] == $_SESSION['user_id'];
        } catch (PDOException $e) {
            error_log("Error checking article permissions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Require login - redirect to login page if not logged in
     * @param string $redirectTo Where to redirect after login
     */
    public function requireLogin($redirectTo = null)
    {
        if (!$this->isLoggedIn()) {
            if ($redirectTo) {
                redirect('login.php?redirect=' . urlencode($redirectTo));
            } else {
                redirect('login.php');
            }
        }
    }

    /**
     * Require admin access - redirect if not admin
     */
    public function requireAdmin()
    {
        $this->requireLogin();

        if (!$this->isAdmin()) {
            redirect('admin/dashboard.php');
        }
    }

    /**
     * Create new user (admin only)
     * @param string $username
     * @param string $password
     * @param string $name
     * @param string $role
     * @return array|false Returns user data on success, error message on failure
     */
    public function createUser($username, $password, $name, $role = 'author')
    {
        if (!$this->isAdmin()) {
            return ['error' => 'Unauthorized'];
        }

        if (empty($username) || empty($password) || empty($name)) {
            return ['error' => 'All fields are required'];
        }

        if (!in_array($role, ['admin', 'author'])) {
            return ['error' => 'Invalid role'];
        }
        try {
            // Check if username exists
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['error' => 'Username already exists'];
            }

            // Create user with plain text password
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, name, role) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$username, $password, $name, $role]);

            $userId = $this->pdo->lastInsertId();

            // Return created user data
            $stmt = $this->pdo->prepare("
                SELECT id, username, name, role, created_at 
                FROM users WHERE id = ?
            ");
            $stmt->execute([$userId]);

            return ['success' => true, 'user' => $stmt->fetch(PDO::FETCH_ASSOC)];
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return ['error' => 'Error creating user'];
        }
    }

    /**
     * Update user password
     * @param int $userId
     * @param string $currentPassword
     * @param string $newPassword
     * @return array
     */
    public function updatePassword($userId, $currentPassword, $newPassword)
    {
        // Users can only update their own password, or admin can update any
        if (!$this->isAdmin() && $_SESSION['user_id'] != $userId) {
            return ['error' => 'Unauthorized'];
        }

        if (empty($newPassword)) {
            return ['error' => 'New password is required'];
        }

        try {
            // Verify current password (except for admin updating other users)
            if (!$this->isAdmin() || $_SESSION['user_id'] == $userId) {
                $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user || $currentPassword !== $user['password']) {
                    return ['error' => 'Current password is incorrect'];
                }
            }

            // Update password (plain text)
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newPassword, $userId]);

            return ['success' => true, 'message' => 'Password updated successfully'];
        } catch (PDOException $e) {
            error_log("Error updating password: " . $e->getMessage());
            return ['error' => 'Error updating password'];
        }
    }

    /**
     * Validate session and refresh if needed
     * @return bool
     */
    public function validateSession()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Check if session is too old (optional - 24 hours)
        if (isset($_SESSION['logged_in_at']) && (time() - $_SESSION['logged_in_at']) > 86400) {
            $this->logout();
            return false;
        }

        // Verify user still exists in database
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            if (!$stmt->fetch()) {
                $this->logout();
                return false;
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error validating session: " . $e->getMessage());
            return false;
        }
    }
}

// Create global auth instance
$auth = new Auth();

// Update global helper functions to use the Auth class
function isLoggedIn()
{
    global $auth;
    return $auth->isLoggedIn();
}

function isAdmin()
{
    global $auth;
    return $auth->isAdmin();
}

function requireLogin($redirectTo = null)
{
    global $auth;
    $auth->requireLogin($redirectTo);
}

function requireAdmin()
{
    global $auth;
    $auth->requireAdmin();
}
