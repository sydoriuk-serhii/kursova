<?php
// Підключення до бази даних
include('includes/db.php');
session_start();

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

        // Додаємо книгу до кошика, якщо її ще немає
        $is_book_in_cart = false;
        foreach ($_SESSION['cart'] as $item) {
            if ($item['id'] == $book_id) {
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
                'image' => $book['image']
            ];

            // Перенаправлення на сторінку кошика
            header("Location: cart.php");
            exit();
        } else {
            // Якщо книга вже в кошику, перенаправляємо назад з повідомленням
            header("Location: catalog.php?message=Книга вже в кошику");
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
