<?php
// Файл: profile.php

// 1. Підключення до бази даних (потрібне для отримання даних користувача)
include_once('includes/db.php'); //

// 2. Запуск сесії (header.php це робить)
if (session_status() == PHP_SESSION_NONE) { //
    session_start(); //
}

// 3. Перевірка, чи користувач увійшов в систему та чи не є адміном
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') { //
    header("Location: login.php"); //
    exit; //
}

$user_id = $_SESSION['user_id']; //
$user = null; //

// 4. Отримання даних профілю користувача
$query_user = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?"); //
$query_user->bind_param("i", $user_id); //
$query_user->execute(); //
$result_user = $query_user->get_result(); //

if ($result_user->num_rows > 0) { //
    $user = $result_user->fetch_assoc(); //
} else {
    session_unset(); //
    session_destroy(); //
    header("Location: login.php?message=" . urlencode("Помилка профілю, увійдіть знову.")); //
    exit; //
}
$query_user->close(); //


// 5. Встановлюємо заголовок сторінки
if ($user) { //
    $page_title = "Профіль: " . htmlspecialchars($user['username']); //
} else {
    $page_title = "Мій профіль"; //
}

// 6. ПІДКЛЮЧАЄМО ХЕДЕР
// header.php тепер автоматично підключає css/style.css та css/profile.css (якщо він існує)
include_once('includes/header.php'); //
?>

<?php // 7. Рядок <link rel="stylesheet" href="css/profile.css"> ВИДАЛЕНО ?>

<?php // 8. Починаємо HTML-розмітку ?>
<?php // Замінюємо клас контейнера секції та контейнера заголовка ?>
    <section class="panel-container profile-custom-panel"> <?php // ?>
        <div class="section-title-container"><h2>Мій профіль</h2></div> <?php // ?>
        <?php if ($user): ?>
            <div class="profile-details-separator"> <?php // ?>
                <?php // Змінюємо клас для привітання та для блоку з інформацією ?>
                <h3 class="profile-welcome-message">Ласкаво просимо, <?php echo htmlspecialchars($user['username']); ?>!</h3> <?php // ?>
                <div class="info-grid"> <?php // ?>
                    <p><strong>ID користувача:</strong> <?php echo $user['id']; ?></p> <?php // ?>
                    <p><strong>Електронна пошта:</strong> <?php echo htmlspecialchars($user['email']); ?></p> <?php // ?>
                    <p><strong>Дата реєстрації:</strong> <?php echo date("d.m.Y H:i", strtotime($user['created_at'])); ?></p> <?php // ?>
                </div>
            </div>

            <?php // Клас .section-subtitle вже визначений в css/profile.css (або може бути перенесений в style.css, якщо універсальний) ?>
            <h3 class="profile-section-subtitle">Історія моїх замовлень</h3> <?php // ?>
            <?php // Додаємо клас для абзацу з посиланням ?>
            <p class="profile-action-link">Переглянути деталі ваших замовлень можна на сторінці <a href="order.php">"Мої замовлення"</a>.</p> <?php // ?>
        <?php else: ?>
            <p class="error-message">Не вдалося завантажити інформацію профілю.</p> <?php // ?>
        <?php endif; ?>
    </section>

<?php
// 9. Закриваємо з'єднання з БД
if (isset($conn)) { //
    mysqli_close($conn); //
}

// 10. Підключаємо футер
include_once('includes/footer.php'); //
?>