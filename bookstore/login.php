<?php
// Файл: login.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php, але потрібен тут для логіки редиректу)
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
$form_username = ''; // Для збереження введеного логіна у випадку помилки

// 4. Перевірка, чи була надана форма (обробка POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_username = isset($_POST['username']) ? trim($_POST['username']) : ''; // Зберігаємо для повторного виведення
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    // $username_db = mysqli_real_escape_string($conn, $form_username); // Не потрібно з prepared statements

    if (empty($form_username) || empty($password)) {
        $page_alert_message = "Будь ласка, введіть логін та пароль.";
        $page_alert_type = 'danger';
    } else {
        // Якщо це адміністратор (спеціальні облікові дані)
        if ($form_username === 'adminBooks' && $password === 'adminBooks') {
            $admin_query = $conn->prepare("SELECT id, username, role, email FROM users WHERE username = ? AND role = 'admin' LIMIT 1");
            if ($admin_query) {
                $admin_query->bind_param("s", $form_username);
                $admin_query->execute();
                $admin_result = $admin_query->get_result();

                if ($admin_result && $admin_result->num_rows > 0) {
                    $admin_user = $admin_result->fetch_assoc();
                    $_SESSION['user_id'] = $admin_user['id'];
                    $_SESSION['username'] = $admin_user['username'];
                    $_SESSION['role'] = $admin_user['role'];
                    $_SESSION['user_email'] = $admin_user['email']; // Зберігаємо email адміна

                    if ($admin_result) $admin_result->close();
                    $admin_query->close();
                    if(isset($conn)) mysqli_close($conn);
                    header('Location: admin_panel.php');
                    exit;
                } else {
                    $page_alert_message = "Невірний логін або пароль для адміністратора!";
                    $page_alert_type = 'danger';
                }
                if ($admin_result) $admin_result->close(); // Закриваємо, якщо ще не закрито
                $admin_query->close();
            } else {
                $page_alert_message = "Помилка підготовки запиту для адміністратора.";
                $page_alert_type = 'danger';
                error_log("Admin login prepare error: " . $conn->error);
            }
        } else {
            // Якщо звичайний користувач, перевірка в базі даних
            $query = $conn->prepare("SELECT id, username, password, role, email FROM users WHERE username = ? LIMIT 1");
            if ($query) {
                $query->bind_param("s", $form_username);
                $query->execute();
                $result = $query->get_result();

                if ($result && $result->num_rows > 0) {
                    $user = $result->fetch_assoc();

                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['user_email'] = $user['email']; // Зберігаємо email користувача

                        if ($result) $result->close();
                        $query->close();
                        if(isset($conn)) mysqli_close($conn);

                        if ($user['role'] === 'admin') { // На випадок, якщо адмін увійшов через звичайну форму
                            header('Location: admin_panel.php');
                        } else {
                            header('Location: profile.php');
                        }
                        exit;
                    } else {
                        $page_alert_message = "Невірний логін або пароль!";
                        $page_alert_type = 'danger';
                    }
                } else {
                    $page_alert_message = "Користувача з таким логіном не існує!";
                    $page_alert_type = 'danger';
                }
                if ($result) $result->close(); // Закриваємо, якщо ще не закрито
                $query->close();
            } else {
                $page_alert_message = "Помилка підготовки запиту для користувача.";
                $page_alert_type = 'danger';
                error_log("User login prepare error: " . $conn->error);
            }
        }
    }
}

// 5. Встановлюємо заголовок сторінки
$page_title = "Вхід - Інтернет-магазин книг";

// 6. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 8. Починаємо HTML-розмітку ?>
    <section class="auth-form-container">
        <div class="section-title-container"><h2>Вхід до акаунту</h2></div>

        <?php // Виведення повідомлень (якщо є) ?>
        <?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
            <div class="alert alert-<?php echo $page_alert_type; ?>">
            <span class="alert-icon">
                <?php
                if ($page_alert_type === 'danger') echo '&#10008;';
                else echo '&#8505;';
                ?>
            </span>
                <?php echo htmlspecialchars($page_alert_message); // Повідомлення тепер не містить HTML, тому htmlspecialchars тут безпечно ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form-content">
            <div class="form-group">
                <label for="username">Логін:</label>
                <input type="text" name="username" id="username" required value="<?php echo htmlspecialchars($form_username); ?>">
            </div>

            <div class="form-group">
                <label for="password">Пароль:</label>
                <input type="password" name="password" id="password" required>
            </div>

            <?php /*
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="rememberMe">
            <label class="form-check-label" for="rememberMe">Запам'ятати мене</label>
        </div>
        */ ?>

            <button type="submit" class="btn-generic btn-primary btn-full-width" style="margin-top: 10px;">Увійти</button>
        </form>
        <p class="auth-alternate-action">Ще не маєте акаунту? <a href="register.php">Зареєструватися</a></p>
        <?php /* <p class="auth-alternate-action" style="margin-top: 10px;"><a href="forgot_password.php">Забули пароль?</a></p> */ ?>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 10. Підключаємо футер
include_once('includes/footer.php');
?>