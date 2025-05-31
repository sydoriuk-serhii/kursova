<?php
// Файл: order.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії (header.php це робить)
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Перевірка, чи користувач увійшов в систему та чи не є адміном
if (!isset($_SESSION['user_id'])) { //
    header("Location: login.php?message=" . urlencode("Будь ласка, увійдіть, щоб переглянути ваші замовлення.")); //
    exit; //
}
if ($_SESSION['role'] === 'admin') { //
    header("Location: admin_panel.php"); //
    exit; //
}

$user_id = $_SESSION['user_id']; //

// 4. Видалення замовлення, якщо передано delete_id
if (isset($_GET['delete_id'])) { //
    $delete_id = intval($_GET['delete_id']); //

    $check_order_query = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?"); //
    $check_order_query->bind_param("ii", $delete_id, $user_id); //
    $check_order_query->execute(); //
    $check_result = $check_order_query->get_result(); //

    if ($check_result->num_rows > 0) { //
        mysqli_begin_transaction($conn); //
        try {
            $delete_items_query = $conn->prepare("DELETE FROM order_items WHERE order_id = ?"); //
            $delete_items_query->bind_param("i", $delete_id); //
            $delete_items_query->execute(); //
            $delete_items_query->close(); //

            $delete_order_query = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?"); //
            $delete_order_query->bind_param("ii", $delete_id, $user_id); //
            $delete_order_query->execute(); //

            if ($delete_order_query->affected_rows > 0) { //
                mysqli_commit($conn); //
                header("Location: order.php?message=" . urlencode("Замовлення #{$delete_id} успішно видалено.")); //
            } else {
                mysqli_rollback($conn); //
                header("Location: order.php?message=" . urlencode("Помилка: Не вдалося видалити замовлення.")); //
            }
            $delete_order_query->close(); //
        } catch (mysqli_sql_exception $exception) {
            mysqli_rollback($conn); //
            error_log("Помилка видалення замовлення: " . $exception->getMessage()); //
            header("Location: order.php?message=" . urlencode("Сталася помилка бази даних при видаленні замовлення.")); //
        }
        exit; //
    } else {
        header("Location: order.php?message=" . urlencode("Помилка: Замовлення не знайдено або у вас немає прав на його видалення.")); //
        exit; //
    }
    $check_order_query->close(); //
}

// 5. Отримання замовлень поточного авторизованого користувача
$order_query_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC"; //
$order_query = $conn->prepare($order_query_sql); //
$order_query->bind_param("i", $user_id); //
$order_query->execute(); //
$order_result = $order_query->get_result(); //


// 6. Встановлюємо заголовок сторінки
$page_title = "Мої замовлення - Інтернет-магазин книг"; //

// 7. Підключаємо хедер
// header.php тепер автоматично підключає css/style.css та css/order.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 8. Рядок <link rel="stylesheet" href="css/order.css"> ВИДАЛЕНО ?>

<?php // 9. HTML-розмітка сторінки ?>
<?php // Змінюємо клас контейнера заголовка на уніфікований ?>
    <div class="section-title-container"><h2>Ваші замовлення</h2></div> <?php // ?>

<?php if (isset($_GET['message'])): ?>
    <?php // Клас .message вже є в style.css, .success-message також ?>
    <p class="message success-message"><?php echo htmlspecialchars($_GET['message']); ?></p> <?php // ?>
<?php endif; ?>

<?php if ($order_result && $order_result->num_rows > 0): ?>
    <?php // Змінюємо класи для контейнера таблиці та самої таблиці ?>
    <div class="data-table-container"> <?php // ?>
        <table class="data-table user-orders-table"> <?php // Додаємо user-orders-table для можливих мікро-налаштувань, якщо .data-table недостатньо ?>
            <thead>
            <tr>
                <th>ID</th>
                <th>Ім'я отримувача</th>
                <th>Email</th>
                <th>Адреса</th>
                <th>Телефон</th>
                <th>Сума</th>
                <th>Дата</th>
                <th>Склад замовлення</th>
                <th>Дія</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($order = $order_result->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                    <td><?php echo htmlspecialchars($order['email']); ?></td>
                    <td><?php echo htmlspecialchars($order['address']); ?></td>
                    <td><?php echo htmlspecialchars($order['phone']); ?></td>
                    <td><?php echo number_format($order['total'], 2); ?> грн</td>
                    <td><?php echo date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                    <td>
                        <?php
                        $order_items_query = $conn->prepare(
                            "SELECT oi.quantity, oi.price, b.title 
                             FROM order_items oi 
                             JOIN books b ON oi.book_id = b.id 
                             WHERE oi.order_id = ?"
                        ); //
                        $order_items_query->bind_param("i", $order['id']); //
                        $order_items_query->execute(); //
                        $order_items_result = $order_items_query->get_result(); //
                        if ($order_items_result->num_rows > 0) { //
                            echo "<ul>"; //
                            while ($item = $order_items_result->fetch_assoc()) { //
                                echo "<li>" . htmlspecialchars($item['title']) . " (" . $item['quantity'] . " шт. x " . number_format($item['price'], 2) . " грн) = " . number_format($item['price'] * $item['quantity'], 2) . " грн</li>"; //
                            }
                            echo "</ul>"; //
                        } else {
                            echo "Деталі не знайдено."; //
                        }
                        $order_items_query->close(); //
                        ?>
                    </td>
                    <td>
                        <?php // Змінюємо клас посилання на видалення ?>
                        <a href="order.php?delete_id=<?php echo $order['id']; ?>" class="action-link-danger" onclick="return confirm('Ви впевнені, що хочете видалити замовлення #<?php echo $order['id']; ?>? Ця дія незворотна.')">❌ Видалити</a> <?php // ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <?php // Змінюємо клас для повідомлення про відсутність замовлень ?>
    <p class="no-items-info">У вас поки немає замовлень. <a href="catalog.php">Перейти до каталогу?</a></p> <?php // ?>
<?php endif; ?>

<?php
if(isset($order_query)) $order_query->close(); //

// 10. Закриваємо з'єднання з БД
if (isset($conn)) { //
    mysqli_close($conn); //
}

// 11. Підключаємо футер
include_once('includes/footer.php'); //
?>