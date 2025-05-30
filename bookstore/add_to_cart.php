<?php
// Підключення до бази даних
include('includes/db.php');
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Перевірка, чи передано параметр book_id через POST
if (isset($_POST['book_id']) && is_numeric($_POST['book_id'])) {
    $book_id = $_POST['book_id'];

    // Перевірка, чи книга існує в базі даних
    $query = "SELECT * FROM books WHERE id = $book_id";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $book = mysqli_fetch_assoc($result);

        // Якщо кошик ще не існує в сесії, створюємо його
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Перевірка, чи книга вже є в кошику
        $is_book_in_cart = false;
        foreach ($_SESSION['cart'] as $item_key => $item_value) {
            if ($item_value['id'] == $book_id) {
                // Тут можна додати логіку збільшення кількості, якщо потрібно
                // Наприклад: $_SESSION['cart'][$item_key]['quantity'] += 1;
                $is_book_in_cart = true;
                break;
            }
        }

        if (!$is_book_in_cart) {
            $_SESSION['cart'][] = [
                'id' => $book['id'],
                'title' => $book['title'],
                'author' => $book['author'],
                'price' => $book['price'],
                'image' => $book['image'],
                'quantity' => 1 // Додаємо кількість
            ];
            // Повернення на попередню сторінку з повідомленням про успіх
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'catalog.php';
            // Додаємо параметр до URL, щоб уникнути кешування та показати повідомлення
            if (strpos($redirect_url, '?') !== false) {
                header("Location: " . $redirect_url . "&message_cart=Книгу додано до кошика!");
            } else {
                header("Location: " . $redirect_url . "?message_cart=Книгу додано до кошика!");
            }
            exit();
        } else {
            // Якщо книга вже в кошику, перенаправляємо назад з повідомленням
            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'catalog.php';
            if (strpos($redirect_url, '?') !== false) {
                header("Location: " . $redirect_url . "&message_cart=Книга вже є у вашому кошику.");
            } else {
                header("Location: " . $redirect_url . "?message_cart=Книга вже є у вашому кошику.");
            }
            exit();
        }
    } else {
        // Якщо книга не знайдена, перенаправляємо назад
        header("Location: catalog.php?message=Книга не знайдена");
        exit();
    }
} else {
    // Якщо параметр book_id не передано або він некоректний, перенаправляємо назад
    header("Location: catalog.php?message=Невірний запит");
    exit();
}

// Закриття з'єднання з базою даних
mysqli_close($conn);
?>