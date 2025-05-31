<?php
// Файл: add_book.php
if (session_status() == PHP_SESSION_NONE) { // Переконуємось, що сесія активна
    session_start();
}
include_once('includes/db.php'); // Використовуємо include_once

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Ініціалізуємо змінні для повідомлень за замовчуванням, хоча тут вони використовуються лише для редиректу
// $error_message = '';
// $success_message = ''; // Не використовується для прямого виводу тут

// Перевірка, чи була надіслана форма
if (isset($_POST['submit'])) {
    // Отримання даних із форми
    $title = trim($_POST['title']); // mysqli_real_escape_string не потрібен при використанні prepare
    $author = trim($_POST['author']);
    $description = trim($_POST['description']);
    $genre = trim($_POST['genre']);
    $price = $_POST['price']; // Валідація is_numeric буде нижче

    $current_error = ''; // Локальна змінна для помилок у цьому блоці

    // Валідація даних
    if (empty($title) || empty($author) || empty($description) || empty($genre) || !is_numeric($price) || $price < 0) {
        $current_error = "Будь ласка, заповніть усі поля коректно. Ціна має бути числом не менше 0.";
    } elseif (empty($_FILES['image']['name'])) {
        $current_error = "Будь ласка, виберіть зображення для книги.";
    } else {
        // Обробка зображення
        $image = $_FILES['image'];
        $image_name = $image['name'];
        $image_tmp_name = $image['tmp_name'];
        $image_size = $image['size'];
        $image_error = $image['error'];

        if ($image_error === 0) {
            if ($image_size < 5000000) { // Максимальний розмір 5MB
                $image_info = @getimagesize($image_tmp_name); // @ пригнічує помилку, якщо файл не зображення
                if ($image_info) {
                    $image_ext_actual = image_type_to_extension($image_info[2], false);
                    $allowed_extensions = ['jpg', 'jpeg', 'png'];

                    if (in_array(strtolower($image_ext_actual), $allowed_extensions)) {
                        $new_image_name = uniqid('book_', true) . '.' . $image_ext_actual;
                        $image_destination_folder = 'uploads/';

                        if (!is_dir($image_destination_folder)) {
                            if (!mkdir($image_destination_folder, 0755, true)) { // Використовуємо 0755
                                $current_error = "Не вдалося створити папку для завантажень.";
                            }
                        }

                        if (empty($current_error)) { // Продовжуємо, якщо папка створена або існує
                            $image_destination_path = $image_destination_folder . $new_image_name;

                            if (move_uploaded_file($image_tmp_name, $image_destination_path)) {
                                $insert_query = $conn->prepare("INSERT INTO books (title, author, description, genre, price, image, created_at)
                                                 VALUES (?, ?, ?, ?, ?, ?, NOW())");
                                if ($insert_query) { // Перевірка успішності prepare
                                    $insert_query->bind_param("ssssds", $title, $author, $description, $genre, $price, $new_image_name);
                                    if ($insert_query->execute()) {
                                        $insert_query->close();
                                        mysqli_close($conn);
                                        header('Location: admin_panel.php?success_add=true');
                                        exit;
                                    } else {
                                        $current_error = "Помилка додавання книги в базу даних: " . $insert_query->error;
                                    }
                                    $insert_query->close();
                                } else {
                                    $current_error = "Помилка підготовки запиту до БД: " . $conn->error;
                                }
                            } else {
                                $current_error = "Помилка при завантаженні зображення на сервер.";
                            }
                        }
                    } else {
                        $current_error = "Невірний формат зображення. Дозволені формати: JPG, JPEG, PNG.";
                    }
                } else {
                    $current_error = "Завантажений файл не є зображенням або пошкоджений.";
                }
            } else {
                $current_error = "Зображення занадто велике. Максимальний розмір: 5MB.";
            }
        } else {
            $current_error = "Сталася помилка при завантаженні зображення. Код помилки: " . $image_error;
        }
    }

    // Якщо є помилка, повертаємося на адмін-панель з повідомленням
    if (!empty($current_error)) {
        mysqli_close($conn);
        header('Location: admin_panel.php?error_add=' . urlencode($current_error));
        exit;
    }
} else {
    // Якщо хтось намагається отримати доступ до цього файлу напряму без POST-запиту
    mysqli_close($conn); // Закриваємо з'єднання, якщо воно було відкрито
    header('Location: admin_panel.php');
    exit;
}

// Цей рядок mysqli_close($conn); тут не потрібен, оскільки скрипт завжди завершується exit; вище.
?>