<?php // Файл: includes/footer.php ?>
</main> <footer>
    <p>&copy; <?php echo date("Y"); ?> Інтернет-магазин книг. Всі права захищено.</p>
    <?php /* Можна додати ще якийсь текст або посилання, якщо потрібно */ ?>
</footer>

<?php
// Визначаємо, чи поточна сторінка - головна (index.php), щоб не показувати кнопку "Назад"
$current_page_basename = basename($_SERVER['PHP_SELF']);
if ($current_page_basename != 'index.php'): //
    ?>
    <div class="fixed-bottom-nav"> <?php // ?>
        <?php // Змінюємо клас кнопки для уніфікації зі стилями з style.css ?>
        <button onclick="history.back()" class="btn-generic btn-secondary btn-back-fixed">Повернутися назад</button> <?php // ?>
    </div>
<?php endif; ?>

<?php // Сюди можна підключати глобальні JS-скрипти, якщо вони є (перед </body>) ?>
<?php // <script src="js/main.js"></script> ?>
</body>
</html>