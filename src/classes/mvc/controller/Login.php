<?php

namespace classes\mvc\controller;

use classes\entity\User;
use classes\util\Utils;

class Login extends Controller
{
    public function actionIndex($params = [])
    {
        if (isset($params['with_error']))
        {
            $_SESSION['pre_url'] = $_SERVER['REQUEST_URI'];
        }

        $visitor = \Core::visitor();
        if ($visitor->user_id)
        {
            return false;
        }
        return $this->view->content('login', ['sidebar' => false, 'title' => 'Вход']);
    }

    public function actionLogin($params = [])
    {
        $visitor = \Core::visitor();
        if ($visitor->user_id)
        {
            return false;
        }

        $data = $params['POST'] ?? false;

        if (!$data)
        {
            return $this->errorAuth();
        }

        $login = $data['login'];


        /** @var User $user */
        $user = \Core::app()->find('User', $login);
        if (!$user && !$user->user_id)
        {
            return $this->errorAuth();
        }

        $password = md5(trim($data['password']));

        if ($password != $user->__password)
        {
            return $this->errorAuth();
        }

        $_SESSION['auth'] = true;
        $_SESSION['user_id'] = $user->user_id;

        $session_id = $user->__session_id ? $user->__session_id : Utils::generateHash($user->user_id);
        $user->setSessionId($session_id);
        $user->save();
        \Core::app()->setCookie('core_user', $user->user_id . ':' . $session_id);

        return true;
    }

    protected function errorAuth()
    {
        $this->view->notify('error', 'Неверные имя пользователя или пароль');
        return $this->actionIndex();
    }
}
