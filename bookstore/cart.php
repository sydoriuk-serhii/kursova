<?php
// Файл: cart.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)

// 3. Ініціалізація змінних для повідомлень
$page_alert_message = '';
$page_alert_type = '';
$cart_empty_message_text = null; // Змінено ім'я, щоб уникнути конфлікту

// Обробка повідомлень з GET-параметрів (від оновлення/видалення)
if (isset($_GET['message'])) {
    $page_alert_message = htmlspecialchars($_GET['message']);
    // Проста логіка для визначення типу повідомлення (можна покращити)
    if (strpos(strtolower($page_alert_message), 'видалено') !== false || strpos(strtolower($page_alert_message), 'оновлено') !== false) {
        $page_alert_type = 'success';
    } else {
        $page_alert_type = 'info';
    }
}

// 4. Перевірка, чи є кошик в сесії, та оновлення повідомлення про порожній кошик
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    // Якщо кошик став порожнім після дії, а повідомлення про успіх вже є, не перезаписуємо його
    if (empty($page_alert_message)) {
        $cart_empty_message_text = "Ваш кошик порожній. <a href='catalog.php' class='alert-link'>Перейти до каталогу</a>";
    }
}


// 5. Функція для обчислення загальної вартості кошика
function calculateCartTotal($cart_items_func) { // Змінено ім'я параметра
    $total_func = 0; // Змінено ім'я змінної
    if (is_array($cart_items_func)) {
        foreach ($cart_items_func as $item_func) { // Змінено ім'я змінної
            $price_func = isset($item_func['price']) && is_numeric($item_func['price']) ? (float)$item_func['price'] : 0;
            $quantity_func = isset($item_func['quantity']) && is_numeric($item_func['quantity']) ? (int)$item_func['quantity'] : 0;
            $total_func += $price_func * $quantity_func;
        }
    }
    return $total_func;
}

// 6. Якщо користувач видаляє книгу з кошика
if (isset($_GET['remove_id'])) {
    $remove_id = (int)$_GET['remove_id'];

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $index_remove => $item_remove) { // Змінено імена змінних
            if (isset($item_remove['id']) && $item_remove['id'] == $remove_id) {
                unset($_SESSION['cart'][$index_remove]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                header("Location: cart.php?message=" . urlencode("Книгу «" . htmlspecialchars($item_remove['title']) . "» видалено з кошика."));
                exit();
            }
        }
    }
}

// 7. Оновлення кількості
if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
    $item_id_update = (int)$_POST['item_id']; // Змінено ім'я змінної
    $quantity_update = intval($_POST['quantity']); // Змінено ім'я змінної
    $item_title_for_message = '';

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        $item_found_for_update = false;
        // Спочатку знайдемо назву книги для повідомлення
        foreach ($_SESSION['cart'] as $item_check) {
            if (isset($item_check['id']) && $item_check['id'] == $item_id_update) {
                $item_title_for_message = $item_check['title'];
                break;
            }
        }

        if ($quantity_update > 0) {
            foreach ($_SESSION['cart'] as $index_update => $item_update) { // Змінено імена змінних
                if (isset($item_update['id']) && $item_update['id'] == $item_id_update) {
                    $_SESSION['cart'][$index_update]['quantity'] = $quantity_update;
                    $item_found_for_update = true;
                    break;
                }
            }
            if ($item_found_for_update) {
                header("Location: cart.php?message=" . urlencode("Кількість для книги «" . htmlspecialchars($item_title_for_message) . "» оновлено."));
                exit();
            }
        } elseif ($quantity_update <= 0) { // Якщо кількість 0 або менше - видаляємо
            foreach ($_SESSION['cart'] as $index_delete => $item_delete) { // Змінено імена змінних
                if (isset($item_delete['id']) && $item_delete['id'] == $item_id_update) {
                    unset($_SESSION['cart'][$index_delete]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                    $item_found_for_update = true;
                    break;
                }
            }
            if ($item_found_for_update) {
                header("Location: cart.php?message=" . urlencode("Книгу «" . htmlspecialchars($item_title_for_message) . "» видалено з кошика (кількість 0)."));
                exit();
            }
        }
    }
}

