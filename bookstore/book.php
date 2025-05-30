<?php
// Підключення до бази даних
include('includes/db.php');

// Перевірка, чи передано параметр id книги через URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = $_GET['id'];

    // Запит для отримання даних про книгу
    $query = "SELECT * FROM books WHERE id = $book_id";
    $result = mysqli_query($conn, $query);

    // Перевірка, чи книга знайдена
    if (mysqli_num_rows($result) == 1) {
        $book = mysqli_fetch_assoc($result);
    } else {
        // Якщо книга не знайдена, перенаправляємо на каталог
        header("Location: catalog.php");
        exit();
    }
} else {
    // Якщо id не передано або воно неправильне, перенаправляємо на каталог
    header("Location: catalog.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - Деталі книги</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/book.css">
</head>
<body>

    <!-- Шапка сайту -->
    <header>
        <h1>Інтернет-магазин книг</h1>
        <nav>
            <ul>
                <li><a href="index.php">Головна</a></li>
                <li><a href="catalog.php">Каталог книг</a></li>
                <li><a href="login.php">Вхід</a></li>
                <li><a href="register.php">Реєстрація</a></li>
            </ul>
        </nav>
    </header>

    <!-- Основний контент -->
    <main>
        <section class="book-detail">
            <h2>Деталі книги</h2>

            <div class="book-card">
                <div class="book-image">
                    <img src="uploads/<?php echo $book['image']; ?>" alt="book image">
                </div>
                <div class="book-info">
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн.</p>
                    <p><strong>Опис:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>

                    <!-- Кнопка для додавання в кошик -->
                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" class="btn-add-to-cart">Додати в кошик</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Футер -->
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>

</body>
</html>

<?php
// Закриття з'єднання з базою даних
mysqli_close($conn);
?>
