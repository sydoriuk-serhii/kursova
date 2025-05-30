<?php
session_start();
include('includes/db.php');

// Функція обчислення загальної суми
function calculateTotal() {
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['price']) ? $item['price'] : 0;
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $total += $price * $quantity;
        }
    }
    return $total;
}

// Обробка POST-запиту
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $user_id = $_SESSION['user_id'] ?? null;
    $total = calculateTotal();

    // Зберігаємо замовлення
    $order_sql = "INSERT INTO orders (user_id, name, email, address, phone, total) 
                  VALUES ('$user_id', '$name', '$email', '$address', '$phone', '$total')";
    mysqli_query($conn, $order_sql);
    $order_id = mysqli_insert_id($conn);

    // Зберігаємо товари замовлення
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $book_id => $item) {
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
            $price = isset($item['price']) ? $item['price'] : 0;
            $item_sql = "INSERT INTO order_items (order_id, book_id, quantity, price) 
                         VALUES ('$order_id', '$book_id', '$quantity', '$price')";
            mysqli_query($conn, $item_sql);
        }
    }

    // Очищення кошика
    unset($_SESSION['cart']);

    // Перенаправлення на головну сторінку
    header("Location: index.php?success=true");
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Оформлення замовлення</title>
    <link rel="stylesheet" href="css/checkout.css">
</head>
<body>
    <header>
        <h1>Оформлення замовлення</h1>
    </header>

    <main>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
            <p class="success-message">Замовлення успішно оформлено! Ви будете перенаправлені на головну сторінку.</p>
        <?php else: ?>
            <form action="checkout.php" method="POST" class="checkout-form">
                <label>Ім'я:</label>
                <input type="text" name="name" required>

                <label>Електронна пошта:</label>
                <input type="email" name="email" required>

                <label>Адреса доставки:</label>
                <textarea name="address" required></textarea>

                <label>Телефон:</label>
                <input type="text" name="phone" required>

                <p><strong>Загальна сума:</strong> <?php echo number_format(calculateTotal(), 2); ?> грн</p>

                <button type="submit">Підтвердити замовлення</button>
            </form>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>
</body>
</html>
