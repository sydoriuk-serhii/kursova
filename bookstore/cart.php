<?php
// Файл: cart.php

// ЗАПУСК СЕСІЇ, ЯКЩО ЦЕ НЕ ЗРОБЛЕНО В header.php ДО ЙОГО ПІДКЛЮЧЕННЯ
// (АЛЕ У ВАШОМУ ВИПАДКУ header.php МАЄ session_start())
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// 1. Підключення до бази даних
include_once('includes/db.php');

// --- ПОЧАТОК БЛОКУ ОБРОБКИ ДІЙ ---
// Важливо, щоб цей блок був ДО будь-якого HTML виводу, якщо використовуються header() для редиректу

// 6. Якщо користувач видаляє книгу з кошика
if (isset($_GET['remove_id'])) {
    $remove_id = (int)$_GET['remove_id'];

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $removed_item_title = 'Невідома книга'; // Для повідомлення
        $item_removed_flag = false;
        foreach ($_SESSION['cart'] as $index_remove => $item_remove) {
            if (isset($item_remove['id']) && $item_remove['id'] == $remove_id) {
                $removed_item_title = $item_remove['title'] ?? 'Невідома книга';
                unset($_SESSION['cart'][$index_remove]);
                $item_removed_flag = true;
                break; // Виходимо з циклу, як тільки товар знайдено та видалено
            }
        }
        if ($item_removed_flag) {
            $_SESSION['cart'] = array_values($_SESSION['cart']); // Переіндексація масиву
            if(isset($conn)) mysqli_close($conn); // Закриваємо з'єднання перед редиректом
            header("Location: cart.php?message=" . urlencode("Книгу «" . htmlspecialchars($removed_item_title) . "» видалено з кошика."));
            exit();
        }
    }
}

// 7. Оновлення кількості
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    if (isset($_POST['item_id']) && isset($_POST['quantity'])) {
        $item_id_update = (int)$_POST['item_id'];
        $quantity_update = intval($_POST['quantity']);
        $item_title_for_message = 'Невідома книга';
        $action_performed = false;

        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            // Спочатку знайдемо назву книги для повідомлення
            foreach ($_SESSION['cart'] as $item_check) {
                if (isset($item_check['id']) && $item_check['id'] == $item_id_update) {
                    $item_title_for_message = $item_check['title'] ?? 'Невідома книга';
                    break;
                }
            }

            if ($quantity_update > 0) {
                foreach ($_SESSION['cart'] as $index_update => $item_update) {
                    if (isset($item_update['id']) && $item_update['id'] == $item_id_update) {
                        $_SESSION['cart'][$index_update]['quantity'] = $quantity_update;
                        $action_performed = true;
                        $message_text = "Кількість для книги «" . htmlspecialchars($item_title_for_message) . "» оновлено.";
                        break;
                    }
                }
            } elseif ($quantity_update <= 0) { // Якщо кількість 0 або менше - видаляємо
                foreach ($_SESSION['cart'] as $index_delete => $item_delete) {
                    if (isset($item_delete['id']) && $item_delete['id'] == $item_id_update) {
                        unset($_SESSION['cart'][$index_delete]);
                        $_SESSION['cart'] = array_values($_SESSION['cart']);
                        $action_performed = true;
                        $message_text = "Книгу «" . htmlspecialchars($item_title_for_message) . "» видалено з кошика (кількість 0).";
                        break;
                    }
                }
            }

            if ($action_performed) {
                if(isset($conn)) mysqli_close($conn); // Закриваємо з'єднання перед редиректом
                header("Location: cart.php?message=" . urlencode($message_text));
                exit();
            }
        }
    }
}
// --- КІНЕЦЬ БЛОКУ ОБРОБКИ ДІЙ ---


// 3. Ініціалізація змінних для повідомлень (після можливих редиректів з блоку обробки)
$page_alert_message = '';
$page_alert_type = '';

// Обробка повідомлень з GET-параметрів, які прийшли ПІСЛЯ редиректу
if (isset($_GET['message'])) {
    $page_alert_message = htmlspecialchars($_GET['message']);
    if (strpos(strtolower($page_alert_message), 'видалено') !== false || strpos(strtolower($page_alert_message), 'оновлено') !== false) {
        $page_alert_type = 'success';
    } else {
        $page_alert_type = 'info';
    }
}

