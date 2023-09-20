<?php if ($conv)
{
    $receiver = Core::app()->find('User', $conv->first_user_id == $visitor->user_id ? $conv->second_user_id : $conv->first_user_id); ?>
    <div class="block-container block-conversation<?php if ($conv_id) echo ' block-conversation--' . $conv_id ?>"
         data-receiver="<?php echo $receiver->user_id ?>">
        <div class="block-header"><a class="button" href="<?php echo $root ?>/conv">< Назад</a><a class="message-receiver"><?php echo $receiver->username ?></a></div>
        <div class="block-messages">
            <div class="messages-container">
                <?php
                if (!count($message_list))
                {
                    echo '<span class="empty-message">У вас еще не было диалога с ' . $receiver->username . '</span>';
                } else
                {
                    foreach ($message_list as $i => $m)
                    {
                        $author = $m->sender_user_id == $visitor->user_id ? $visitor : Core::app()->find('User', $m->sender_user_id);
                        echo '<div class="message-item"><a href="' . $root . '/id' . $author->user_id . '" class="message-user">' . $author->username . '</a>: ' . $m->message . ' <span class="message-date">' . date('d/m/Y H:i', strtotime($m->date)) . '</span></div>';
                    }
                }
                ?>
            </div>
        </div>
        <form action="<?php echo $root . '/conv' . $conv->conversation_id . '/send' ?>" method="post"
              class="message-editor is-empty"><input class="input" type="text" name="message" placeholder="Enter a message">
            <button class="button" type="submit">Отправить</button>
        </form>
    </div>
<?php }
?>