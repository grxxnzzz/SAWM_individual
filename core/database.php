<?php
/**
 * Класс для работы с базой данных SQLite
 * Использует подготовленные запросы для защиты от SQL Injection
 */

class Database
{
    private $connection;

    /**
     * Конструктор - создает подключение к БД
     */
    public function __construct()
    {
        try {
            $this->connection = new PDO('sqlite:' . DB_PATH);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Включаем внешние ключи для SQLite
            $this->connection->exec('PRAGMA foreign_keys = ON');

            $this->initDatabase();
        } catch (PDOException $e) {
            Logger::error('Database connection failed: ' . $e->getMessage());
            $this->redirectToError('Ошибка подключения к базе данных');
        }
    }

    /**
     * Инициализация структуры базы данных
     */
    private function initDatabase()
    {
        try {
            // Таблица пользователей
            $this->connection->exec("
        CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        role TEXT DEFAULT 'guest' CHECK(role IN ('guest', 'manager', 'admin')),
        password_changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        failed_login_attempts INTEGER DEFAULT 0,
        locked_until DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
");

            // Таблица компьютерных комплектующих
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS parts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    category TEXT NOT NULL,
                    manufacturer TEXT NOT NULL,
                    price REAL NOT NULL,
                    description TEXT,
                    created_by INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id)
                )
            ");

            // Таблица действий пользователей (аудит)
            $this->connection->exec("
                CREATE TABLE IF NOT EXISTS user_actions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    action TEXT NOT NULL,
                    ip_address TEXT NOT NULL,
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )
            ");
        } catch (PDOException $e) {
            Logger::error('Database initialization failed: ' . $e->getMessage());
            $this->redirectToError('Ошибка инициализации базы данных');
        }

        $userCount = $this->connection->query("SELECT COUNT(*) as count FROM users")->fetchColumn();
        if ($userCount == 0) {
            $adminPassword = password_hash('Admin123!', PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost' => 4,
                'threads' => 3
            ]);

            $this->connection->exec("
        INSERT INTO users (username, email, password_hash, role) 
        VALUES ('admin', 'admin@example.com', '$adminPassword', 'admin')
    ");

            Logger::info('Default admin user created', [
                'username' => 'admin',
                'password' => 'Admin123!'
            ]);
        }
    }

    /**
     * Выполнение SELECT запроса с параметрами
     * @param string $query SQL запрос
     * @param array $params Параметры для подготовленного запроса
     * @return array Результат запроса
     */
    public function select($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Logger::error('Database select error: ' . $e->getMessage());
            $this->redirectToError('Ошибка выполнения запроса');
        }
    }

    /**
     * Выполнение SELECT запроса с возвратом одной строки
     */
    public function selectOne($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            Logger::error('Database selectOne error: ' . $e->getMessage());
            $this->redirectToError('Ошибка выполнения запроса');
        }
    }

    /**
     * Выполнение INSERT, UPDATE, DELETE запросов
     * @param string $query SQL запрос
     * @param array $params Параметры
     * @return bool Успех выполнения
     */
    public function execute($query, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Logger::error('Database execute error: ' . $e->getMessage());
            $this->redirectToError('Ошибка выполнения операции');
        }
    }

    /**
     * Получение ID последней вставленной записи
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Перенаправление на страницу ошибки
     */
    private function redirectToError($message)
    {
        $_SESSION['error_message'] = $message;
        header('Location: /error.php');
        exit;
    }
}
?>