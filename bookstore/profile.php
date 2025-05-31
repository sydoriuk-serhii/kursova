<?php
// Файл: profile.php

// 1. Підключення до бази даних
include_once('includes/db.php');

// 2. Запуск сесії (вже в header.php)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 3. Перевірка, чи користувач увійшов в систему та чи не є адміном
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') {
    if(isset($conn)) mysqli_close($conn); // Закриваємо з'єднання перед редиректом
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user = null;
$page_alert_message = ''; // Для можливих повідомлень
$page_alert_type = '';

// 4. Отримання даних профілю користувача
$query_user = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
if ($query_user) {
    $query_user->bind_param("i", $user_id);
    $query_user->execute();
    $result_user = $query_user->get_result();

    if ($result_user && $result_user->num_rows > 0) {
        $user = $result_user->fetch_assoc();
    } else {
        // Якщо користувача з таким ID немає в базі, незважаючи на наявність сесії, це помилка
        // Видаляємо сесію та перенаправляємо на логін
        session_unset();
        session_destroy();
        if ($result_user) $result_user->close();
        $query_user->close();
        if(isset($conn)) mysqli_close($conn);
        header("Location: login.php?message=" . urlencode("Помилка даних профілю. Будь ласка, увійдіть знову."));
        exit;
    }
    if ($result_user) $result_user->close();
    $query_user->close();
} else {
    // Помилка підготовки запиту
    error_log("Profile page prepare error: " . $conn->error);
    $page_alert_message = "Не вдалося завантажити інформацію профілю через технічну помилку.";
    $page_alert_type = 'danger';
    // Не перенаправляємо, але покажемо помилку на сторінці, якщо $user залишиться null
}


// 5. Встановлюємо заголовок сторінки
if ($user) {
    $page_title = "Профіль: " . htmlspecialchars($user['username']);
} else {
    $page_title = "Мій профіль - Помилка";
}

// 6. Підключаємо хедер
include_once('includes/header.php');
?>

<?php // 8. Починаємо HTML-розмітку ?>
    <section class="panel-container profile-custom-panel">
        <div class="section-title-container"><h2>Мій профіль</h2></div>

        <?php // Виведення повідомлень (якщо є) ?>
        <?php if (!empty($page_alert_message) && !empty($page_alert_type)): ?>
            <div class="alert alert-<?php echo $page_alert_type; ?>">
            <span class="alert-icon">
                <?php
                if ($page_alert_type === 'danger') echo '&#10008;';
                else echo '&#8505;';
                ?>
            </span>
                <?php echo htmlspecialchars($page_alert_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="profile-details-separator">
                <h3 class="profile-welcome-message">Ласкаво просимо, <?php echo htmlspecialchars($user['username']); ?>!</h3>
                <div class="info-grid">
                    <p><strong>ID користувача:</strong> <?php echo $user['id']; ?></p>
                    <p><strong>Ім'я користувача (логін):</strong> <?php echo htmlspecialchars($user['username']); ?></p> <?php // Додано логін для повноти ?>
                    <p><strong>Електронна пошта:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Дата реєстрації:</strong> <?php echo date("d.m.Y \о H:i", strtotime($user['created_at'])); ?></p> <?php // Змінено формат дати ?>
                </div>
            </div>

            <h3 class="profile-section-subtitle">Історія моїх замовлень</h3>
            <p class="profile-action-link-container"> <?php // Обгортка для можливої стилізації ?>
                Переглянути деталі ваших замовлень можна на сторінці <a href="order.php" class="text-link">"Мої замовлення"</a>.
                <?php // Або як кнопка: ?>
                <?php /* <a href="order.php" class="btn-generic btn-primary btn-sm">Перейти до моїх замовлень</a> */ ?>
            </p>

            <?php /*
        <div style="margin-top: 30px;">
            <a href="edit_profile.php" class="btn-generic btn-secondary">Редагувати профіль</a>
        </div>
        */ ?>

        <?php elseif (empty($page_alert_message)): // Якщо $user не встановлено і немає іншого повідомлення про помилку ?>
            <div class="alert alert-warning">
                <span class="alert-icon">&#8505;</span>
                Не вдалося завантажити інформацію профілю. Будь ласка, спробуйте <a href="login.php" class="alert-link">увійти знову</a>.
            </div>
        <?php endif; ?>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn) && $conn instanceof mysqli && mysqli_ping($conn)) {
    mysqli_close($conn);
}

// 10. Підключаємо футер
include_once('includes/footer.php');
?>