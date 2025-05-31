<?php
// Файл: catalog.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії (header.php це вже робить)
// if (session_status() == PHP_SESSION_NONE) {
// session_start();
// }

// 3. Ініціалізація змінних для повідомлень
$message_cart = '';
if (isset($_GET['message_cart'])) {
    $message_cart = htmlspecialchars($_GET['message_cart']); //
}
$message_general = '';
if (isset($_GET['message'])) {
    $message_general = htmlspecialchars($_GET['message']); //
}

// 4. Обробка фільтрів
$conditions = [];
$params = [];
$types = "";

if (isset($_GET['genre']) && $_GET['genre'] !== '') {
    $genre = $_GET['genre']; //
    $conditions[] = "genre = ?"; //
    $params[] = $genre; //
    $types .= "s"; //
}

if (isset($_GET['author']) && $_GET['author'] !== '') {
    $author = $_GET['author']; //
    $conditions[] = "author = ?"; //
    $params[] = $author; //
    $types .= "s"; //
}

$sql_where = "";
if (count($conditions) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $conditions); //
}

// 5. Формування та виконання запиту для отримання книг
$books_sql = "SELECT * FROM books $sql_where ORDER BY title ASC"; //
$result = null;

if (!empty($params)) {
    $stmt_books = $conn->prepare($books_sql); //
    if ($stmt_books) {
        $stmt_books->bind_param($types, ...$params); //
        $stmt_books->execute(); //
        $result = $stmt_books->get_result(); //
    } else {
        error_log("Помилка підготовки SQL-запиту (каталог): " . $conn->error); //
        $message_general = "Виникла помилка при завантаженні каталогу."; //
    }
} else {
    $result = mysqli_query($conn, $books_sql); //
    if (!$result) {
        error_log("Помилка SQL-запиту (каталог): " . mysqli_error($conn)); //
        $message_general = "Виникла помилка при завантаженні каталогу."; //
    }
}

// 6. Отримання списку жанрів та авторів для фільтрів
$genres_result_q = mysqli_query($conn, "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC"); //
$authors_result_q = mysqli_query($conn, "SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author ASC"); //

// 7. Встановлення заголовка сторінки
$page_title = "Каталог книг - Інтернет-магазин книг"; //

// 8. ПІДКЛЮЧЕННЯ ХЕДЕРА
// header.php тепер автоматично підключає css/style.css та css/catalog.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 9. Рядок <link rel="stylesheet" href="css/catalog.css"> ВИДАЛЕНО, оскільки header.php це робить ?>

<?php // 10. HTML-контент сторінки ?>
    <div class="section-title-container"><h2>Каталог книг</h2></div> <?php // ?>

<?php if (!empty($message_cart)): ?>
    <p class="success-message"><?php echo $message_cart; ?></p> <?php // ?>
<?php endif; ?>
<?php if (!empty($message_general)): ?>
    <p class="message error-message"><?php echo $message_general; ?></p> <?php // ?>
<?php endif; ?>

<?php // Змінюємо клас для форми фільтрів для відповідності оновленому CSS ?>
    <form method="GET" action="catalog.php" class="filter-form-panel"> <?php // ?>
        <label for="genre">Фільтрувати за жанром:</label> <?php // ?>
        <select name="genre" id="genre" onchange="this.form.submit()"> <?php // ?>
            <option value="">Усі жанри</option> <?php // ?>
            <?php if($genres_result_q && mysqli_num_rows($genres_result_q) > 0) : while ($row = mysqli_fetch_assoc($genres_result_q)): ?>
                <option value="<?php echo htmlspecialchars($row['genre']); ?>" <?php if (isset($_GET['genre']) && $_GET['genre'] == $row['genre']) echo 'selected'; ?>> <?php // ?>
                    <?php echo htmlspecialchars($row['genre']); ?>
                </option>
            <?php endwhile; endif; ?>
        </select>

        <label for="author">Фільтрувати за автором:</label> <?php // ?>
        <select name="author" id="author" onchange="this.form.submit()"> <?php // ?>
            <option value="">Усі автори</option> <?php // ?>
            <?php if($authors_result_q && mysqli_num_rows($authors_result_q) > 0) : while ($row = mysqli_fetch_assoc($authors_result_q)): ?>
                <option value="<?php echo htmlspecialchars($row['author']); ?>" <?php if (isset($_GET['author']) && $_GET['author'] == $row['author']) echo 'selected'; ?>> <?php // ?>
                    <?php echo htmlspecialchars($row['author']); ?>
                </option>
            <?php endwhile; endif; ?>
        </select>
        <?php if (!empty($_GET['genre']) || !empty($_GET['author'])): ?>
            <?php // Змінюємо клас для посилання "Скинути фільтри" ?>
            <a href="catalog.php" class="reset-filters-button">Скинути фільтри</a> <?php // ?>
        <?php endif; ?>
    </form>

    <div class="books"> <?php // ?>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <div class="book"> <?php // ?>
                    <a href="book.php?id=<?php echo $book['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">  <?php // ?>
                    </a>
                    <h3><a href="book.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3> <?php // ?>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p> <?php // ?>
                    <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p> <?php // ?>
                    <p class="book-description"><?php echo mb_substr(htmlspecialchars(strip_tags($book['description'])), 0, 80); ?>...</p> <?php // ?>
                    <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн</p> <?php // ?>

                    <?php // Видаляємо інлайновий стиль style="margin-top: 10px;" з форми ?>
                    <form action="add_to_cart.php" method="POST"> <?php // ?>
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>"> <?php // ?>
                        <button type="submit" class="btn-add-to-cart-small">Додати до кошика</button> <?php // ?>
                    </form>
                    <p><a href="book.php?id=<?php echo $book['id']; ?>" class="details-link">Детальніше</a></p> <?php // ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <?php // Замінюємо інлайновий стиль на клас .no-items-info ?>
            <p class="no-items-info"> <?php // ?>
                <?php echo (empty($params) && !$result) ? "Виникла помилка при завантаженні каталогу." : "За заданими критеріями книги не знайдені."; ?>
            </p>
        <?php endif; ?>
    </div>

<?php
// 11. Звільнення результатів та закриття з'єднань
if (isset($stmt_books) && $stmt_books instanceof mysqli_stmt) $stmt_books->close(); //
if (isset($result) && $result instanceof mysqli_result) mysqli_free_result($result); //
if (isset($genres_result_q) && $genres_result_q instanceof mysqli_result) mysqli_free_result($genres_result_q); //
if (isset($authors_result_q) && $authors_result_q instanceof mysqli_result) mysqli_free_result($authors_result_q); //

if (isset($conn)) {
    mysqli_close($conn); //
}

// 12. Підключення футера
include_once('includes/footer.php'); //
?>