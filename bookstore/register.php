<?php
// Підключення до бази даних
include('includes/db.php');

// Перевірка, чи форма була надіслана
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Отримуємо значення з форми
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Перевірка, чи паролі співпадають
    if ($password !== $confirm_password) {
        $error_message = "Паролі не співпадають!";
    } else {
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // SQL-запит для перевірки наявності користувача з таким email
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) > 0) {
            $error_message = "Користувач з таким email вже існує!";
        } else {
            // SQL-запит для додавання нового користувача
            $query = "INSERT INTO users (username, email, password, role) 
                      VALUES ('$username', '$email', '$hashed_password', 'user')";

            if (mysqli_query($conn, $query)) {
                $success_message = "Реєстрація успішна! Тепер ви можете увійти.";
            } else {
                $error_message = "Сталася помилка при реєстрації: " . mysqli_error($conn);
            }
        }
    }
}

// Закриття з'єднання
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реєстрація</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
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
        <section class="login-form">
            <h2>Реєстрація</h2>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if (isset($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <form action="register.php" method="POST">
                <label for="username">Ім'я користувача</label>
                <input type="text" id="username" name="username" required>

                <label for="email">Електронна пошта</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Підтвердження пароля</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit">Зареєструватися</button>
            </form>
            <p>Вже маєте акаунт? <a href="login.php">Увійти</a></p>
        </section>
    </main>

    <!-- Футер -->
    <footer>
        <p>&copy; 2025 Інтернет-магазин книг</p>
    </footer>

</body>
</html>
