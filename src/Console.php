<?php

namespace carono\janitor;
/**
 * Class Console
 *
 * @package carono\janitor
 */
class Console extends \yii\helpers\BaseConsole
{
    /**
     * @param bool $raw
     * @return bool|string
     */
    public static function stdin($raw = false)
    {
        $result = $raw ? fgets(\STDIN) : rtrim(fgets(\STDIN), PHP_EOL);
        rewind(\STDIN);
        return $result;
    }
}