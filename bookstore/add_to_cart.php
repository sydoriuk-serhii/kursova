<?php
// Файл: add_to_cart.php

// 1. Запуск сесії (якщо ще не активна)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Підключення до бази даних
include_once('includes/db.php'); // include_once для уникнення повторного підключення, якщо db.php вже був десь викликаний

// 3. Перевірка, чи передано параметр book_id через POST та чи він є числом
if (isset($_POST['book_id']) && is_numeric($_POST['book_id'])) {
    $book_id = (int)$_POST['book_id']; // Приведення до цілого числа

    // 4. Перевірка, чи книга існує в базі даних (використовуємо підготовлений запит)
    $query_book = $conn->prepare("SELECT * FROM books WHERE id = ?");
    if ($query_book) {
        $query_book->bind_param("i", $book_id);
        $query_book->execute();
        $result = $query_book->get_result();

        if ($result->num_rows == 1) {
            $book = $result->fetch_assoc();

            // Якщо кошик ще не існує в сесії, створюємо його
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { // Додав перевірку, чи це масив
                $_SESSION['cart'] = [];
            }

            $is_book_in_cart = false;
            $found_item_key = null; // Для збереження ключа знайденого товару

            // Перевірка, чи книга вже є в кошику
            foreach ($_SESSION['cart'] as $item_key => $item_value) {
                // Переконуємося, що 'id' існує в елементі кошика
                if (isset($item_value['id']) && $item_value['id'] == $book_id) {
                    $is_book_in_cart = true;
                    $found_item_key = $item_key; // Зберігаємо ключ
                    break;
                }
            }

            $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'catalog.php';
            $message_param = '';

            if (!$is_book_in_cart) {
                $_SESSION['cart'][] = [ // Додаємо як новий елемент масиву
                    'id' => $book['id'],
                    'title' => $book['title'],
                    'author' => $book['author'],
                    'price' => $book['price'],
                    'image' => $book['image'],
                    'quantity' => 1 // Початкова кількість
                ];
                $message_param = "message_cart=" . urlencode("Книгу '" . htmlspecialchars($book['title']) . "' додано до кошика!");
            } else {
                // Якщо книга вже в кошику, можна збільшити кількість (якщо така логіка потрібна)
                // Наприклад: $_SESSION['cart'][$found_item_key]['quantity']++;
                // Або просто повідомити, що вона вже там
                $message_param = "message_cart=" . urlencode("Книга '" . htmlspecialchars($book['title']) . "' вже є у вашому кошику.");
            }

            // Формуємо URL для редиректу
            if (strpos($redirect_url, '?') !== false) {
                header("Location: " . $redirect_url . "&" . $message_param);
            } else {
                header("Location: " . $redirect_url . "?" . $message_param);
            }
            $query_book->close(); // Закриваємо підготовлений запит
            mysqli_close($conn);  // Закриваємо з'єднання з БД
            exit();

        } else {
            // Якщо книга не знайдена в БД
            $query_book->close();
            mysqli_close($conn);
            header("Location: catalog.php?message=" . urlencode("Помилка: Книгу не знайдено в базі даних."));
            exit();
        }
    } else {
        // Помилка підготовки запиту
        error_log("Помилка підготовки SQL-запиту (add_to_cart): " . $conn->error);
        mysqli_close($conn);
        header("Location: catalog.php?message=" . urlencode("Сталася серверна помилка. Спробуйте пізніше."));
        exit();
    }
} else {
    // Якщо параметр book_id не передано або він некоректний
    if (isset($conn)) mysqli_close($conn); // Закриваємо з'єднання, якщо воно було відкрито
    header("Location: catalog.php?message=" . urlencode("Невірний запит для додавання до кошика."));
    exit();
}
?>