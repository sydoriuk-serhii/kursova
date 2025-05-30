<?php
// Налаштування підключення до бази даних
$host = 'localhost';    // Хост бази даних (зазвичай 'localhost')
$username = 'root';     // Ім'я користувача (зазвичай 'root' на локальному сервері)
$password = '';         // Пароль (зазвичай порожній на локальному сервері)
$dbname = 'bookstore';  // Назва бази даних

// Створення з'єднання
$conn = mysqli_connect($host, $username, $password, $dbname);

// Перевірка з'єднання
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
D