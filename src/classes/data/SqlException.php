<?php

namespace classes\data;

class SqlException extends \Exception
{
    public function __construct($message = "", $error = "", $state = "") {
        $this->message = $message . ' [' . $error . '/' . $state . ']';
    }
}