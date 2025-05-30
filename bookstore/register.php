<?php
// session_start(); // Вже в header.php
include_once('includes/db.php');
include_once('includes/header.php');

// Якщо користувач вже авторизований, перенаправити його
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_panel.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}


// Перевірка, чи форма була надіслана
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Отримуємо значення з форми
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password']; // Пароль не екрануємо перед хешуванням
    $confirm_password = $_POST['confirm_password'];

    // Валідація
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Будь ласка, заповніть усі поля.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Некоректний формат електронної пошти.";
    } elseif (strlen($password) < 6) {
        $error_message = "Пароль повинен містити щонайменше 6 символів.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Паролі не співпадають!";
    } else {
        // Хешування пароля
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // SQL-запит для перевірки наявності користувача з таким email або username
        $check_query = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $check_query->bind_param("ss", $email, $username);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows > 0) {
            // Перевіряємо, що саме співпало
            $existing_user = $check_result->fetch_assoc(); // Не використовуємо, просто для перевірки
            $check_email_query = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email_query->bind_param("s", $email);
            $check_email_query->execute();
            if ($check_email_query->get_result()->num_rows > 0) {
                $error_message = "Користувач з таким email вже існує!";
            } else {
                $error_message = "Користувач з таким логіном вже існує!";
            }
            $check_email_query->close();

        } else {
            // SQL-запит для додавання нового користувача
            $insert_query = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) 
                                            VALUES (?, ?, ?, 'user', NOW())");
            $insert_query->bind_param("sss", $username, $email, $hashed_password);

            if ($insert_query->execute()) {
                $success_message = "Реєстрація успішна! Тепер ви можете <a href='login.php'>увійти</a>.";
            } else {
                $error_message = "Сталася помилка при реєстрації: " . $insert_query->error;
            }
            $insert_query->close();
        }
        $check_query->close();
    }
}

$page_title = "Реєстрація - Інтернет-магазин книг";
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/register.css"> <section class="register-form-container">
    <h2>Реєстрація нового користувача</h2>
    <?php if (isset($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>
    <?php if (isset($success_message)): ?>
        <div class="success-message"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!isset($success_message)): // Ховаємо форму після успішної реєстрації ?>
        <form action="register.php" method="POST" class="register-form-content">
            <label for="username">Ім'я користувача (логін):</label>
            <input type="text" id="username" name="username" value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" required>

            <label for="email">Електронна пошта:</label>
            <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

            <label for="password">Пароль (мін. 6 символів):</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Підтвердження пароля:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <button type="submit">Зареєструватися</button>
        </form>
    <?php endif; ?>
    <p>Вже маєте акаунт? <a href="login.php">Увійти</a></p>
</section>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>