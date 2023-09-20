<?php

namespace classes\util;

class Utils
{
    public static function generateHash($str = '', $length = 32): string
    {
        $chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';
        $chars_length = strlen($chars);
        $hash = '';
        for ($i = 0; $i < $length; $i++)
        {
            $hash .= $chars[rand(0, $chars_length - 1)];
        }
        return md5($str . $hash);
    }

    public static function millis() {
        $mt = explode(' ', microtime());
        return ((int)$mt[1]) * 1000 + ((int)round($mt[0] * 1000));
    }
}