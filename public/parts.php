<?php
/**
 * Страница списка комплектующих
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

// Получение параметров фильтрации
$category = Validator::sanitize($_GET['category'] ?? '');
$search = Validator::sanitize($_GET['search'] ?? '');

// Построение запроса с учетом фильтров
$query = 'SELECT p.*, u.username as creator 
          FROM parts p 
          JOIN users u ON p.created_by = u.id 
          WHERE 1=1';
$params = [];

if ($category) {
    $query .= ' AND p.category = ?';
    $params[] = $category;
}

if ($search) {
    $query .= ' AND (p.name LIKE ? OR p.manufacturer LIKE ? OR p.description LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= ' ORDER BY p.created_at DESC';

$parts = $db->select($query, $params);

// Получение списка категорий для фильтра
$categories = $db->select('SELECT DISTINCT category FROM parts ORDER BY category');

// Логирование просмотра
$auth->logUserAction($auth->getCurrentUserId(), 'Viewed parts list');
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Комплектующие - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <nav>
        <div class="container">
            <h2>Компьютерные комплектующие</h2>
            <div class="nav-links">
                <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                <a href="/index.php">Главная</a>
                <a href="/add_part.php">Добавить</a>
                <a href="/logout.php">Выход</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1>Каталог комплектующих</h1>

        <!-- Фильтры -->
        <div class="filters">
            <form method="GET" action="/parts.php">
                <div class="filter-group">
                    <label for="category">Категория:</label>
                    <select id="category" name="category">
                        <option value="">Все категории</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="search">Поиск:</label>
                    <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>"
                        placeholder="Название, производитель...">
                </div>

                <button type="submit">Применить</button>
                <a href="/parts.php" class="button-link">Сбросить</a>
            </form>
        </div>

        <!-- Список комплектующих -->
        <?php if (empty($parts)): ?>
            <p class="no-data">Комплектующие не найдены. <a href="/add_part.php">Добавить первую?</a></p>
        <?php else: ?>
            <div class="parts-grid">
                <?php foreach ($parts as $part): ?>
                    <div class="part-card">
                        <h3><?= htmlspecialchars($part['name']) ?></h3>
                        <div class="part-info">
                            <p><strong>Категория:</strong> <?= htmlspecialchars($part['category']) ?></p>
                            <p><strong>Производитель:</strong> <?= htmlspecialchars($part['manufacturer']) ?></p>
                            <p><strong>Цена:</strong> $<?= number_format($part['price'], 2) ?></p>
                            <?php if ($part['description']): ?>
                                <p><strong>Описание:</strong> <?= htmlspecialchars($part['description']) ?></p>
                            <?php endif; ?>
                            <p class="meta">Добавлено: <?= htmlspecialchars($part['creator']) ?>
                                (<?= date('d.m.Y H:i', strtotime($part['created_at'])) ?>)</p>
                        </div>
                        <?php if ($auth->hasAnyRole(['manager', 'admin'])): ?>
                            <div class="part-actions">
                                <a href="/edit_part.php?id=<?= $part['id'] ?>" class="btn-small">Редактировать</a>
                                <form method="POST" action="/delete_part.php" style="display: inline;">
                                    <input type="hidden" name="part_id" value="<?= $part['id'] ?>">
                                    <button type="submit" class="btn-small btn-danger"
                                        onclick="return confirm('Удалить товар?')">Удалить</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>