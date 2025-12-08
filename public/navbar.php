<?php
/**
 * Навигационная панель
 * Показывается на всех страницах
 */
?>
<nav>
    <div class="container">
        <h2>Компьютерные комплектующие</h2>
        <div class="nav-links">
            <?php if ($auth->isAuthenticated()): ?>
                <span>Привет, <?= htmlspecialchars($_SESSION['username']) ?>!
                    <span class="role-badge role-<?= htmlspecialchars($_SESSION['role'] ?? 'guest') ?>">
                        <?= htmlspecialchars($_SESSION['role'] ?? 'guest') ?>
                    </span>
                </span>
            <?php endif; ?>
            
            <a href="/index.php">Главная</a>
            <a href="/parts.php">Комплектующие</a>
            
            <?php if ($auth->isAuthenticated() && in_array($_SESSION['role'] ?? '', ['manager', 'admin'])): ?>
                <a href="/add_part.php">Добавить товар</a>
            <?php endif; ?>
            
            <?php if ($auth->isAuthenticated() && $_SESSION['role'] === 'admin'): ?>
                <a href="/admin.php">Админ-панель</a>
            <?php endif; ?>
            
            <?php if ($auth->isAuthenticated()): ?>
                <a href="/logout.php">Выход</a>
            <?php else: ?>
                <a href="/login.php">Вход</a>
                <a href="/register.php">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<style>
.nav-links .role-badge {
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 5px;
}
</style>