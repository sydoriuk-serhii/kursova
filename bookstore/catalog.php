<?php
// НЕ запускаємо session_start(), якщо вона вже запущена в header.php
// session_start(); // Цей рядок тепер у header.php
include('includes/db.php');

// Обробка додавання в кошик тепер повністю через add_to_cart.php
// Видалено блок if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id']))

$message = '';
if (isset($_GET['message'])) {
    $message = htmlspecialchars($_GET['message']);
}


// Фільтри
$genre_filter_sql = '';
$author_filter_sql = '';
$conditions = [];

// Фільтруємо за жанром
if (isset($_GET['genre']) && $_GET['genre'] !== '') {
    $genre = mysqli_real_escape_string($conn, $_GET['genre']);
    $conditions[] = "genre = '$genre'";
}

// Фільтруємо за автором
if (isset($_GET['author']) && $_GET['author'] !== '') {
    $author = mysqli_real_escape_string($conn, $_GET['author']);
    $conditions[] = "author = '$author'";
}

$sql_where = "";
if (count($conditions) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $conditions);
}

// Отримуємо книги згідно з фільтрами
$books_sql = "SELECT * FROM books $sql_where ORDER BY title ASC";
$result = mysqli_query($conn, $books_sql);

// Список жанрів та авторів для фільтра
$genres_result = mysqli_query($conn, "SELECT DISTINCT genre FROM books ORDER BY genre ASC");
$authors_result = mysqli_query($conn, "SELECT DISTINCT author FROM books ORDER BY author ASC");

$page_title = "Каталог книг - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/catalog.css">

<?php if (!empty($message)): ?>
    <p class="message" style="text-align: center; padding: 10px; background-color: #e6fffa; border: 1px solid #00bfa5; color: #00796b; margin: 15px auto; width: 80%; max-width: 600px; border-radius: 5px;"><?php echo $message; ?></p>
<?php endif; ?>

    <div style="text-align: center; margin-bottom: 20px;">
        <button onclick="history.back()" class="btn-back">Назад</button>
    </div>

    <form method="GET" action="catalog.php" class="filter-form">
        <label for="genre">Фільтрувати за жанром:</label>
        <select name="genre" id="genre" onchange="this.form.submit()">
            <option value="">Усі жанри</option>
            <?php while ($row = mysqli_fetch_assoc($genres_result)): ?>
                <option value="<?php echo htmlspecialchars($row['genre']); ?>" <?php if (isset($_GET['genre']) && $_GET['genre'] == $row['genre']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['genre']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="author" style="margin-left: 20px;">Фільтрувати за автором:</label>
        <select name="author" id="author" onchange="this.form.submit()">
            <option value="">Усі автори</option>
            <?php while ($row = mysqli_fetch_assoc($authors_result)): ?>
                <option value="<?php echo htmlspecialchars($row['author']); ?>" <?php if (isset($_GET['author']) && $_GET['author'] == $row['author']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['author']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <?php if (!empty($_GET['genre']) || !empty($_GET['author'])): ?>
            <a href="catalog.php" style="margin-left: 15px; text-decoration: none; color: #007bff; font-weight: bold;">Скинути фільтри</a>
        <?php endif; ?>
    </form>

    <div class="book-list">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <div class="book-item">
                    <a href="book.php?id=<?php echo $book['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <h3><a href="book.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <p class="book-description"><?php echo mb_substr(htmlspecialchars($book['description']), 0, 100); ?>...</p>
                    <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн</p>

                    <form action="add_to_cart.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit">Додати до кошика</button>
                    </form>

                    <p><a href="book.php?id=<?php echo $book['id']; ?>" class="details-link">Детальніше</a></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; width: 100%;">За заданими критеріями книги не знайдені.</p>
        <?php endif; ?>
    </div>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>