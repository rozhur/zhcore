<?php

namespace classes;

use classes\data\Adapter;
use classes\entity\Entity;
use classes\entity\User;
use classes\http\Response;
use classes\mvc\Router;

class App
{
    protected $config = [];

    protected $db = null;
    /** @var Router */
    protected $router = null;
    /** @var Response */
    protected $response = null;
    /** @var Finder */
    protected $finder = null;

    /** @var CssWriter */
    protected $cssWriter = null;

    protected $socketServer = null;

    public function __construct()
    {
        $this->initializeConfig();
        $this->initializeModules();
    }

    public function initializeConfig()
    {
        $config = [];

        $config_file = \Core::getSourceDir() . '/config.php';

        if (!file_exists($config_file))
        {
            if (!copy($config_file . '.default', $config_file))
            {
                throw new \InvalidArgumentException('Error copying default config file ' . $config_file . '.default');
            }
        }

        include_once \Core::getRootDir() . '/src/config.php';

        $this->config = $config;
    }

    public function initializeModules()
    {
        $db_config = $this->config['db'] ?? [];
        $this->db = new Adapter($db_config);
        $this->router = new Router($this);
        $this->response = new Response();
    }

    /** @return Response */
    public function run()
    {
        $view = $this->router->start();
        return $this->response->view($view);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Adapter
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return CssWriter
     */
    public function cssWriter() {
        if ($this->cssWriter == null) {
            $this->cssWriter = new CssWriter($this);
        }
        return $this->cssWriter;
    }

    public function setCookie($key, $value, $time = 0)
    {
        setcookie($key, $value, $time == 0 ? time() + 86400 * 365 : time() + $time, '/');
    }

    public function auth($user_id = 0, $session_id = null)
    {
        session_name('core_session_id');
        session_start();

        if (isset($_SESSION['auth']) && $_SESSION['auth'] === true)
        {
            $user_id = $_SESSION['user_id'];
            return $this->find('User', $user_id);
        }
        else
        {
            if ($user_id == 0 || $session_id === null)
            {
                if (!isset($_COOKIE['core_user']))
                {
                    return false;
                }
                $user_cookie = mb_split(':', $_COOKIE['core_user']);
                if (!count($user_cookie))
                {
                    return false;
                }

                $user_id = $user_cookie[0];
                $session_id = $user_cookie[1];
            }

            /** @var User $user */
            $user = $this->find('User', $user_id);

            if ($user->__session_id != $session_id)
            {
                return false;
            }

            $_SESSION['auth'] = true;
            $_SESSION['user_id'] = $user->user_id;

            return $user;
        }
    }

    public function repository($type)
    {
        $repo_class = 'classes\\repository\\' . $type;
        return new $repo_class($this);
    }

    public function find($type, $id)
    {
        if (!$this->finder)
        {
            $this->finder = new Finder($this->getDb());
        }
        return $this->finder->find($type, $id);
    }

    public function sendReply($data = [])
    {
        if (!$this->socketServer)
        {
            $this->socketServer = stream_socket_client('tcp://127.0.0.1:2448');
        }
        fwrite($this->socketServer, json_encode($data) . "\n");
    }
}