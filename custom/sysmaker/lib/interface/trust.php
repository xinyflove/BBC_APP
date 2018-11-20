<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 创客wap微信信任登录配置接口
 */
interface sysmaker_interface_trust
{
    /**
     * 获取Logo地址
     * @return mixed
     */
    public function getLogoUrl();

    /**
     * 设置回调链接地址
     * @param $url
     * @return mixed
     */
    public function setCallbackUrl($url);

    /**
     * 获取回调链接地址
     * @return mixed
     */
    public function getCallbackUrl();

    /**
     * 设置访问源(手机端或PC端)
     * @param $view
     * @return mixed
     */
    public function setView($view);

    /**
     * 获取访问源(手机端或PC端)
     * @return mixed
     */
    public function getView();

    /**
     * 获取access token
     * @param string $code -> $state
     * @return string
     */
    public function getAccessToken($code = null);

    /**
     * 获取OpenId
     * @return mixed
     */
    public function getOpenId();

    /**
     * 获取用户信息
     * @return mixed
     */
    public function getUserInfo();

    /**
     * 获取用户标记
     * @return mixed
     */
    public function getUserFlag();

    /**
     * 生成访问令牌
     * @param $code
     * @return mixed
     */
    public function generateAccessToken($code);

    /**
     * 生成OpenId
     * @return mixed
     */
    public function generateOpenId();

    /**
     * 生成用户信息
     * @return mixed
     */
    public function generateUserInfo();
    
    /**
     * 获取授权链接地址
     * @param string $state
     * @return string
     */
    public function getAuthorizeUrl($state);
}
