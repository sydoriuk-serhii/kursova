<?php
session_start();
include('includes/db.php');

// Перевірка, чи була надана форма
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Якщо це адміністратор
    if ($username === 'adminBooks' && $password === 'adminBooks') {
        $_SESSION['user_id'] = 0; // Адміністратор має спеціальний ID
        $_SESSION['username'] = 'adminBooks';
        $_SESSION['role'] = 'admin';
        header('Location: admin_panel.php');
        exit;
    } else {
        // Якщо звичайний користувач, перевірка в базі даних
        $query = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $query->bind_param("s", $username);
        $query->execute();
        $result = $query->get_result();

        // Перевірка, чи існує користувач з таким логіном
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            // Перевірка пароля
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header('Location: order.php');
                exit;
            } else {
                $error = "❌ Невірний пароль!";
            }
        } else {
            $error = "❌ Користувача з таким логіном не існує!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вхід</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<header>
    <h1>Інтернет-магазин книг</h1>
    <nav>
        <ul>
            <li><a href="index.php">Головна</a></li>
            <li><a href="catalog.php">Каталог</a></li>
            <li><a href="register.php">Реєстрація</a></li>
        </ul>
    </nav>
</header>

<main>
    <section class="login-form">
        <h2>Вхід до акаунту</h2>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="username">Логін:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Пароль:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">Увійти</button>
        </form>
    </section>
</main>

<footer>
    <p>&copy; 2025 Інтернет-магазин книг</p>
</footer>

</body>
</html>
