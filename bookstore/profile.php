<?php
// Підключення до бази даних
include('includes/db.php');

// Перевірка, чи користувач увійшов в систему
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримання даних профілю користувача
$query = "SELECT * FROM users WHERE id = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Отримання замовлень користувача
$query_orders = "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC";
$result_orders = mysqli_query($conn, $query_orders);

// Закриття з'єднання
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профіль користувача</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>

    <!-- Шапка сайту -->
    <header>
        <h1>Інтернет-магазин книг</h1>
        <nav>
            <ul>
                <li><a href="index.php">Головна</a></li>
                <li><a href="catalog.php">Каталог книг</a></li>
                <li><a href="profile.php">Мій профіль</a></li>
                <li><a href="logout.php">Вихід</a></li>
            </ul>
        </nav>
    </header>

    <!-- Основний контент -->
    <main>
        <section class="profile-section">
            <h2>Мій профіль</h2>
            <div class="profile-details">
                <div class="profile-avatar">
                    <img src="uploads/<?php echo $user['image']; ?>" alt="Avatar">
                </div>
                <div class="profile-info">
                    <h3><?php echo $user['username']; ?></h3>
                    <p><strong>Електронна пошта:</strong> <?php echo $user['email']; ?></p>
                    <p><strong>Вік:</strong> <?php echo $user['age']; ?></p>
                    <p><strong>Біографія:</strong> <?php echo $user['bio']; ?></p>
                </div>
                <a href="edit_profile.php" class="edit-profile-button">Редагувати профіль</a>
            </div>

            <h3>Мої замовлення</h3>
            <?php if (mysqli_num_rows($result_orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Номер замовлення</th>
                            <th>Дата замовлення</th>
                            <th>Статус</th>
                            <th>Сума</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = mysqli_fetch_assoc($result_orders)): ?>
                            <tr>
                                <td>#<?php echo $order['order_id']; ?></td>
                                <td><?php echo $order['order_date']; ?></td>
                                <td><?php echo $order['status']; ?></td>
                                <td><?php echo $order['total_price']; ?> грн</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>У вас ще немає замовлень.</p>
            <?php endif; ?>
        </section>
    </main>

    <!-- Футер -->
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>

</body>
</html>
