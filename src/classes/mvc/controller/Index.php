<?php

namespace classes\mvc\controller;

class Index extends Controller
{
    public function actionIndex($params = [])
    {
        $visitor = \Core::visitor();
        if (!$visitor->user_id)
        {
            $this->view->notify('warning', 'Вы не авторизованы. Пожалуйста, <a href="' . $this->app->getRouter()->root . '/login">войдите</a>');
        }
        return $this->view->content('index');
    }
}