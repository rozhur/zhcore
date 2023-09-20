<?php

namespace classes\mvc\controller;

use classes\entity\User;

class Id extends Controller
{
    public function actionIndex($params = [])
    {
        $user_id = $params['PRIMARY_ID'];

        /** @var User $user */
        $user = $this->app->find('User', $user_id);

        if (!$user->user_id)
        {
            return $this->error('Запрашиваемый пользователь не найден');
        }

        return $this->view
            ->content('member', [
                'user' => $user,
                'title' => $user->username,
                'sidebar' => false
            ]);
    }
}