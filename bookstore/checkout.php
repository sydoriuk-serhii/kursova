<?php
// Файл: checkout.php

// ЗАПУСК СЕСІЇ НА САМОМУ ПОЧАТКУ!
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Підключення до бази даних
include_once('includes/db.php');

// Ініціалізація змінної для повідомлень про помилки
$page_alert_message = '';
$page_alert_type = '';

// 3. Функція обчислення загальної суми
// (Визначення функції тут, або можна винести в окремий файл helpers.php і підключати його)
function calculateTotalCheckout() { //
    $total_checkout = 0;
    // Переконуємося, що $_SESSION['cart'] існує і є масивом перед використанням
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart']) && !empty($_SESSION['cart'])) { //
        foreach ($_SESSION['cart'] as $item_checkout) {
            $price_checkout = isset($item_checkout['price']) && is_numeric($item_checkout['price']) ? (float)$item_checkout['price'] : 0;
            $quantity_checkout = isset($item_checkout['quantity']) && is_numeric($item_checkout['quantity']) ? (int)$item_checkout['quantity'] : 1; // За замовчуванням кількість 1, якщо не вказано
            $total_checkout += $price_checkout * $quantity_checkout;
        }
    }
    return $total_checkout;
}

// 4. Якщо кошик порожній, не дозволяємо оформлювати замовлення
if (empty($_SESSION['cart'])) { // Тепер ця перевірка має працювати коректно
    $cart_empty_msg = "Ваш кошик порожній. Неможливо оформити замовлення.";
    if (isset($conn) && $conn instanceof mysqli) mysqli_close($conn); // Закриваємо з'єднання, якщо воно було відкрито
    header("Location: cart.php?message=" . urlencode($cart_empty_msg));
    exit();
}

// 5. Обробка POST-запиту
if ($_SERVER['REQUEST_METHOD'] == 'POST') { //
    $name = isset($_POST['name']) ? trim($_POST['name']) : ''; //
    $email = isset($_POST['email']) ? trim($_POST['email']) : ''; //
    $address = isset($_POST['address']) ? trim($_POST['address']) : ''; //
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : ''; //

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : NULL; //
    $total = calculateTotalCheckout(); //

    // Валідація
    if (empty($name) || empty($email) || empty($address) || empty($phone)) { //
        $page_alert_message = "Будь ласка, заповніть усі обов'язкові поля."; //
        $page_alert_type = 'danger'; //
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //
        $page_alert_message = "Некоректний формат електронної пошти."; //
        $page_alert_type = 'danger'; //
    } elseif (!preg_match("/^\+380[0-9]{9}$/", $phone)) { //
        $page_alert_message = "Телефон має бути у форматі +380xxxxxxxxx (наприклад, +380991234567)."; //
        $page_alert_type = 'danger'; //
    } else {
        mysqli_begin_transaction($conn); // Починаємо транзакцію

        try {
            $order_sql_insert = "INSERT INTO orders (user_id, name, email, address, phone, total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"; //
            $stmt_order = $conn->prepare($order_sql_insert); //

            if (!$stmt_order) {
                throw new Exception("Помилка підготовки запиту для створення замовлення: " . $conn->error);
            }

            $stmt_order->bind_param(is_null($user_id) ? "sssssd" : "issssd", $user_id, $name, $email, $address, $phone, $total); //

            if (!$stmt_order->execute()) { //
                throw new Exception("Помилка при створенні замовлення: " . $stmt_order->error);
            }

            $order_id = mysqli_insert_id($conn); //
            $stmt_order->close();

            if (!empty($_SESSION['cart'])) { //
                $item_sql_insert = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)"; //
                $stmt_item = $conn->prepare($item_sql_insert); //

                if(!$stmt_item) { //
                    throw new Exception("Помилка підготовки запиту для деталей замовлення: " . $conn->error);
                }

                foreach ($_SESSION['cart'] as $cart_item_process) { //
                    $book_id_process = isset($cart_item_process['id']) ? (int)$cart_item_process['id'] : 0; //
                    $quantity_process = isset($cart_item_process['quantity']) ? (int)$cart_item_process['quantity'] : 1; //
                    $price_process = isset($cart_item_process['price']) ? (float)$cart_item_process['price'] : 0; //

                    if ($book_id_process == 0) { // Пропускаємо товар, якщо ID книги невірний
                        error_log("Пропущено товар без ID в замовленні {$order_id}");
                        continue;
                    }

                    $stmt_item->bind_param("iiid", $order_id, $book_id_process, $quantity_process, $price_process); //
                    if (!$stmt_item->execute()) { //
                        throw new Exception("Помилка при збереженні товару ({$book_id_process}) в замовленні: " . $stmt_item->error);
                    }
                }
                $stmt_item->close(); //
            }

            mysqli_commit($conn); // Фіксуємо транзакцію

            unset($_SESSION['cart']); // Очищуємо кошик ТІЛЬКИ ПІСЛЯ успішного коміту

            if(isset($conn) && $conn instanceof mysqli) mysqli_close($conn); //
            header("Location: index.php?success_order=true&order_id=" . $order_id . "&customer_name=" . urlencode($name)); //
            exit(); //

        } catch (Exception $e) {
            mysqli_rollback($conn); // Відкочуємо транзакцію у випадку помилки
            $page_alert_message = $e->getMessage();
            $page_alert_type = 'danger';
            error_log("Помилка оформлення замовлення: " . $e->getMessage());
            // Закриваємо стейтменти, якщо вони були відкриті та сталася помилка
            if (isset($stmt_order) && $stmt_order instanceof mysqli_stmt) $stmt_order->close();
            if (isset($stmt_item) && $stmt_item instanceof mysqli_stmt) $stmt_item->close();
        }
    }
}

