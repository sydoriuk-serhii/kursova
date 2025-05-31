<?php
// Файл: book.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)

// 3. Ініціалізація змінних
$book = null;
$page_title_default = "Деталі книги - Інтернет-магазин книг"; // Загальний заголовок, якщо книга не знайдена
$error_message_book = ''; // Ініціалізуємо повідомлення про помилку

// 4. Перевірка, чи передано параметр id книги через URL, та отримання даних
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $book_id = (int)$_GET['id'];

    $query_book = $conn->prepare("SELECT * FROM books WHERE id = ?");
    if ($query_book) {
        $query_book->bind_param("i", $book_id);
        $query_book->execute();
        $result = $query_book->get_result();

        if ($result && $result->num_rows == 1) { // Додано перевірку $result
            $book = $result->fetch_assoc();
            $page_title_default = htmlspecialchars($book['title']) . " - Деталі книги";
        } else {
            $error_message_book = "На жаль, книгу за вашим запитом не знайдено.";
            $page_title_default = "Книгу не знайдено - Інтернет-магазин книг"; // Оновлюємо заголовок вкладки
        }
        if ($result) $result->close(); // Закриваємо результат, якщо він був
        $query_book->close();
    } else {
        error_log("Помилка підготовки SQL-запиту (деталі книги): " . $conn->error);
        $error_message_book = "Виникла помилка при завантаженні інформації про книгу.";
        $page_title_default = "Помилка завантаження - Інтернет-магазин книг";
    }
} else {
    $error_message_book = "Неправильний запит або ID книги не вказано.";
    $page_title_default = "Неправильний запит - Інтернет-магазин книг";
}

// 5. Встановлюємо остаточний заголовок сторінки
$page_title = $page_title_default;

// 6. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 8. HTML-контент сторінки ?>
    <section class="panel-container book-detail-panel">
        <?php if ($book): // Якщо книга успішно завантажена ?>
            <div class="section-title-container">
                <h2><?php echo htmlspecialchars($book['title']); ?></h2> <?php // Заголовок тепер тільки назва книги, "Деталі книги" можна прибрати звідси ?>
            </div>

            <div class="book-card-layout">
                <div class="book-image-container">
                    <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                </div>
                <div class="book-info-details">
                    <?php /* Назва книги вже є в .section-title-container, тут можна прибрати або зробити меншим */ ?>
                    <?php /* <h3><?php echo htmlspecialchars($book['title']); ?></h3> */ ?>

                    <p class="price-highlight"><?php echo number_format($book['price'], 2); ?> грн.</p> <?php // Ціну можна винести вище, прибравши "Ціна:" ?>

                    <div class="info-grid"> <?php // Використовуємо info-grid для характеристик ?>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <?php // Тут можна додати інші характеристики, якщо вони є: рік видання, видавництво, кількість сторінок тощо. ?>
                    </div>

                    <h4 class="content-subtitle">Опис:</h4> <?php // Змінено на h4 для кращої ієрархії, якщо h2 вже є ?>
                    <div class="book-description-text"> <?php // Окремий контейнер для тексту опису ?>
                        <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                    </div>

                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form"> <?php // Додано клас формі ?>
                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                        <button type="submit" class="btn-generic btn-positive btn-lg">
                            <span class="icon" aria-hidden="true">🛒</span> Додати в кошик
                        </button>
                    </form>
                </div>
            </div>
        <?php else: // Якщо книга не знайдена або сталася помилка ?>
            <div class="section-title-container">
                <h2><?php echo ($error_message_book === "Неправильний запит або ID книги не вказано.") ? "Неправильний запит" : "Помилка"; ?></h2>
            </div>
            <div class="alert alert-danger">
                <span class="alert-icon">&#10008;</span>
                <?php echo htmlspecialchars($error_message_book); ?> <a href="catalog.php" class="alert-link">Повернутися до каталогу</a>.
            </div>
        <?php endif; ?>
    </section>

<?php
// 9. Закриття з'єднання з базою даних
if (isset($conn)) {
    mysqli_close($conn);
}

// 10. Підключаємо футер
include_once('includes/footer.php');
?>