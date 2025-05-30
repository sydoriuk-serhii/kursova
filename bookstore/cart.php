<?php
// Підключення до бази даних
include('includes/db.php');
session_start();

// Перевірка, чи є кошик в сесії
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: catalog.php?message=Ваш кошик порожній.");
    exit();
}

// Функція для обчислення загальної вартості кошика
function calculateTotal($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'];
    }
    return $total;
}

// Якщо користувач видаляє книгу з кошика
if (isset($_GET['remove_id'])) {
    $remove_id = $_GET['remove_id'];

    // Видалення книги з кошика
    foreach ($_SESSION['cart'] as $index => $item) {
        if ($item['id'] == $remove_id) {
            unset($_SESSION['cart'][$index]);
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Перевстановлюємо індекси масиву
            header("Location: cart.php?message=Книга видалена з кошика.");
            exit();
        }
    }
}

// Закриття з'єднання з базою даних
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ваш кошик - Інтернет-магазин книг</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>

    <!-- Шапка сайту -->
    <header>
        <h1>Інтернет-магазин книг</h1>
        <nav>
            <ul>
                <li><a href="index.php">Головна</a></li>
                <li><a href="catalog.php">Каталог книг</a></li>
                <li><a href="login.php">Вхід</a></li>
                <li><a href="register.php">Реєстрація</a></li>
            </ul>
        </nav>
    </header>

    <!-- Основний контент -->
    <main>
        <section class="cart">
            <h2>Ваш кошик</h2>

            <!-- Повідомлення -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert"><?php echo htmlspecialchars($_GET['message']); ?></div>
            <?php endif; ?>

            <!-- Список книг в кошику -->
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <img src="uploads/<?php echo $item['image']; ?>" alt="book image">
                        </div>
                        <div class="cart-item-info">
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p><strong>Автор:</strong> <?php echo htmlspecialchars($item['author']); ?></p>
                            <p><strong>Ціна:</strong> <?php echo number_format($item['price'], 2); ?> грн.</p>
                            <a href="cart.php?remove_id=<?php echo $item['id']; ?>" class="remove-item">Видалити</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Загальна сума -->
            <div class="cart-total">
                <h3>Загальна сума: <?php echo number_format(calculateTotal($_SESSION['cart']), 2); ?> грн.</h3>
            </div>

            <!-- Кнопка оформлення замовлення -->
            <div class="cart-actions">
                <a href="checkout.php" class="btn-checkout">Оформити замовлення</a>
                <a href="catalog.php" class="btn-continue">Продовжити покупки</a>
            </div>
        </section>
    </main>

    <!-- Футер -->
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>

</body>
</html>
