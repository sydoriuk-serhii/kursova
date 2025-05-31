<?php
// Файл: checkout.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії
if (session_status() == PHP_SESSION_NONE) {
    session_start(); //
}

// 3. Функція обчислення загальної суми
function calculateTotalCheckout() {
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['price']) ? $item['price'] : 0; //
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1; //
            $total += $price * $quantity; //
        }
    }
    return $total;
}

// 4. Якщо кошик порожній, не дозволяємо оформлювати замовлення
if (empty($_SESSION['cart'])) {
    header("Location: cart.php?message=" . urlencode("Ваш кошик порожній. Неможливо оформити замовлення.")); //
    exit();
}

// 5. Обробка POST-запиту
if ($_SERVER['REQUEST_METHOD'] == 'POST') { //
    $name = mysqli_real_escape_string($conn, $_POST['name']); //
    $email = mysqli_real_escape_string($conn, $_POST['email']); //
    $address = mysqli_real_escape_string($conn, $_POST['address']); //
    $phone = mysqli_real_escape_string($conn, $_POST['phone']); //
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL; //
    $total = calculateTotalCheckout(); //

    if (empty($name) || empty($email) || empty($address) || empty($phone) || !filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match("/^\+380[0-9]{9}$/", $phone)) { //
        $error_message = "Будь ласка, заповніть усі поля коректно. Телефон має бути у форматі +380xxxxxxxxx."; //
    } else {
        $order_sql_insert = "INSERT INTO orders (user_id, name, email, address, phone, total, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())"; //
        $stmt_order = $conn->prepare($order_sql_insert); //

        if ($stmt_order) {
            $stmt_order->bind_param(is_null($user_id) ? "sssssd" : "issssd", $user_id, $name, $email, $address, $phone, $total); //

            if ($stmt_order->execute()) { //
                $order_id = mysqli_insert_id($conn); //

                if (!empty($_SESSION['cart'])) {
                    $item_sql_insert = "INSERT INTO order_items (order_id, book_id, quantity, price) VALUES (?, ?, ?, ?)"; //
                    $stmt_item = $conn->prepare($item_sql_insert); //

                    foreach ($_SESSION['cart'] as $item) {
                        $book_id = $item['id']; //
                        $quantity = isset($item['quantity']) ? $item['quantity'] : 1; //
                        $price = isset($item['price']) ? $item['price'] : 0; //
                        $stmt_item->bind_param("iiid", $order_id, $book_id, $quantity, $price); //
                        $stmt_item->execute(); //
                    }
                    $stmt_item->close(); //
                }
                unset($_SESSION['cart']); //
                $stmt_order->close(); //
                mysqli_close($conn); //
                header("Location: index.php?success_order=true&order_id=" . $order_id . "&customer_name=" . urlencode($name)); //
                exit();
            } else {
                $error_message = "Помилка при створенні замовлення: " . $stmt_order->error; //
            }
            // $stmt_order->close(); // Цей рядок вже є вище, тут він зайвий
        } else {
            $error_message = "Помилка підготовки запиту: " . $conn->error; //
        }
    }
}

// 6. Встановлюємо заголовок сторінки
$page_title = "Оформлення замовлення - Інтернет-магазин книг"; //

// 7. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/checkout.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 8. Рядок <link rel="stylesheet" href="css/checkout.css"> ВИДАЛЕНО ?>

<?php // 9. Починаємо HTML-розмітку ?>
<?php // Змінюємо клас контейнера заголовка ?>
    <div class="section-title-container"><h2>Оформлення замовлення</h2></div> <?php // ?>

    <div class="checkout-navigation-buttons"> <?php // ?>
        <?php // Видаляємо інлайновий стиль та додаємо клас .btn-secondary ?>
        <button onclick="window.location.href='cart.php'" class="btn-generic btn-secondary">Повернутися до кошика</button> <?php // ?>
    </div>

<?php // Змінюємо клас контейнера форми ?>
    <section class="panel-container"> <?php // ?>
        <form action="checkout.php" method="POST" class="checkout-form"> <?php // ?>
            <?php if (isset($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p> <?php // ?>
            <?php endif; ?>

            <label for="name">Ім'я та Прізвище:</label> <?php // ?>
            <input type="text" id="name" name="name" value="<?php
            if (isset($_POST['name'])) {
                echo htmlspecialchars($_POST['name']); //
            } elseif (isset($_SESSION['username']) && $_SESSION['role'] !== 'admin') {
                echo htmlspecialchars($_SESSION['username']); //
            }
            ?>" required>

            <label for="email">Електронна пошта:</label> <?php // ?>
            <input type="email" id="email" name="email" value="<?php
            if (isset($_POST['email'])) {
                echo htmlspecialchars($_POST['email']); //
            } elseif (isset($_SESSION['user_id'])) {
                $current_user_id_for_email = $_SESSION['user_id']; //
                $user_email_query_checkout = $conn->prepare("SELECT email FROM users WHERE id = ?"); //
                if ($user_email_query_checkout) {
                    $user_email_query_checkout->bind_param("i", $current_user_id_for_email); //
                    $user_email_query_checkout->execute(); //
                    $user_email_result_checkout = $user_email_query_checkout->get_result(); //
                    if ($user_email_data_checkout = $user_email_result_checkout->fetch_assoc()) { //
                        echo htmlspecialchars($user_email_data_checkout['email']); //
                    }
                    $user_email_query_checkout->close(); //
                }
            }
            ?>" required>

            <label for="address">Адреса доставки (Місто, вулиця, будинок, квартира):</label> <?php // ?>
            <textarea id="address" name="address" rows="4" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea> <?php // ?>

            <label for="phone">Телефон (у форматі +380xxxxxxxxx):</label> <?php // ?>
            <input type="tel" id="phone" name="phone" pattern="^\+380[0-9]{9}$" placeholder="+380xxxxxxxxx" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required> <?php // ?>

            <?php // Змінюємо клас блоку "Склад замовлення" ?>
            <div class="order-summary-details"> <?php // ?>
                <h3>Склад замовлення:</h3> <?php // ?>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <ul>
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <li>
                                <span><?php echo htmlspecialchars($item['title']); ?> (<?php echo $item['quantity']; ?> шт.)</span> <?php // ?>
                                <span><?php echo number_format($item['price'] * $item['quantity'], 2); ?> грн</span> <?php // ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php // Додаємо клас до абзацу з загальною сумою ?>
                    <p class="total-amount"><strong>Загальна сума до сплати:</strong> <?php echo number_format(calculateTotalCheckout(), 2); ?> грн</p> <?php // ?>
                <?php endif; ?>
            </div>

            <?php // Додаємо класи .btn-positive та .btn-full-width до кнопки ?>
            <button type="submit" class="btn-generic btn-positive btn-full-width">Підтвердити замовлення</button> <?php // ?>
        </form>
    </section>

<?php
// 10. Закриваємо з'єднання з БД
if (isset($conn) && mysqli_ping($conn)) {
    mysqli_close($conn); //
}

// 11. Підключаємо футер
include_once('includes/footer.php'); //
?>