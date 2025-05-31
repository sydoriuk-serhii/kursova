<?php
// Файл: add_to_cart.php

// 1. Запуск сесії (якщо ще не активна)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Підключення до бази даних
include_once('includes/db.php');

// 3. Перевірка, чи передано параметр book_id через POST та чи він є числом
if (isset($_POST['book_id']) && is_numeric($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id'];

    // 4. Перевірка, чи книга існує в базі даних
    $query_book = $conn->prepare("SELECT id, title, author, price, image FROM books WHERE id = ?"); // Вибираємо тільки потрібні поля
    if ($query_book) {
        $query_book->bind_param("i", $book_id);
        $query_book->execute();
        $result = $query_book->get_result();

        if ($result && $result->num_rows == 1) {
            $book = $result->fetch_assoc();
            $result->close(); // Закриваємо результат якомога раніше
            $query_book->close(); // Закриваємо стейтмент

            // Якщо кошик ще не існує в сесії, створюємо його
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $is_book_in_cart = false;
            $found_item_key = null;

            // Перевірка, чи книга вже є в кошику
            foreach ($_SESSION['cart'] as $item_key => $item_value) {
                if (isset($item_value['id']) && $item_value['id'] == $book_id) {
                    $is_book_in_cart = true;
                    $found_item_key = $item_key;
                    break;
                }
            }

            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'catalog.php';
            $message_param_text = ''; // Змінено ім'я змінної

            if (!$is_book_in_cart) {
                $_SESSION['cart'][] = [
                    'id' => $book['id'],
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'price' => (float)$book['price'], // Зберігаємо як float
                    'image' => $book['image'],
                    'quantity' => 1
                ];
                $message_param_text = "Книгу «" . htmlspecialchars($book['title']) . "» додано до кошика!";
            } else {
                // Збільшуємо кількість, якщо книга вже в кошику
                if (isset($_SESSION['cart'][$found_item_key]['quantity'])) {
                    $_SESSION['cart'][$found_item_key]['quantity']++;
                } else {
                    $_SESSION['cart'][$found_item_key]['quantity'] = 1; // На випадок, якщо quantity не було встановлено раніше
                }
                $message_param_text = "Кількість книги «" . htmlspecialchars($book['title']) . "» у кошику збільшено.";
            }

            $message_param = "message_cart=" . urlencode($message_param_text);


            // Формуємо URL для редиректу
            if (strpos($redirect_url, '?') !== false) {
                $final_redirect_url = $redirect_url . "&" . $message_param;
            } else {
                $final_redirect_url = $redirect_url . "?" . $message_param;
            }

            if (isset($conn)) mysqli_close($conn);
            header("Location: " . $final_redirect_url);
            exit();

        } else {
            // Якщо книга не знайдена в БД
            if ($result) $result->close();
            $query_book->close();
            if (isset($conn)) mysqli_close($conn);
            header("Location: catalog.php?message=" . urlencode("Помилка: Книгу не знайдено в базі даних."));
            exit();
        }
    } else {
        // Помилка підготовки запиту
        error_log("Помилка підготовки SQL-запиту (add_to_cart): " . $conn->error);
        if (isset($conn)) mysqli_close($conn);
        header("Location: catalog.php?message=" . urlencode("Сталася серверна помилка при додаванні книги до кошика. Спробуйте пізніше."));
        exit();
    }
} else {
    // Якщо параметр book_id не передано або він некоректний
    if (isset($conn)) mysqli_close($conn);
    header("Location: catalog.php?message=" . urlencode("Невірний запит для додавання до кошика."));
    exit();
}
?>