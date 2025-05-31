<?php
// Файл: checkout.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)

// Ініціалізація змінної для повідомлень про помилки
$page_alert_message = ''; // Використовуємо цю змінну для всіх повідомлень на сторінці
$page_alert_type = '';

// 3. Функція обчислення загальної суми
function calculateTotalCheckout() {
    $total_checkout = 0; // Змінено ім'я змінної
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item_checkout) { // Змінено ім'я змінної
            $price_checkout = isset($item_checkout['price']) ? (float)$item_checkout['price'] : 0;
            $quantity_checkout = isset($item_checkout['quantity']) ? (int)$item_checkout['quantity'] : 1;
            $total_checkout += $price_checkout * $quantity_checkout;
        }
    }
    return $total_checkout;
}

// 4. Якщо кошик порожній, не дозволяємо оформлювати замовлення
if (empty($_SESSION['cart'])) {
    // Перевіряємо, чи $conn існує, перш ніж використовувати mysqli_real_escape_string, хоча тут він не потрібен для urlencode
    $cart_empty_msg = "Ваш кошик порожній. Неможливо оформити замовлення.";
    if (isset($conn)) mysqli_close($conn); // Закриваємо з'єднання, якщо воно було відкрито
    header("Location: cart.php?message=" . urlencode($cart_empty_msg));
    exit();
}

// 5. Обробка POST-запиту
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Використовуємо trim для видалення зайвих пробілів
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    // mysqli_real_escape_string тут не потрібен, оскільки дані йдуть у bind_param
    // $name_db = mysqli_real_escape_string($conn, $name);
    // $email_db = mysqli_real_escape_string($conn, $email);
    // $address_db = mysqli_real_escape_string($conn, $address);
    // $phone_db = mysqli_real_escape_string($conn, $phone);

    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    $total = calculateTotalCheckout();

    // Валідація
    if (empty($name) || empty($email) || empty($address) || empty($phone)) {
        $page_alert_message = "Будь ласка, заповніть усі обов'язкові поля.";
        $page_alert_type = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $page_alert_message = "Некоректний формат електронної пошти.";
        $page_alert_type = 'danger';
    } elseif (!preg_match("/^\+380[0-9]{9}$/", $phone)) {
        $page_alert_message = "Телефон має бути у форматі +380xxxxxxxxx (наприклад, +380991234567).";
        $page_alert_type = 'danger';
    } else {
        $order_sql_insert = "INSERT INTO orders (user_id, name, email, address, phone, total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt_order = $conn->prepare($order_sql_insert);

        if ($stmt_order) {
            // Використовуємо очищені значення $name, $email, $address, $phone
            $stmt_order->bind_param(is_null($user_id) ? "sssssd" : "issssd", $user_id, $name, $email, $address, $phone, $total);

            if ($stmt_order->execute()) {
                $order_id = mysqli_insert_id($conn);

                if (!empty($_SESSION['cart'])) {
                    $item_sql_insert = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)";
                    $stmt_item = $conn->prepare($item_sql_insert);

                    if($stmt_item) { // Додана перевірка успішності prepare
                        foreach ($_SESSION['cart'] as $cart_item_process) { // Змінено ім'я змінної
                            $book_id_process = $cart_item_process['id'];
                            $quantity_process = isset($cart_item_process['quantity']) ? (int)$cart_item_process['quantity'] : 1;
                            $price_process = isset($cart_item_process['price']) ? (float)$cart_item_process['price'] : 0;
                            $stmt_item->bind_param("iiid", $order_id, $book_id_process, $quantity_process, $price_process);
                            $stmt_item->execute();
                        }
                        $stmt_item->close();
                    } else {
                        // Обробка помилки підготовки запиту для order_items
                        error_log("Помилка підготовки запиту order_items: " . $conn->error);
                        // Можливо, варто відкотити транзакцію, якщо використовується, або видалити створене замовлення
                        // Для простоти, поки що просто логуємо
                        $page_alert_message = "Сталася помилка при збереженні деталей замовлення.";
                        $page_alert_type = 'danger';
                    }
                }

                // Якщо не було помилок з order_items, продовжуємо
                if (empty($page_alert_message)) {
                    unset($_SESSION['cart']);
                    $stmt_order->close();
                    mysqli_close($conn);
                    header("Location: index.php?success_order=true&order_id=" . $order_id . "&customer_name=" . urlencode($name));
                    exit();
                }
            } else {
                $page_alert_message = "Помилка при створенні замовлення: " . htmlspecialchars($stmt_order->error);
                $page_alert_type = 'danger';
            }
            if ($stmt_order instanceof mysqli_stmt) $stmt_order->close(); // Перевірка, чи $stmt_order ще існує
        } else {
            $page_alert_message = "Помилка підготовки запиту для створення замовлення: " . htmlspecialchars($conn->error);
            $page_alert_type = 'danger';
        }
    }
}

