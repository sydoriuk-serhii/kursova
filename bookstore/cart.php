<?php
// Підключення до бази даних
include('includes/db.php');
// session_start(); // Вже в header.php

// Перевірка, чи є кошик в сесії
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    // Якщо кошик порожній, можна показати повідомлення на цій же сторінці
    // замість редіректу, щоб користувач бачив навігацію
    $cart_empty_message = "Ваш кошик порожній. <a href='catalog.php'>Перейти до каталогу</a>";
}

// Функція для обчислення загальної вартості кошика
function calculateTotal($cart) {
    $total = 0;
    if (is_array($cart)) {
        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
    }
    return $total;
}

// Якщо користувач видаляє книгу з кошика
if (isset($_GET['remove_id'])) {
    $remove_id = $_GET['remove_id'];

    // Видалення книги з кошика
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['id'] == $remove_id) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Перевстановлюємо індекси масиву
                header("Location: cart.php?message=Книгу видалено з кошика.");
                exit();
            }
        }
    }
}

// Оновлення кількості
if (isset($_POST['update_quantity']) && isset($_POST['item_id']) && isset($_POST['quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = intval($_POST['quantity']);

    if ($quantity > 0 && isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['id'] == $item_id) {
                $_SESSION['cart'][$index]['quantity'] = $quantity;
                header("Location: cart.php?message=Кількість оновлено.");
                exit();
            }
        }
    } elseif ($quantity <= 0) {
        // Якщо кількість 0 або менше, видаляємо товар
        foreach ($_SESSION['cart'] as $index => $item) {
            if ($item['id'] == $item_id) {
                unset($_SESSION['cart'][$index]);
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                header("Location: cart.php?message=Книгу видалено з кошика.");
                exit();
            }
        }
    }
}


$page_title = "Ваш кошик - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/cart.css"> <section class="cart">
    <h2>Ваш кошик</h2>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert" style="padding: 10px; background-color: #e6fffa; border: 1px solid #00bfa5; color: #00796b; margin-bottom: 15px; border-radius: 5px;"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>

    <?php if (isset($cart_empty_message)): ?>
        <p style="text-align: center; font-size: 1.1em;"><?php echo $cart_empty_message; ?></p>
    <?php elseif (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
        <div class="cart-items">
            <?php foreach ($_SESSION['cart'] as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-image">
                        <a href="book.php?id=<?php echo $item['id']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                        </a>
                    </div>
                    <div class="cart-item-info">
                        <h3><a href="book.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['title']); ?></a></h3>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($item['author']); ?></p>
                        <p><strong>Ціна за одиницю:</strong> <?php echo number_format($item['price'], 2); ?> грн.</p>
                        <form action="cart.php" method="POST" class="update-quantity-form">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <label for="quantity_<?php echo $item['id']; ?>">Кількість:</label>
                            <input type="number" id="quantity_<?php echo $item['id']; ?>" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" style="width: 60px; padding: 5px;">
                            <button type="submit" name="update_quantity" class="btn-update-quantity">Оновити</button>
                        </form>
                        <p><strong>Сума:</strong> <?php echo number_format($item['price'] * $item['quantity'], 2); ?> грн.</p>
                        <a href="cart.php?remove_id=<?php echo $item['id']; ?>" class="remove-item" onclick="return confirm('Ви впевнені, що хочете видалити цю книгу з кошика?');">Видалити</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-total">
            <h3>Загальна сума: <?php echo number_format(calculateTotal($_SESSION['cart']), 2); ?> грн.</h3>
        </div>

        <div class="cart-actions">
            <a href="checkout.php" class="btn-checkout">Оформити замовлення</a>
            <a href="catalog.php" class="btn-continue">Продовжити покупки</a>
        </div>
    <?php else: ?>
        <p style="text-align: center; font-size: 1.1em;">Ваш кошик порожній. <a href='catalog.php'>Перейти до каталогу</a></p>
    <?php endif; ?>
</section>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>