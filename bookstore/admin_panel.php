<?php
// Файл: admin_panel.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { //
    header('Location: login.php'); //
    exit; //
}

// 4. Обробка повідомлень (успіх/помилка)
$message = ''; //
if (isset($_GET['success_add'])) { //
    $message = "Книгу успішно додано!"; //
} elseif (isset($_GET['success_delete'])) { //
    $message = "Замовлення успішно видалено!"; //
} elseif (isset($_GET['error_delete'])) { //
    $message = "Помилка видалення замовлення: " . htmlspecialchars($_GET['error_delete']); //
} elseif (isset($_GET['error_add'])) { //
    $message = "Помилка додавання книги: " . htmlspecialchars($_GET['error_add']); //
}


// 5. Запит для отримання всіх замовлень
$ordersQuery = "SELECT * FROM `orders` ORDER BY created_at DESC"; //
$orders_result = mysqli_query($conn, $ordersQuery); //
if (!$orders_result) { //
    error_log("Помилка отримання замовлень в адмін-панелі: " . mysqli_error($conn)); //
    if (empty($message)) { //
        $message = "Виникла помилка при завантаженні списку замовлень."; //
    }
}

// 6. Встановлюємо заголовок сторінки
$page_title = "Панель адміністратора - Інтернет-магазин книг"; //

// 7. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/admin_panel.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 8. Рядок <link rel="stylesheet" href="css/admin_panel.css"> ВИДАЛЕНО ?>

<?php // 9. HTML-контент сторінки ?>
<?php // Клас .admin-content можна залишити, якщо для нього є специфічні стилі в css/admin_panel.css,
// які не покриваються .site-main-content. Якщо ні - його можна прибрати. ?>
    <section class="admin-content">
        <div class="section-title-container"><h2>Управління книгарнею</h2></div> <?php // ?>

        <?php if ($message): ?>
            <p class="message <?php echo (strpos($message, 'Помилка') !== false || strpos($message, 'error') !== false) ? 'error-message' : 'success-message'; ?>"> <?php // ?>
                <?php echo $message; ?>
            </p>
        <?php endif; ?>

        <?php // Змінюємо клас контейнера секції на уніфікований .panel-container з модифікатором ?>
        <div class="panel-container admin-panel-section"> <?php // ?>
            <?php // Використовуємо уніфікований клас для заголовка підсекції ?>
            <h3 class="panel-section-title">Додати нову книгу</h3> <?php // ?>
            <form action="add_book.php" method="POST" enctype="multipart/form-data" class="add-book-form"> <?php // ?>
                <label for="title">Назва:</label> <?php // ?>
                <input type="text" name="title" id="title" required> <?php // ?>

                <label for="author">Автор:</label> <?php // ?>
                <input type="text" name="author" id="author" required> <?php // ?>

                <label for="description">Опис:</label> <?php // ?>
                <textarea name="description" id="description" rows="5" required></textarea> <?php // ?>

                <label for="genre">Жанр:</label> <?php // ?>
                <input type="text" name="genre" id="genre" required> <?php // ?>

                <label for="price">Ціна (грн):</label> <?php // ?>
                <input type="number" name="price" step="0.01" id="price" min="0" required> <?php // ?>

                <label for="image">Зображення (jpg, jpeg, png, до 5MB):</label> <?php // ?>
                <input type="file" name="image" id="image" accept="image/jpeg, image/png, image/jpg" required> <?php // ?>

                <?php // Додаємо класи .btn-generic та .btn-positive до кнопки ?>
                <button type="submit" name="submit" class="btn-generic btn-positive">Додати книгу</button> <?php // ?>
            </form>
        </div>

        <?php // Змінюємо клас контейнера секції ?>
        <div class="panel-container admin-panel-section"> <?php // ?>
            <?php // Використовуємо уніфікований клас для заголовка підсекції ?>
            <h3 class="panel-section-title">Замовлення користувачів</h3> <?php // ?>
            <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                <?php // Змінюємо класи для контейнера таблиці та самої таблиці ?>
                <div class="data-table-container"> <?php // ?>
                    <table class="data-table admin-orders-table"> <?php // Додаємо admin-orders-table для можливих мікро-налаштувань ?>
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
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo $order['user_id'] ? htmlspecialchars($order['user_id']) : 'Гість'; ?></td>
                                <td><?php echo htmlspecialchars($order['name']); ?></td>
                                <td><?php echo htmlspecialchars($order['email']); ?></td>
                                <td><?php echo htmlspecialchars($order['address']); ?></td>
                                <td><?php echo htmlspecialchars($order['phone']); ?></td>
                                <td><?php echo number_format($order['total'], 2); ?> грн</td>
                                <td><?php echo date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php
                                    $order_items_q = $conn->prepare(
                                        "SELECT oi.quantity, oi.price, b.title 
                                         FROM order_items oi 
                                         JOIN books b ON oi.book_id = b.id 
                                         WHERE oi.order_id = ?"
                                    ); //
                                    if ($order_items_q) { //
                                        $order_items_q->bind_param("i", $order['id']); //
                                        $order_items_q->execute(); //
                                        $order_items_r = $order_items_q->get_result(); //
                                        if ($order_items_r->num_rows > 0) { //
                                            // Видаляємо інлайнові стилі з ul та li, стилі будуть з .data-table td ul
                                            echo "<ul>"; //
                                            while ($item = $order_items_r->fetch_assoc()) { //
                                                echo "<li>" . htmlspecialchars($item['title']) . " (" . $item['quantity'] . "&nbsp;шт. x " . number_format($item['price'], 2) . ")</li>"; //
                                            }
                                            echo "</ul>"; //
                                        } else { echo "Немає деталей."; } //
                                        $order_items_q->close(); //
                                    } else {
                                        echo "Помилка завантаження деталей."; //
                                        error_log("Помилка підготовки запиту для деталей замовлення (адмін): " . $conn->error); //
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php // Змінюємо клас посилання на видалення ?>
                                    <a class="action-link-danger" href="delete_order.php?id=<?php echo $order['id']; ?>" onclick="return confirm('Ви впевнені, що хочете видалити замовлення #<?php echo $order['id']; ?>? Ця дія незворотна.')">❌ Видалити</a> <?php // ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif ($orders_result): ?>
                <?php // Змінюємо клас для повідомлення про відсутність замовлень ?>
                <p class="no-items-info">Немає замовлень для відображення.</p> <?php // ?>
            <?php else: ?>
                <?php if(empty($message)): ?>
                    <p class="error-message">Не вдалося завантажити список замовлень.</p> <?php // ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>
<?php
// 10. Звільнення результатів та закриття з'єднань
if (isset($orders_result) && $orders_result instanceof mysqli_result) { //
    mysqli_free_result($orders_result); //
}
if (isset($conn)) { //
    mysqli_close($conn); //
}

// 11. Підключаємо футер
include_once('includes/footer.php'); //
?>