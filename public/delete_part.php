<?php
/**
 * Удаление комплектующей
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partId = intval($_POST['part_id'] ?? 0);
    
    if ($partId) {
        // Получаем информацию о товаре для логов
        $part = $db->selectOne(
            'SELECT name, created_by FROM parts WHERE id = ?',
            [$partId]
        );
        
        if ($part) {
            // Проверяем права (только создатель или администратор может удалить)
            if ($part['created_by'] == $auth->getCurrentUserId() || $auth->hasRole('admin')) {
                $result = $db->execute(
                    'DELETE FROM parts WHERE id = ?',
                    [$partId]
                );
                
                if ($result) {
                    Logger::info('Part deleted', [
                        'part_id' => $partId,
                        'part_name' => $part['name'],
                        'user_id' => $auth->getCurrentUserId()
                    ]);
                    $auth->logUserAction($auth->getCurrentUserId(), "Deleted part: {$part['name']} (ID: $partId)");
                }
            }
        }
    }
}

header('Location: /parts.php');
exit;