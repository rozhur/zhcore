<?php

namespace classes\repository;

use classes\App;

abstract class Repository
{
    protected $app;

    public function __construct(App $app) {
        $this->app = $app;
    }
}