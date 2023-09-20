<?php

namespace classes\mvc\controller;

class Logout extends Controller
{
    public function actionIndex($params = [])
    {
        $this->assert('login');

        session_destroy();

        \Core::app()->setCookie('core_user', null);

        $this->redirect('/')->send();
    }
}