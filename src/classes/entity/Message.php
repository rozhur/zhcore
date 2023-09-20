<?php

namespace classes\entity;

class Message extends Entity
{
    /** @var int */ public $message_id;
    /** @var int */ public $conversation_id;
    public $date;
    public $message;
    public $sender_user_id;
    /** @var bool */ public $is_read;

    /** @var Conversation */ public $Conversation;
    /** @var User */ public $sender_User;
}