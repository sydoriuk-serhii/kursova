<?php
session_start();
include('includes/db.php');

// Перевірка, чи користувач є адміністратором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = intval($_GET['id']);

    // Починаємо транзакцію для безпечного видалення
    mysqli_begin_transaction($conn);

    try {
        // 1. Видалити пов'язані записи з order_items
        $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $stmt_items->close();

        // 2. Видалити саме замовлення
        $stmt_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt_order->bind_param("i", $order_id);
        $stmt_order->execute();

        if ($stmt_order->affected_rows > 0) {
            mysqli_commit($conn);
            header('Location: admin_panel.php?success_delete=true');
        } else {
            mysqli_rollback($conn);
            header('Location: admin_panel.php?error_delete=Замовлення не знайдено або вже видалено.');
        }
        $stmt_order->close();
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        header('Location: admin_panel.php?error_delete=Помилка бази даних при видаленні.');
    }
} else {
    header('Location: admin_panel.php?error_delete=Невірний ID замовлення.');
}
mysqli_close($conn);
exit;
?>