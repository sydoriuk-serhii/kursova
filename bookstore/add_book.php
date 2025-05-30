<?php
session_start(); // Потрібно для перевірки сесії
include('includes/db.php');

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$error_message = '';
$success_message = '';

// Перевірка, чи була надіслана форма
if (isset($_POST['submit'])) {
    // Отримання даних із форми
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $author = mysqli_real_escape_string($conn, trim($_POST['author']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $genre = mysqli_real_escape_string($conn, trim($_POST['genre']));
    $price = mysqli_real_escape_string($conn, $_POST['price']);

    // Валідація даних
    if (empty($title) || empty($author) || empty($description) || empty($genre) || !is_numeric($price) || $price < 0) {
        $error_message = "Будь ласка, заповніть усі поля коректно.";
    } elseif (empty($_FILES['image']['name'])) {
        $error_message = "Будь ласка, виберіть зображення для книги.";
    } else {
        // Обробка зображення
        $image = $_FILES['image'];
        $image_name = $image['name'];
        $image_tmp_name = $image['tmp_name'];
        $image_size = $image['size'];
        $image_error = $image['error'];

        // Перевірка на помилки при завантаженні зображення
        if ($image_error === 0) {
            if ($image_size < 5000000) { // Максимальний розмір 5MB
                $image_info = getimagesize($image_tmp_name);
                $image_ext_actual = image_type_to_extension($image_info[2], false); // отримуємо розширення з mime типу
                $allowed_extensions = ['jpg', 'jpeg', 'png'];

                if ($image_info && in_array(strtolower($image_ext_actual), $allowed_extensions)) {
                    // Генерація унікальної назви для зображення
                    $new_image_name = uniqid('book_', true) . '.' . $image_ext_actual;
                    $image_destination_folder = 'uploads/'; // Переконайтеся, що папка існує і доступна для запису

                    if (!is_dir($image_destination_folder)) {
                        mkdir($image_destination_folder, 0777, true);
                    }
                    $image_destination_path = $image_destination_folder . $new_image_name;

                    // Переміщення зображення до каталогу
                    if (move_uploaded_file($image_tmp_name, $image_destination_path)) {
                        // Додавання книги в базу даних
                        $insert_query = $conn->prepare("INSERT INTO books (title, author, description, genre, price, image, created_at) 
                                         VALUES (?, ?, ?, ?, ?, ?, NOW())");
                        $insert_query->bind_param("ssssds", $title, $author, $description, $genre, $price, $new_image_name);

                        if ($insert_query->execute()) {
                            header('Location: admin_panel.php?success_add=true');
                            exit;
                        } else {
                            $error_message = "Помилка додавання книги в базу даних: " . $insert_query->error;
                        }
                        $insert_query->close();
                    } else {
                        $error_message = "Помилка при завантаженні зображення на сервер.";
                    }
                } else {
                    $error_message = "Невірний формат зображення. Дозволені формати: JPG, JPEG, PNG.";
                }
            } else {
                $error_message = "Зображення занадто велике. Максимальний розмір: 5MB.";
            }
        } else {
            $error_message = "Сталася помилка при завантаженні зображення. Код помилки: " . $image_error;
        }
    }
    // Якщо є помилка, повертаємося на адмін-панель з повідомленням
    if (!empty($error_message)) {
        header('Location: admin_panel.php?error_add=' . urlencode($error_message));
        exit;
    }
} else {
    // Якщо хтось намагається отримати доступ до цього файлу напряму без POST-запиту
    header('Location: admin_panel.php');
    exit;
}

// Ця частина HTML не буде відображатися, оскільки скрипт завжди робить редирект
// Але якщо редиректу не буде (наприклад, при помилці, яку ми не перехопили),
// то краще мати тут підключення хедера/футера або просто вихід.
// В поточній логіці редирект є завжди.
mysqli_close($conn);
?>