<?php

namespace classes\entity;

class Conversation extends Entity
{
    /** @var int */ public $conversation_id;
    public $first_user_id;
    public $second_user_id;
    public $last_message_id;

    /** @var User */ public $first_User;
    /** @var User */ public $second_User;

    public function setLastMessageId($last_message_id)
    {
        $this->last_message_id = $last_message_id;
    }
}