<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2018/6/21
 * Time: 14:31
 */
class sysmall_api_item_delete{

    public $apiDescription = "选货商品删除";

    public $use_strict_filter = true; // 是否严格过滤参数

    public function getParams()
    {
        $return['params'] = array(
            'mall_item_id' => ['type'=>'integer','valid'=>'integer|min:1','description'=>'选货商品id','example'=>'1'],
            'item_id' => ['type'=>'integer','valid'=>'integer|min:1','description'=>'原始商品id','example'=>'2'],
            'shop_id' => ['type'=>'integer','valid'=>'required|integer|min:1','description'=>'原始店铺id','example'=>'3'],
        );
        return $return;
    }

    public function itemDelete($params)
    {
        if(is_null($params['mall_item_id']) && is_null($params['item_id']))
        {
            throw new \LogicException('删除数据标识有误[mall_item_id或item_id不为空]');
        }

        $where = array('fields' => 'mall_item_id,item_id,shop_id');
        if(!empty($params['mall_item_id']))
        {
            $where['mall_item_id'] = $params['mall_item_id'];
        }
        if(!empty($params['item_id']))
        {
            $where['item_id'] = $params['item_id'];
        }
        $mall_item = app::get('sysmall')->rpcCall('mall.item.get',$where);//获取选货商品详情数据
        unset($params, $where);

        if(empty($mall_item))
        {
            $msg = app::get('sysmall')->_('只能删除本店铺商品或数据不存在');
            throw new \LogicException($msg);
        }

        try
        {
            //更新选货商品的状态
            $mall_params = array(
                'mall_item_id' => $mall_item['mall_item_id'],
                'deleted' => 1,
                'status' => 'instock',
                'reason' => '',
            );
            $objMallDataItem = kernel::single('sysmall_data_item');
            $mall_res = $objMallDataItem->update($mall_params, $msg);
            if( !$mall_res )
            {
                throw new \LogicException('删除选货商品失败[更新选货商品的状态出错]');
            }

            //更新代售商品的状态
            $filter = array(
                'init_item_id' => $mall_item['item_id'],
                'init_shop_id' => $mall_item['shop_id'],
            );
            $objMdlItem = app::get('sysitem')->model('item');
            $init_item = $objMdlItem->getList('item_id,shop_id', $filter);
            unset($filter, $mall_item);
            if( !empty($init_item) )
            {
                $objMdlItemStatus = app::get('sysitem')->model('item_status');
                foreach ($init_item as $item)
                {
                    $data = array(
                        'approve_status' => 'instock',
                        'delist_time' => time(),
                    );
                    $filter = array(
                        'item_id' => $item['item_id'],
                        'shop_id' => $item['shop_id'],
                    );
                    $status_res = $objMdlItemStatus->update($data, $filter);
                    if( !$status_res )
                    {
                        throw new \LogicException('删除选货商品失败[更新代售商品的状态出错]');
                    }
                }
            }
        }
        catch(Exception $e)
        {
            throw new Exception($e->getMessage());
        }
        return true;
    }
}
