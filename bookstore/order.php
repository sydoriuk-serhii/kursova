<?php
// session_start(); // Вже в header.php
include('includes/db.php');

// Перевірка входу та ролі
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SESSION['role'] === 'admin') {
    // Адміністратор не повинен бачити "свої" замовлення тут, а в адмін-панелі
    header("Location: admin_panel.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримуємо email користувача з таблиці users (хоча краще фільтрувати за user_id напряму)
// Але для узгодження з вашим поточним підходом, де замовлення можуть бути не прив'язані до user_id,
// якщо вони зроблені гостем, а потім цей гість реєструється/логіниться з тим же email.
// Однак, якщо замовлення робить АВТОРИЗОВАНИЙ користувач, то user_id ПОВИНЕН бути в таблиці orders.
$query_user_email = $conn->prepare("SELECT email FROM users WHERE id = ?");
$query_user_email->bind_param("i", $user_id);
$query_user_email->execute();
$result_user_email = $query_user_email->get_result();
if ($user_data = $result_user_email->fetch_assoc()) {
    $user_email = $user_data['email'];
} else {
    // Якщо не вдалося отримати email, можливо, помилка
    $user_email = null; // Або перенаправити на вихід
}
$query_user_email->close();

// Видалення замовлення, якщо передано delete_id
if (isset($_GET['delete_id']) && $user_email) {
    $delete_id = intval($_GET['delete_id']);

    // Перевіряємо, що замовлення належить поточному користувачеві (за user_id або email)
    // Краще за user_id, якщо він є в orders.
    $check_order_query = $conn->prepare("SELECT id FROM orders WHERE id = ? AND (user_id = ? OR email = ?)");
    $check_order_query->bind_param("iis", $delete_id, $user_id, $user_email);
    $check_order_query->execute();
    $check_result = $check_order_query->get_result();

    if ($check_result->num_rows > 0) {
        // Спочатку видаляємо пов'язані order_items
        $delete_items_query = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $delete_items_query->bind_param("i", $delete_id);
        $delete_items_query->execute();
        $delete_items_query->close();

        // Потім видаляємо саме замовлення
        $delete_order_query = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $delete_order_query->bind_param("i", $delete_id);
        if ($delete_order_query->execute()) {
            header("Location: order.php?message=Замовлення успішно видалено.");
        } else {
            header("Location: order.php?message=Помилка видалення замовлення.");
        }
        $delete_order_query->close();
        exit;
    } else {
        header("Location: order.php?message=Помилка: Замовлення не знайдено або у вас немає прав на його видалення.");
        exit;
    }
    $check_order_query->close();
}

// Отримання замовлень користувача (за user_id, якщо він є, або за email)
// Пріоритет user_id
$order_query_sql = "SELECT * FROM orders WHERE user_id = ?";
if (!$user_email) { // Якщо email не вдалося отримати, хоча це малоймовірно
    $order_result = null;
} else {
    $order_query_sql .= " OR email = ?";
    $order_query_sql .= " ORDER BY created_at DESC";
    $order_query = $conn->prepare($order_query_sql);
    $order_query->bind_param("is", $user_id, $user_email);
    $order_query->execute();
    $order_result = $order_query->get_result();
}


$page_title = "Мої замовлення - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/order.css"> <div style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.location.href='profile.php'" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right:10px;">До профілю</button>
    <button onclick="history.back()" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Назад</button>
</div>

    <h2>Ваші замовлення</h2>

<?php if (isset($_GET['message'])): ?>
    <p class="message" style="text-align:center; padding:10px; background-color: #e6fffa; border: 1px solid #00bfa5; color: #00796b; border-radius: 5px; margin-bottom:15px;"><?php echo htmlspecialchars($_GET['message']); ?></p>
<?php endif; ?>

<?php if ($order_result && $order_result->num_rows > 0): ?>
    <table class="orders-table">
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
                <td><?php echo $order['id']; ?></td>
                <td><?php echo htmlspecialchars($order['name']); ?></td>
                <td><?php echo htmlspecialchars($order['email']); ?></td>
                <td><?php echo htmlspecialchars($order['address']); ?></td>
                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                <td><?php echo number_format($order['total'], 2); ?> грн</td>
                <td><?php echo date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                <td>
                    <?php
                    $order_items_query = $conn->prepare("SELECT oi.quantity, oi.price, b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
                    $order_items_query->bind_param("i", $order['id']);
                    $order_items_query->execute();
                    $order_items_result = $order_items_query->get_result();
                    if ($order_items_result->num_rows > 0) {
                        echo "<ul>";
                        while ($item = $order_items_result->fetch_assoc()) {
                            echo "<li>" . htmlspecialchars($item['title']) . " (" . $item['quantity'] . " шт.) - " . number_format($item['price'] * $item['quantity'], 2) . " грн</li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "Деталі не знайдено.";
                    }
                    $order_items_query->close();
                    ?>
                </td>
                <td>
                    <a href="order.php?delete_id=<?php echo $order['id']; ?>" class="delete-link" onclick="return confirm('Ви впевнені, що хочете видалити це замовлення?')">❌ Видалити</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>У вас поки немає замовлень.</p>
<?php endif; ?>
<?php if(isset($order_query)) $order_query->close(); ?>
<?php
mysqli_close($conn);
include('includes/footer.php');
?>