<?php
// Файл: admin_panel.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if(isset($conn)) mysqli_close($conn);
    header('Location: login.php');
    exit;
}

// Ініціалізація змінних для повідомлень
$page_alert_message = '';
$page_alert_type = '';

// 4. Обробка повідомлень (успіх/помилка)
if (isset($_GET['success_add'])) {
    $page_alert_message = "Книгу успішно додано!";
    $page_alert_type = 'success';
} elseif (isset($_GET['success_delete'])) {
    $page_alert_message = htmlspecialchars($_GET['success_delete']); // Беремо повідомлення з GET
    $page_alert_type = 'success';
} elseif (isset($_GET['error_delete'])) {
    $page_alert_message = "Помилка видалення замовлення: " . htmlspecialchars($_GET['error_delete']);
    $page_alert_type = 'danger';
} elseif (isset($_GET['error_add'])) {
    $page_alert_message = "Помилка додавання книги: " . htmlspecialchars($_GET['error_add']);
    $page_alert_type = 'danger';
}


// 5. Запит для отримання всіх замовлень
// ВИДАЛЕНО `status` ІЗ ЗАПИТУ
$orders_query_sql = "SELECT id, user_id, name, email, address, phone, total, created_at FROM `orders` ORDER BY created_at DESC";
$orders_result_query = mysqli_query($conn, $orders_query_sql);
$orders_data_admin = [];
$order_items_by_order_id_admin = [];

if ($orders_result_query) {
    $order_ids_admin = [];
    while($order_row_admin = mysqli_fetch_assoc($orders_result_query)) {
        $orders_data_admin[] = $order_row_admin;
        $order_ids_admin[] = $order_row_admin['id'];
    }

    if (!empty($order_ids_admin)) {
        $ids_placeholder_admin = implode(',', array_fill(0, count($order_ids_admin), '?'));
        $types_for_items_admin = str_repeat('i', count($order_ids_admin));

        $items_sql_admin = "SELECT oi.order_id, oi.quantity, oi.price, b.title
                      FROM order_items oi
                      JOIN books b ON oi.book_id = b.id
                      WHERE oi.order_id IN ($ids_placeholder_admin)";
        $stmt_items_all_admin = $conn->prepare($items_sql_admin);
        if ($stmt_items_all_admin) {
            $stmt_items_all_admin->bind_param($types_for_items_admin, ...$order_ids_admin);
            $stmt_items_all_admin->execute();
            $items_result_all_admin = $stmt_items_all_admin->get_result();
            if ($items_result_all_admin) {
                while ($item_detail_admin = $items_result_all_admin->fetch_assoc()) {
                    $order_items_by_order_id_admin[$item_detail_admin['order_id']][] = $item_detail_admin;
                }
                $items_result_all_admin->close();
            } else {
                error_log("Помилка виконання запиту деталей замовлень (адмін): " . $stmt_items_all_admin->error);
            }
            $stmt_items_all_admin->close();
        } else {
            error_log("Помилка підготовки запиту деталей замовлень (адмін): " . $conn->error);
        }
    }
} else {
    error_log("Помилка отримання замовлень в адмін-панелі: " . mysqli_error($conn));
    if (empty($page_alert_message)) {
        $page_alert_message = "Виникла помилка при завантаженні списку замовлень.";
        $page_alert_type = 'danger';
    }
}


// 6. Встановлюємо заголовок сторінки
$page_title = "Панель адміністратора - Інтернет-магазин книг";

