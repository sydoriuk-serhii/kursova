<?php
// НЕ запускаємо session_start(), якщо вона вже запущена в header.php
include_once('includes/db.php');
include_once('includes/header.php');
$message_cart = ''; // Для повідомлень про кошик
if (isset($_GET['message_cart'])) {
    $message_cart = htmlspecialchars($_GET['message_cart']);
}
$message_general = ''; // Для інших повідомлень
if (isset($_GET['message'])) {
    $message_general = htmlspecialchars($_GET['message']);
}


// Фільтри
$conditions = [];
$params = [];
$types = "";

// Фільтруємо за жанром
if (isset($_GET['genre']) && $_GET['genre'] !== '') {
    $genre = $_GET['genre'];
    $conditions[] = "genre = ?";
    $params[] = $genre;
    $types .= "s";
}

// Фільтруємо за автором
if (isset($_GET['author']) && $_GET['author'] !== '') {
    $author = $_GET['author'];
    $conditions[] = "author = ?";
    $params[] = $author;
    $types .= "s";
}

$sql_where = "";
if (count($conditions) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $conditions);
}

$books_sql = "SELECT * FROM books $sql_where ORDER BY title ASC";

if (!empty($params)) {
    $stmt_books = $conn->prepare($books_sql);
    $stmt_books->bind_param($types, ...$params);
    $stmt_books->execute();
    $result = $stmt_books->get_result();
} else {
    $result = mysqli_query($conn, $books_sql);
}


// Список жанрів та авторів для фільтра
$genres_result_q = mysqli_query($conn, "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC");
$authors_result_q = mysqli_query($conn, "SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author ASC");

$page_title = "Каталог книг - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/catalog.css">

    <div class="section-title-container"><h2>Каталог книг</h2></div>

<?php if (!empty($message_cart)): ?>
    <p class="success-message"><?php echo $message_cart; ?></p>
<?php endif; ?>
<?php if (!empty($message_general)): ?>
    <p class="message"><?php echo $message_general; ?></p>
<?php endif; ?>


    <form method="GET" action="catalog.php" class="filter-form">
        <label for="genre">Фільтрувати за жанром:</label>
        <select name="genre" id="genre" onchange="this.form.submit()">
            <option value="">Усі жанри</option>
            <?php if($genres_result_q) : while ($row = mysqli_fetch_assoc($genres_result_q)): ?>
                <option value="<?php echo htmlspecialchars($row['genre']); ?>" <?php if (isset($_GET['genre']) && $_GET['genre'] == $row['genre']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['genre']); ?>
                </option>
            <?php endwhile; endif; ?>
        </select>

        <label for="author">Фільтрувати за автором:</label>
        <select name="author" id="author" onchange="this.form.submit()">
            <option value="">Усі автори</option>
            <?php if($authors_result_q) : while ($row = mysqli_fetch_assoc($authors_result_q)): ?>
                <option value="<?php echo htmlspecialchars($row['author']); ?>" <?php if (isset($_GET['author']) && $_GET['author'] == $row['author']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['author']); ?>
                </option>
            <?php endwhile; endif; ?>
        </select>
        <?php if (!empty($_GET['genre']) || !empty($_GET['author'])): ?>
            <a href="catalog.php" class="reset-filters-link">Скинути фільтри</a>
        <?php endif; ?>
    </form>

    <div class="books">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <div class="book">
                    <a href="book.php?id=<?php echo $book['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <h3><a href="book.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <p class="book-description"><?php echo mb_substr(htmlspecialchars(strip_tags($book['description'])), 0, 80); ?>...</p>
                    <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн</p>

                    <form action="add_to_cart.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" class="btn-add-to-cart-small">Додати до кошика</button>
                    </form>
                    <p><a href="book.php?id=<?php echo $book['id']; ?>" class="details-link">Детальніше</a></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%; font-size: 1.1em; margin-top: 20px;">За заданими критеріями книги не знайдені.</p>
        <?php endif; ?>
    </div>

<?php
if (isset($stmt_books)) $stmt_books->close();
mysqli_close($conn);
include('includes/footer.php');
?>