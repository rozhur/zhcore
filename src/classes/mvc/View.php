<?php

namespace classes\mvc;

use classes\repository\Conversation;
use Core;

class View
{
    protected $template = null;
    protected $template_style = [];
    protected $data = [];

    public $notifies = [];

    public function content($template, $data = [])
    {
        $this->template = $template;
        $this->data = $data;

        return $this;
    }

    public function notify($type, $message)
    {
        $this->notifies[$type][] = $message;
        return $this;
    }

    /** @return View|bool */
    public function style($template_style = null)
    {
        if ($template_style == null)
        {
            return false;
        }

        if (is_array($template_style))
        {
            array_merge($this->template_style, $template_style);
        } else
        {
            array_push($this->template_style, $template_style);
        }

        return $this;
    }

    public function send()
    {
        $template = $this->template;

        $content = Core::getRootDir() . '/template/' . $template . '.php';
        
        $app = Core::app();

        $root = $app->getRouter()->root;

        $extra_style = Core::getRootDir() . '/style/less/' . $template . '.less';
        if (file_exists($extra_style))
        {
            $this->template_style[] = $template;
        }

        $style = [];
        if ($this->template_style)
        {
            $style = ['css' => implode(' ', $this->template_style)];
        }

        $sidenav = true;
        $sidebar = true;

        $notifies = $this->notifies;

        extract($this->data);

        $sidenav = $this->getTemplate($sidenav, 'sidenav');
        $sidebar = $this->getTemplate($sidebar, 'sidebar');

        $extra_js = '/js/' . $template . '.min.js';
        if (!file_exists(Core::getRootDir() . $extra_js))
        {
            $extra_js = null;
        }
        else
        {
            $extra_js = '<script src="' . $root . $extra_js . '?_v=' . md5(filemtime(Core::getRootDir() . $extra_js)) . '"></script>';
        }

        $visitor = Core::visitor();

        $queries = $app->getDb()->getQueries();

        $now = microtime(true);

        $time = $now - Core::$time;

        /** @var Conversation $conv_repo */
        $conv_repo = $app->repository('Conversation');

        $unread_list = $conv_repo->getUnreadMessagesCount($visitor->user_id);

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax']))
        {
            include $content;
        }
        else
        {
            include Core::getRootDir() . '/template/global.php';
        }
    }

    protected function getTemplate($template, $type) {
        if (is_bool($template))
        {
            if ($template)
            {
                $template = Core::getRootDir() . '/template/' . $template . '_' . $type . '.php';
                if (!file_exists($template))
                {
                    $template = Core::getRootDir() . '/template/global_' . $type . '.php';
                }
            }
        }
        else
        {
            $template = Core::getRootDir() . '/template/' . $template . '_' . $type . '.php';
            if (!file_exists($template))
            {
                $template = false;
            }
        }
        return $template;
    }

    public function template($template) {
        $template = Core::getRootDir() . '/template/' . $template . '.php';
        if (!file_exists($template)) {
            return false;
        }
        return $template;
    }
}