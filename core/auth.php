<?php
/**
 * Класс аутентификации и авторизации
 * Использует Argon2id для хэширования паролей
 */

class Auth {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
        $this->startSecureSession();
    }
    
    /**
     * Запуск защищенной сессии
     */
    private function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
            
            // Проверка времени жизни сессии
            if (isset($_SESSION['LAST_ACTIVITY']) && 
                (time() - $_SESSION['LAST_ACTIVITY'] > SESSION_LIFETIME)) {
                $this->logout();
            }
            
            $_SESSION['LAST_ACTIVITY'] = time();
            
            // Регенерация ID сессии для защиты от фиксации сессии
            if (!isset($_SESSION['CREATED'])) {
                $_SESSION['CREATED'] = time();
            } else if (time() - $_SESSION['CREATED'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['CREATED'] = time();
            }
        }
    }
    
    /**
     * Регистрация нового пользователя
     * @param string $username Имя пользователя
     * @param string $email Email
     * @param string $password Пароль
     * @return bool|string True при успехе, текст ошибки при неудаче
     */
    public function register($username, $email, $password) {
        // Проверка существования пользователя
        $existing = $this->db->selectOne(
            'SELECT id FROM users WHERE username = ? OR email = ?',
            [$username, $email]
        );
        
        if ($existing) {
            Logger::security('Registration attempt with existing credentials', [
                'username' => $username,
                'email' => $email
            ]);
            return 'Пользователь с таким именем или email уже существует';
        }
        
        // Хэширование пароля с использованием Argon2id
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]);
        
        // Создание пользователя
        $result = $this->db->execute(
            'INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)',
            [$username, $email, $passwordHash]
        );
        
        if ($result) {
            Logger::security('New user registered', ['username' => $username]);
            $this->logUserAction($this->db->lastInsertId(), 'User registered');
            return true;
        }
        
        return 'Ошибка при регистрации';
    }
    
    /**
     * Вход пользователя
     * @param string $username Имя пользователя
     * @param string $password Пароль
     * @return bool|string True при успехе, текст ошибки при неудаче
     */
    public function login($username, $password) {
        $user = $this->db->selectOne(
            'SELECT * FROM users WHERE username = ?',
            [$username]
        );
        
        // Проверка блокировки аккаунта
        if ($user && $user['locked_until']) {
            $lockedUntil = strtotime($user['locked_until']);
            if ($lockedUntil > time()) {
                Logger::security('Login attempt on locked account', ['username' => $username]);
                $minutes = ceil(($lockedUntil - time()) / 60);
                return "Аккаунт заблокирован. Повторите попытку через $minutes минут";
            } else {
                // Разблокировка аккаунта
                $this->db->execute(
                    'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?',
                    [$user['id']]
                );
            }
        }
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            // Неудачная попытка входа
            if ($user) {
                $attempts = $user['failed_login_attempts'] + 1;
                
                // Блокировка при превышении попыток
                if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                    $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_TIME);
                    $this->db->execute(
                        'UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE id = ?',
                        [$attempts, $lockUntil, $user['id']]
                    );
                    Logger::security('Account locked due to failed login attempts', ['username' => $username]);
                } else {
                    $this->db->execute(
                        'UPDATE users SET failed_login_attempts = ? WHERE id = ?',
                        [$attempts, $user['id']]
                    );
                }
            }
            
            Logger::security('Failed login attempt', ['username' => $username]);
            return 'Неверное имя пользователя или пароль';
        }
        
        // Проверка необходимости смены пароля
        if (PASSWORD_EXPIRY_DAYS > 0) {
            $passwordAge = time() - strtotime($user['password_changed_at']);
            if ($passwordAge > (PASSWORD_EXPIRY_DAYS * 86400)) {
                $_SESSION['password_expired'] = true;
                $_SESSION['user_id_pending'] = $user['id'];
                return 'Срок действия пароля истек. Необходимо изменить пароль';
            }
        }
        
        // Успешный вход
        $this->db->execute(
            'UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = ?',
            [$user['id']]
        );
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        Logger::security('Successful login', ['username' => $username]);
        $this->logUserAction($user['id'], 'User logged in');
        
        return true;
    }
    
    /**
     * Выход пользователя
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logUserAction($_SESSION['user_id'], 'User logged out');
            Logger::security('User logged out', ['username' => $_SESSION['username']]);
        }
        
        session_unset();
        session_destroy();
    }
    
    /**
     * Проверка аутентификации
     * @return bool Аутентифицирован ли пользователь
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Проверка роли пользователя
     * @param string $role Требуемая роль
     * @return bool Имеет ли пользователь роль
     */
    public function hasRole($role) {
        return $this->isAuthenticated() && $_SESSION['role'] === $role;
    }
    
    /**
     * Проверка, что пользователь имеет хотя бы одну из указанных ролей
     */
    public function hasAnyRole(array $roles) {
        if (!$this->isAuthenticated()) {
            return in_array(ROLE_GUEST, $roles);
        }
        return in_array($_SESSION['role'] ?? '', $roles);
    }
    
    /**
     * Требование определенной роли (редирект если не подходит)
     */
    public function requireRole($role) {
        if (!$this->hasRole($role)) {
            header('Location: /index.php');
            exit;
        }
    }
    
    /**
     * Требование одной из ролей (редирект если не подходит)
     */
    public function requireAnyRole(array $roles) {
        if (!$this->hasAnyRole($roles)) {
            header('Location: /index.php');
            exit;
        }
    }

    /**
     * Получение ID текущего пользователя
     * @return int|null ID пользователя или null
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Журналирование действий пользователя
     * @param int $userId ID пользователя
     * @param string $action Описание действия
     */
    public function logUserAction($userId, $action) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $this->db->execute(
            'INSERT INTO user_actions (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)',
            [$userId, $action, $ip, $userAgent]
        );
    }
    
    /**
     * Требование аутентификации (редирект если не авторизован)
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            header('Location: /login.php');
            exit;
        }
    }
}
?>