// 6. Встановлюємо заголовок сторінки
$page_title = "Оформлення замовлення - Інтернет-магазин книг"; //

// 7. Підключаємо хедер
include_once('includes/header.php'); //
?>

<?php // 9. Починаємо HTML-розмітку ?>
    <div class="section-title-container"><h2>Оформлення замовлення</h2></div>

<?php // Виведення повідомлень (якщо є) ?>
<?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
    <div class="alert alert-<?php echo $page_alert_type; ?>">
    <span class="alert-icon">
        <?php
        if ($page_alert_type === 'danger') echo '&#10008;'; //
        else echo '&#8505;'; //
        ?>
    </span>
        <?php echo htmlspecialchars($page_alert_message); // Використовуємо htmlspecialchars тут ?>
    </div>
<?php endif; ?>

    <div class="checkout-navigation-buttons" style="margin-bottom: 20px;">
        <button onclick="window.location.href='cart.php'" class="btn-generic btn-outline-secondary">
            <span class="icon" aria-hidden="true">❮</span> Повернутися до кошика
        </button>
    </div>

    <section class="panel-container checkout-form-panel">
        <form action="checkout.php" method="POST" class="checkout-form">

            <div class="form-group">
                <label for="name">Ім'я та Прізвище:</label>
                <input type="text" id="name" name="name" value="<?php
                echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : (isset($_SESSION['username']) && isset($_SESSION['role']) && $_SESSION['role'] !== 'admin' ? $_SESSION['username'] : '')); // [ AVIA-2456 ]
                ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Електронна пошта:</label>
                <input type="email" id="email" name="email" value="<?php
                echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '')); // [ AVIA-2456 ]
                ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Адреса доставки (Місто, вулиця, будинок, квартира):</label>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="phone">Телефон (у форматі +380xxxxxxxxx):</label>
                <input type="tel" id="phone" name="phone" pattern="^\+380[0-9]{9}$" placeholder="+380991234567" value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>" required>
            </div>

            <div class="order-summary-details">
                <h3 class="panel-section-title">Склад замовлення:</h3>
                <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): // Додана перевірка isset ?>
                    <ul>
                        <?php foreach ($_SESSION['cart'] as $summary_item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($summary_item['title'] ?? 'Невідомий товар'); ?> (<?php echo (int)($summary_item['quantity'] ?? 1); ?> шт.)</span>
                                <span><?php echo number_format(((float)($summary_item['price'] ?? 0) * (int)($summary_item['quantity'] ?? 1)), 2); ?> грн</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="total-amount"><strong>Загальна сума до сплати: <?php echo number_format(calculateTotalCheckout(), 2); ?> грн</strong></p>
                <?php else: ?>
                    <p>Ваш кошик порожній.</p> <?php // Повідомлення, якщо кошик порожній на етапі рендерингу HTML ?>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-generic btn-positive btn-lg btn-full-width" style="margin-top: 25px;">
                <span class="icon" aria-hidden="true">✔</span> Підтвердити замовлення
            </button>
        </form>
    </section>

<?php
// 10. Закриваємо з'єднання з БД, тільки якщо воно ще існує і активне
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) { //
    mysqli_close($conn); //
}

// 11. Підключаємо футер
include_once('includes/footer.php'); //
?>