// 5. Функція для обчислення загальної вартості кошика
function calculateCartTotal($cart_items_func) {
    $total_func = 0;
    if (is_array($cart_items_func)) {
        foreach ($cart_items_func as $item_func) {
            $price_func = isset($item_func['price']) && is_numeric($item_func['price']) ? (float)$item_func['price'] : 0;
            $quantity_func = isset($item_func['quantity']) && is_numeric($item_func['quantity']) ? (int)$item_func['quantity'] : 1;
            $total_func += $price_func * $quantity_func;
        }
    }
    return $total_func;
}

// 8. Встановлюємо заголовок сторінки
$page_title = "Ваш кошик - Інтернет-магазин книг";

// 9. Підключаємо хедер
include_once('includes/header.php');
// Після підключення header.php, $_SESSION['cart'] вже має бути доступний і оброблений лічильником
?>

<?php // 11. HTML-контент сторінки ?>
    <section class="cart-page-container">
        <div class="section-title-container"><h2>Ваш кошик</h2></div>

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

        <?php
        // ОСНОВНА ЛОГІКА ВІДОБРАЖЕННЯ
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            // Кошик існує і не порожній - показуємо товари
            ?>
            <div class="cart-items-list">
                <?php foreach ($_SESSION['cart'] as $item_index => $item_display): ?>
                    <div class="cart-item-row panel-container">
                        <div class="cart-item-image">
                            <a href="book.php?id=<?php echo htmlspecialchars($item_display['id'] ?? ''); ?>">
                                <img src="uploads/<?php echo htmlspecialchars($item_display['image'] ?? 'default.jpg'); ?>" alt="<?php echo htmlspecialchars($item_display['title'] ?? 'Книга'); ?>">
                            </a>
                        </div>
                        <div class="cart-item-details">
                            <h3><a href="book.php?id=<?php echo htmlspecialchars($item_display['id'] ?? ''); ?>"><?php echo htmlspecialchars($item_display['title'] ?? 'Назва невідома'); ?></a></h3>
                            <p><strong>Автор:</strong> <?php echo htmlspecialchars($item_display['author'] ?? 'Автор невідомий'); ?></p>
                            <p><strong>Ціна за одиницю:</strong> <?php echo number_format($item_display['price'] ?? 0, 2); ?> грн.</p>

                            <form action="cart.php" method="POST" class="update-quantity-form-inline">
                                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_display['id'] ?? ''); ?>">
                                <div class="form-group">
                                    <label for="quantity_<?php echo $item_index; ?>">Кількість:</label>
                                    <input type="number" id="quantity_<?php echo $item_index; ?>" name="quantity" value="<?php echo htmlspecialchars($item_display['quantity'] ?? 1); ?>" min="1" class="form-control-sm">
                                </div>
                                <button type="submit" name="update_quantity" class="btn-generic btn-secondary btn-sm">Оновити</button>
                            </form>

                            <p class="item-subtotal"><strong>Сума:</strong> <?php echo number_format((($item_display['price'] ?? 0) * ($item_display['quantity'] ?? 1)), 2); ?> грн.</p>

                            <a href="cart.php?remove_id=<?php echo htmlspecialchars($item_display['id'] ?? ''); ?>" class="action-link-danger" onclick="return confirm('Ви впевнені, що хочете видалити цю книгу з кошика?');">
                                <span class="icon" aria-hidden="true">🗑️</span> Видалити
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary-block panel-container">
                <h3>Загальна сума: <?php echo number_format(calculateCartTotal($_SESSION['cart']), 2); ?> грн.</h3>
            </div>

            <div class="cart-actions-buttons">
                <a href="checkout.php" class="btn-generic btn-positive btn-lg">Оформити замовлення</a>
                <a href="catalog.php" class="btn-generic btn-outline-secondary btn-lg">Продовжити покупки</a>
            </div>
            <?php
        } else {
            // Кошик порожній або не існує.
            if (empty($page_alert_message)) {
                echo "<p class='no-items-info'>Ваш кошик порожній. <a href='catalog.php' class='alert-link'>Перейти до каталогу</a></p>";
            }
        }
        ?>
    </section>

<?php
// 12. Закриваємо з'єднання з БД (тільки якщо воно ще відкрите)
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 13. Підключаємо футер
include_once('includes/footer.php');
?>