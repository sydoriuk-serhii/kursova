<?php
session_start();
include_once('includes/db.php'); // Використовуємо include_once

// Перевірка, чи користувач є адміністратором
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    if (isset($conn)) mysqli_close($conn);
    header('Location: login.php');
    exit;
}

$redirect_message = ''; // Для формування повідомлення

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $order_id_for_message = "#" . $order_id; // Для повідомлень

    // Починаємо транзакцію для безпечного видалення
    if (!mysqli_begin_transaction($conn)) {
        error_log("Не вдалося розпочати транзакцію для видалення замовлення ID: {$order_id}");
        $redirect_message = "Помилка сервера при спробі видалити замовлення {$order_id_for_message}.";
        if (isset($conn)) mysqli_close($conn);
        header('Location: admin_panel.php?error_delete=' . urlencode($redirect_message));
        exit;
    }

    try {
        // 1. Видалити пов'язані записи з order_items
        $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        if (!$stmt_items) {
            throw new mysqli_sql_exception("Помилка підготовки запиту видалення позицій замовлення: " . $conn->error);
        }
        $stmt_items->bind_param("i", $order_id);
        if (!$stmt_items->execute()) {
            throw new mysqli_sql_exception("Помилка виконання запиту видалення позицій замовлення: " . $stmt_items->error);
        }
        $stmt_items->close();

        // 2. Видалити саме замовлення
        $stmt_order = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if (!$stmt_order) {
            throw new mysqli_sql_exception("Помилка підготовки запиту видалення замовлення: " . $conn->error);
        }
        $stmt_order->bind_param("i", $order_id);
        if (!$stmt_order->execute()) {
            throw new mysqli_sql_exception("Помилка виконання запиту видалення замовлення: " . $stmt_order->error);
        }

        if ($stmt_order->affected_rows > 0) {
            if (!mysqli_commit($conn)) {
                throw new mysqli_sql_exception("Помилка підтвердження транзакції: " . mysqli_error($conn));
            }
            $redirect_message = "Замовлення {$order_id_for_message} успішно видалено.";
            if (isset($conn)) mysqli_close($conn);
            header('Location: admin_panel.php?success_delete=' . urlencode($redirect_message)); // Передаємо повідомлення як параметр
            exit;
        } else {
            // Якщо affected_rows == 0, можливо, замовлення вже було видалено або ID невірний
            mysqli_rollback($conn);
            $redirect_message = "Замовлення {$order_id_for_message} не знайдено або вже було видалено. Зміни не застосовано.";
            if (isset($conn)) mysqli_close($conn);
            header('Location: admin_panel.php?error_delete=' . urlencode($redirect_message));
            exit;
        }
        // $stmt_order->close(); // Закриється автоматично при виході з блоку або помилці
    } catch (mysqli_sql_exception $exception) {
        mysqli_rollback($conn);
        error_log("Помилка транзакції при видаленні замовлення ID {$order_id}: " . $exception->getMessage());
        $redirect_message = "Помилка бази даних при видаленні замовлення {$order_id_for_message}. Деталі залоговано.";
        if (isset($conn)) mysqli_close($conn);
        header('Location: admin_panel.php?error_delete=' . urlencode($redirect_message));
        exit;
    } finally {
        // Переконуємося, що стейтменти закриті, якщо вони були ініціалізовані
        if (isset($stmt_items) && $stmt_items instanceof mysqli_stmt) {
            $stmt_items->close();
        }
        if (isset($stmt_order) && $stmt_order instanceof mysqli_stmt) {
            $stmt_order->close();
        }
    }
} else {
    $redirect_message = "Невірний або відсутній ID замовлення для видалення.";
    if (isset($conn)) mysqli_close($conn);
    header('Location: admin_panel.php?error_delete=' . urlencode($redirect_message));
    exit;
}

// Цей код не повинен досягатися, оскільки всі шляхи завершуються exit;
if (isset($conn)) mysqli_close($conn);
exit;
?>