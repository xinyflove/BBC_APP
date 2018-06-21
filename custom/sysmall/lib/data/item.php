<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2018/6/20
 * Time: 15:45
 */
class sysmall_data_item {
    // 选货商品model
    public $mallItemModel = null;

    /**
     * sysmall_data_item constructor.
     */
    public function __construct()
    {
        $this->mallItemModel = app::get('sysmall')->model('item');
    }

    /**
     * 数据更新
     * @param $data
     * @param $msg
     * @return bool
     */
    public function update($data, &$msg)
    {
        if( !$this->__check($data,$msg) ) return false;

        $data['modified_time'] = time();

        $filter = array();
        if( !empty($data['mall_item_id']) )
        {
            $filter['mall_item_id'] = $data['mall_item_id'];
        }
        if( !empty($data['item_id']) )
        {
            $filter['item_id'] = $data['item_id'];
        }

        //更新属性
        if( !$this->mallItemModel->update($data, $filter) )
        {
            $msg = app::get('sysmall')->_('数据更新失败');
            return false;
        }
        $msg = app::get('sysmall')->_('数据更新成功');

        return true;
    }

    /**
     * 数据删除(逻辑删除)
     * @param $filter
     * @return bool
     */
    public function delete($filter)
    {
        if( !$this->__check($filter, $msg) )
        {
            throw new \LogicException($msg);
            return false;
        }

        $where = " WHERE 1";
        foreach ($filter as $k => $v)
        {
            if(!empty($v) && !is_object($v))
            {
                if(is_array($v))
                {
                    $v_str = implode(',', $v);
                    $where .= " AND {$k} IN ({$v_str})";
                }
                else
                {
                    $where .= " AND {$k} = {$v}";
                }
            }
        }

        $db = app::get('base')->database();
        $sql = "UPDATE sysmall_item SET deleted = 1".$where;
        $delete = $db->executeUpdate($sql);

        if(!$delete)
        {
            $msg = app::get('sysmall')->_('数据删除失败');
            throw new \LogicException($msg);
            return false;
        }
        
        return true;
    }

    /**
     * 检查数据
     * @param $data
     * @param $msg
     * @return bool
     */
    private function __check($data, &$msg)
    {
        if( empty($data['mall_item_id']) && empty($data['item_id']) )
        {
            $msg = app::get('sysmall')->_('参数错误(mall_item_id或item_id不为空)');
            return false;
        }

        if( !empty($data['status']) && !in_array($data['status'], $this->getStatusValue()) )
        {
            $msg = app::get('sysmall')->_('选货商品状态值错误');
            return false;
        }

        return true;
    }

    /**
     * 选货商品状态值
     * @return array
     */
    public function getStatusValue()
    {
        $arr = array('pending', 'refuse', 'onsale', 'instock');
        return $arr;
    }
}