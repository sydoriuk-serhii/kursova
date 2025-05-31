<?php
// Файл: includes/header.php
if (session_status() == PHP_SESSION_NONE) { // Запускаємо сесію, тільки якщо вона ще не активна
    session_start();
}
// Тут можна додати підключення до db.php, якщо воно потрібне для логіки в хедері,
// але зазвичай db.php підключається у файлах сторінок перед логікою, що його використовує.
// Якщо db.php потрібен для кожного запиту (наприклад, для отримання даних користувача для хедера),
// то його можна підключити тут:
// include_once('db.php'); // або include_once(__DIR__ . '/db.php'); для надійності шляху
?>
    <!DOCTYPE html>
    <html lang="uk">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Інтернет-магазин книг"; // Динамічний заголовок вкладки ?></title>
        <?php // ПІДКЛЮЧЕННЯ ОСНОВНОГО ФАЙЛУ СТИЛІВ ?>
        <link rel="stylesheet" href="css/style.css"> <?php // ?>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"> <?php // Підключення шрифту Roboto ?>
        <?php
        // Динамічне підключення CSS для конкретної сторінки
        // Отримуємо ім'я поточного файлу, наприклад, "catalog.php"
        $current_page_css = basename($_SERVER['PHP_SELF'], '.php'); // "catalog"
        // Формуємо шлях до CSS-файлу сторінки
        $page_specific_css_file = "css/" . $current_page_css . ".css"; // "css/catalog.css"

        // Перевіряємо, чи існує такий CSS-файл, і якщо так - підключаємо його
        if (file_exists($page_specific_css_file)) {
            echo '<link rel="stylesheet" href="' . $page_specific_css_file . '?' . filemtime($page_specific_css_file) . '">'; // Додаємо мітку часу для уникнення кешування
        }
        ?>
        <?php // Сюди можна додавати інші загальні для всіх сторінок теги <link> або <meta> ?>
    </head>
<body>
    <header> <?php // ?>
        <h1><a href="index.php" class="site-title-link">Інтернет-магазин книг</a></h1> <?php // ?>
        <nav>
            <ul>
                <li><a href="index.php" <?php if(basename($_SERVER['PHP_SELF']) == 'index.php') echo 'class="active"'; ?>>Головна</a></li> <?php // ?>
                <li><a href="catalog.php" <?php if(basename($_SERVER['PHP_SELF']) == 'catalog.php') echo 'class="active"'; ?>>Каталог книг</a></li> <?php // ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li><a href="admin_panel.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_panel.php') echo 'class="active"'; ?>>Адмін-панель</a></li> <?php // ?>
                    <?php else: ?>
                        <li><a href="profile.php" <?php if(basename($_SERVER['PHP_SELF']) == 'profile.php') echo 'class="active"'; ?>>Мій профіль</a></li> <?php // ?>
                        <li><a href="order.php" <?php if(basename($_SERVER['PHP_SELF']) == 'order.php') echo 'class="active"'; ?>>Мої замовлення</a></li> <?php // ?>
                    <?php endif; ?>
                    <li><a href="cart.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cart.php') echo 'class="active"'; ?>>Кошик</a></li> <?php // ?>
                    <li><a href="logout.php">Вийти</a></li> <?php // ?>
                <?php else: ?>
                    <li><a href="login.php" <?php if(basename($_SERVER['PHP_SELF']) == 'login.php') echo 'class="active"'; ?>>Вхід</a></li> <?php // ?>
                    <li><a href="register.php" <?php if(basename($_SERVER['PHP_SELF']) == 'register.php') echo 'class="active"'; ?>>Реєстрація</a></li> <?php // ?>
                    <li><a href="cart.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cart.php') echo 'class="active"'; ?>>Кошик</a></li> <?php // ?>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
<main class="site-main-content"> <?php // ?>
<?php // Основний контент кожної сторінки буде тут ?>