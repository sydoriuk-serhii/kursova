<?php
// Файл: book.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Ініціалізація змінної $book
$book = null; //
$page_title_default = "Деталі книги - Книгу не знайдено"; //

// 4. Перевірка, чи передано параметр id книги через URL, та отримання даних
if (isset($_GET['id']) && is_numeric($_GET['id'])) { //
    $book_id = (int)$_GET['id']; //

    $query_book = $conn->prepare("SELECT * FROM books WHERE id = ?"); //
    if ($query_book) { //
        $query_book->bind_param("i", $book_id); //
        $query_book->execute(); //
        $result = $query_book->get_result(); //

        if ($result->num_rows == 1) { //
            $book = $result->fetch_assoc(); //
            $page_title_default = htmlspecialchars($book['title']) . " - Деталі книги"; //
        } else {
            $error_message_book = "На жаль, книгу за вашим запитом не знайдено."; //
        }
        $query_book->close(); //
    } else {
        error_log("Помилка підготовки SQL-запиту (деталі книги): " . $conn->error); //
        $error_message_book = "Виникла помилка при завантаженні інформації про книгу."; //
    }
} else {
    $error_message_book = "Неправильний запит або ID книги не вказано."; //
}

// 5. Встановлюємо остаточний заголовок сторінки
$page_title = $page_title_default; //

// 6. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/book.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 7. Рядок <link rel="stylesheet" href="css/book.css"> ВИДАЛЕНО ?>

<?php // 8. HTML-контент сторінки ?>
<?php // Замінюємо клас основного контейнера ?>
    <section class="panel-container book-detail-panel"> <?php // ?>
        <?php if (isset($book) && $book): ?>
            <?php // Замінюємо клас контейнера заголовка ?>
            <div class="section-title-container"><h2>Деталі книги: <?php echo htmlspecialchars($book['title']); ?></h2></div> <?php // ?>

            <?php // Клас book-card-layout для специфічної розкладки цієї сторінки ?>
            <div class="book-card-layout"> <?php // ?>
                <div class="book-image-container"> <?php // ?>
                    <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>"> <?php // ?>
                </div>
                <div class="book-info-details"> <?php // ?>
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3> <?php // ?>
                    <?php // Замінюємо клас для ціни ?>
                    <p class="price-highlight"><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн.</p> <?php // ?>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p> <?php // ?>
                    <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p> <?php // ?>
                    <?php // Замінюємо клас для заголовка опису ?>
                    <p class="content-subtitle"><strong>Опис:</strong></p> <?php // ?>
                    <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p> <?php // ?>

                    <?php // Видаляємо інлайновий стиль та змінюємо клас кнопки ?>
                    <form action="add_to_cart.php" method="POST"> <?php // ?>
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>"> <?php // ?>
                        <button type="submit" class="btn-generic btn-positive">Додати в кошик</button> <?php // ?>
                    </form>
                </div>
            </div>
        <?php elseif (isset($error_message_book)): ?>
            <div class="section-title-container"><h2>Помилка</h2></div> <?php // ?>
            <?php // Видаляємо інлайновий стиль, .error-message вже має стилі ?>
            <p class="error-message"><?php echo $error_message_book; ?> <a href="catalog.php">Повернутися до каталогу</a>.</p> <?php // ?>
        <?php else: ?>
            <div class="section-title-container"><h2>Інформація недоступна</h2></div> <?php // ?>
            <?php // Видаляємо інлайновий стиль, можна додати .no-items-info або залишити <p> ?>
            <p class="no-items-info">Не вдалося завантажити деталі книги. <a href="catalog.php">Повернутися до каталогу</a>.</p> <?php // ?>
        <?php endif; ?>
    </section>

<?php
// 9. Закриття з'єднання з базою даних
if (isset($conn)) { //
    mysqli_close($conn); //
}

// 10. Підключаємо футер
include_once('includes/footer.php'); //
?>