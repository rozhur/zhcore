<?php

namespace classes\mvc\controller;

class Error extends Controller
{
    public function actionIndex($params = [])
    {
        if (!isset($params['error']))
        {
            $this->redirect('/')->send();
        }
        return $this->view->content('error', [
            'error' => $params['error'],
            'title' => $params['error'],
            'sidebar' => false
        ]);
    }
}