<div class="block">
    Вы на странице пользователя <b><?php echo $user->username ?></b>
</div>
<?php if ($visitor->user_id != $user->user_id) { ?>
<div class="block">
    <a class="button" href="<?php echo $root . '/conv?start=' . $user->user_id ?>">Сообщение</a>
</div>
<?php } ?>