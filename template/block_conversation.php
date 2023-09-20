<div class="block-container block-conversation<?php if ($conv) echo ' is-loading' ?>" data-conv="<?php echo $conv_id ?>"
     data-receiver="<?php if ($receiver) echo $receiver->user_id ?>">
    <div class="block-header"><a class="button button--back" href="<?php echo $root ?>/conv">< Назад</a><a href="" class="message-receiver"><?php if ($receiver) echo $receiver->username; else echo '...' ?></a></div>
    <div class="block-messages">
        <div class="messages-container"></div>
        <form action="" method="post" id="messenger" class="message-editor is-empty">
            <div contenteditable="true" class="input" name="message" autocomplete="off" placeholder="Введите сообщение"></div>
            <button class="button" type="submit"><span class="button-text">Отправить</span></button>
            <span class="message-editor--placeholder">Сообщение</span>
        </form>
    </div>
</div>