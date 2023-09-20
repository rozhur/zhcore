<?php

namespace classes\mvc;

use Throwable;

class ReplyException extends \Exception
{
    public function __construct($type) {
        parent::__construct($type);
    }
}