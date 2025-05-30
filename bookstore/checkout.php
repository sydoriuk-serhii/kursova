<?php
// session_start(); // Вже в header.php
include('includes/db.php');

// Функція обчислення загальної суми
function calculateTotal() {
    $total = 0;
    if (!empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = isset($item['price']) ? $item['price'] : 0;
            $quantity = isset($item['quantity']) ? $item['quantity'] : 1; // Беремо кількість
            $total += $price * $quantity;
        }
    }
    return $total;
}

// Якщо кошик порожній, не дозволяємо оформлювати замовлення
if (empty($_SESSION['cart'])) {
    header("Location: cart.php?message=Ваш кошик порожній. Неможливо оформити замовлення.");
    exit();
}


// Обробка POST-запиту
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // Визначаємо user_id, якщо користувач авторизований
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : NULL;
    // Якщо user_id = 0 (адмін), зберігаємо як NULL, оскільки адмін зазвичай не робить замовлення для себе.
    // Або можна зберігати 0, якщо це передбачено логікою. Для узгодженості з вашою БД, де є user_id = 0 для замовлення,
    // можна залишити як є. Однак, більш типово, щоб user_id був NULL для незареєстрованих.
    // Давайте припустимо, що user_id = 0 це спеціальний випадок, який ми хочемо зберегти.
    // $user_id = (isset($_SESSION['user_id']) && $_SESSION['user_id'] != 0) ? $_SESSION['user_id'] : NULL;

    $total = calculateTotal();

    // Зберігаємо замовлення
    // Переконуємося, що user_id правильно обробляється (число або NULL)
    if ($user_id === NULL) {
        $order_sql = "INSERT INTO orders (user_id, name, email, address, phone, total, created_at) 
                      VALUES (NULL, '$name', '$email', '$address', '$phone', '$total', NOW())";
    } else {
        $order_sql = "INSERT INTO orders (user_id, name, email, address, phone, total, created_at) 
                      VALUES ('$user_id', '$name', '$email', '$address', '$phone', '$total', NOW())";
    }

    if (mysqli_query($conn, $order_sql)) {
        $order_id = mysqli_insert_id($conn);

        // Зберігаємо товари замовлення
        if (!empty($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) { // Змінено для нового формату кошика
                $book_id = $item['id'];
                $quantity = isset($item['quantity']) ? $item['quantity'] : 1;
                $price = isset($item['price']) ? $item['price'] : 0;

                $item_sql = "INSERT INTO order_items (order_id, book_id, quantity, price) 
                             VALUES ('$order_id', '$book_id', '$quantity', '$price')";
                mysqli_query($conn, $item_sql);
            }
        }

        // Очищення кошика
        unset($_SESSION['cart']);

        // Перенаправлення на сторінку подяки або головну
        header("Location: index.php?success_order=true&order_id=" . $order_id);
        exit();
    } else {
        $error_message = "Помилка при створенні замовлення: " . mysqli_error($conn);
    }
}

$page_title = "Оформлення замовлення - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/checkout.css"> <div style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.location.href='cart.php'" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right:10px;">Повернутися до кошика</button>
    <button onclick="history.back()" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Назад</button>
</div>


    <form action="checkout.php" method="POST" class="checkout-form">
        <h2>Інформація про замовлення</h2>

        <?php if (isset($error_message)): ?>
            <p class="error-message" style="color: red; background-color: #ffebee; padding: 10px; border-radius: 5px; border: 1px solid #ef5350;"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <label for="name">Ім'я та Прізвище:</label>
        <input type="text" id="name" name="name" value="<?php echo isset($_SESSION['username']) && $_SESSION['role'] !== 'admin' ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>

        <label for="email">Електронна пошта:</label>
        <input type="email" id="email" name="email" value="<?php
        // Спробуємо отримати email, якщо користувач авторизований
        if (isset($_SESSION['user_id'])) {
            $current_user_id = $_SESSION['user_id'];
            $user_email_query = mysqli_query($conn, "SELECT email FROM users WHERE id = '$current_user_id'");
            if ($user_email_data = mysqli_fetch_assoc($user_email_query)) {
                echo htmlspecialchars($user_email_data['email']);
            }
        }
        ?>" required>

        <label for="address">Адреса доставки (Місто, вулиця, будинок, квартира):</label>
        <textarea id="address" name="address" rows="4" required></textarea>

        <label for="phone">Телефон (у форматі +380xxxxxxxxx):</label>
        <input type="tel" id="phone" name="phone" pattern="^\+380[0-9]{9}$" placeholder="+380xxxxxxxxx" required>

        <div class="order-summary">
            <h3>Склад замовлення:</h3>
            <?php if (!empty($_SESSION['cart'])): ?>
                <ul>
                    <?php foreach ($_SESSION['cart'] as $item): ?>
                        <li><?php echo htmlspecialchars($item['title']); ?> (<?php echo $item['quantity']; ?> шт.) - <?php echo number_format($item['price'] * $item['quantity'], 2); ?> грн</li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Загальна сума до сплати:</strong> <?php echo number_format(calculateTotal(), 2); ?> грн</p>
            <?php endif; ?>
        </div>

        <button type="submit">Підтвердити замовлення</button>
    </form>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>