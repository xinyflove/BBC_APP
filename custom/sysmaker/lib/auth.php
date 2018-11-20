<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客认证Facade类
 */
class sysmaker_auth extends base_facades_facade {
    
    private static $__request;

    protected static function getFacadeAccessor()
    {
        if (!static::$__request)
        {
            static::$__request = new sysmaker_passport();

        }
        return static::$__request;
    }
}