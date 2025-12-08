<?php
/**
 * Главная страница
 * Доступна всем пользователям (включая неавторизованных)
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Получаем последние товары для главной страницы
$latestParts = $db->select(
    'SELECT p.*, u.username as creator 
     FROM parts p 
     JOIN users u ON p.created_by = u.id 
     ORDER BY p.created_at DESC LIMIT 6'
);

// Получаем статистику
$stats = $db->selectOne(
    'SELECT COUNT(*) as total_parts, 
            COUNT(DISTINCT category) as total_categories,
            COUNT(DISTINCT manufacturer) as total_manufacturers
     FROM parts'
);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
            border-radius: 10px;
            margin-bottom: 40px;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        .hero p {
            font-size: 20px;
            max-width: 800px;
            margin: 0 auto 30px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }
        
        .latest-parts {
            margin-top: 40px;
        }
        
        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .part-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .part-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .part-card h3 {
            margin-top: 0;
            color: #333;
        }
        
        .part-price {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
            margin: 10px 0;
        }
        
        .btn-primary {
            display: inline-block;
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            color: white;
        }
        
        .btn-secondary {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            margin: 10px;
        }
        
        .btn-secondary:hover {
            background: #545b62;
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <!-- Герой-секция -->
        <div class="hero">
            <h1>Каталог компьютерных комплектующих</h1>
            <p>Найдите лучшие комплектующие для вашего компьютера. Процессоры, видеокарты, материнские платы и многое другое.</p>
            <div>
                <a href="/parts.php" class="btn-primary">Смотреть все товары</a>
                <?php if ($auth->isAuthenticated() && in_array($_SESSION['role'] ?? '', ['manager', 'admin'])): ?>
                    <a href="/add_part.php" class="btn-secondary">Добавить товар</a>
                <?php elseif (!$auth->isAuthenticated()): ?>
                    <a href="/register.php" class="btn-secondary">Зарегистрироваться</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Статистика -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?= htmlspecialchars($stats['total_parts'] ?? 0) ?></div>
                <div class="stat-label">Товаров в каталоге</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= htmlspecialchars($stats['total_categories'] ?? 0) ?></div>
                <div class="stat-label">Категорий</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= htmlspecialchars($stats['total_manufacturers'] ?? 0) ?></div>
                <div class="stat-label">Производителей</div>
            </div>
        </div>
        
        <!-- Последние добавленные товары -->
        <div class="latest-parts">
            <h2>Последние добавленные товары</h2>
            
            <?php if (empty($latestParts)): ?>
                <p>Товары еще не добавлены. 
                <?php if ($auth->isAuthenticated() && in_array($_SESSION['role'] ?? '', ['manager', 'admin'])): ?>
                    <a href="/add_part.php">Добавить первый товар</a>
                <?php endif; ?>
                </p>
            <?php else: ?>
                <div class="parts-grid">
                    <?php foreach ($latestParts as $part): ?>
                        <div class="part-card">
                            <h3><?= htmlspecialchars($part['name']) ?></h3>
                            <div class="part-price">$<?= number_format($part['price'], 2) ?></div>
                            <p><strong>Категория:</strong> <?= htmlspecialchars($part['category']) ?></p>
                            <p><strong>Производитель:</strong> <?= htmlspecialchars($part['manufacturer']) ?></p>
                            <?php if ($part['description']): ?>
                                <p><?= htmlspecialchars(substr($part['description'], 0, 100)) ?>...</p>
                            <?php endif; ?>
                            <p class="meta">Добавил: <?= htmlspecialchars($part['creator']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: center; margin-top: 30px;">
                    <a href="/parts.php" class="btn-primary">Смотреть все товары</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Информация о системе -->
        <div style="margin-top: 50px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>О системе</h3>
            <p>Эта система обеспечивает:</p>
            <ul>
                <li>Просмотр каталога компьютерных комплектующих для всех пользователей</li>
                <li>Разделение ролей: Гость (только просмотр), Менеджер (добавление/редактирование), Администратор (полный доступ)</li>
                <li>Безопасную аутентификацию с хэшированием паролей (Argon2id)</li>
                <li>Защиту от SQL Injection, XSS и брутфорс-атак</li>
                <li>Журналирование всех действий пользователей</li>
            </ul>
        </div>
    </div>
</body>
</html>