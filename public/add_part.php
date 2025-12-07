<?php
/**
 * Страница добавления комплектующей
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

$errors = [];
$success = false;

// Предопределенные категории
$categories = [
    'Процессоры',
    'Материнские платы',
    'Оперативная память',
    'Видеокарты',
    'Жесткие диски',
    'SSD накопители',
    'Блоки питания',
    'Корпуса',
    'Охлаждение',
    'Периферия'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение и очистка данных
    $name = Validator::sanitize($_POST['name'] ?? '');
    $category = Validator::sanitize($_POST['category'] ?? '');
    $manufacturer = Validator::sanitize($_POST['manufacturer'] ?? '');
    $price = $_POST['price'] ?? '';
    $description = Validator::sanitize($_POST['description'] ?? '');
    
    // Валидация
    $validator = new Validator();
    
    $validator->required($name, 'Название');
    $validator->length($name, 3, 100);
    
    $validator->required($category, 'Категория');
    
    $validator->required($manufacturer, 'Производитель');
    $validator->length($manufacturer, 2, 50);
    
    $validator->required($price, 'Цена');
    $validator->number($price, 0.01, 999999.99);
    
    if ($description) {
        $validator->length($description, 0, 500);
    }
    
    $errors = $validator->getErrors();
    
    // Сохранение если нет ошибок
    if (empty($errors)) {
        $result = $db->execute(
            'INSERT INTO parts (name, category, manufacturer, price, description, created_by) 
             VALUES (?, ?, ?, ?, ?, ?)',
            [$name, $category, $manufacturer, $price, $description, $auth->getCurrentUserId()]
        );
        
        if ($result) {
            $success = true;
            Logger::info('New part added', [
                'part_name' => $name,
                'user_id' => $auth->getCurrentUserId()
            ]);
            $auth->logUserAction($auth->getCurrentUserId(), "Added part: $name");
            
            // Очистка формы
            $_POST = [];
        } else {
            $errors[] = 'Ошибка при сохранении комплектующей';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить комплектующую - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <nav>
        <div class="container">
            <h2>Компьютерные комплектующие</h2>
            <div class="nav-links">
                <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!</span>
                <a href="/index.php">Главная</a>
                <a href="/parts.php">Комплектующие</a>
                <a href="/logout.php">Выход</a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <h1>Добавить комплектующую</h1>
        
        <?php if ($success): ?>
            <div class="success">
                Комплектующая успешно добавлена! 
                <a href="/parts.php">Просмотреть каталог</a> или 
                <a href="/add_part.php">добавить еще</a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="/add_part.php">
            <div class="form-group">
                <label for="name">Название:</label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" 
                       required maxlength="100">
            </div>
            
            <div class="form-group">
                <label for="category">Категория:</label>
                <select id="category" name="category" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"
                            <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="manufacturer">Производитель:</label>
                <input type="text" id="manufacturer" name="manufacturer" 
                       value="<?= htmlspecialchars($_POST['manufacturer'] ?? '') ?>" 
                       required maxlength="50">
            </div>
            
            <div class="form-group">
                <label for="price">Цена ($):</label>
                <input type="number" id="price" name="price" 
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" 
                       step="0.01" min="0.01" max="999999.99" required>
            </div>
            
            <div class="form-group">
                <label for="description">Описание (необязательно):</label>
                <textarea id="description" name="description" 
                          rows="4" maxlength="500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <small>Максимум 500 символов</small>
            </div>
            
            <button type="submit">Добавить комплектующую</button>
        </form>
    </div>
</body>
</html>