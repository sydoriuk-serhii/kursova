<?php
// Файл: catalog.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії вже відбувається в header.php

// 3. Ініціалізація змінних для повідомлень
$page_alert_message = '';
$page_alert_type = '';

if (isset($_GET['message_cart'])) {
    $page_alert_message = htmlspecialchars($_GET['message_cart']);
    $page_alert_type = 'success';
} elseif (isset($_GET['message'])) { // Використовуємо elseif, щоб повідомлення не перезаписувалися
    $page_alert_message = htmlspecialchars($_GET['message']);
    // Визначаємо тип помилки більш надійно
    if (strpos(strtolower($page_alert_message), 'помилка') !== false || strpos(strtolower($page_alert_message), 'error') !== false || strpos(strtolower($page_alert_message), 'не знайдено') !== false) {
        $page_alert_type = 'danger';
    } else {
        $page_alert_type = 'info'; // За замовчуванням - інформаційне
    }
}


// 4. Обробка фільтрів
$conditions = [];
$params = [];
$types = "";

$current_genre = isset($_GET['genre']) ? $_GET['genre'] : '';
$current_author = isset($_GET['author']) ? $_GET['author'] : '';

if ($current_genre !== '') {
    $conditions[] = "genre = ?";
    $params[] = $current_genre;
    $types .= "s";
}

if ($current_author !== '') {
    $conditions[] = "author = ?";
    $params[] = $current_author;
    $types .= "s";
}

$sql_where = "";
if (count($conditions) > 0) {
    $sql_where = "WHERE " . implode(" AND ", $conditions);
}

// 5. Формування та виконання запиту для отримання книг
$books_sql = "SELECT * FROM books $sql_where ORDER BY title ASC";
$result = null; // Ініціалізуємо $result

if (!empty($params)) {
    $stmt_books = $conn->prepare($books_sql);
    if ($stmt_books) {
        $stmt_books->bind_param($types, ...$params);
        $stmt_books->execute();
        $result = $stmt_books->get_result();
        if (!$result && empty($page_alert_message)) { // Якщо запит не вдався і немає іншого повідомлення
            $page_alert_message = "Виникла помилка при завантаженні каталогу (stmt).";
            $page_alert_type = 'danger';
        }
    } else {
        error_log("Помилка підготовки SQL-запиту (каталог): " . $conn->error);
        if (empty($page_alert_message)) {
            $page_alert_message = "Виникла помилка при завантаженні каталогу.";
            $page_alert_type = 'danger';
        }
    }
} else {
    $query_result = mysqli_query($conn, $books_sql); // Використовуємо іншу змінну, щоб не конфліктувати з $result
    if ($query_result) {
        $result = $query_result;
    } else {
        error_log("Помилка SQL-запиту (каталог): " . mysqli_error($conn));
        if (empty($page_alert_message)) {
            $page_alert_message = "Виникла помилка при завантаженні каталогу.";
            $page_alert_type = 'danger';
        }
    }
}

// 6. Отримання списку жанрів та авторів для фільтрів
$genres_result_q = mysqli_query($conn, "SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL AND genre != '' ORDER BY genre ASC");
$authors_result_q = mysqli_query($conn, "SELECT DISTINCT author FROM books WHERE author IS NOT NULL AND author != '' ORDER BY author ASC");

// 7. Встановлення заголовка сторінки
$page_title = "Каталог книг - Інтернет-магазин книг";

// 8. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // Виведення повідомлень (якщо є) ?>
<?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
    <div class="alert alert-<?php echo $page_alert_type; ?>">
        <span class="alert-icon">
            <?php
            // Проста логіка для іконок
            if ($page_alert_type === 'success') echo '&#10004;';
            elseif ($page_alert_type === 'danger') echo '&#10008;';
            else echo '&#8505;';
            ?>
        </span>
        <?php echo $page_alert_message; ?>
    </div>
<?php endif; ?>

    <div class="section-title-container"><h2>Каталог книг</h2></div>

