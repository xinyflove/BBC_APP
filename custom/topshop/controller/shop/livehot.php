<?php

/**
 * 直播热销类
 * Class topshop_ctl_shop_livehot_list
 */
class topshop_ctl_shop_livehot extends topshop_controller{

    /**
     * 直播热售商品列表
     */
    public function list_livehot(){
        $shop_id = $this->shopId;
        //更新一遍直播热售表数据
//        $livehotData2 = app::get('topshop')->rpccall('item.livehot.get',['shop_id'=>$shop_id]);
//        $livehot_item = app::get('sysshop')->model('livehot_item');
//        foreach ($livehotData2 as $v2){
//            $item_info2 = array();
//            $where2 = [
//                'item_id'=>$v2['item_id'],
//                'shop_id'=>$v2['shop_id'],
//                'fields'=>[
//                    'row'=>'livehot_img'
//                ],
//            ];
//            $item_info2 = app::get('topshop')->rpccall('item.get',$where2);
//            $a = $item_info2['livehot_img'];
//            if(!empty($item_info2['livehot_img'])){
//                $livehot_item->update(['status'=>1],['livehot_id'=>$v2['livehot_id']]);
//            }else{
//                $livehot_item->update(['status'=>0],['livehot_id'=>$v2['livehot_id']]);
//            }
//        }
        //生成列表数据
        $livehotData = app::get('topshop')->rpccall('item.livehot.get',['shop_id'=>$shop_id]);
        $itemIds = [];
        foreach ($livehotData as &$v){
            $item_info = array();
            $where = [
                'item_id'=>$v['item_id'],
                'shop_id'=>$v['shop_id'],
                'fields'=>[
                    'row'=>'item_id,title,price,livehot_img'
                ],
            ];
//            $item_info = app::get('topshop')->rpccall('item.get',$where);
//            $v['item'] =$item_info;
            array_push($itemIds,$v['item_id']);
        }

        if(empty($itemIds)){
            return $this->page('topshop/shop/livehot/list.html');
        }
        $where_item = [
            'item_id'=>$itemIds,
            'shop_id'=>$shop_id,
        ];
        $row = 'item_id,title,price,livehot_img,approve_status';
        $items = app::get('sysitem')->model('item')->getList($row, $where_item, null, null, null);

        $items_arr = [];
        foreach ($items as $v_items){
            $items_arr[$v_items['item_id']] = $v_items;
        }
        foreach ($livehotData as &$vv){
            $vv['item'] =$items_arr[$vv['item_id']];
        }


        $pagedata['data'] = $livehotData;

        return $this->page('topshop/shop/livehot/list.html', $pagedata);
    }

    /**
     * 直播热售添加
     */
    public function add(){
        return $this->page('topshop/shop/livehot/add.html');
    }

    public function add_post(){
        $a = input::all();
        //获取选取的item_id数组
        $itmes = input::get('item_id');
        if(empty($itmes)){
            return $this->splash("error",url('shop/livehot/add_livehot.html'),"热售商品不能为空，请选择商品!",true);
        }
        // TODO  sku情况暂时没考虑
        $item_sku = input::get('item_sku');
        $shop_id = $this->shopId;
        $no_hot_img = app::get('topshop')->rpccall('item.livehot.save',['items'=>$itmes,'shop_id'=>$shop_id]);
//        if($no_hot_img === true){
//            return $this->splash("error",url('shop/livehot/list_livehot.html'),"直播广告图缺失，请及时添加！",true);
//        }
        return $this->splash("success",url('shop/livehot/list_livehot.html'),"保存成功",true);
    }


    /**
     * 直播热售删除
     */
    public function delete(){
        $livehot_item = app::get('sysshop')->model('livehot_item');
        $livehot_id = input::get('id');
        $livehot_item->delete(['livehot_id'=>$livehot_id]);
        $this->splash("success",null,"删除成功",true);
    }
}