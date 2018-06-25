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
     * 数据添加
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if( !$this->__check($data,$msg) )  return false;

        $data['created_time'] = time();
        $data['modified_time'] = $data['create_time'];

        $mallItemId = $this->mallItemModel->insert($data);
        if( !$mallItemId )
        {
            $msg = app::get('sysmall')->_('数据添加失败');
            return false;
        }

        $msg = app::get('sysmall')->_('数据添加成功');

        return $mallItemId;
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
     * @param $filter 通过mall_item_id删除
     * @return bool
     */
    public function delete($filter)
    {
        if( empty($filter['mall_item_id']) )
        {
            throw new \LogicException('参数mall_item_id不为空');
            return false;
        }
        if( is_object($filter['mall_item_id']) )
        {
            throw new \LogicException('参数mall_item_id类型错误');
            return false;
        }
        if( !is_array($filter['mall_item_id']) )
        {
            $filter['mall_item_id'] = array($filter['mall_item_id']);
        }

        foreach ($filter['mall_item_id'] as $mall_item_id)
        {
            $where = array(
                'mall_item_id' => $mall_item_id,
                'fields' => 'shop_id'
            );
            $mall_item = app::get('sysmall')->rpcCall('mall.item.get',$where);//获取选货商品详情数据

            $params = array(
                'mall_item_id' => $mall_item_id,
                'shop_id' => $mall_item['shop_id']
            );
            $res = app::get('sysmall')->rpcCall('mall.item.delete', $params);

            if(!$res)
            {
                $msg = app::get('sysmall')->_('数据删除失败');
                throw new \LogicException($msg);
                return false;
            }
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