<?php // Форма фільтрів. Клас filter-form-panel залишаємо, його стилі є в catalog.css і можуть бути унікальними. ?>
    <form method="GET" action="catalog.php" class="filter-form-panel panel-container"> <?php // Додано panel-container для уніфікованого вигляду ?>
        <div class="form-group"> <?php // Обгортаємо для кращої структури (опціонально для горизонтальних фільтрів) ?>
            <label for="genre">Фільтрувати за жанром:</label>
            <select name="genre" id="genre" onchange="this.form.submit()">
                <option value="">Усі жанри</option>
                <?php if($genres_result_q && mysqli_num_rows($genres_result_q) > 0) : while ($row_genre = mysqli_fetch_assoc($genres_result_q)): ?>
                    <option value="<?php echo htmlspecialchars($row_genre['genre']); ?>" <?php if ($current_genre == $row_genre['genre']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row_genre['genre']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="author">Фільтрувати за автором:</label>
            <select name="author" id="author" onchange="this.form.submit()">
                <option value="">Усі автори</option>
                <?php if($authors_result_q && mysqli_num_rows($authors_result_q) > 0) : while ($row_author = mysqli_fetch_assoc($authors_result_q)): ?>
                    <option value="<?php echo htmlspecialchars($row_author['author']); ?>" <?php if ($current_author == $row_author['author']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($row_author['author']); ?>
                    </option>
                <?php endwhile; endif; ?>
            </select>
        </div>

        <?php if ($current_genre !== '' || $current_author !== ''): ?>
            <a href="catalog.php" class="btn-generic btn-outline-secondary btn-sm">
                <span class="icon" aria-hidden="true">🔄</span> Скинути фільтри
            </a>
        <?php endif; ?>
    </form>

    <div class="books">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($book = mysqli_fetch_assoc($result)): ?>
                <div class="book">
                    <a href="book.php?id=<?php echo $book['id']; ?>" class="book-image-link">
                        <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    </a>
                    <h3><a href="book.php?id=<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?></a></h3>
                    <p class="book-author"><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="book-genre"><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                    <?php /* <p class="book-description"><?php echo mb_substr(htmlspecialchars(strip_tags($book['description'])), 0, 80); ?>...</p> */ // Опис можна прибрати з каталогу для компактності, він є на сторінці книги ?>
                    <p class="book-price"><?php echo number_format($book['price'], 2); ?> грн</p>

                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" class="btn-generic btn-primary btn-sm btn-full-width">
                            <span class="icon" aria-hidden="true">🛒</span> Додати до кошика
                        </button>
                    </form>
                    <a href="book.php?id=<?php echo $book['id']; ?>" class="details-link">Детальніше</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-items-info">
                <?php
                // Покращене повідомлення, якщо книги не знайдено або сталася помилка
                if (!empty($page_alert_message) && $page_alert_type === 'danger') {
                    echo $page_alert_message; // Якщо вже є повідомлення про помилку завантаження
                } elseif (!empty($params)) { // Якщо застосовані фільтри
                    echo "За заданими критеріями книги не знайдені.";
                } else { // Якщо фільтри не застосовані і немає помилок завантаження
                    echo "Наразі книги відсутні в каталозі.";
                }
                ?>
            </p>
        <?php endif; ?>
    </div>

<?php
// 11. Звільнення результатів та закриття з'єднань
if (isset($stmt_books) && $stmt_books instanceof mysqli_stmt) {
    $stmt_books->close();
}
// $result звільняється автоматично, якщо він був результатом $stmt_books->get_result()
// або якщо це результат mysqli_query, то його треба звільнити явно
if (isset($result) && $result instanceof mysqli_result && !isset($stmt_books)) { // Звільняємо, тільки якщо це результат mysqli_query
    mysqli_free_result($result);
}
if (isset($genres_result_q) && $genres_result_q instanceof mysqli_result) {
    mysqli_free_result($genres_result_q);
}
if (isset($authors_result_q) && $authors_result_q instanceof mysqli_result) {
    mysqli_free_result($authors_result_q);
}

if (isset($conn)) {
    mysqli_close($conn);
}

// 12. Підключення футера
include_once('includes/footer.php');
?>