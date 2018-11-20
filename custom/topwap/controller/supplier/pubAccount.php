<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-06-04
 * Time: 14:51
 */
class topwap_ctl_supplier_pubAccount extends topwap_controller {
    //订阅号列表
    public function index(){
        //获取当前店铺id
        $shop_id = input::get('shop_id', -1);

        $pagedata['shop_id'] = $shop_id;
        return view::make('topwap/supplier/pub_account.html',$pagedata);
    }

    //获取订阅号信息
    public function getAjaxData(){
        $page = input::get('curpage', 1);
        $shop_id = input::get('shop_id',-1);
        $params = array();
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        if($shop_id >0){
            $params['shop_id'] = $shop_id;
        }

        $params['fields'] = 'public_account_id,public_account_name,image_url,modified_time,description,url';
        try {
            //supplier.shop.agent.list
            $agentShopData = app::get('topshop')->rpcCall('shop.supplier.publicaccount.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach($agentShopData['data'] as &$value){
            if(isset($value['image_url'])){
                $value['image_url'] = base_storager::modifier($value['image_url']);
            }
            if(isset($value['modified_time'])){
                $value['modified_time'] = date('Y年m月d日',$value['modified_time']);
            }
        }
        return json_encode($agentShopData);
    }
}