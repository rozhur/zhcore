<?php

namespace classes\mvc\controller;

use classes\App;
use classes\mvc\model\Model;
use classes\mvc\ReplyException;
use classes\mvc\View;

abstract class Controller
{
    protected $app;
    protected $model;
    protected $view;
    protected $isPost;
    protected $isAjax;

    public function __construct(App $app, Model $model = null)
    {
        $this->app = $app;
        $this->model = $model;
        $this->view = new View();
        $this->isPost = $_SERVER['REQUEST_METHOD'] == 'POST';
        $this->isAjax = $this->isPost && isset($_POST['ajax']);
    }

    abstract public function actionIndex($params = []);

    public function redirect($url = null, $code = 302)
    {
        return \Core::app()->getRouter()->redirect($url, $code);
    }

    public function previousRedirect($code = 302)
    {
        return \Core::app()->getRouter()->redirect($_SESSION['prev_url'], $code);
    }

    public function assert($type)
    {
        switch ($type)
        {
            case 'login':
            {
                if (!\Core::visitor()->user_id)
                {
                    throw new ReplyException($type);
                }
                break;
            }
        }
    }

    public function error($error = null)
    {
        $error_controller = new Error($this->app);
        return $error_controller->actionIndex(['error' => $error]);
    }

    public function notify($type, $message)
    {
        $this->view->notify($type, $message);
    }

    public function ajax($data = [])
    {
        echo json_encode($data);
        exit;
    }

    public function reply($data = [])
    {
        $this->app->sendReply($data);
        exit;
    }
}