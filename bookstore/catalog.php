<?php
session_start();
include('includes/db.php');

// Додавання до кошика
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['book_id'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $price = $_POST['price'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$book_id])) {
        $_SESSION['cart'][$book_id]['quantity'] += 1;
    } else {
        $_SESSION['cart'][$book_id] = [
            'title' => $title,
            'price' => $price,
            'quantity' => 1
        ];
    }

    $message = "Книга додана до кошика!";
}

// Фільтри
$genre_filter = '';
$author_filter = '';

// Фільтруємо за жанром
if (isset($_GET['genre']) && $_GET['genre'] !== '') {
    $genre = mysqli_real_escape_string($conn, $_GET['genre']);
    $genre_filter = "WHERE genre = '$genre'";
}

// Фільтруємо за автором
if (isset($_GET['author']) && $_GET['author'] !== '') {
    $author = mysqli_real_escape_string($conn, $_GET['author']);
    $author_filter = $genre_filter ? " AND author = '$author'" : "WHERE author = '$author'";
}

// Отримуємо книги згідно з фільтрами
$books_sql = "SELECT * FROM books $genre_filter $author_filter";
$result = mysqli_query($conn, $books_sql);

// Список жанрів та авторів для фільтра
$genres_result = mysqli_query($conn, "SELECT DISTINCT genre FROM books");
$authors_result = mysqli_query($conn, "SELECT DISTINCT author FROM books");
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Каталог книг</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/catalog.css">
    <style>
        .filter-form {
            margin: 20px auto;
            text-align: center;
            font-family: Helvetica, sans-serif;
            font-size: 14px;
        }

        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-form select:hover {
            background-color: #e6e6e6;
        }

        .message {
            text-align: center;
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 5px;
            margin: 10px auto;
            width: fit-content;
        }

        .book-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .book-item {
            width: 220px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 10px;
            text-align: center;
            background-color: #f9f9f9;
        }

        .book-item img {
            width: 150px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .book-item h3 {
            font-size: 16px;
            margin: 10px 0;
        }

        .book-item p {
            font-size: 14px;
        }

        .book-item button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .book-item button:hover {
            background-color: #0056b3;
        }

        footer {
            text-align: center;
            padding: 10px;
            background-color: #f1f1f1;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<header>
    <h1>Інтернет-магазин книг</h1>
    <nav>
        <ul>
            <li><a href="index.php">Головна</a></li>
            <li><a href="catalog.php">Каталог книг</a></li>
            <a href="checkout.php">Перейти до кошика</a>
            <li><a href="login.php">Вхід</a></li>
            <li><a href="register.php">Реєстрація</a></li>
        </ul>
    </nav>
</header>

<main>
    <?php if (!empty($message)): ?>
        <p class="message"><?php echo $message; ?></p>
    <?php endif; ?>

    <form method="GET" class="filter-form">
        <label for="genre">Фільтрувати за жанром:</label>
        <select name="genre" id="genre" onchange="this.form.submit()">
            <option value="">Усі жанри</option>
            <?php while ($row = mysqli_fetch_assoc($genres_result)): ?>
                <option value="<?php echo htmlspecialchars($row['genre']); ?>" <?php if (isset($_GET['genre']) && $_GET['genre'] == $row['genre']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['genre']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="author">Фільтрувати за автором:</label>
        <select name="author" id="author" onchange="this.form.submit()">
            <option value="">Усі автори</option>
            <?php while ($row = mysqli_fetch_assoc($authors_result)): ?>
                <option value="<?php echo htmlspecialchars($row['author']); ?>" <?php if (isset($_GET['author']) && $_GET['author'] == $row['author']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($row['author']); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>

    <div class="book-list">
        <?php while ($book = mysqli_fetch_assoc($result)): ?>
            <div class="book-item">
                <img src="uploads/<?php echo htmlspecialchars($book['image']); ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                <p><strong>Автор:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                <p><strong>Жанр:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                <p><?php echo htmlspecialchars($book['description']); ?></p>
                <p><strong>Ціна:</strong> <?php echo number_format($book['price'], 2); ?> грн</p>

                <form action="catalog.php<?php if (isset($_GET['genre'])) echo '?genre=' . urlencode($_GET['genre']); ?>" method="POST">
                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($book['title']); ?>">
                    <input type="hidden" name="price" value="<?php echo $book['price']; ?>">
                    <button type="submit">Додати до кошика</button>
                </form>

                <p><a href="book.php?id=<?php echo $book['id']; ?>">Детальніше</a></p>
            </div>
        <?php endwhile; ?>
    </div>
</main>

<footer>
    <p>&copy; 2025 Інтернет-магазин книг</p>
</footer>

</body>
</html>
