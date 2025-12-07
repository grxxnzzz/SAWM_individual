<?php
/**
 * Главная страница
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Требуется авторизация
$auth->requireAuth();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav>
        <div class="container">
            <h2>Компьютерные комплектующие</h2>
            <div class="nav-links">
                <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                <a href="/parts.php">Комплектующие</a>
                <a href="/add_part.php">Добавить комплектующую</a>
                <a href="/logout.php">Выход</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <h1>Добро пожаловать в каталог компьютерных комплектующих</h1>
        
        <div class="welcome-text">
            <p>Это учебный проект для управления каталогом компьютерных комплектующих.</p>
            <p>Система обеспечивает:</p>
            <ul>
                <li>Безопасную аутентификацию с хэшированием паролей (Argon2id)</li>
                <li>Защиту от SQL Injection через подготовленные запросы</li>
                <li>Защиту от XSS через валидацию и экранирование данных</li>
                <li>Журналирование всех действий пользователей</li>
                <li>Политику надежных паролей</li>
                <li>Защиту от брутфорса (блокировка после неудачных попыток входа)</li>
            </ul>
        </div>
        
        <div class="actions">
            <a href="/parts.php" class="button">Просмотреть комплектующие</a>
            <a href="/add_part.php" class="button">Добавить новую комплектующую</a>
        </div>
    </div>
</body>
</html>