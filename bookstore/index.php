<?php
// Підключення до бази даних
include_once('includes/db.php');
include_once('includes/header.php');
// Отримання всіх книг з бази даних
$query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 10"; // Покажемо останні 10 книг
$result = mysqli_query($conn, $query);

$page_title = "Головна - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/style.css">

    <section class="book-list">
        <h2>Новинки та популярні книги</h2>
        <div class="books">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <div class="book">
                        <a href="book.php?id=<?php echo $row['id']; ?>">
                            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        </a>
                        <h3><a href="book.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                        <p><strong>Автор:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                        <p><strong>Ціна:</strong> <?php echo number_format($row['price'], 2); ?> грн.</p>
                        <p><a href="book.php?id=<?php echo $row['id']; ?>" class="details-link">Детальніше</a></p>
                        <form action="add_to_cart.php" method="POST" style="margin-top: 10px;">
                            <input type="hidden" name="book_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn-add-to-cart-small">Додати в кошик</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>Наразі книги відсутні в каталозі.</p>
            <?php endif; ?>
        </div>
    </section>

<?php
// Закриття з'єднання з базою даних
mysqli_close($conn);
include('includes/footer.php');
?>