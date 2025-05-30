<?php
// session_start(); // Вже в header.php
include_once('includes/db.php');
include_once('includes/header.php');
// Якщо користувач вже авторизований, перенаправити його
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_panel.php');
    } else {
        header('Location: profile.php'); // Або order.php, або index.php
    }
    exit;
}

// Перевірка, чи була надана форма
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Якщо це адміністратор (спеціальні облікові дані)
    if ($username === 'adminBooks' && $password === 'adminBooks') {
        // Перевіряємо, чи є такий адмін в таблиці users
        $admin_query = $conn->prepare("SELECT id, username, role FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
        $admin_query->bind_param("s", $username);
        $admin_query->execute();
        $admin_result = $admin_query->get_result();

        if ($admin_result->num_rows > 0) {
            $admin_user = $admin_result->fetch_assoc();
            // Для адміна пароль 'adminBooks' не хешується в вашій поточній логіці,
            // але краще б хешувався при реєстрації адміна.
            // Зараз просто перевіряємо пароль напряму.
            $_SESSION['user_id'] = $admin_user['id']; // Використовуємо ID з бази
            $_SESSION['username'] = $admin_user['username'];
            $_SESSION['role'] = $admin_user['role'];
            header('Location: admin_panel.php');
            exit;
        } else {
            // Якщо такого адміна немає в БД, або якщо логін/пароль невірні
            $error = "❌ Невірний логін або пароль для адміністратора!";
        }
        $admin_query->close();
    } else {
        // Якщо звичайний користувач, перевірка в базі даних
        $query = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        $query->bind_param("s", $username);
        $query->execute();
        $result = $query->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Перевірка пароля
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') { // Якщо раптом звичайний юзер має роль адміна
                    header('Location: admin_panel.php');
                } else {
                    header('Location: profile.php'); // Або order.php
                }
                exit;
            } else {
                $error = "❌ Невірний логін або пароль!";
            }
        } else {
            $error = "❌ Користувача з таким логіном не існує!";
        }
        $query->close();
    }
}

$page_title = "Вхід - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/login.css"> <section class="login-form-container">
    <h2>Вхід до акаунту</h2>

    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="login-form-content">
        <label for="username">Логін:</label>
        <input type="text" name="username" id="username" required>

        <label for="password">Пароль:</label>
        <input type="password" name="password" id="password" required>

        <button type="submit">Увійти</button>
    </form>
    <p>Ще не маєте акаунту? <a href="register.php">Зареєструватися</a></p>
</section>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>