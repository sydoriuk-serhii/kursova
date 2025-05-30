<?php
// Підключення до бази даних
include('includes/db.php');

// Отримання всіх книг з бази даних
$query = "SELECT * FROM books";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Інтернет-магазин книг</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Підключення стилів -->
</head>
<body>

    <!-- Шапка сайту -->
    <header>
        <h1>Інтернет-магазин книг</h1>
        <nav>
            <ul>
                <li><a href="index.php">Головна</a></li>
                <li><a href="catalog.php">Каталог книг</a></li>
                <li><a href="login.php">Вхід</a></li>
                <li><a href="register.php">Реєстрація</a></li>
            </ul>
        </nav>
    </header>

    <!-- Основний контент -->
    <main>
        <section class="book-list">
            <h2>Наші книги</h2>
            <div class="books">

                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="book">
                            <img src="uploads/<?php echo $row['image']; ?>" alt="book image">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><strong>Автор:</strong> <?php echo htmlspecialchars($row['author']); ?></p>
                            <p><strong>Ціна:</strong> <?php echo number_format($row['price'], 2); ?> грн.</p>
                            <p><a href="book.php?id=<?php echo $row['id']; ?>">Детальніше</a></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>Книги не знайдені.</p>
                <?php endif; ?>

            </div>
        </section>
    </main>

    <!-- Футер -->
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>

</body>
</html>

<?php
// Закриття з'єднання з базою даних
mysqli_close($conn);
?>
