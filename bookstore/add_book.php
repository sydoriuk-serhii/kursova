<?php
// Файл: add_book.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once('includes/db.php'); // Використовуємо include_once

// Якщо не авторизований або не адміністратор — перенаправити на login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($conn)) mysqli_close($conn);
    header('Location: login.php');
    exit;
}

// Перевірка, чи була надіслана форма і чи є кнопка submit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    // Отримання даних із форми та їх очищення
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $author = isset($_POST['author']) ? trim($_POST['author']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $genre = isset($_POST['genre']) ? trim($_POST['genre']) : '';
    $price = isset($_POST['price']) ? $_POST['price'] : ''; // Валідація is_numeric буде нижче

    $current_error = ''; // Локальна змінна для помилок у цьому блоці

    // Валідація даних
    if (empty($title) || empty($author) || empty($description) || empty($genre)) {
        $current_error = "Будь ласка, заповніть усі текстові поля.";
    } elseif (!is_numeric($price) || (float)$price < 0) { // Перевіряємо, чи є числом і не від'ємне
        $current_error = "Ціна має бути числом не менше 0.";
    } elseif (empty($_FILES['image']['name'])) {
        $current_error = "Будь ласка, виберіть зображення для книги.";
    } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) { // Перевірка помилок завантаження файлу
        // Обробка різних кодів помилок завантаження
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $current_error = "Зображення занадто велике. Перевищено максимальний розмір файлу.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $current_error = "Файл було завантажено лише частково.";
                break;
            case UPLOAD_ERR_NO_FILE: // Хоча ми вже перевірили empty($_FILES['image']['name'])
                $current_error = "Файл не було завантажено.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $current_error = "Відсутня тимчасова папка для завантаження.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $current_error = "Не вдалося записати файл на диск.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $current_error = "Завантаження файлу зупинено розширенням PHP.";
                break;
            default:
                $current_error = "Сталася невідома помилка при завантаженні зображення.";
                break;
        }
    } else {
        // Обробка зображення, якщо немає попередніх помилок
        $image = $_FILES['image'];
        // $image_name = $image['name']; // Не використовується далі, генерується нове ім'я
        $image_tmp_name = $image['tmp_name'];
        $image_size = $image['size'];

        if ($image_size > 5000000) { // Максимальний розмір 5MB (5 * 1024 * 1024)
            $current_error = "Зображення занадто велике. Максимальний розмір: 5MB.";
        } else {
            $image_info_check = getimagesize($image_tmp_name);
            if ($image_info_check !== false) {
                // $image_info_check[2] містить тип зображення (IMAGETYPE_XXX константи)
                $image_ext_actual = image_type_to_extension($image_info_check[2], false);
                $allowed_extensions = ['jpg', 'jpeg', 'png'];

                if (in_array(strtolower($image_ext_actual), $allowed_extensions)) {
                    $new_image_name = uniqid('book_', true) . '.' . $image_ext_actual;
                    $image_destination_folder = 'uploads/'; // Переконайтеся, що ця папка існує і має права на запис

                    if (!is_dir($image_destination_folder)) {
                        if (!mkdir($image_destination_folder, 0755, true)) {
                            $current_error = "Не вдалося створити папку для завантажень: " . $image_destination_folder;
                            error_log("Failed to create upload directory: " . $image_destination_folder);
                        }
                    }

                    // Додаткова перевірка, чи папка для завантажень доступна для запису
                    if (empty($current_error) && !is_writable($image_destination_folder)) {
                        $current_error = "Папка для завантажень недоступна для запису.";
                        error_log("Upload directory not writable: " . $image_destination_folder);
                    }


                    if (empty($current_error)) {
                        $image_destination_path = $image_destination_folder . $new_image_name;

                        if (move_uploaded_file($image_tmp_name, $image_destination_path)) {
                            $insert_query = $conn->prepare("INSERT INTO books (title, author, description, genre, price, image, created_at)
                                             VALUES (?, ?, ?, ?, ?, ?, NOW())");
                            if ($insert_query) {
                                $float_price = (float)$price; // Явне перетворення ціни у float
                                $insert_query->bind_param("ssssds", $title, $author, $description, $genre, $float_price, $new_image_name);
                                if ($insert_query->execute()) {
                                    $insert_query->close();
                                    if (isset($conn)) mysqli_close($conn);
                                    header('Location: admin_panel.php?success_add=true');
                                    exit;
                                } else {
                                    $current_error = "Помилка додавання книги в базу даних: " . htmlspecialchars($insert_query->error);
                                    error_log("DB insert error (add_book.php): " . $insert_query->error);
                                    // Якщо помилка вставки, можливо, варто видалити завантажений файл
                                    if (file_exists($image_destination_path)) {
                                        unlink($image_destination_path);
                                    }
                                }
                                $insert_query->close(); // Закриваємо, якщо ще не закрито
                            } else {
                                $current_error = "Помилка підготовки запиту до БД: " . htmlspecialchars($conn->error);
                                error_log("DB prepare error (add_book.php): " . $conn->error);
                            }
                        } else {
                            $current_error = "Помилка при переміщенні завантаженого зображення на сервер.";
                            error_log("move_uploaded_file failed for " . $image_tmp_name . " to " . $image_destination_path);
                        }
                    }
                } else {
                    $current_error = "Невірний формат зображення. Дозволені формати: JPG, JPEG, PNG. Ваш тип: " . ($image_ext_actual ?: 'не визначено');
                }
            } else {
                $current_error = "Завантажений файл не є зображенням або пошкоджений.";
            }
        }
    }

    // Якщо є помилка, повертаємося на адмін-панель з повідомленням
    if (!empty($current_error)) {
        if (isset($conn)) mysqli_close($conn);
        header('Location: admin_panel.php?error_add=' . urlencode($current_error));
        exit;
    }
} else {
    // Якщо хтось намагається отримати доступ до цього файлу напряму без POST-запиту або без кнопки submit
    if (isset($conn)) mysqli_close($conn);
    header('Location: admin_panel.php');
    exit;
}
?>