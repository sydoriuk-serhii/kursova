<?php
// Файл: register.php

// 1. Підключення до бази даних
include_once('includes/db.php'); //

// 2. Запуск сесії
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Якщо користувач вже авторизований, перенаправити його
if (isset($_SESSION['user_id'])) { //
    if ($_SESSION['role'] === 'admin') { //
        header('Location: admin_panel.php'); //
    } else {
        header('Location: profile.php'); //
    }
    exit; //
}

// 4. Перевірка, чи форма була надіслана (обробка POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') { //
    $username = mysqli_real_escape_string($conn, trim($_POST['username'])); //
    $email = mysqli_real_escape_string($conn, trim($_POST['email'])); //
    $password = $_POST['password']; //
    $confirm_password = $_POST['confirm_password']; //

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) { //
        $error_message = "Будь ласка, заповніть усі поля."; //
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { //
        $error_message = "Некоректний формат електронної пошти."; //
    } elseif (strlen($password) < 6) { //
        $error_message = "Пароль повинен містити щонайменше 6 символів."; //
    } elseif ($password !== $confirm_password) { //
        $error_message = "Паролі не співпадають!"; //
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); //

        $check_query = $conn->prepare("SELECT id, email, username FROM users WHERE email = ? OR username = ?"); //
        $check_query->bind_param("ss", $email, $username); //
        $check_query->execute(); //
        $check_result = $check_query->get_result(); //

        if ($check_result->num_rows > 0) { //
            $existing_user = $check_result->fetch_assoc(); //
            if ($existing_user['email'] === $email) { //
                $error_message = "Користувач з таким email вже існує!"; //
            } elseif ($existing_user['username'] === $username) { //
                $error_message = "Користувач з таким логіном вже існує!"; //
            } else {
                $error_message = "Користувач з такими даними вже існує (перевірте логін та email)."; //
            }
        } else {
            $insert_query = $conn->prepare("INSERT INTO users (username, email, password, role, created_at)
                                            VALUES (?, ?, ?, 'user', NOW())"); //
            $insert_query->bind_param("sss", $username, $email, $hashed_password); //

            if ($insert_query->execute()) { //
                $success_message = "Реєстрація успішна! Тепер ви можете <a href='login.php'>увійти</a>."; //
            } else {
                $error_message = "Сталася помилка при реєстрації: " . $insert_query->error; //
            }
            $insert_query->close(); //
        }
        $check_query->close(); //
    }
}

// 5. Встановлюємо заголовок сторінки
$page_title = "Реєстрація - Інтернет-магазин книг"; //

// 6. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/register.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 7. Рядок <link rel="stylesheet" href="css/register.css"> ВИДАЛЕНО ?>

<?php // 8. Починаємо HTML-розмітку ?>
<?php // Замінюємо клас контейнера форми на уніфікований .auth-form-container ?>
    <section class="auth-form-container"> <?php // ?>
        <div class="section-title-container"><h2>Реєстрація нового користувача</h2></div> <?php // ?>
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div> <?php // ?>
        <?php endif; ?>
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div> <?php // ?>
        <?php endif; ?>

        <?php if (!isset($success_message)): ?>
            <?php // Додаємо клас .auth-form-content до форми ?>
            <form action="register.php" method="POST" class="auth-form-content"> <?php // ?>
                <label for="username">Ім'я користувача (логін):</label> <?php // ?>
                <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required> <?php // ?>

                <label for="email">Електронна пошта:</label> <?php // ?>
                <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required> <?php // ?>

                <label for="password">Пароль (мін. 6 символів):</label> <?php // ?>
                <input type="password" id="password" name="password" required> <?php // ?>

                <label for="confirm_password">Підтвердження пароля:</label> <?php // ?>
                <input type="password" id="confirm_password" name="confirm_password" required> <?php // ?>

                <?php // Кнопка успадкує стилі від form button[type="submit"] та .btn-generic ?>
                <?php // Додаємо клас .btn-full-width для повної ширини ?>
                <button type="submit" class="btn-full-width">Зареєструватися</button> <?php // ?>
            </form>
        <?php endif; ?>
        <?php // Додаємо клас до абзацу з посиланням ?>
        <p class="alternate-action-link">Вже маєте акаунт? <a href="login.php">Увійти</a></p> <?php // ?>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn)) { // Перевіряємо, чи існує змінна $conn //
    mysqli_close($conn); //
}

// 10. Підключаємо футер
include_once('includes/footer.php'); //
?>