// 7. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 9. HTML-контент сторінки ?>
    <section class="admin-panel-container">
        <div class="section-title-container"><h2>Управління книгарнею</h2></div>

        <?php // Виведення повідомлень (якщо є) ?>
        <?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
            <div class="alert alert-<?php echo $page_alert_type; ?>">
            <span class="alert-icon">
                <?php
                if ($page_alert_type === 'success') echo '&#10004;';
                elseif ($page_alert_type === 'danger') echo '&#10008;';
                else echo '&#8505;';
                ?>
            </span>
                <?php echo $page_alert_message; ?>
            </div>
        <?php endif; ?>

        <div class="panel-container admin-panel-section add-book-panel">
            <h3 class="panel-section-title">Додати нову книгу</h3>
            <form action="add_book.php" method="POST" enctype="multipart/form-data" class="add-book-form">
                <div class="form-group">
                    <label for="title">Назва:</label>
                    <input type="text" name="title" id="title" required>
                </div>
                <div class="form-group">
                    <label for="author">Автор:</label>
                    <input type="text" name="author" id="author" required>
                </div>
                <div class="form-group">
                    <label for="description">Опис:</label>
                    <textarea name="description" id="description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label for="genre">Жанр:</label>
                    <input type="text" name="genre" id="genre" required>
                </div>
                <div class="form-group">
                    <label for="price">Ціна (грн):</label>
                    <input type="number" name="price" step="0.01" id="price" min="0" required>
                </div>
                <div class="form-group">
                    <label for="image">Зображення (jpg, jpeg, png, до 5MB):</label>
                    <input type="file" name="image" id="image" accept=".jpg, .jpeg, .png" required>
                </div>
                <button type="submit" name="submit" class="btn-generic btn-positive" style="min-width: 200px;">
                    <span class="icon" aria-hidden="true">➕</span> Додати книгу
                </button>
            </form>
        </div>


        <div class="panel-container admin-panel-section orders-panel">
            <h3 class="panel-section-title">Замовлення користувачів</h3>
            <?php if (!empty($orders_data_admin)): ?>
                <div class="data-table-container">
                    <table class="data-table admin-orders-table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Дата</th>
                            <th>Ім'я</th>
                            <th>Телефон</th>
                            <th>Адреса</th> <?php // Додано Адресу, якщо потрібно її бачити тут ?>
                            <th>Сума</th>
                            <th>Склад</th>
                            <th>Дії</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders_data_admin as $order_admin): ?>
                            <tr>
                                <td>
                                    <a href="admin_order_detail.php?id=<?php echo $order_admin['id']; ?>" class="text-link">#<?php echo $order_admin['id']; ?></a>
                                    <?php if ($order_admin['user_id']): ?>
                                        <br><small>(User ID: <?php echo htmlspecialchars($order_admin['user_id']); ?>)</small>
                                    <?php else: ?>
                                        <br><small>(Гість)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date("d.m.Y H:i", strtotime($order_admin['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($order_admin['name']); ?><br><small><?php echo htmlspecialchars($order_admin['email']); ?></small></td>
                                <td><?php echo htmlspecialchars($order_admin['phone']); ?></td>
                                <td><?php echo htmlspecialchars($order_admin['address']); ?></td> <?php // Вивід адреси ?>
                                <td><?php echo number_format($order_admin['total'], 2); ?> грн</td>
                                <td>
                                    <?php
                                    if (isset($order_items_by_order_id_admin[$order_admin['id']]) && !empty($order_items_by_order_id_admin[$order_admin['id']])) {
                                        echo "<ul>";
                                        foreach ($order_items_by_order_id_admin[$order_admin['id']] as $item_admin) {
                                            echo "<li>" . htmlspecialchars($item_admin['title']) . " (" . (int)$item_admin['quantity'] . "&nbsp;шт. &times;&nbsp;" . number_format((float)$item_admin['price'], 2) . ")</li>";
                                        }
                                        echo "</ul>";
                                    } else { echo "Немає деталей."; }
                                    ?>
                                </td>
                                <td>
                                    <a class="action-link-danger" href="delete_order.php?id=<?php echo $order_admin['id']; ?>" onclick="return confirm('Ви впевнені, що хочете видалити замовлення #<?php echo $order_admin['id']; ?>? Ця дія незворотна.')">
                                        <span class="icon" aria-hidden="true">🗑️</span> Видалити
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (empty($page_alert_message)): ?>
                <p class="no-items-info">Немає замовлень для відображення.</p>
            <?php endif; ?>
        </div>
    </section>
<?php
// 10. Звільнення результатів та закриття з'єднань
if (isset($orders_result_query) && $orders_result_query instanceof mysqli_result) {
    mysqli_free_result($orders_result_query);
}

if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 11. Підключаємо футер
include_once('includes/footer.php');
?>