<?php
/*
 * Date: 2018-6-15 16:31:30
 * Author: 王衍生
 * authorEmail: 50634235@qq.com
 * company: 青岛广电电商
 * 推送商品到选货商城
 * item.get
 */
class sysmall_api_item_push{

    /**
     * 接口作用说明
     */
    public $apiDescription = '推送商品到选货商城';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'item_id' => ['type'=>'int','valid'=>'required','description'=>'商品id','example'=>'2','default'=>''],
            'shop_id' => ['type'=>'int','valid'=>'required','description'=>'店铺id','example'=>'2','default'=>''],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的商品字段集','example'=>'title,item_store.store,item_status.approve_status','default'=>'*'],
        );

        // $return['extendsFields'] = ['item_desc','item_count','item_store','item_status','sku','item_nature','spec_index','promotion'];
        return $return;
    }

    public function itemPush($params)
    {
        /*add_by_xinyufeng_2018-06-22_start*/
        // 判断原始商品是否上架和是否有供货价
        $init_params['item_id'] = $params['item_id'];
        $init_params['fields'] = "supply_price,sysitem_item_status.approve_status";
        $init_item = app::get('sysitem')->rpcCall('item.get', $init_params);//获取原始商品详情数据
        if( !intval($init_item['supply_price']) )
        {
            throw new LogicException('请完善商品供货价数据！');
        }
        if( $init_item['approve_status'] != 'onsale' )
        {
            throw new LogicException('请先上架商品！');
        }
        unset($init_params, $init_item);
        /*add_by_xinyufeng_2018-06-22_end*/

        $mallItemModel = app::get('sysmall')->model('item');
        $iteminfo = $mallItemModel->getRow('item_id', ['item_id' => $params['item_id']]);
        $data['item_id'] = $params['item_id'];
        $data['shop_id'] = $params['shop_id'];
        $data['status'] = app::get('sysmall')->getConf('sysmall.setting.goods_check') == 'true' ? 'pending' : 'onsale';
        $data['reason'] = '';
        $data['deleted'] = 0;
        if($iteminfo){
            // 更新
            $res = kernel::single('sysmall_data_item')->update($data, $msg);
            if($res)
            {
                return true;
            }
        }else{
            // 新增
            $mallItemId = kernel::single('sysmall_data_item')->add($data,$msg);
            if($mallItemId)
            {
                return true;
            }
        }

        throw new LogicException('商品插入到选货商城发生错误，请重试！');
    }
}
