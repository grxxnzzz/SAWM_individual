<?php
/**
 * Страница ошибки
 * Отображается при критических ошибках без раскрытия технической информации
 */

session_start();

$errorMessage = $_SESSION['error_message'] ?? 'Произошла непредвиденная ошибка';
unset($_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ошибка - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <div class="error-page">
            <h1>Упс! Что-то пошло не так</h1>
            
            <div class="error">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
            
            <p>Мы уже работаем над решением проблемы. Пожалуйста, попробуйте позже.</p>
            
            <div class="actions">
                <a href="/index.php" class="button">На главную</a>
                <a href="/login.php" class="button">Войти</a>
            </div>
        </div>
    </div>
</body>
</html>