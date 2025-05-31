<?php
// Файл: index.php

// 1. Підключення до бази даних (потрібне для отримання списку книг)
include_once('includes/db.php');

// 2. Запуск сесії (header.php це робить, якщо потрібно ДО header.php, інакше можна прибрати)
// if (session_status() == PHP_SESSION_NONE) {
//     session_start();
// }

// 3. Отримання всіх книг з бази даних
$query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 10"; // Покажемо останні 10 книг
$result = mysqli_query($conn, $query);

// 4. Встановлюємо заголовок сторінки
$page_title = "Головна - Інтернет-магазин книг";

// 5. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/index.css (якщо він існує)
include_once('includes/header.php');
?>

<?php // 6. HTML-розмітка, специфічна для цієї сторінки ?>
    <div class="section-title-container"><h2>Новинки та популярні книги</h2></div> <?php // ?>
<?php // <section class="book-list"> - цей тег section можна залишити для семантики або прибрати,
// оскільки .site-main-content вже є основним контейнером.
// Якщо залишити, переконайся, що для .book-list немає конфліктуючих стилів.
// Для простоти, можна його прибрати, якщо він не несе унікальних стилів.
?>
    <div class="books"> <?php // ?>
        <?php if ($result && mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="book"> <?php // ?>
                    <a href="book.php?id=<?php echo $row['id']; ?>">
                        <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                    </a>
                    <h3><a href="book.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3> <?php // ?>
                    <p><strong>Автор:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                    <p><strong>Ціна:</strong> <?php echo number_format($row['price'], 2); ?> грн.</p>
                    <p><a href="book.php?id=<?php echo $row['id']; ?>" class="details-link">Детальніше</a></p> <?php // ?>
                    <?php // Видаляємо інлайновий стиль style="margin-top: 10px;" з форми ?>
                    <form action="add_to_cart.php" method="POST"> <?php // ?>
                        <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" class="btn-add-to-cart-small">Додати в кошик</button> <?php // ?>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <?php // Замінюємо інлайновий стиль на клас .no-items-info з style.css ?>
            <p class="no-items-info">Наразі книги відсутні в каталозі.</p> <?php // ?>
        <?php endif; ?>
    </div>
<?php // </section> -- якщо використовували тег section ?>

<?php
// 7. Закриття з'єднання з базою даних та звільнення результату
if (isset($result)) {
    mysqli_free_result($result);
}
if (isset($conn)) {
    mysqli_close($conn);
}

// 8. Підключаємо футер
include_once('includes/footer.php');
?>