<?php
// Файл: register.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. Якщо користувач вже авторизований, перенаправити його
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin_panel.php');
    } else {
        header('Location: profile.php');
    }
    exit;
}

// Ініціалізація змінних для повідомлень та даних форми
$page_alert_message = '';
$page_alert_type = '';
$form_username = ''; // Для збереження введеного username
$form_email = '';    // Для збереження введеного email

// 4. Перевірка, чи форма була надіслана (обробка POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Отримуємо та очищуємо дані
    $form_username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $form_email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // mysqli_real_escape_string не потрібен для $form_username та $form_email, якщо вони йдуть у bind_param

    // Валідація
    if (empty($form_username) || empty($form_email) || empty($password) || empty($confirm_password)) {
        $page_alert_message = "Будь ласка, заповніть усі поля.";
        $page_alert_type = 'danger';
    } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {
        $page_alert_message = "Некоректний формат електронної пошти.";
        $page_alert_type = 'danger';
    } elseif (strlen($password) < 6) {
        $page_alert_message = "Пароль повинен містити щонайменше 6 символів.";
        $page_alert_type = 'danger';
    } elseif ($password !== $confirm_password) {
        $page_alert_message = "Паролі не співпадають!";
        $page_alert_type = 'danger';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Перевірка на існування користувача
        $check_query = $conn->prepare("SELECT id, email, username FROM users WHERE email = ? OR username = ?");
        if ($check_query) {
            $check_query->bind_param("ss", $form_email, $form_username);
            $check_query->execute();
            $check_result = $check_query->get_result();

            if ($check_result && $check_result->num_rows > 0) {
                $existing_user = $check_result->fetch_assoc();
                if ($existing_user['email'] === $form_email) {
                    $page_alert_message = "Користувач з таким email вже існує!";
                } elseif ($existing_user['username'] === $form_username) {
                    $page_alert_message = "Користувач з таким логіном вже існує!";
                } else {
                    // Цей випадок малоймовірний через OR у запиті, але залишаємо
                    $page_alert_message = "Користувач з такими даними вже існує (перевірте логін та email).";
                }
                $page_alert_type = 'danger';
                if ($check_result) $check_result->close();
            } else {
                // Закриваємо попередній результат, якщо він був
                if ($check_result) $check_result->close();

                // Додавання нового користувача
                $insert_query = $conn->prepare("INSERT INTO users (username, email, password, role, created_at)
                                                VALUES (?, ?, ?, 'user', NOW())");
                if ($insert_query) {
                    $insert_query->bind_param("sss", $form_username, $form_email, $hashed_password);

                    if ($insert_query->execute()) {
                        $page_alert_message = "Реєстрація успішна! Тепер ви можете <a href='login.php' class='alert-link'>увійти</a>.";
                        $page_alert_type = 'success';
                        // Очищаємо поля форми після успішної реєстрації
                        $form_username = '';
                        $form_email = '';
                    } else {
                        $page_alert_message = "Сталася помилка при реєстрації: " . htmlspecialchars($insert_query->error);
                        $page_alert_type = 'danger';
                        error_log("User registration insert error: " . $insert_query->error);
                    }
                    $insert_query->close();
                } else {
                    $page_alert_message = "Помилка підготовки запиту для реєстрації.";
                    $page_alert_type = 'danger';
                    error_log("User registration prepare error (insert): " . $conn->error);
                }
            }
            $check_query->close();
        } else {
            $page_alert_message = "Помилка підготовки запиту для перевірки користувача.";
            $page_alert_type = 'danger';
            error_log("User registration prepare error (check): " . $conn->error);
        }
    }
}

// 5. Встановлюємо заголовок сторінки
$page_title = "Реєстрація - Інтернет-магазин книг";

// 6. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 8. Починаємо HTML-розмітку ?>
    <section class="auth-form-container">
        <div class="section-title-container"><h2>Реєстрація нового користувача</h2></div>

        <?php // Виведення повідомлень (якщо є) ?>
        <?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
            <div class="alert alert-<?php echo $page_alert_type; ?>">
            <span class="alert-icon">
                <?php
                if ($page_alert_type === 'success') echo '&#10004;';
                elseif ($page_alert_type === 'danger') echo '&#10008;';
                else echo '&#8505;';
                ?>
            </span>
                <?php echo $page_alert_message; // Дозволяємо HTML для посилання в success_message ?>
            </div>
        <?php endif; ?>

        <?php if ($page_alert_type !== 'success'): // Не показувати форму, якщо реєстрація успішна ?>
            <form action="register.php" method="POST" class="auth-form-content">
                <div class="form-group">
                    <label for="username">Ім'я користувача (логін):</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_username); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Електронна пошта:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_email); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Пароль (мін. 6 символів):</label>
                    <input type="password" id="password" name="password" required minlength="6"> <?php // Додано minlength ?>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Підтвердження пароля:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6"> <?php // Додано minlength ?>
                </div>

                <?php /*
            <div class="form-group form-check">
                <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                <label class="form-check-label" for="terms">Я погоджуюсь з <a href="terms.php" target="_blank">умовами використання</a> та <a href="privacy.php" target="_blank">політикою конфіденційності</a>.</label>
            </div>
            */ ?>

                <button type="submit" class="btn-generic btn-primary btn-full-width" style="margin-top: 10px;">Зареєструватися</button>
            </form>
        <?php endif; ?>
        <p class="auth-alternate-action">Вже маєте акаунт? <a href="login.php">Увійти</a></p>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 10. Підключаємо футер
include_once('includes/footer.php');
?>