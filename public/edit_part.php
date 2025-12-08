<?php
/**
 * Редактирование комплектующей
 * Доступно только менеджерам и администраторам
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Требуется роль менеджера или администратора
$auth->requireAnyRole(['manager', 'admin']);

$partId = intval($_GET['id'] ?? 0);
$errors = [];
$success = false;

// Получаем данные товара
$part = $db->selectOne(
    'SELECT * FROM parts WHERE id = ?',
    [$partId]
);

if (!$part) {
    header('Location: /parts.php');
    exit;
}

// Проверяем права (только создатель или администратор может редактировать)
if ($part['created_by'] != $auth->getCurrentUserId() && !$auth->hasRole('admin')) {
    header('Location: /parts.php');
    exit;
}

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
    
    if (empty($errors)) {
        $result = $db->execute(
            'UPDATE parts SET name = ?, category = ?, manufacturer = ?, price = ?, description = ? WHERE id = ?',
            [$name, $category, $manufacturer, $price, $description, $partId]
        );
        
        if ($result) {
            $success = true;
            Logger::info('Part updated', [
                'part_id' => $partId,
                'user_id' => $auth->getCurrentUserId()
            ]);
            $auth->logUserAction($auth->getCurrentUserId(), "Updated part: $name (ID: $partId)");
        } else {
            $errors[] = 'Ошибка при обновлении товара';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать товар - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Редактировать товар</h1>
        
        <?php if ($success): ?>
            <div class="success">
                Товар успешно обновлен! 
                <a href="/parts.php">Вернуться к списку</a>
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
        
        <form method="POST" action="/edit_part.php?id=<?= $partId ?>">
            <div class="form-group">
                <label for="name">Название:</label>
                <input type="text" id="name" name="name" 
                       value="<?= htmlspecialchars($part['name']) ?>" 
                       required maxlength="100">
            </div>
            
            <div class="form-group">
                <label for="category">Категория:</label>
                <select id="category" name="category" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>"
                            <?= ($part['category'] === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="manufacturer">Производитель:</label>
                <input type="text" id="manufacturer" name="manufacturer" 
                       value="<?= htmlspecialchars($part['manufacturer']) ?>" 
                       required maxlength="50">
            </div>
            
            <div class="form-group">
                <label for="price">Цена ($):</label>
                <input type="number" id="price" name="price" 
                       value="<?= htmlspecialchars($part['price']) ?>" 
                       step="0.01" min="0.01" max="999999.99" required>
            </div>
            
            <div class="form-group">
                <label for="description">Описание:</label>
                <textarea id="description" name="description" 
                          rows="4" maxlength="500"><?= htmlspecialchars($part['description'] ?? '') ?></textarea>
                <small>Максимум 500 символов</small>
            </div>
            
            <button type="submit">Сохранить изменения</button>
            <a href="/parts.php" class="button-link">Отмена</a>
        </form>
    </div>
</body>
</html>