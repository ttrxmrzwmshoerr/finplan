</main>
<footer class="site-footer">
    <div class="wrap footer-row">
        <div class="logo logo-light">
            <img src="<?= BASE ?>/assets/img/logo-mark-light.svg" alt="" width="28" height="28">
            <span>Фин<span class="accent">План</span></span>
        </div>
        <div>Сервис для планирования финансов<br>ООО «Герлах А.О.», <?= date('Y') ?></div>
        <div class="footer-links">
            <a href="<?= BASE ?>/stats.php">Статистика</a>
            <a href="<?= BASE ?>/goals.php">Цели</a>
            <a href="<?= BASE ?>/tips.php">Советы</a>
            <a href="<?= BASE ?>/rates.php">Курсы валют</a>
        </div>
    </div>
</footer>
<script src="<?= BASE ?>/assets/js/main.js"></script>
<?php foreach ($scripts ?? [] as $s): ?>
<script src="<?= BASE ?>/assets/js/<?= $s ?>"></script>
<?php endforeach; ?>
</body>
</html>
