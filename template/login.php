<form action="<?php echo $root ?>/login/login" method="post">
    <div class="form-row">
        <span class="form-name">Логин</span>
        <span class="form-input"><input class="input" type="text" name="login" autocomplete="username"></span>
    </div>
    <div class="form-row">
        <span class="form-name">Пароль</span>
        <span class="form-input"><input class="input" type="password" name="password" autocomplete="current-password"></span>
    </div>
    <div class="form-submit">
        <button type="submit" class="button">Войти</button>
    </div>
</form>