<?php

namespace classes;

use classes\entity\User;
use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;

class WorkerConnect
{

    public function open()
    {
        $users = [];
        if (PHP_SAPI != 'cli')
        {
            die();
        }
        $worker = new Worker("websocket://127.0.0.1:1212");
        /** @var ConnectionInterface $connection */
        $worker->onWorkerStart = function() use (&$users)
        {
            $inner_tcp_worker = new Worker("tcp://127.0.0.1:2448");
            $inner_tcp_worker->onMessage = function($connection, $data) use (&$users) {
                try
                {
                    $data = json_decode($data);

                    $target = $data->target;
                    $result_data = $data->data;
                    if (is_array($target))
                    {
                        foreach ($target as $i => $user)
                        {
                            if (isset($users[$user]))
                            {
                                foreach ($users[$user] as $key => $connection)
                                {
                                    $connection->send(json_encode($result_data));
                                }
                            }
                        }
                    }
                    else if (is_string($target) || is_numeric($target))
                    {
                        switch ($target)
                        {
                            case 'all':
                            {
                                foreach ($users as $user => $connections)
                                {
                                    foreach ($connections as $key => $connection)
                                    {
                                        $connection->send(json_encode($result_data));
                                    }
                                }
                                break;
                            }
                            default:
                            {
                                $user_id = $target;
                                if (isset($users[$user_id]))
                                {
                                    foreach ($users[$user_id] as $key => $connection)
                                    {
                                        $connection->send(json_encode($result_data));
                                    }
                                }
                            }
                        }
                    }
                }
                catch (\Throwable $e)
                {
                    $connection->send(json_encode([
                        'type' => 'log',
                        'error' => $e->getMessage()
                    ]));
                }
            };
            $inner_tcp_worker->listen();
        };

        $worker->onConnect = function($connection) use (&$users)
        {
            $connection->onWebSocketConnect = function($connection) use (&$users)
            {
                /** @var ConnectionInterface $connection */
                $user_id = $_GET['user'] ?? 0;
                $session_id = $_GET['session_id'] ?? null;
                $user = \Core::app()->auth($user_id, $session_id);
                if ($user instanceof User)
                {
                    $connection_id = $_GET['connection'];
                    $users[$user->user_id][$connection_id] = $connection;
                    $connection->send(json_encode([
                        'type' => 'log',
                        'message' => 'Auth success',
                        'result' => count($users[$user->user_id]),
                        'ob' => ob_get_contents()
                    ]));
                }
                else
                {
                    $connection->send(json_encode([
                        'type' => 'log',
                        'message' => 'Auth error',
                        'ob' => ob_get_contents()
                    ]));
                }
            };
        };

        $worker->onClose = function($connection) use(&$users)
        {
            foreach ($users as $user => $connections)
            {
                foreach ($connections as $id => $conn)
                {
                     if ($connection == $conn)
                     {
                         unset($users[$user][$id]);
                     }
                }
            }
        };
        Worker::runAll();
    }
}
