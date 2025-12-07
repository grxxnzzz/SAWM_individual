<?php
/**
 * Страница регистрации
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Если уже авторизован - перенаправление
if ($auth->isAuthenticated()) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получение и очистка данных
    $username = Validator::sanitize($_POST['username'] ?? '');
    $email = Validator::sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; // Пароль не санитизируем
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Валидация
    $validator = new Validator();
    
    $validator->required($username, 'Имя пользователя');
    $validator->username($username);
    
    $validator->required($email, 'Email');
    $validator->email($email);
    
    $validator->required($password, 'Пароль');
    $validator->password($password);
    
    if ($password !== $confirmPassword) {
        $validator->getErrors()[] = 'Пароли не совпадают';
    }
    
    $errors = $validator->getErrors();
    
    // Регистрация если нет ошибок
    if (empty($errors)) {
        $result = $auth->register($username, $email, $password);
        
        if ($result === true) {
            $success = true;
        } else {
            $errors[] = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Регистрация</h1>
        
        <?php if ($success): ?>
            <div class="success">
                Регистрация успешна! <a href="/login.php">Войти</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="/register.php">
                <div class="form-group">
                    <label for="username">Имя пользователя:</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    <small>3-20 символов (буквы, цифры, подчеркивание)</small>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required>
                    <small>Минимум <?= PASSWORD_MIN_LENGTH ?> символов, должен содержать заглавные и строчные буквы, цифры и спецсимволы</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit">Зарегистрироваться</button>
            </form>
            
            <p>Уже есть аккаунт? <a href="/login.php">Войти</a></p>
        <?php endif; ?>
    </div>
</body>
</html>