<?php
/**
 * Админ-панель
 * Доступ только для администраторов
 */

require_once '../config/database.php';
require_once '../core/Database.php';
require_once '../core/Logger.php';
require_once '../core/Validator.php';
require_once '../core/Auth.php';

$db = new Database();
$auth = new Auth($db);

// Проверяем, что пользователь администратор
if (!$auth->isAuthenticated() || !$auth->hasRole(ROLE_ADMIN)) {
    header('Location: /index.php');
    exit;
}

$errors = [];
$success = '';

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_user':
            $userId = intval($_POST['user_id'] ?? 0);
            if ($userId && $userId !== $auth->getCurrentUserId()) {
                $result = $db->execute('DELETE FROM users WHERE id = ?', [$userId]);
                if ($result) {
                    $success = 'Пользователь удален';
                    Logger::security('User deleted by admin', [
                        'admin_id' => $auth->getCurrentUserId(),
                        'user_id' => $userId
                    ]);
                } else {
                    $errors[] = 'Ошибка при удалении пользователя';
                }
            } else {
                $errors[] = 'Нельзя удалить себя или несуществующего пользователя';
            }
            break;
            
        case 'reset_password':
            $userId = intval($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            
            if ($userId && strlen($newPassword) >= 8) {
                $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
                    'memory_cost' => 65536,
                    'time_cost' => 4,
                    'threads' => 3
                ]);
                
                $result = $db->execute(
                    'UPDATE users SET password_hash = ?, password_changed_at = CURRENT_TIMESTAMP WHERE id = ?',
                    [$hashedPassword, $userId]
                );
                
                if ($result) {
                    $success = 'Пароль пользователя сброшен';
                    Logger::security('User password reset by admin', [
                        'admin_id' => $auth->getCurrentUserId(),
                        'user_id' => $userId
                    ]);
                } else {
                    $errors[] = 'Ошибка при сбросе пароля';
                }
            } else {
                $errors[] = 'Некорректные данные';
            }
            break;
            
        case 'change_role':
            $userId = intval($_POST['user_id'] ?? 0);
            $newRole = $_POST['new_role'] ?? '';
            
            if ($userId && in_array($newRole, [ROLE_GUEST, ROLE_MANAGER, ROLE_ADMIN])) {
                $result = $db->execute(
                    'UPDATE users SET role = ? WHERE id = ?',
                    [$newRole, $userId]
                );
                
                if ($result) {
                    $success = 'Роль пользователя изменена';
                    Logger::security('User role changed by admin', [
                        'admin_id' => $auth->getCurrentUserId(),
                        'user_id' => $userId,
                        'new_role' => $newRole
                    ]);
                } else {
                    $errors[] = 'Ошибка при изменении роли';
                }
            } else {
                $errors[] = 'Некорректные данные';
            }
            break;
    }
}

// Получаем список пользователей
$users = $db->select('SELECT id, username, email, role, created_at FROM users ORDER BY id');

// Читаем логи
$errorLog = '';
$securityLog = '';

if (file_exists(LOG_PATH . 'errors.log')) {
    $errorLog = file_get_contents(LOG_PATH . 'errors.log');
    if (strlen($errorLog) > 100000) { // Ограничиваем размер
        $errorLog = substr($errorLog, -100000);
    }
}

