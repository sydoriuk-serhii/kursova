<?php
// Файл: includes/header.php
if (session_status() == PHP_SESSION_NONE) { // Запускаємо сесію, тільки якщо вона ще не активна
    session_start();
}

// Опціонально: Лічильник товарів у кошику
$cart_item_count = 0;
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item_in_cart) { // Змінив ім'я змінної, щоб уникнути конфлікту, якщо $item використовується нижче
        $cart_item_count += isset($item_in_cart['quantity']) ? (int)$item_in_cart['quantity'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : "Інтернет-магазин книг"; ?></title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime('css/style.css'); ?>"> <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <?php
    // Динамічне підключення CSS для конкретної сторінки
    $current_page_css = basename($_SERVER['PHP_SELF'], '.php');
    $page_specific_css_file = "css/" . $current_page_css . ".css";

    if (file_exists($page_specific_css_file)) {
        echo '<link rel="stylesheet" href="' . $page_specific_css_file . '?' . filemtime($page_specific_css_file) . '">';
    }
    ?>
</head>
<body>
<header>
    <h1><a href="index.php" class="site-title-link">Інтернет-магазин книг</a></h1>
    <nav>
        <ul>
            <li><a href="index.php" <?php if(basename($_SERVER['PHP_SELF']) == 'index.php') echo 'class="active"'; ?>>Головна</a></li>
            <li><a href="catalog.php" <?php if(basename($_SERVER['PHP_SELF']) == 'catalog.php') echo 'class="active"'; ?>>Каталог книг</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <li><a href="admin_panel.php" <?php if(basename($_SERVER['PHP_SELF']) == 'admin_panel.php') echo 'class="active"'; ?>>Адмін-панель</a></li>
                <?php else: ?>
                    <li><a href="profile.php" <?php if(basename($_SERVER['PHP_SELF']) == 'profile.php') echo 'class="active"'; ?>>Мій профіль</a></li>
                    <li><a href="order.php" <?php if(basename($_SERVER['PHP_SELF']) == 'order.php') echo 'class="active"'; ?>>Мої замовлення</a></li>
                <?php endif; ?>
                <li><a href="cart.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cart.php') echo 'class="active"'; ?>>Кошик <?php if ($cart_item_count > 0) echo '<span class="cart-count">(' . $cart_item_count . ')</span>'; ?></a></li>
                <li><a href="logout.php">Вийти</a></li>
            <?php else: ?>
                <li><a href="login.php" <?php if(basename($_SERVER['PHP_SELF']) == 'login.php') echo 'class="active"'; ?>>Вхід</a></li>
                <li><a href="register.php" <?php if(basename($_SERVER['PHP_SELF']) == 'register.php') echo 'class="active"'; ?>>Реєстрація</a></li>
                <li><a href="cart.php" <?php if(basename($_SERVER['PHP_SELF']) == 'cart.php') echo 'class="active"'; ?>>Кошик <?php if ($cart_item_count > 0) echo '<span class="cart-count">(' . $cart_item_count . ')</span>'; ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
<main class="site-main-content">