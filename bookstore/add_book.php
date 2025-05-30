<?php
session_start();
include('includes/db.php');

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Перевірка, чи була надіслана форма
if (isset($_POST['submit'])) {
    // Отримання даних із форми
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $genre = mysqli_real_escape_string($conn, $_POST['genre']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    
    // Обробка зображення
    $image = $_FILES['image'];
    $image_name = $image['name'];
    $image_tmp_name = $image['tmp_name'];
    $image_size = $image['size'];
    $image_error = $image['error'];

    // Перевірка на помилки при завантаженні зображення
    if ($image_error === 0) {
        if ($image_size < 5000000) { // Максимальний розмір 5MB
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_ext = strtolower($image_ext);
            $allowed_extensions = ['jpg', 'jpeg', 'png'];

            if (in_array($image_ext, $allowed_extensions)) {
                // Генерація унікальної назви для зображення
                $new_image_name = uniqid('', true) . '.' . $image_ext;
                $image_destination = 'uploads/' . $new_image_name;

                // Переміщення зображення до каталогу
                if (move_uploaded_file($image_tmp_name, $image_destination)) {
                    // Додавання книги в базу даних
                    $insert_query = "INSERT INTO books (title, author, description, genre, price, image) 
                                     VALUES ('$title', '$author', '$description', '$genre', '$price', '$new_image_name')";

                    if (mysqli_query($conn, $insert_query)) {
                        header('Location: admin_panel.php?success=true');
                        exit;
                    } else {
                        echo "Помилка додавання книги: " . mysqli_error($conn);
                    }
                } else {
                    echo "Помилка при завантаженні зображення.";
                }
            } else {
                echo "Невірний формат зображення. Дозволені формати: jpg, jpeg, png.";
            }
        } else {
            echo "Зображення занадто велике. Максимальний розмір: 5MB.";
        }
    } else {
        echo "Сталася помилка при завантаженні зображення.";
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Додати нову книгу</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin_panel.css">
</head>
<body>

<header>
    <h1>Додати нову книгу</h1>
    <nav>
        <ul>
            <li><a href="admin_panel.php">Панель адміністратора</a></li>
            <li><a href="logout.php">Вийти</a></li>
        </ul>
    </nav>
</header>

<main>
    <form action="add_book.php" method="POST" enctype="multipart/form-data">
        <label for="title">Назва:</label>
        <input type="text" name="title" id="title" required>

        <label for="author">Автор:</label>
        <input type="text" name="author" id="author" required>

        <label for="description">Опис:</label>
        <textarea name="description" id="description" required></textarea>

        <label for="genre">Жанр:</label>
        <input type="text" name="genre" id="genre" required>

        <label for="price">Ціна (грн):</label>
        <input type="number" name="price" step="0.01" id="price" required>

        <label for="image">Зображення:</label>
        <input type="file" name="image" id="image" accept="image/*" required>

        <button type="submit" name="submit">Додати книгу</button>
    </form>
</main>

<footer>
    <p>&copy; 2025 Інтернет-магазин книг</p>
</footer>

</body>
</html>
