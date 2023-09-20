<?php

namespace classes\http;

use classes\mvc\View;

class Response
{
    protected $httpCode = 200;
    protected $headers = [];

    /** @var View */
    protected $view = null;

    public function httpCode($httpCode = null)
    {
        if ($httpCode === null)
        {
            return $this->httpCode;
        }

        $this->httpCode = intval($httpCode);

        return $this;
    }

    public function redirect($url = '/', $httpCode = 200)
    {
        $this->header('Location', $url);
        $this->httpCode = $httpCode;
        return $this;
    }

    public function header($name, $value = null)
    {
        $this->headers[$name] = $value ?? false;
        return $this;
    }

    public function view(View $view = null)
    {
        if ($view == null)
        {
            return $this->view;
        }
        $this->view = $view;
        return $this;
    }

    public function send()
    {
        $_SESSION['prev_url'] = $_SERVER['REQUEST_URI'];

        http_response_code($this->httpCode);
        foreach ($this->headers as $key => $value)
        {
            header("$key: $value");
        }

        if ($this->view)
        {
            $this->view->send();
        }
        \Core::getDb()->getConnection()->close();
        exit;
    }
}