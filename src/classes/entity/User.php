<?php

namespace classes\entity;

class User extends Entity
{
    /** @var int */
    public $user_id;
    public $username;
    public $__password;
    public $__session_id;

    public function setSessionId($session_id)
    {
        $this->__session_id = $session_id;
    }
}