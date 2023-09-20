<?php

namespace classes\mvc\controller;

class Panel extends Controller
{
    public function actionIndex($params = [])
    {
        $this->assert('login');

        return $this->view->content('panel', [
            'sidebar' => false
        ]);
    }
}