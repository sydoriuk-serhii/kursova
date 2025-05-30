<?php
// session_start(); // Вже в header.php
include('includes/db.php');

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Повідомлення після успішного додавання/видалення
$message = '';
if (isset($_GET['success_add'])) {
    $message = "Книгу успішно додано!";
}
if (isset($_GET['success_delete'])) {
    $message = "Замовлення успішно видалено!";
}
if (isset($_GET['error_delete'])) {
    $message = "Помилка видалення замовлення!";
}


// Запит для отримання всіх замовлень
$ordersQuery = "SELECT * FROM `orders` ORDER BY created_at DESC";
$orders_result = mysqli_query($conn, $ordersQuery);


$page_title = "Панель адміністратора - Інтернет-магазин книг";
include('includes/header.php'); // Використовуємо загальний хедер
?>
    <link rel="stylesheet" href="css/admin_panel.css">

    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="history.back()" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Назад</button>
    </div>

    <section class="admin-content">
        <h2>Управління книгарнею</h2>

        <?php if ($message): ?>
            <p class="message" style="text-align:center; padding:10px; background-color: #e6fffa; border: 1px solid #00bfa5; color: #00796b; border-radius: 5px; margin-bottom:15px;"><?php echo $message; ?></p>
        <?php endif; ?>

        <div class="admin-section">
            <h3>Додати нову книгу</h3>
            <form action="add_book.php" method="POST" enctype="multipart/form-data" class="add-book-form">
                <label for="title">Назва:</label>
                <input type="text" name="title" id="title" required>

                <label for="author">Автор:</label>
                <input type="text" name="author" id="author" required>

                <label for="description">Опис:</label>
                <textarea name="description" id="description" rows="5" required></textarea>

                <label for="genre">Жанр:</label>
                <input type="text" name="genre" id="genre" required>

                <label for="price">Ціна (грн):</label>
                <input type="number" name="price" step="0.01" id="price" min="0" required>

                <label for="image">Зображення (jpg, jpeg, png, до 5MB):</label>
                <input type="file" name="image" id="image" accept="image/jpeg, image/png, image/jpg" required>

                <button type="submit" name="submit">Додати книгу</button>
            </form>
        </div>

        <div class="admin-section">
            <h3>Замовлення користувачів</h3>
            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                <table class="orders-table-admin">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Ім'я</th>
                        <th>Email</th>
                        <th>Адреса</th>
                        <th>Телефон</th>
                        <th>Сума</th>
                        <th>Дата</th>
                        <th>Склад</th>
                        <th>Дії</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['user_id'] ? $order['user_id'] : 'Гість'; ?></td>
                            <td><?php echo htmlspecialchars($order['name']); ?></td>
                            <td><?php echo htmlspecialchars($order['email']); ?></td>
                            <td><?php echo htmlspecialchars($order['address']); ?></td>
                            <td><?php echo htmlspecialchars($order['phone']); ?></td>
                            <td><?php echo number_format($order['total'], 2); ?> грн</td>
                            <td><?php echo date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                            <td>
                                <?php
                                $order_items_q = $conn->prepare("SELECT oi.quantity, oi.price, b.title FROM order_items oi JOIN books b ON oi.book_id = b.id WHERE oi.order_id = ?");
                                $order_items_q->bind_param("i", $order['id']);
                                $order_items_q->execute();
                                $order_items_r = $order_items_q->get_result();
                                if ($order_items_r->num_rows > 0) {
                                    echo "<ul style='padding-left: 15px;'>";
                                    while ($item = $order_items_r->fetch_assoc()) {
                                        echo "<li style='font-size:0.9em;'>" . htmlspecialchars($item['title']) . " (" . $item['quantity'] . "x" . number_format($item['price'], 2) . ")</li>";
                                    }
                                    echo "</ul>";
                                } else { echo "Немає деталей."; }
                                $order_items_q->close();
                                ?>
                            </td>
                            <td>
                                <a class="delete-link" href="delete_order.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Ви впевнені, що хочете видалити це замовлення (#<?php echo $order['id']; ?>)? Ця дія незворотна.')">❌ Видалити</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Немає замовлень для відображення.</p>
            <?php endif; ?>
        </div>
    </section>
<?php
if(isset($orders_result)) mysqli_free_result($orders_result);
mysqli_close($conn);
include('includes/footer.php');
?>