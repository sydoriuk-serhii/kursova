<?php // Файл: includes/footer.php ?>
</main> <?php // Закриття .site-main-content, що відкрився в header.php ?>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Інтернет-магазин книг. Всі права захищено.</p>
    <?php /* Тут можна додати додаткову інформацію, наприклад, посилання на соціальні мережі або сторінку "Про нас" */ ?>
</footer>

<?php
// Визначаємо, чи поточна сторінка - головна (index.php), щоб не показувати кнопку "Назад"
$current_page_basename = basename($_SERVER['PHP_SELF']);
if ($current_page_basename != 'index.php'):
    ?>
    <div class="fixed-bottom-nav">
        <button onclick="history.back()" class="btn-generic btn-secondary btn-sm btn-back-fixed" title="Повернутися на попередню сторінку">
            <span class="icon" aria-hidden="true">↩</span> Назад
        </button>
    </div>
<?php endif; ?>

<?php // Сюди можна підключати глобальні JS-скрипти, якщо вони є (перед </body>) ?>
<?php // Наприклад: <script src="js/main.js?v=<?php echo filemtime('js/main.js'); ?>"></script> ?>
</body>
</html>