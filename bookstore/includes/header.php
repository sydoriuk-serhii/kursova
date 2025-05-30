<?php
include('includes/db.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Інтернет-магазин книг"; ?></title>
    <link rel="stylesheet" href="css/style.css"> <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

</head>
<body>
<header>
    <h1><a href="index.php" class="site-title-link">Інтернет-магазин книг</a></h1>
    <nav>
        <ul>
            <li><a href="index.php">Головна</a></li>
            <li><a href="catalog.php">Каталог книг</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin_panel.php">Адмін-панель</a></li>
                <?php else: ?>
                    <li><a href="profile.php">Мій профіль</a></li>
                    <li><a href="order.php">Мої замовлення</a></li>
                <?php endif; ?>
                <li><a href="cart.php">Кошик</a></li>
                <li><a href="logout.php">Вийти</a></li>
            <?php else: ?>
                <li><a href="login.php">Вхід</a></li>
                <li><a href="register.php">Реєстрація</a></li>
                <li><a href="cart.php">Кошик</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="site-main-content">