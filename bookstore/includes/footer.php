</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> Інтернет-магазин книг. Всі права захищено.</p>
</footer>

<?php
$current_page_basename = basename($_SERVER['PHP_SELF']); //
if ($current_page_basename != 'index.php'): //
    ?>
    <div class="fixed-bottom-nav">
        <button onclick="history.back()" class="btn-generic btn-secondary btn-sm btn-back-fixed" title="Повернутися на попередню сторінку">
            <span class="icon" aria-hidden="true">↩</span> Назад
        </button>
    </div>
<?php
endif;
?>
</body>
</html>