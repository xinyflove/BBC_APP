<?php
/**
 * Created by PhpStorm.
 * User: CaffreyXin
 * Date: 2017/8/31
 * Time: 13:50
 * Desc: 
 */
class testapp_data_level {
    // 等级model
    public $levelModel = null;

    /**
     * testapp_data_level constructor.
     */
    public function __construct()
    {
        $this->levelModel = app::get('testapp')->model('level');
    }

    /**
     * 等级添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['create_time'] = time();
        $data['modified_time'] = $data['create_time'];
        
        $levelId = $this->levelModel->insert($data);
        if( !$levelId )
        {
            $msg = app::get('testapp')->_('等级添加失败');
            return false;
        }

        $msg = app::get('testapp')->_('等级添加成功');

        return $levelId;
    }

    /**
     * 等级更新
     * @param $data
     * @param $msg
     * @return bool
     */
    public function update($data, &$msg)
    {
        if( empty( $data['level_id'] ) )
        {
            $msg = app::get('testapp')->_('参数错误');
            return false;
        }

        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        //更新属性
        if( !$this->levelModel->update($data, array('level_id'=>$data['level_id'])) )
        {
            $msg = app::get('testapp')->_('等级更新失败');
            return false;
        }
        $msg = app::get('testapp')->_('等级更新成功');

        return true;
    }

    /**
     * 验证数据函数
     * @param $data
     * @param $msg
     * @return bool
     */
    private function __check($data, &$msg)
    {
        if( empty( $data['name']) )
        {
            $msg = app::get('testapp')->_('请添加等级名称');
            return false;
        }

        if( empty( $data['discount']) )
        {
            $msg = app::get('testapp')->_('请添加折扣数');
            return false;
        }

        return true;
    }
}
