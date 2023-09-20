<div class="block block-inline<?php if ($conv) echo ' is-open' ?>">
    <ul class="block-container block-dialogs<?php if ($conv) echo ' is-selected' ?>">
        <?php if (!count($last_messages)) { ?>
            <li><span class="conv-item" data-unread="0">Пусто...</span></li>
        <?php } else
        {
            /** @var \classes\entity\Message $message */
            foreach ($last_messages as $i => $message)
            {
                $c = $conv_list[$message->conversation_id] ?? null;
                if ($c === null) {
                    continue;
                }
                $_receiver = $c->first_User->user_id == $visitor->user_id ? $c->second_User : $c->first_User;
                $unread = isset($unread_list[$c->conversation_id]) ? count($unread_list[$c->conversation_id]) : 0;
                echo '<li><a data-unread="' . $unread . '" data-receiver="' . $_receiver->user_id . '" data-conv="' . $c->conversation_id . '" class="conv-item' . ($conv && $conv->conversation_id == $c->conversation_id ? ' is-selected' : '') . '"
                         href="' . $root . '/conv' . $c->conversation_id . '"><span class="message-user">' . $_receiver->username . '</span> <span class="message-text">' . ($message ? ($message->sender_User && $message->sender_User->user_id == $visitor->user_id ? '<i>(Вы)</i> ' : '') . preg_replace('/<br\s?\/?>/', ' ', $message->message) : '') . '</span><span class="message-date">' . ($message ? $message->date : '') . '</span></a></li>';
            }
        }
        ?>
    </ul>
    <?php include $this->template('block_conversation') ?>
</div>