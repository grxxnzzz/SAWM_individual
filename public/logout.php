<?php
/**
 * Выход из системы
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Выход
$auth->logout();

// Перенаправление на страницу входа
header('Location: /login.php');
exit;
?>