<?php
// Файл: logout.php
if (session_status() == PHP_SESSION_NONE) { // На випадок, якщо десь ще може бути викликано
    session_start();
}

// Скасувати всі змінні сесії
$_SESSION = array(); // Більш надійний спосіб очистити масив $_SESSION

// Якщо використовуються сесійні куки, їх також варто видалити
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Знищити сесію
session_destroy();

// Перенаправити на сторінку входу з повідомленням
header("Location: login.php?logout_success=true&message=" . urlencode("Ви успішно вийшли з системи."));
exit;
?>