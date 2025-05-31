<?php
// Файл: index.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії вже відбувається в header.php

// Обробка повідомлення про успішне замовлення (якщо воно є)
$page_alert_message = '';
$page_alert_type = '';
if (isset($_GET['success_order']) && $_GET['success_order'] == 'true' && isset($_GET['order_id']) && isset($_GET['customer_name'])) {
    $page_alert_message = "Дякуємо, " . htmlspecialchars(urldecode($_GET['customer_name'])) . "! Ваше замовлення #" . htmlspecialchars($_GET['order_id']) . " успішно оформлено.";
    $page_alert_type = 'success';
}

// 3. Отримання останніх 10 книг
$query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $query);

// 4. Встановлюємо заголовок сторінки
$page_title = "Головна - Інтернет-магазин книг";

// 5. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // Виведення повідомлення про успішне замовлення (якщо є) ?>
<?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
    <div class="alert alert-<?php echo $page_alert_type; ?>">
        <span class="alert-icon"><?php echo $page_alert_type === 'success' ? '&#10004;' : '&#8505;'; // Проста іконка для прикладу ?></span>
        <?php echo $page_alert_message; ?>
    </div>
<?php endif; ?>

    <div class="section-title-container">
        <h2>Новинки та популярні книги</h2>
    </div>

    <div class="books">
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="book">
                    <a href="book.php?id=<?php echo $row['id']; ?>" class="book-image-link">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                    </a>
                    <h3><a href="book.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                    <p class="book-author"><strong>Автор:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                    <?php /* <p><strong>Жанр:</strong> <?php echo htmlspecialchars($row['genre']); ?></p> */ // Можна додати, якщо потрібно ?>
                    <p class="book-price"><?php echo number_format($row['price'], 2); ?> грн.</p>

                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn-generic btn-primary btn-sm btn-full-width">
                            <span class="icon" aria-hidden="true">🛒</span> Додати в кошик
                        </button>
                    </form>
                    <a href="book.php?id=<?php echo $row['id']; ?>" class="details-link">Детальніше</a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="no-items-info">Наразі книги відсутні в каталозі.</p>
        <?php endif; ?>
    </div>

<?php
// 7. Закриття з'єднання з базою даних та звільнення результату
if (isset($result) && $result instanceof mysqli_result) { // Додана перевірка типу $result
    mysqli_free_result($result);
}
if (isset($conn)) {
    mysqli_close($conn);
}

// 8. Підключаємо футер
include_once('includes/footer.php');
?>