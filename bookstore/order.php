<?php
session_start();
include('includes/db.php');

// Перевірка входу
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем email пользователя из таблицы users
$query = "SELECT email FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);
$user_email = $user['email'];

// Видалення замовлення, якщо передано
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    // Проверяем, что заказ принадлежит текущему пользователю с этим email
    $delete_query = "SELECT * FROM orders WHERE id = $delete_id AND email = '$user_email'";
    $delete_result = mysqli_query($conn, $delete_query);
    
    if (mysqli_num_rows($delete_result) > 0) {
        // Удаляем заказ
        mysqli_query($conn, "DELETE FROM orders WHERE id = $delete_id AND email = '$user_email'");
        header("Location: order.php");
        exit;
    } else {
        echo "<script>alert('Ви не можете видаляти цей заказ!');</script>";
    }
}

// Отримання замовлень користувача
$order_query = "SELECT * FROM orders WHERE email = '$user_email' ORDER BY created_at DESC";
$order_result = mysqli_query($conn, $order_query);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Ваші замовлення</title>
    <link rel="stylesheet" href="css/order.css">
</head>
<body>
    <header>
        <h1>Ваші замовлення</h1>
        <nav>
            <a href="catalog.php">Каталог</a> |
            <a href="logout.php">Вийти</a>
        </nav>
    </header>

    <main>
        <?php if (mysqli_num_rows($order_result) > 0): ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ім'я</th>
                        <th>Електронна пошта</th>
                        <th>Адреса доставки</th>
                        <th>Телефон</th>
                        <th>Загальна сума</th>
                        <th>Дата замовлення</th>
                        <th>Дія</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($order_result)): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= $row['name'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['address'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td><?= number_format($row['total'], 2) ?> грн</td>
                            <td><?= $row['created_at'] ?></td>
                            <td><a href="?delete_id=<?= $row['id'] ?>" onclick="return confirm('Ви впевнені?')">❌ Видалити</a></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>У вас поки немає замовлень.</p>
        <?php endif; ?>
    </main>
    
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>
</body>
</html>
