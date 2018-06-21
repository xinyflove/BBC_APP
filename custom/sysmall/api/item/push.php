<?php
/*
 * Date: 2018-6-15 16:31:30
 * Author: 王衍生
 * authorEmail: 50634235@qq.com
 * company: 青岛广电电商
 * 推送商品到集采商城
 * item.get
 */
class sysmall_api_item_push{

    /**
     * 接口作用说明
     */
    public $apiDescription = '推送商品到集采商城';

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
        // 优化后期加上商品判断
        $mallItemModel = app::get('sysmall')->model('item');
        $iteminfo = $mallItemModel->getRow('item_id', ['item_id' => $params['item_id']]);
        if($iteminfo){
            throw new LogicException('集采商城已存在此商品！');
        }
        $insertData['item_id'] = $params['item_id'];
        $insertData['shop_id'] = $params['shop_id'];
        $insertData['created_time'] = time();
        $insertData['modified_time'] = time();
        $insertData['status'] = app::get('sysmall')->getConf('sysmall.setting.goods_check') == 'true' ? 'pending' : 'onsale';
        if($mallItemModel->save($insertData)){
            return true;
        }
        throw new LogicException('商品插入到集采商城发生错误，请重试！');
    }
}