// 8. Встановлюємо заголовок сторінки
$page_title = "Ваш кошик - Інтернет-магазин книг";

// 9. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 11. HTML-контент сторінки ?>
    <section class="cart-page-container"> <?php // Замінено клас .cart на більш описовий, якщо потрібно ?>
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

        <?php if ($cart_empty_message_text): // Використовуємо нову змінну ?>
            <p class="no-items-info"><?php echo $cart_empty_message_text; // Дозволяємо HTML для посилання ?></p>
        <?php elseif (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <div class="cart-items-list">
                <?php foreach ($_SESSION['cart'] as $item_index => $item_display): // Змінено ім'я змінної ?>
                    <div class="cart-item-row panel-container"> <?php // Додано panel-container для стилізації кожного рядка ?>
                        <div class="cart-item-image">
                            <a href="book.php?id=<?php echo htmlspecialchars($item_display['id']); ?>">
                                <img src="uploads/<?php echo htmlspecialchars($item_display['image']); ?>" alt="<?php echo htmlspecialchars($item_display['title']); ?>">
                            </a>
                        </div>
                        <div class="cart-item-details">
                            <h3><a href="book.php?id=<?php echo htmlspecialchars($item_display['id']); ?>"><?php echo htmlspecialchars($item_display['title']); ?></a></h3>
                            <p><strong>Автор:</strong> <?php echo htmlspecialchars($item_display['author']); ?></p>
                            <p><strong>Ціна за одиницю:</strong> <?php echo number_format($item_display['price'], 2); ?> грн.</p>

                            <form action="cart.php" method="POST" class="update-quantity-form-inline">
                                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item_display['id']); ?>">
                                <div class="form-group"> <?php // Обгортаємо для кращого вирівнювання ?>
                                    <label for="quantity_<?php echo $item_index; ?>">Кількість:</label>
                                    <input type="number" id="quantity_<?php echo $item_index; ?>" name="quantity" value="<?php echo htmlspecialchars($item_display['quantity']); ?>" min="1" class="form-control-sm"> <?php // Додано form-control-sm, min="1" ?>
                                </div>
                                <button type="submit" name="update_quantity" class="btn-generic btn-secondary btn-sm">Оновити</button>
                            </form>

                            <p class="item-subtotal"><strong>Сума:</strong> <?php echo number_format((isset($item_display['price']) ? $item_display['price'] : 0) * (isset($item_display['quantity']) ? $item_display['quantity'] : 0), 2); ?> грн.</p>

                            <a href="cart.php?remove_id=<?php echo htmlspecialchars($item_display['id']); ?>" class="action-link-danger" onclick="return confirm('Ви впевнені, що хочете видалити цю книгу з кошика?');">
                                <span class="icon" aria-hidden="true">🗑️</span> Видалити
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary-block panel-container"> <?php // Додано panel-container ?>
                <h3>Загальна сума: <?php echo number_format(calculateCartTotal($_SESSION['cart']), 2); ?> грн.</h3>
            </div>

            <div class="cart-actions-buttons">
                <a href="checkout.php" class="btn-generic btn-positive btn-lg">Оформити замовлення</a>
                <a href="catalog.php" class="btn-generic btn-outline-secondary btn-lg">Продовжити покупки</a> <?php // Зроблено більшою та контурною ?>
            </div>
        <?php elseif (empty($page_alert_message)): // Додаткова умова, щоб не показувати цей блок, якщо вже є повідомлення про успішну дію, яка зробила кошик порожнім ?>
            <p class="no-items-info">Ваш кошик порожній. <a href='catalog.php' class='alert-link'>Перейти до каталогу</a></p>
        <?php endif; ?>
    </section>

<?php
// 12. Закриваємо з'єднання з БД
if (isset($conn) && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 13. Підключаємо футер
include_once('includes/footer.php');
?>