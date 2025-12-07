<?php
/**
 * Конфигурация базы данных
 */

// Путь к базе данных SQLite
define('DB_PATH', __DIR__ . '/../database/computer_parts.db');

// Путь к логам
define('LOG_PATH', __DIR__ . '/../logs/');

// Настройки безопасности
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_DIGIT', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Срок действия пароля (в днях, 0 = без ротации)
define('PASSWORD_EXPIRY_DAYS', 90);

// Количество попыток входа перед блокировкой
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 минут в секундах

// Длительность сессии (в секундах)
define('SESSION_LIFETIME', 3600); // 1 час

// Настройки сессии для безопасности
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Использовать только через HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Отключение вывода ошибок для пользователя (только логи)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', LOG_PATH . 'errors.log');
error_reporting(E_ALL);
?>