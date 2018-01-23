<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/4
 * Time: 15:39
 * Desc: 商城用户认证Facade类
 */
class sysstore_auth extends base_facades_facade {
    
    private static $__request;

    protected static function getFacadeAccessor()
    {
        if (!static::$__request)
        {
            static::$__request = new sysstore_passport();

        }
        return static::$__request;
    }
}