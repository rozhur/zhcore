<?php

namespace classes\mvc;

use classes\App;
use classes\http\Response;
use classes\mvc\controller\Controller;
use classes\mvc\controller\Login;

class Router
{
    protected $app = null;

    public $root = '';

    public function __construct(App $app)
    {
        $this->app = $app;

        $root = dirname($_SERVER['PHP_SELF']);
        if ($root === '/')
        {
            $root = '';
        }

        $this->root = $root;
    }

    /** @return Response */
    public function redirect($uri = null, $code = 200)
    {
        if ($uri === null)
        {
            $uri = $_SESSION['pre_url'] ?? $_SERVER['REQUEST_URI'];
        }
        else
        {
            $uri = $this->root . $uri;
        }
        return $this->app->getResponse()->redirect($uri, $code);
    }

    /** @return View */
    public function start($requestUri = null)
    {
        $root = $this->root;

        if ($requestUri == null)
        {
            $requestUri = $_SERVER['REQUEST_URI'];
        }
        $requestUri = preg_replace('#^' . $root . '#', '', $requestUri);
        $uriSplit = explode('?', trim($requestUri, '/'), 2);
        $routes = explode('/', $uriSplit[0]);
        $params['GET'] = $_GET;
        $params['POST'] = $_POST;

        $controllerName = 'index';
        $actionName = 'index';

        if (!empty($routes[0]))
        {
            $controllerName = strtolower($routes[0]);

            if (!empty($routes[1]))
            {
                $actionName = strtolower($routes[1]);
            }
        }

        $numbers = preg_replace('#[^0-9_]#', '', $controllerName);
        $ids = explode('_', $numbers, 2);

        $params['PRIMARY_ID'] = intval($ids[0] ?? 0);
        $params['SECONDARY_ID'] = intval($ids[1] ?? 0);

        $controllerClass = 'classes\\mvc\\controller\\' . ucfirst(preg_replace('#([a-z])[0-9_]+#i', '$1', $controllerName));

        $actionName = str_replace('_', ' ', strtolower($actionName));

        $method = 'action' . str_replace(' ', '', ucwords($actionName));
        $correctUri = $root . '/' . ($controllerName === 'index' || $controllerName === 'controller' || !class_exists($controllerClass) ? '' : $controllerName) . ($actionName === 'index' || !method_exists($controllerClass, $method) ? '' : '/' . strtolower($actionName)) . ($params['GET'] ? '?' . http_build_query($params['GET']) : '');

        if ($_SERVER['REQUEST_URI'] != $correctUri) {
            $this->app->getResponse()->redirect($correctUri)->send();
        }

        $modelClass = 'classes\\mvc\\model\\' . ucfirst(preg_replace('#([a-z])[0-9_]#i', '$1', $controllerName));

        /** @var Controller $controller */
        $controller = new $controllerClass($this->app, class_exists($modelClass) ? new $modelClass() : null);
        try
        {
            $result = $controller->$method($params);
            if (is_bool($result))
            {
                if ($result === false)
                {
                    $this->redirect('/')->send();
                }
                else
                {
                    $this->redirect()->send();
                }
            }
            return $result;
        }
        catch (ReplyException $e)
        {
            switch ($e->getMessage())
            {
                case 'login': {
                    $login = new Login($this->app);
                    $login->notify('error', 'Для этого действия вы должны авторизоваться');
                    return $login->actionIndex([
                        'with_error' => true
                    ]);
                }
            }
        }
    }
}