if (file_exists(LOG_PATH . 'security.log')) {
    $securityLog = file_get_contents(LOG_PATH . 'security.log');
    if (strlen($securityLog) > 100000) {
        $securityLog = substr($securityLog, -100000);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - Компьютерные комплектующие</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .admin-section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .log-content {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .user-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .user-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .user-actions {
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .user-actions form {
            margin: 0;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .role-guest { background: #6c757d; color: white; }
        .role-manager { background: #17a2b8; color: white; }
        .role-admin { background: #dc3545; color: white; }
        
        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        
        .tab-button {
            padding: 10px 20px;
            background: #eee;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .tab-button.active {
            background: #007bff;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .log-filter {
            margin-bottom: 15px;
        }
        
        .log-filter input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #007bff;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="container">
        <h1>Админ-панель</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <!-- Статистика -->
        <div class="stats-grid">
            <?php
            $totalUsers = $db->selectOne('SELECT COUNT(*) as count FROM users')['count'] ?? 0;
            $totalParts = $db->selectOne('SELECT COUNT(*) as count FROM parts')['count'] ?? 0;
            $totalLogs = $db->selectOne('SELECT COUNT(*) as count FROM user_actions')['count'] ?? 0;
            ?>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($totalUsers) ?></div>
                <div class="stat-label">Пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($totalParts) ?></div>
                <div class="stat-label">Комплектующих</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($totalLogs) ?></div>
                <div class="stat-label">Записей в логах</div>
            </div>
        </div>
        
        <!-- Вкладки -->
        <div class="tab-buttons">
            <button class="tab-button active" onclick="switchTab('users')">Пользователи</button>
            <button class="tab-button" onclick="switchTab('errorLogs')">Логи ошибок</button>
            <button class="tab-button" onclick="switchTab('securityLogs')">Логи безопасности</button>
        </div>
        
        <!-- Вкладка: Пользователи -->
        <div id="usersTab" class="tab-content active">
            <div class="admin-section">
                <h2>Управление пользователями</h2>
                
                <div class="user-list">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <h3><?= htmlspecialchars($user['username']) ?>
                                <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </h3>
                            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                            <p><strong>Зарегистрирован:</strong> <?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></p>
                            
                            <div class="user-actions">
                                <!-- Изменение роли -->
                                <form method="POST" action="/admin.php" onsubmit="return confirm('Изменить роль пользователя?')">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="new_role" style="width: 100%; margin-bottom: 5px;">
                                        <option value="guest" <?= $user['role'] == 'guest' ? 'selected' : '' ?>>Гость</option>
                                        <option value="manager" <?= $user['role'] == 'manager' ? 'selected' : '' ?>>Менеджер</option>
                                        <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Администратор</option>
                                    </select>
                                    <button type="submit" style="width: 100%;">Изменить роль</button>
                                </form>
                                
                                <!-- Сброс пароля -->
                                <form method="POST" action="/admin.php" onsubmit="return confirm('Сбросить пароль пользователя?')">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="text" name="new_password" placeholder="Новый пароль" style="width: 100%; margin-bottom: 5px;" required>
                                    <button type="submit" style="width: 100%; background: #ffc107;">Сбросить пароль</button>
                                </form>
                                
                                <!-- Удаление (кроме себя) -->
                                <?php if ($user['id'] != $auth->getCurrentUserId()): ?>
                                    <form method="POST" action="/admin.php" onsubmit="return confirm('Удалить пользователя? Это действие нельзя отменить.')">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" style="width: 100%; background: #dc3545; color: white;">Удалить пользователя</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Вкладка: Логи ошибок -->
        <div id="errorLogsTab" class="tab-content">
            <div class="admin-section">
                <h2>Логи ошибок</h2>
                <div class="log-filter">
                    <input type="text" id="errorFilter" placeholder="Фильтр по тексту..." onkeyup="filterLog('errorLogContent', 'errorFilter')">
                </div>
                <div id="errorLogContent" class="log-content"><?= htmlspecialchars($errorLog) ?></div>
            </div>
        </div>
        
        <!-- Вкладка: Логи безопасности -->
        <div id="securityLogsTab" class="tab-content">
            <div class="admin-section">
                <h2>Логи безопасности</h2>
                <div class="log-filter">
                    <input type="text" id="securityFilter" placeholder="Фильтр по тексту..." onkeyup="filterLog('securityLogContent', 'securityFilter')">
                </div>
                <div id="securityLogContent" class="log-content"><?= htmlspecialchars($securityLog) ?></div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Скрыть все вкладки
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Убрать активный класс у всех кнопок
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Показать выбранную вкладку
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Активировать кнопку
            event.target.classList.add('active');
        }
        
        function filterLog(logId, filterId) {
            const filter = document.getElementById(filterId).value.toLowerCase();
            const logContent = document.getElementById(logId);
            const lines = logContent.textContent.split('\n');
            
            if (!filter) {
                logContent.textContent = lines.join('\n');
                return;
            }
            
            const filteredLines = lines.filter(line => 
                line.toLowerCase().includes(filter)
            );
            
            logContent.textContent = filteredLines.join('\n');
        }
        
        // Автоматическое обновление логов каждые 30 секунд
        setInterval(() => {
            const activeTab = document.querySelector('.tab-content.active').id;
            if (activeTab === 'errorLogsTab' || activeTab === 'securityLogsTab') {
                // Здесь можно добавить AJAX запрос для обновления логов
                // Пока просто перезагружаем страницу
                // window.location.reload();
            }
        }, 30000);
    </script>
</body>
</html>