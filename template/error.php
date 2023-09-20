<div class="error-message">
    <div class="block">
        <span>Упс!<?php echo isset($error) ? ' ' . $error : ' Произошла неизвестная ошибка' ?></span>
    </div>
    <a class="button" href="<?php echo $_SESSION['prev_url'] ?? $root ?>">Назад</a>
</div>