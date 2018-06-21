<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2017/12/21
 * Time: 17:26
 * Desc: 接口日志
 */
class foodmap_data_apilog {
    // 接口日志model
    public $apilogModel = null;

    /**
     * foodmap_data_apilog constructor.
     */
    public function __construct()
    {
        $this->apilogModel = app::get('foodmap')->model('apilog');
    }

    /**
     * 接口日志添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];

        $apilogId = $this->apilogModel->insert($data);
        if( !$apilogId )
        {
            $msg = app::get('foodmap')->_('接口日志添加失败');
            return false;
        }

        $msg = app::get('foodmap')->_('接口日志添加成功');

        return $apilogId;
    }

    /**
     * 验证数据函数
     * @param $data
     * @param $msg
     * @return bool
     */
    private function __check($data, &$msg)
    {
        if( empty( $data['api_type']) )
        {
            $msg = app::get('foodmap')->_('请填写接口类型');
            return false;
        }

        if( empty( $data['api_url']) )
        {
            $msg = app::get('foodmap')->_('请填写接口链接');
            return false;
        }

        return true;
    }
}