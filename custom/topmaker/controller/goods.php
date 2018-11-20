<?php
/**
 * Auth: jiangyunhan
 * Time: 2018-11-15
 * Desc: 创客自选商品列表页面
 */
class topmaker_ctl_goods extends topmaker_controller {

    //自选商品列表
    public function index()
    {

        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        //获取参数信息

        /*$input = input::all();

        //获取主持人绑定的店铺信息
        $shop_seller_model = app::get('sysmaker')->model('shop_rel_seller')->getRow('*',array('seller_id'=>$targetId,'status'=>'success','deleted'=>0));

        //获取店铺的分类信息
        $scparams = ['shop_id'=>$shop_seller_model['shop_id']];
        $shopCatList = app::get('sysitem')->rpcCall('shop.cat.list', $scparams);

        $pagedata['shopCatList'] = $shopCatList['data'];

        $shop_id = $shop_seller_model['shop_id'];
        $cat_id = isset($input['cat_id'])?$input['cat_id']:'';
        $search_keyword = isset($input['search_keyword'])?$input['search_keyword']:'';
        $page = isset($input['page'])?$input['page']:0;
        $page_size = 10;//默认每一页显示10条数据
        //判断提交的参数中有分类或关键字时的处理逻辑
        if(!empty($cat_id)&&!empty($search_keyword)){
            $sql_count="SELECT count(sm.mall_item_id) as count_page FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.cat_id=".$cat_id." AND si.title LIKE '%".$search_keyword."%'";
            $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();
            $max_page = (int)($goods_count['count_page']/$page_size);
            //判断总页数
            if($page>$max_page){
                $page = $max_page;
            }

            $sql="SELECT sm.*,si.title FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.cat_id=".$cat_id." AND si.title LIKE '%".$search_keyword."%' LIMIT ".$page*$page_size.",".$page_size;
            $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();


        }elseif(empty($cat_id)&&!empty($search_keyword)){
            //如果有搜索关键字，则根据关键字去查找对应的商品信息
            $sql_count="SELECT count(sm.mall_item_id) as count_page FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.title LIKE '%".$search_keyword."%'";
            $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();
            $max_page = (int)($goods_count['count_page']/$page_size);
            //判断总页数
            if($page>$max_page){
                $page = $max_page;
            }

            $sql="SELECT sm.*,si.title FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.title LIKE '%".$search_keyword."%' LIMIT ".$page*$page_size.",".$page_size;
            $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();

        }elseif(!empty($cat_id)&&empty($search_keyword)){
            $sql_count="SELECT count(sm.mall_item_id) as count_page FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.cat_id=".$cat_id;
            $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();
            $max_page = (int)($goods_count['count_page']/$page_size);
            //判断总页数
            if($page>$max_page){
                $page = $max_page;
            }

            $sql="SELECT sm.*,si.title FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' AND si.cat_id=".$cat_id." LIMIT ".$page*$page_size.",".$page_size;
            $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();

        }else{

            $sql_count="SELECT count(sm.mall_item_id) as count_page FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale'";
            $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();
            $max_page = (int)($goods_count['count_page']/$page_size);
            //判断总页数
            if($page>$max_page){
                $page = $max_page;
            }

            $sql="SELECT sm.*,si.title,si.cat_id FROM ".
                " sysmall_item AS sm LEFT JOIN sysitem_item AS si ON sm.shop_id = si.shop_id AND sm.item_id=si.item_id WHERE ".
                " sm.status='onsale' LIMIT ".$page*$page_size.",".$page_size;
            $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();

        }



        $pagedata['goods_list'] = $goods_list;
        //获取已经选中的商品信息，用于在显示的时候标记出以选中
        $checked_goods = app::get('sysmaker')->model('seller_item')->getList('item_id',array('seller_id'=>$targetId,'deleted'=>0));
        $checked_goods_arr = array();
        foreach($checked_goods as $checked_good){
            $checked_goods_arr[] = $checked_good['item_id'];
        }
        $pagedata['checked_goods_arr'] = $checked_goods_arr;*/


        /*$sql_count="SELECT count(sk.id) as count_page FROM ".
            " sysmaker_seller_item AS sk LEFT JOIN sysitem_item AS si ON  sk.item_id=si.item_id WHERE ".
            " sk.seller_id=".$targetId;


        $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();
        $max_page = ceil($goods_count['count_page']/$page_size);
        $pagedata['max_page'] = $max_page;
        //判断总页数
        if($page>$max_page){
            $page = $max_page;
        }

        $sql="SELECT sk.*,si.title,si.price,si.image_default_id,si.supply_price FROM ".
            " sysmaker_seller_item AS sk LEFT JOIN sysitem_item AS si ON  sk.item_id=si.item_id WHERE ".
            " sk.seller_id=".$targetId." LIMIT ".$page*$page_size.",".$page_size;
        $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();
        $pagedata['goods_list'] = $goods_list;*/


        return view::make('topmaker/maker/goodselected.html');
    }
    //自选商品ajax数据
    public function indexAjax()
    {

        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        //获取参数信息

        $input = input::all();
        $page = isset($input['num'])?$input['num']:1;
        if($page<1){
            $page = 1;
        }
        $page_size = isset($input['size'])?$input['size']:10;;//默认每一页显示10条数据


        $sql_count="SELECT count(sk.id) as count_page FROM ".
            " sysmaker_seller_item AS sk LEFT JOIN sysitem_item AS si ON  sk.item_id=si.item_id WHERE ".
            " sk.seller_id=".$targetId;


        $goods_count = app::get('base')->database()->executeQuery($sql_count)->fetch();

        $max_page = ceil($goods_count['count_page']/$page_size);
        $pagedata['max_page'] = $max_page;

        //判断总页数
        if($page>$max_page){
            $page = $max_page;
        }

        $sql="SELECT sk.*,si.title,si.price,si.image_default_id,si.supply_price FROM ".
            " sysmaker_seller_item AS sk LEFT JOIN sysitem_item AS si ON  sk.item_id=si.item_id WHERE ".
            " sk.seller_id=".$targetId." LIMIT ".($page-1)*$page_size.",".$page_size;
        $goods_list = app::get('base')->database()->executeQuery($sql)->fetchAll();

        foreach($goods_list as &$good_info) {
            $good_info['image_default_id'] = base_storager::modifier($good_info['image_default_id'], 't');
        }
        $pagedata['goods_list'] = $goods_list;

        return json_encode($pagedata);
    }




