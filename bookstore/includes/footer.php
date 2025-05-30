</main> <footer>
    <p>&copy; <?php echo date("Y"); ?> Інтернет-магазин книг. Всі права захищено.</p>
</footer>

<?php
// Визначаємо, чи поточна сторінка - головна (index.php)
$is_home_page = basename($_SERVER['PHP_SELF']) == 'index.php';
if (!$is_home_page):
    ?>
    <div class="fixed-bottom-nav">
        <button onclick="history.back()" class="btn-back-fixed">Повернутися назад</button>
    </div>
<?php endif; ?>

</body>
</html>