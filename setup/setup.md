### 1. Основной запуск
```bash
php -S localhost:8000 -t public
```

### 2. Улучшенный запуск (с правильным php.ini)
```bash
# Указываем конкретный php.ini
php -c php.ini -S localhost:8000 -t public

# Или копируем настройки в локальный php.ini
cp php.ini php-local.ini
php -c php-local.ini -S localhost:8000 -t public
```

### 3. Для Windows (PowerShell)
```powershell
# В директории проекта
php -S localhost:8000 -t public

# С проверкой версии
php --version
php -S 127.0.0.1:8000 -t public
```

### 4. Если возникают проблемы с расширениями

**Создайте локальный `php-local.ini`:**
```ini
; Минимальные настройки для работы
extension_dir = "ext"  ; Путь к расширениям

; Включаем необходимые расширения
extension=sqlite3
extension=pdo_sqlite
extension=openssl
extension=mbstring
extension=fileinfo

; Настройки сессии для безопасности
session.cookie_httponly = 1
session.use_strict_mode = 1
session.cookie_samesite = "Strict"

; Логирование ошибок
display_errors = 1  ; 1 для разработки
log_errors = 1
error_log = logs/php_errors.log
```

### 5. Быстрая проверка готовности

Создайте файл `public/test.php`:
```php
<?php
echo "PHP версия: " . PHP_VERSION . "<br>";
echo "Расширения:<br>";
$required = ['sqlite3', 'pdo_sqlite', 'openssl', 'mbstring', 'fileinfo'];
foreach ($required as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "✓" : "✗") . "<br>";
}
?>
```

Перейдите на `http://localhost:8000/test.php`

## 🔧 Быстрое решение частых проблем

### Проблема 1: "Class Database not found"
```bash
# Убедитесь, что вы в корне проекта
pwd  # Должно быть: /path/to/SAWM_individual

# Запускайте из корня
php -S localhost:8000 -t public
```

### Проблема 2: Ошибки с базой данных
```bash
# Проверьте права
ls -la database/

# Если файла нет, создайте
touch database/computer_parts.db
chmod 664 database/computer_parts.db
```

### Проблема 3: Сессии не работают
```bash
# Создайте директорию для сессий
mkdir -p /tmp/php_sessions
chmod 777 /tmp/php_sessions

# В php.ini добавьте
session.save_path = "/tmp/php_sessions"
```

### Проблема 4: Расширения не загружаются
```bash
# Проверьте какие расширения есть
php -m | grep -E "sqlite|pdo|openssl"

# На Windows проверьте путь к extensions
php --ini
# Посмотрите где extension_dir
```

## 🎯 Оптимальная команда для запуска

Создайте файл `start.sh` (Linux/Mac) или `start.bat` (Windows):

**start.sh:**
```bash
#!/bin/bash
echo "PHP версия: $(php --version | head -1)"
echo ""

# Проверяем необходимые расширения
required_ext=("pdo_sqlite" "sqlite3" "openssl" "mbstring")
missing_ext=()

for ext in "${required_ext[@]}"; do
    if ! php -m | grep -q "$ext"; then
        missing_ext+=("$ext")
    fi
done

if [ ${#missing_ext[@]} -ne 0 ]; then
    echo "❌ Отсутствуют расширения: ${missing_ext[*]}"
    echo "Добавьте их в php.ini"
    exit 1
fi

echo "Все расширения доступны"
echo "Создание необходимых директорий..."

# Создаем директории если их нет
mkdir -p logs
mkdir -p database

# Проверяем права
if [ ! -w "logs" ]; then
    chmod 755 logs
fi
if [ ! -w "database" ]; then
    chmod 755 database
fi

echo "Запуск сервера на http://localhost:8000"
echo "Нажмите Ctrl+C для остановки"
echo "========================================"

php -S localhost:8000 -t public
```

**start.bat (Windows):**
```batch
@echo off
echo Запуск SAWM приложения...
php --version | findstr /B "PHP"
echo.

echo Проверка расширений...
php -m | findstr "pdo_sqlite" >nul && echo ✓ pdo_sqlite || echo ✗ pdo_sqlite
php -m | findstr "sqlite3" >nul && echo ✓ sqlite3 || echo ✗ sqlite3
php -m | findstr "openssl" >nul && echo ✓ openssl || echo ✗ openssl
echo.

if not exist logs mkdir logs
if not exist database mkdir database

echo Запуск сервера на http://localhost:8000
echo Нажмите Ctrl+C для остановки
echo ========================================
php -S localhost:8000 -t public
```

## 📱 Доступ с других устройств

Чтобы сделать сервер доступным в локальной сети:
```bash
# Доступен по IP вашего компьютера
php -S 0.0.0.0:8000 -t public
# Тогда можно зайти с телефона по http://[ваш-ip]:8000
```

## 🔍 Проверка работы приложения

После запуска:

1. **Главная страница:** `http://localhost:8000`
   - Должна перенаправить на логин
   
2. **Регистрация:** `http://localhost:8000/register.php`
   - Создайте тестового пользователя
   
3. **Вход:** `http://localhost:8000/login.php`
   - Войдите с созданным пользователем
   
4. **Проверка безопасности:**
   - Попробуйте SQL-инъекцию: `' OR '1'='1`
   - Попробуйте XSS: `<script>alert('test')</script>`
   - Проверьте логи в `logs/security.log`

## ⚡ Профилирование и отладка

Для отладки добавьте в начало `index.php`:
```php
<?php
// Включить все ошибки для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
```

## 📊 Полезные команды для мониторинга

```bash
# Следить за логами ошибок
tail -f logs/errors.log

# Следить за логами безопасности
tail -f logs/security.log

# Проверить занятые порты
netstat -tulpn | grep :8000

# Проверить процесс PHP
ps aux | grep php
```

## 🎉 Готовые команды для копирования

**Для быстрого старта просто выполните:**
```bash
# Клонируйте проект (если еще не сделали)
git clone https://github.com/grxxnzzz/SAWM_individual.git
cd SAWM_individual

# Создайте необходимые директории
mkdir -p logs database

# Запустите сервер
php -S localhost:8000 -t public
```