    //保存用户提交的需要保存的商品信息
    public function saveGoods(){
        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        //获取参数信息
        $input = input::all();

        //获取了创客提交的选中的商品id
        $input_goods_arr = array('11','411');

        //获取已经选中的商品信息，用于在显示的时候标记出以选中
        $checked_goods = app::get('sysmaker')->model('seller_item')->getList('item_id',array('seller_id'=>$targetId,'deleted'=>0));
        $checked_goods_arr = array();
        foreach($checked_goods as $checked_good){
            $checked_goods_arr[] = $checked_good['item_id'];
        }

        //需要添加的商品id
        $add_goods = array_diff($input_goods_arr,$checked_goods_arr);

        //需要删除的商品id
        $del_goods = array_diff($checked_goods_arr,$input_goods_arr);

        if(count($del_goods)>0){
            foreach ($del_goods as $del_good){
                app::get('sysmaker')->model('seller_item')->delete(['item_id'=>$del_good,'seller_id'=>$targetId]);
            }
        }

        if(count($add_goods)>0){
            foreach ($add_goods as $add_good){
                $add_good_info = ['item_id' => $add_good, 'seller_id' => $targetId, 'deleted' => 0,'created_time'=>time()];
                app::get('sysmaker')->model('seller_item')->save($add_good_info);
            }
        }

        echo json_encode(['code'=>true]);


    }

    //删除创客已有的商品信息
    public function delGoods(){

        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        //获取参数信息
        $input = input::all();

        //获取了创客提交的选中的商品id
        $input_good_id = $input['item_id'];

        $result = app::get('sysmaker')->model('seller_item')->delete(['item_id'=>$input_good_id,'seller_id'=>$targetId]);
        if($result){
            echo json_encode(['code'=>true]);
        }else{
            echo json_encode(['code'=>false]);
        }

    }


}