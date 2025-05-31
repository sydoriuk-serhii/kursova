<?php
// Файл: cart.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Ініціалізація змінної для повідомлення про порожній кошик
$cart_empty_message = null; //

// 4. Перевірка, чи є кошик в сесії
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) { //
    $cart_empty_message = "Ваш кошик порожній. <a href='catalog.php'>Перейти до каталогу</a>"; //
}

// 5. Функція для обчислення загальної вартості кошика
function calculateCartTotal($cart_items) {
    $total = 0; //
    if (is_array($cart_items)) { //
        foreach ($cart_items as $item) { //
            $price = isset($item['price']) && is_numeric($item['price']) ? (float)$item['price'] : 0; //
            $quantity = isset($item['quantity']) && is_numeric($item['quantity']) ? (int)$item['quantity'] : 0; //
            $total += $price * $quantity; //
        }
    }
    return $total;
}

// 6. Якщо користувач видаляє книгу з кошика
if (isset($_GET['remove_id'])) { //
    $remove_id = (int)$_GET['remove_id']; //

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) { //
        foreach ($_SESSION['cart'] as $index => $item) { //
            if (isset($item['id']) && $item['id'] == $remove_id) { //
                unset($_SESSION['cart'][$index]); //
                $_SESSION['cart'] = array_values($_SESSION['cart']); //
                header("Location: cart.php?message=" . urlencode("Книгу видалено з кошика.")); //
                exit(); //
            }
        }
    }
}

// 7. Оновлення кількості
if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['quantity'])) { //
    $item_id = (int)$_POST['item_id']; //
    $quantity = intval($_POST['quantity']); //

    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) { //
        $item_found_for_update = false; //
        if ($quantity > 0) { //
            foreach ($_SESSION['cart'] as $index => $item) { //
                if (isset($item['id']) && $item['id'] == $item_id) { //
                    $_SESSION['cart'][$index]['quantity'] = $quantity; //
                    $item_found_for_update = true; //
                    break; //
                }
            }
            if($item_found_for_update){ //
                header("Location: cart.php?message=" . urlencode("Кількість оновлено.")); //
                exit(); //
            }
        } elseif ($quantity <= 0) { //
            foreach ($_SESSION['cart'] as $index => $item) { //
                if (isset($item['id']) && $item['id'] == $item_id) { //
                    unset($_SESSION['cart'][$index]); //
                    $_SESSION['cart'] = array_values($_SESSION['cart']); //
                    $item_found_for_update = true; //
                    break; //
                }
            }
            if($item_found_for_update){ //
                header("Location: cart.php?message=" . urlencode("Книгу видалено з кошика (кількість 0).")); //
                exit(); //
            }
        }
    }
}

// 8. Встановлюємо заголовок сторінки
$page_title = "Ваш кошик - Інтернет-магазин книг"; //

// 9. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/cart.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 10. Рядок <link rel="stylesheet" href="css/cart.css"> ВИДАЛЕНО ?>

<?php // 11. HTML-контент сторінки ?>
<?php // Клас .cart можна залишити для загального контейнера сторінки кошика, якщо для нього є стилі в css/cart.css ?>
    <section class="cart">
        <div class="section-title-container"><h2>Ваш кошик</h2></div> <?php // ?>

        <?php if (isset($_GET['message'])): ?>
            <div class="success-message"><?php echo htmlspecialchars($_GET['message']); ?></div> <?php // ?>
        <?php endif; ?>

        <?php if ($cart_empty_message): ?>
            <?php // Видаляємо інлайнові стилі та використовуємо клас .no-items-info ?>
            <p class="no-items-info"><?php echo $cart_empty_message; ?></p> <?php // ?>
        <?php elseif (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
            <div class="cart-items-list"> <?php // ?>
                <?php foreach ($_SESSION['cart'] as $item_index => $item): ?>
                    <div class="cart-item-row"> <?php // ?>
                        <div class="cart-item-image"> <?php // ?>
                            <a href="book.php?id=<?php echo htmlspecialchars($item['id']); ?>">
                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>"> <?php // ?>
                            </a>
                        </div>
                        <div class="cart-item-details"> <?php // ?>
                            <h3><a href="book.php?id=<?php echo htmlspecialchars($item['id']); ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3> <?php // ?>
                            <p><strong>Автор:</strong> <?php echo htmlspecialchars($item['author']); ?></p> <?php // ?>
                            <p><strong>Ціна за одиницю:</strong> <?php echo number_format($item['price'], 2); ?> грн.</p> <?php // ?>
                            <form action="cart.php" method="POST" class="update-quantity-form-inline"> <?php // ?>
                                <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>"> <?php // ?>
                                <label for="quantity_<?php echo $item_index; ?>">Кількість:</label> <?php // ?>
                                <?php // Видаляємо інлайнові стилі з інпута кількості ?>
                                <input type="number" id="quantity_<?php echo $item_index; ?>" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="0"> <?php // ?>
                                <?php // Змінюємо клас кнопки оновлення ?>
                                <button type="submit" name="update_quantity" class="btn-generic btn-secondary">Оновити</button> <?php // ?>
                            </form>
                            <p><strong>Сума:</strong> <?php echo number_format((isset($item['price']) ? $item['price'] : 0) * (isset($item['quantity']) ? $item['quantity'] : 0), 2); ?> грн.</p> <?php // ?>
                            <?php // Змінюємо клас посилання на видалення ?>
                            <a href="cart.php?remove_id=<?php echo htmlspecialchars($item['id']); ?>" class="action-link-danger" onclick="return confirm('Ви впевнені, що хочете видалити цю книгу з кошика?');">Видалити</a> <?php // ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php // Змінюємо клас для блоку загальної суми ?>
            <div class="cart-summary-block"> <?php // ?>
                <h3>Загальна сума: <?php echo number_format(calculateCartTotal($_SESSION['cart']), 2); ?> грн.</h3> <?php // ?>
            </div>

            <div class="cart-actions-buttons"> <?php // ?>
                <?php // Застосовуємо .btn-positive для основної дії, якщо потрібно (або залишаємо синім .btn-generic) ?>
                <a href="checkout.php" class="btn-generic btn-positive">Оформити замовлення</a> <?php // ?>
                <?php // Видаляємо інлайновий стиль та використовуємо .btn-secondary ?>
                <a href="catalog.php" class="btn-generic btn-secondary">Продовжити покупки</a> <?php // ?>
            </div>
        <?php else: ?>
            <?php // Цей блок else тепер не потрібен, оскільки $cart_empty_message обробляється вище ?>
            <?php // Якщо все ж таки потрібен, використовуємо .no-items-info ?>
            <p class="no-items-info">Ваш кошик порожній. <a href='catalog.php'>Перейти до каталогу</a></p> <?php // ?>
        <?php endif; ?>
    </section>

<?php
// 12. Закриваємо з'єднання з БД
if (isset($conn) && mysqli_ping($conn)) { //
    mysqli_close($conn); //
}

// 13. Підключаємо футер
include_once('includes/footer.php'); //
?>