<?php
session_start();
include('includes/db.php');

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Повідомлення після успішного додавання книги
$success = isset($_GET['success']) ? "Книгу успішно додано!" : null;

// Запит для отримання всіх замовлень
$ordersQuery = "SELECT * FROM `orders`";

// Виконання запиту
$orders = mysqli_query($conn, $ordersQuery);

// Перевірка на помилки запиту
if (!$orders) {
    die('Помилка запиту: ' . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Панель адміністратора</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_panel.css">
    <style>
       
    </style>
</head>
<body>

<header>
    <h1>Панель адміністратора</h1>
    <nav>
        <ul>
            <li><a href="index.php">На сайт</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>
</header>

<main>

    <section>
        <h2>Додати нову книгу</h2>

        <?php if ($success): ?>
            <p class="success-message"><?php echo $success; ?></p>
        <?php endif; ?>

        <form action="add_book.php" method="POST" enctype="multipart/form-data">
            <label for="title">Назва:</label>
            <input type="text" name="title" id="title" required>

            <label for="author">Автор:</label>
            <input type="text" name="author" id="author" required>

            <label for="description">Опис:</label>
            <textarea name="description" id="description" required></textarea>

            <label for="genre">Жанр:</label>
            <input type="text" name="genre" id="genre" required>

            <label for="price">Ціна (грн):</label>
            <input type="number" name="price" step="0.01" id="price" required>

            <label for="image">Зображення:</label>
            <input type="file" name="image" id="image" accept="image/*" required>

            <button type="submit" name="submit">Додати книгу</button>
        </form>
    </section>

    <section>
        <h2>Замовлення користувачів</h2>

        <?php if (mysqli_num_rows($orders) > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Ім'я</th>
                    <th>Емейл</th>
                    <th>Адреса</th>
                    <th>Телефон</th>
                    <th>Сума</th>
                    <th>Дата</th>
                    <th>Дії</th>
                </tr>
                <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['name']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo htmlspecialchars($order['address']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo $order['total']; ?> грн</td>
                        <td><?php echo $order['created_at']; ?></td>
                        <td><a class="delete-link" href="delete_order.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Видалити це замовлення?')">❌</a></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>Немає замовлень.</p>
        <?php endif; ?>
    </section>

</main>

<footer>
    <p>&copy; 2025 Інтернет-магазин книг</p>
</footer>

</body>
</html>
