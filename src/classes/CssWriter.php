<?php

namespace classes;

class CssWriter
{
    /** @var App */
    protected $app = null;

    protected $less_template = [];
    protected $less_dir = null;

    /** @var \Less_Parser|null */
    protected $less = null;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->less_dir = \Core::getRootDir() . '/style/less/';
        $this->less = new \Less_Parser([
            'compress' => true
        ]);
        $this->add('global');
    }

    public function addAll($templates)
    {
        if (!is_array($templates))
        {
            $templates = explode(' ', $templates);
        }
        foreach ($templates as $template)
        {
            $this->add($template);
        }
        return $this;
    }

    public function add($less_template) {
        $file = $this->less_dir . $less_template . '.less';
        if (file_exists($file))
        {
            $this->less_template[$less_template] = $file;
        }
    }

    public function includes($value) {
        preg_match_all('/{include:"(.*?)"}/s', $value, $matches);
        $size = count($matches);
        for ($i = 0; $i < $size; $i++)
        {
            if (!isset($matches[1][$i])) continue;
            $template = $matches[1][$i];
            $file = $this->less_dir . $template . '.less';
            if (file_exists($file))
            {
                $value = str_replace($matches[0][$i], file_get_contents($file), $value);
            }
        }
        return $value;
    }

    public function send($compile = false) {
        header('Content-type: text/css');
        $less_files = array_values($this->less_template);
        $less_files = array_fill_keys($less_files, $this->app->getRouter()->root);
        $cache_dir = \Core::getRootDir() . '/cache/css';
        $options = [
            'cache_dir' => $cache_dir,
            'compress' => true
        ];
        $cache = \Less_Cache::Get($less_files, $options);
        $css = file_get_contents($cache_dir . '/'. $cache);
        echo $css;
        exit;
    }
}