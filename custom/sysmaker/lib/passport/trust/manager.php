<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-15
 * Desc: 创客信任登录配置信息管理(主要提供获取功能)
 */
class sysmaker_passport_trust_manager
{
    public $trustFlags;

    public function __construct()
    {
        // 从配置文件获取全部创客信任标记
        $this->trustFlags = config::get('makerAuth.trustLogins');
    }

    /**
     * 根据唯一标识获取信任配置类对象
     * @param $flag
     * @return bool|mixed
     */
    public function getTrustObjectByFlag($flag)
    {
        $trustClass = 'sysmaker_plugin_'.$flag;
        $trustObject = kernel::single($trustClass);
        if ($trustObject instanceof sysmaker_plugin_abstract)
        {
            return $trustObject;
        }
        return false;
    }

    /**
     * 获取信任登录配置类对象列表
     * @return array
     */
    public function getTrusts()
    {
        foreach($this->getTrustFlags() as $flag)
        {
            $trusts[] = $this->getTrustObjectByFlag($flag);
        }
        return $trusts;
    }

    /**
     * 获取全部创客信任标记
     * @return mixed
     */
    public function getTrustFlags()
    {
        return $this->trustFlags;
    }
}
