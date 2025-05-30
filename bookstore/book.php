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
        header("Location: catalog.php?message=Книгу не знайдено");
        exit();
    }
} else {
    // Якщо id не передано або воно неправильне, перенаправляємо на каталог
    header("Location: catalog.php?message=Неправильний запит");
    exit();
}

$page_title = htmlspecialchars($book['title']) . " - Деталі книги";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/book.css"> <div style="text-align: center; margin-bottom: 20px;">
    <button onclick="history.back()" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Повернутися назад</button>
</div>

    <section class="book-detail">
        <h2>Деталі книги</h2>

        <div class="book-card">
            <div class="book-image">
                <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
            </div>
            <div class="book-info">
                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн.</p>
                <p><strong>Опис:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>

                <form action="add_to_cart.php" method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                    <button type="submit" class="btn-add-to-cart">Додати в кошик</button>
                </form>
            </div>
        </div>
    </section>

<?php
// Закриття з'єднання з базою даних
mysqli_close($conn);
include('includes/footer.php');
?>