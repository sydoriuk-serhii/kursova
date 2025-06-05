<?php
// Файл: order.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ініціалізація змінних для повідомлень
$page_alert_message = '';
$page_alert_type = '';

// 3. Перевірка, чи користувач увійшов в систему та чи не є адміном
if (!isset($_SESSION['user_id'])) {
    if(isset($conn)) mysqli_close($conn);
    header("Location: login.php?message=" . urlencode("Будь ласка, увійдіть, щоб переглянути ваші замовлення."));
    exit;
}
if ($_SESSION['role'] === 'admin') {
    if(isset($conn)) mysqli_close($conn);
    header("Location: admin_panel.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Обробка повідомлень з GET-параметрів (від видалення)
if (isset($_GET['message'])) {
    $page_alert_message = htmlspecialchars($_GET['message']);
    if (strpos(strtolower($page_alert_message), 'успішно видалено') !== false) {
        $page_alert_type = 'success';
    } elseif (strpos(strtolower($page_alert_message), 'помилка') !== false) {
        $page_alert_type = 'danger';
    } else {
        $page_alert_type = 'info';
    }
}


// 4. Видалення замовлення, якщо передано delete_id
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $order_title_for_message = "#" . $delete_id; // Для повідомлення

    $check_order_query = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
    if ($check_order_query) {
        $check_order_query->bind_param("ii", $delete_id, $user_id);
        $check_order_query->execute();
        $check_result = $check_order_query->get_result();

        if ($check_result && $check_result->num_rows > 0) {
            mysqli_begin_transaction($conn);
            try {
                $delete_items_query = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                if(!$delete_items_query) throw new mysqli_sql_exception("Помилка підготовки запиту видалення позицій.");
                $delete_items_query->bind_param("i", $delete_id);
                $delete_items_query->execute();
                // Немає потреби перевіряти affected_rows для order_items, замовлення може бути порожнім
                $delete_items_query->close();

                $delete_order_query = $conn->prepare("DELETE FROM orders WHERE id = ? AND user_id = ?");
                if(!$delete_order_query) throw new mysqli_sql_exception("Помилка підготовки запиту видалення замовлення.");
                $delete_order_query->bind_param("ii", $delete_id, $user_id);
                $delete_order_query->execute();

                if ($delete_order_query->affected_rows > 0) {
                    mysqli_commit($conn);
                    $redirect_message = "Замовлення {$order_title_for_message} успішно видалено.";
                } else {
                    mysqli_rollback($conn);
                    $redirect_message = "Помилка: Не вдалося видалити замовлення {$order_title_for_message} (можливо, вже видалено).";
                }
                $delete_order_query->close();
            } catch (mysqli_sql_exception $exception) {
                mysqli_rollback($conn);
                error_log("Помилка видалення замовлення (ID: {$delete_id}, User: {$user_id}): " . $exception->getMessage());
                $redirect_message = "Сталася помилка бази даних при видаленні замовлення {$order_title_for_message}.";
            } finally { // Додано finally для закриття стейтментів, якщо вони ще відкриті
                if (isset($delete_items_query) && $delete_items_query instanceof mysqli_stmt) {
                    $delete_items_query->close();
                }
                if (isset($delete_order_query) && $delete_order_query instanceof mysqli_stmt) {
                    $delete_order_query->close();
                }
            }
        } else {
            $redirect_message = "Помилка: Замовлення {$order_title_for_message} не знайдено або у вас немає прав на його видалення.";
        }
        if ($check_result) $check_result->close();
        $check_order_query->close();
    } else {
        error_log("Помилка підготовки запиту перевірки замовлення (ID: {$delete_id}, User: {$user_id}): " . $conn->error);
        $redirect_message = "Помилка перевірки замовлення {$order_title_for_message}.";
    }
    if(isset($conn)) mysqli_close($conn);
    header("Location: order.php?message=" . urlencode($redirect_message));
    exit;
}

// 5. Отримання замовлень поточного авторизованого користувача
// ВИДАЛЕНО `status` ІЗ ЗАПИТУ
$order_query_sql = "SELECT id, name, email, address, phone, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$order_query = $conn->prepare($order_query_sql);
$orders_data = []; // Масив для зберігання даних замовлень
$order_items_by_order_id = []; // Масив для деталей замовлень

if ($order_query) {
    $order_query->bind_param("i", $user_id);
    $order_query->execute();
    $order_result = $order_query->get_result();

    if ($order_result) {
        $order_ids = [];
        while($order_row_temp = $order_result->fetch_assoc()) {
            $orders_data[] = $order_row_temp;
            $order_ids[] = $order_row_temp['id'];
        }
        // $order_result->close(); // Можна закрити, якщо далі не використовується, але дані вже в $orders_data

        if (!empty($order_ids)) {
            $ids_placeholder = implode(',', array_fill(0, count($order_ids), '?'));
            $types_for_items = str_repeat('i', count($order_ids));

            $items_sql = "SELECT oi.order_id, oi.quantity, oi.price, b.title
                          FROM order_items oi
                          JOIN books b ON oi.book_id = b.id
                          WHERE oi.order_id IN ($ids_placeholder)";
            $stmt_items_all = $conn->prepare($items_sql);
            if ($stmt_items_all) {
                $stmt_items_all->bind_param($types_for_items, ...$order_ids);
                $stmt_items_all->execute();
                $items_result_all = $stmt_items_all->get_result();
                if ($items_result_all) {
                    while ($item_detail = $items_result_all->fetch_assoc()) {
                        $order_items_by_order_id[$item_detail['order_id']][] = $item_detail;
                    }
                    $items_result_all->close();
                } else {
                    error_log("Помилка виконання запиту деталей замовлень (user): " . $stmt_items_all->error);
                }
                $stmt_items_all->close();
            } else {
                error_log("Помилка підготовки запиту деталей замовлень (user): " . $conn->error);
            }
        }
    } else {
        error_log("Помилка виконання запиту замовлень (user): " . $order_query->error);
    }
    $order_query->close();
} else {
    error_log("Помилка підготовки запиту замовлень (user): " . $conn->error);
    $page_alert_message = "Не вдалося завантажити ваші замовлення.";
    $page_alert_type = 'danger';
}


// 6. Встановлюємо заголовок сторінки
$page_title = "Мої замовлення - Інтернет-магазин книг";

// 7. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 9. HTML-розмітка сторінки ?>
    <div class="section-title-container"><h2>Ваші замовлення</h2></div>

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

<?php if (!empty($orders_data)): ?>
    <div class="data-table-container">
        <table class="data-table user-orders-table">
            <thead>
            <tr>
                <th>ID</th>
                <th>Дата</th>
                <th>Отримувач</th>
                <th>Телефон</th>
                <th>Адреса</th>
                <th>Сума</th>
                <th>Склад замовлення</th>
                <th>Дія</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders_data as $order): ?>
                <tr>
                    <td><a href="order_detail.php?id=<?php echo $order['id']; ?>" class="text-link">#<?php echo $order['id']; ?></a></td>
                    <td><?php echo date("d.m.Y H:i", strtotime($order['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($order['name']); ?></td>
                    <td><?php echo htmlspecialchars($order['phone']); ?></td>
                    <td><?php echo htmlspecialchars($order['address']); ?></td>
                    <td><?php echo number_format($order['total'], 2); ?> грн</td>
                    <td>
                        <?php
                        if (isset($order_items_by_order_id[$order['id']]) && !empty($order_items_by_order_id[$order['id']])) {
                            echo "<ul>";
                            foreach ($order_items_by_order_id[$order['id']] as $item) {
                                echo "<li>" . htmlspecialchars($item['title']) . " (" . (int)$item['quantity'] . " шт. &times; " . number_format((float)$item['price'], 2) . " грн)</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "Деталі не знайдено.";
                        }
                        ?>
                    </td>
                    <td>
                        <a href="order.php?delete_id=<?php echo $order['id']; ?>" class="action-link-danger" onclick="return confirm('Ви впевнені, що хочете видалити замовлення #<?php echo $order['id']; ?>? Ця дія незворотна.')">
                            <span class="icon" aria-hidden="true">🗑️</span> Видалити
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php elseif (empty($page_alert_message)): ?>
    <p class="no-items-info">У вас поки немає замовлень. <a href="catalog.php" class="alert-link">Перейти до каталогу?</a></p>
<?php endif; ?>

<?php
// 10. Закриваємо з'єднання з БД
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 11. Підключаємо футер
include_once('includes/footer.php');
?>