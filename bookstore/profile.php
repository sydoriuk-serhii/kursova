<?php
// session_start(); // Вже в header.php
include('includes/db.php');

// Перевірка, чи користувач увійшов в систему
if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'admin') { // Адміна перенаправляємо в адмін-панель
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Отримання даних профілю користувача
$query_user = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$result_user = $query_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    // Малоймовірно, якщо сесія існує, але про всяк випадок
    session_destroy();
    header("Location: login.php?message=Помилка профілю, увійдіть знову.");
    exit;
}
$query_user->close();


$page_title = "Профіль: " . htmlspecialchars($user['username']);
include('includes/header.php');
?>
    <link rel="stylesheet" href="css/profile.css"> <div style="text-align: center; margin-bottom: 20px;">
    <button onclick="history.back()" class="btn-back" style="padding: 10px 15px; background-color: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">Назад</button>
</div>

    <section class="profile-section">
        <h2>Мій профіль</h2>
        <div class="profile-details">
            <div class="profile-info">
                <h3>Ласкаво просимо, <?php echo htmlspecialchars($user['username']); ?>!</h3>
                <p><strong>ID користувача:</strong> <?php echo $user['id']; ?></p>
                <p><strong>Електронна пошта:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Дата реєстрації:</strong> <?php echo date("d.m.Y H:i", strtotime($user['created_at'])); ?></p>
            </div>
        </div>

        <h3 style="margin-top: 30px;">Історія моїх замовлень</h3>
        <p>Переглянути деталі ваших замовлень можна на сторінці <a href="order.php">"Мої замовлення"</a>.</p>

    </section>

<?php
mysqli_close($conn);
include('includes/footer.php');
?>