// 6. Встановлюємо заголовок сторінки
$page_title = "Оформлення замовлення - Інтернет-магазин книг";

// 7. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 9. Починаємо HTML-розмітку ?>
    <div class="section-title-container"><h2>Оформлення замовлення</h2></div>

<?php // Виведення повідомлень (якщо є) ?>
<?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
    <div class="alert alert-<?php echo $page_alert_type; ?>">
        <span class="alert-icon">
            <?php
            if ($page_alert_type === 'danger') echo '&#10008;';
            else echo '&#8505;';
            ?>
        </span>
        <?php echo $page_alert_message; ?>
    </div>
<?php endif; ?>

    <div class="checkout-navigation-buttons" style="margin-bottom: 20px;"> <?php // Додано відступ знизу ?>
        <button onclick="window.location.href='cart.php'" class="btn-generic btn-outline-secondary">
            <span class="icon" aria-hidden="true">❮</span> Повернутися до кошика
        </button>
    </div>

    <section class="panel-container checkout-form-panel"> <?php // Додано специфічний клас для можливих налаштувань ?>
        <form action="checkout.php" method="POST" class="checkout-form">

            <div class="form-group">
                <label for="name">Ім'я та Прізвище:</label>
                <input type="text" id="name" name="name" value="<?php
                // Використовуємо $name, якщо форма була відправлена і є помилка, інакше з сесії або порожнє
                echo htmlspecialchars(isset($_POST['name']) ? $_POST['name'] : (isset($_SESSION['username']) && $_SESSION['role'] !== 'admin' ? $_SESSION['username'] : ''));
                ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Електронна пошта:</label>
                <input type="email" id="email" name="email" value="<?php
                // Використовуємо $_SESSION['user_email'], яке ми домовилися зберігати
                echo htmlspecialchars(isset($_POST['email']) ? $_POST['email'] : (isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''));
                ?>" required>
            </div>

            <div class="form-group">
                <label for="address">Адреса доставки (Місто, вулиця, будинок, квартира):</label>
                <textarea id="address" name="address" rows="3" required><?php echo htmlspecialchars(isset($_POST['address']) ? $_POST['address'] : ''); ?></textarea> <?php // Зменшено rows ?>
            </div>

            <div class="form-group">
                <label for="phone">Телефон (у форматі +380xxxxxxxxx):</label>
                <input type="tel" id="phone" name="phone" pattern="^\+380[0-9]{9}$" placeholder="+380991234567" value="<?php echo htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : ''); ?>" required>
            </div>

            <div class="order-summary-details">
                <h3 class="panel-section-title">Склад замовлення:</h3> <?php // Використовуємо уніфікований клас ?>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <ul>
                        <?php foreach ($_SESSION['cart'] as $summary_item): // Змінено ім'я змінної ?>
                            <li>
                                <span><?php echo htmlspecialchars($summary_item['title']); ?> (<?php echo (int)$summary_item['quantity']; ?> шт.)</span>
                                <span><?php echo number_format((float)$summary_item['price'] * (int)$summary_item['quantity'], 2); ?> грн</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="total-amount"><strong>Загальна сума до сплати: <?php echo number_format(calculateTotalCheckout(), 2); ?> грн</strong></p> <?php // Зроблено жирнішим ?>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-generic btn-positive btn-lg btn-full-width" style="margin-top: 25px;"> <?php // Додано btn-lg та відступ ?>
                <span class="icon" aria-hidden="true">✔</span> Підтвердити замовлення
            </button>
        </form>
    </section>

<?php
// 10. Закриваємо з'єднання з БД, тільки якщо воно ще існує і активне
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 11. Підключаємо футер
include_once('includes/footer.php');
?>