<?php
// Файл: login.php

// 1. Підключення до бази даних (потрібне для перевірки логіна/пароля)
include_once('includes/db.php'); //

// 2. Запуск сесії (header.php це робить, але логіка перевірки авторизації нижче потребує $_SESSION)
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Якщо користувач вже авторизований, перенаправити його
// Ця логіка має бути ДО будь-якого виводу HTML, тобто ДО header.php
if (isset($_SESSION['user_id'])) { //
    if ($_SESSION['role'] === 'admin') { //
        header('Location: admin_panel.php'); //
    } else {
        header('Location: profile.php'); // Або order.php, або index.php //
    }
    exit; //
}

// 4. Перевірка, чи була надана форма (обробка POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') { //
    $username = mysqli_real_escape_string($conn, $_POST['username']); //
    $password = $_POST['password']; //


    // Якщо це адміністратор (спеціальні облікові дані)
    if ($username === 'adminBooks' && $password === 'adminBooks') { // Ви використовуєте пряме порівняння пароля для адміна //
        // Перевіряємо, чи є такий адмін в таблиці users
        $admin_query = $conn->prepare("SELECT id, username, role FROM users WHERE username = ? AND role = 'admin' LIMIT 1"); //
        $admin_query->bind_param("s", $username); //
        $admin_query->execute(); //
        $admin_result = $admin_query->get_result(); //

        if ($admin_result->num_rows > 0) { //
            $admin_user = $admin_result->fetch_assoc(); //
            $_SESSION['user_id'] = $admin_user['id']; //
            $_SESSION['username'] = $admin_user['username']; //
            $_SESSION['role'] = $admin_user['role']; //
            header('Location: admin_panel.php'); //
            exit; //
        } else {
            $error = "❌ Невірний логін або пароль для адміністратора!"; //
        }
        $admin_query->close(); //
    } else {
        // Якщо звичайний користувач, перевірка в базі даних
        $query = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1"); //
        $query->bind_param("s", $username); //
        $query->execute(); //
        $result = $query->get_result(); //

        if ($result->num_rows > 0) { //
            $user = $result->fetch_assoc(); //

            // Перевірка пароля
            if (password_verify($password, $user['password'])) { //
                $_SESSION['user_id'] = $user['id']; //
                $_SESSION['username'] = $user['username']; //
                $_SESSION['role'] = $user['role']; //

                if ($user['role'] === 'admin') { //
                    header('Location: admin_panel.php'); //
                } else {
                    header('Location: profile.php'); //
                }
                exit; //
            } else {
                $error = "❌ Невірний логін або пароль!"; //
            }
        } else {
            $error = "❌ Користувача з таким логіном не існує!"; //
        }
        $query->close(); //
    }
}

// 5. Встановлюємо заголовок сторінки
$page_title = "Вхід - Інтернет-магазин книг"; //

// 6. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/login.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 7. Рядок <link rel="stylesheet" href="css/login.css"> ВИДАЛЕНО ?>

<?php // 8. Починаємо HTML-розмітку ?>
<?php // Замінюємо клас контейнера форми на уніфікований .auth-form-container ?>
    <section class="auth-form-container"> <?php // ?>
        <div class="section-title-container"><h2>Вхід до акаунту</h2></div> <?php // ?>
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div> <?php // ?>
        <?php endif; ?>

        <?php // Додаємо клас .auth-form-content до форми для можливої специфічної стилізації контенту форми ?>
        <form method="POST" action="login.php" class="auth-form-content"> <?php // ?>
            <label for="username">Логін:</label> <?php // ?>
            <input type="text" name="username" id="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>"> <?php // ?>

            <label for="password">Пароль:</label> <?php // ?>
            <input type="password" name="password" id="password" required> <?php // ?>

            <?php // Кнопка успадкує стилі від form button[type="submit"] та .btn-generic з style.css ?>
            <?php // Якщо потрібна повна ширина, можна додати клас .btn-full-width ?>
            <button type="submit" class="btn-full-width">Увійти</button> <?php // ?>
        </form>
        <?php // Додаємо клас до абзацу з посиланням для стилізації ?>
        <p class="alternate-action-link">Ще не маєте акаунту? <a href="register.php">Зареєструватися</a></p> <?php // ?>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn)) { //
    mysqli_close($conn); //
}

// 10. Підключаємо футер
include_once('includes/footer.php'); //
?>