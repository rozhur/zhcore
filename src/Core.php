<?php

class Core
{
    protected static $rootDir;
    protected static $sourceDir;

    protected static $app = null;

    /** @var \classes\entity\User */
    public static $visitor = null;

    public static $time = 0.0;

    public static function init($rootDir)
    {
        self::$time = microtime(true);
        require $rootDir . '/src/vendor/autoload.php';
        self::$rootDir = $rootDir;
        self::$sourceDir = __DIR__;
    }

    /** @return string */
    public static function getRootDir()
    {
        return self::$rootDir;
    }

    /**
     * @return string
     */
    public static function getSourceDir()
    {
        return self::$sourceDir;
    }

    public static function runApp($appClass)
    {
        $app = self::setupApp($appClass);

        ob_start();

        $response = $app->run();

        if ($response)
        {
            $response->send();
        }
    }

    /** @return \classes\App */
    public static function app()
    {
        if (!self::$app)
        {
            return self::setupApp('\classes\App');
        }
        return self::$app;
    }

    /** @param \classes\App $app */
    public static function setApp(\classes\App $app)
    {
        if (self::$app != null)
        {
            throw new LogicException('App already defined!');
        }
        self::$app = $app;
    }

    /** @return  \classes\App $app */
    public static function setupApp($appClass)
    {
        /** @var \classes\App */
        $app = new $appClass();
        self::setApp($app);
        return $app;
    }

    public static function getDb()
    {
        return self::app()->getDb();
    }

    public static function visitor() {
        if (!self::$visitor)
        {
            $user = self::app()->auth();
            if (!$user)
            {
                self::$visitor = self::app()->find('User', 0);
            }
            else
            {
                self::$visitor = $user;
            }
        }
        return self::$visitor;
    }
}