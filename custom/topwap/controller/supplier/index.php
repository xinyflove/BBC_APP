<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-05-15
 * Time: 16:42
 */

use topwap_wechat_jssdk;
class topwap_ctl_supplier_index extends topwap_controller {

    //米粒儿首页获取用户经纬度并存入session
    public function setUserGPS(){
        kernel::single('base_session')->start();
        if(!empty($_SESSION['latitude']) && !empty($_SESSION['longitude'])){
            return json_encode(false);
        }
        //获取用户的经纬度
        $latitude = input::get('latitude', '');
        $longitude = input::get('longitude', '');

        $_SESSION['latitude'] = $latitude;
        $_SESSION['longitude'] = $longitude;

        return json_encode(1);
    }
    //米粒儿二期首页
    public function home()
    {

        //$pagedata['defaultImageId']= kernel::single('image_data_image')->getImageSetting('item');
        $input = input::get();
        $pagedata = array();
        //根据微信公众号填写
        //app::get('site')->setConf('site.appId','wxbc5ba7bb71af67fd');
        //app::get('site')->setConf('site.appSecret','2ecaaac5445c361708fa1e1c438563e0');
        /*$appId = app::get('site')->getConf('site.appSecret');
        var_dump($appId);exit;*/  //wx35563b28ef6ff55f   6c0d9e93caa1447ac5828263a16ed985
        //$jssdk = new topwap_ctl_jssdk();
        $wxAppId = app::get('site')->getConf('site.appId');
        $wxAppsecret = app::get('site')->getConf('site.appSecret');
        //$jssdk = new topwap_wechat_jssdk('wxbc5ba7bb71af67fd','2ecaaac5445c361708fa1e1c438563e0');
        $jssdk = new topwap_wechat_jssdk($wxAppId,$wxAppsecret);
        //var_dump($jssdk);exit;


        $pagedata['signPackage'] = $jssdk->GetSignPackage();
//var_dump($pagedata['signPackage']);exit;
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');
        kernel::single('base_session')->start();


        //获取当前店铺id
        $shop_id = input::get('shop_id', -1);

        $pagedata['shop_id'] = $shop_id;
        $fields = 'template_path,widget_type,params,widget_type';
        $filter = array(
            'page_type'=>'home',
            'disabled'=>'0',
            'deleted'=>'0',
            'shop_id'=>$shop_id
        );
        $order_sort = 'order_sort ASC, widget_id DESC';
        $widget_instance = app::get('syssupplier')->model('widget_instance')
            ->getList($fields, $filter, 0, -1, $order_sort);
        foreach ($widget_instance as $k => &$v)
        {
            $v['id'] = $v['widget_type'] .'_'. $k;
            $v['data'] = unserialize($v['params']);
            //$v['params'] = unserialize($v['params']);
            if($v['data']['item_id']){

                $v['item']=app::get('sysitem')->model('item')->getList('item_id,title,price,image_default_id',array('item_id'=>$v['data']['item_id']));
            }
            if($v['data']['agent_id']){
                $v['agents']=app::get('syssupplier')->model('agent_shop')->getList('agent_shop_id,name,agent_img_src,supplier_id,shop_id',array('agent_shop_id'=>$v['data']['agent_id']));
            }


            unset($v['params']);
        }
        /*//获取店铺logo
        $shopModel = app::get('sysshop')->model('shop');
        $shopInfo = $shopModel->getRow('shop_logo', ['shop_id'=>$shop_id]);
        $pagedata['shop_logo'] = base_storager::modifier($shopInfo['shop_logo']);*/

        $pagedata['widget_instance'] = $widget_instance;
        //获取首页搜索分类的标签
        $pagedata['tag_data'] = topwap_ctl_supplier_index::getAjaxHomeTagData();


        return view::make('topwap/supplier/home.html',$pagedata);
    }

    //米粒儿二期获取指定线下店数据接口
    public function getAjaxHomeData(){
        //获取线下店信息
        $page = input::get('pageNum', 1);
        $shop_id = input::get('shop_id', -1);
        $params = array();
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        //$params['shop_id'] = $shop_id;
        if($shop_id >0){
            $params['shop_id'] = $shop_id;
        }
        kernel::single('base_session')->start();
        //用户坐标
        if(isset($_SESSION['latitude']) && isset($_SESSION['longitude'])){
            if(!empty($_SESSION['longitude'])){
                $params['lon'] = $_SESSION['longitude'];
            }
            if(!empty($_SESSION['latitude'])){
                $params['lat'] = $_SESSION['latitude'];
            }
        }

        //pdType=0-0
        $pdType = input::get('pdType', '');
        //默认是按照置顶优先排序
        $params['order_by'][] = ' agent_shop_sum_up.top desc ';
        //处理首页需要获取的线下店列表信息
        if(!empty($pdType)){
            $pdType_sum_arr = explode(',',$pdType);
            if(count($pdType_sum_arr)>0){
                foreach($pdType_sum_arr as $p_s_a_value){
                    if(!empty($p_s_a_value)){
                        $pdType_arr = explode('-',$p_s_a_value);
                        if(count($pdType_arr)==2){
                            if($pdType_arr[0]=='0'){
                                //按照分类查询
                                if($pdType_arr[1]!='0'){
                                    //根据分类查询数据
                                    $params['agent_category_id'] = $pdType_arr[1];
                                }
                            }
                            /*'1-1'=>'500米内',
                '1-2'=>'1000米内',
                '1-3'=>'3000米内',
                '1-4'=>'5000米内',
                '1-5'=>'5000米外',*/
                            if($pdType_arr[0]=='1'){
                                //按照距离查询
                                if($pdType_arr[1]=='1'){
                                    //根据距离查询数据
                                    if(!empty($params['lon'])&&!empty($params['lat'])){
                                        $params['order_by'][] = 'distance ';
                                    }
                                }

                            }
                            /* '2-1'=>'人气优先',
                 '2-2'=>'人均从高到低',
                 '2-3'=>'人均从低到高'*/
                            if($pdType_arr[0]=='2'){
                                //按照综合查询
                                if($pdType_arr[1]=='1'){//人气优先
                                    $params['trade_offline_flag'] = true;
                                    $params['order_by'][] = 'agent_shop_sum_up.sotagentshopid desc';
                                }
                                if($pdType_arr[1]=='2'){//人均从低到高
                                    //$params['multiple_order'] = '2';
                                    $params['order_by'][] = 'agent_shop_sum_up.shop_consumption';
                                }
                                if($pdType_arr[1]=='3'){//人均从高到低
                                    $params['order_by'][] = 'agent_shop_sum_up.shop_consumption desc';
                                }
                            }
                            /* '3-1'=>'0-50',
                 '3-2'=>'50-100',
                 '3-3'=>'100-200',
                 '3-4'=>'200以上'*/
                            if($pdType_arr[0]=='3'){
                                //按照人均消费查询
                                if($pdType_arr[1]=='1'){//0-50
                                    $params['shop_consumption_where'] = '0-50';
                                }
                                if($pdType_arr[1]=='2'){//50-100
                                    $params['shop_consumption_where'] = '50-100';
                                }
                                if($pdType_arr[1]=='3'){//100-200
                                    $params['shop_consumption_where'] = '100-200';
                                }
                                if($pdType_arr[1]=='4'){//200以上
                                    $params['shop_consumption_where'] = '200-9999';
                                }
                            }
                        }
                    }
                }
            }
        }
        //默认是按照置顶优先排序,排序、创建时间排序
        $params['order_by'][] = ' agent_shop_sum_up.order_sort,agent_shop_sum_up.write_time desc';
        $params['fields'] = 'agent_shop_id,supplier_id,shop_id,name,type,district,disabled,deleted,agent_category_id,shop_rank,shop_consumption,shop_description,agent_img_src,addr_desc,carouse_image_list,top,order_sort,write_time';

        
        try {
            //supplier.shop.agent.list
            $agentShopData = app::get('topshop')->rpcCall('supplier.shop.agent.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach($agentShopData['data'] as &$value){
            //获取线下店的区县信息
            $value['district_info'] = '';
            if(!empty($value['district'])){
                $district_arr = explode('/',$value['district']);
                if(isset($district_arr[2])){
                    $value['district_info'] = app::get('topshop')->rpcCall('logistics.area',array('area'=>$district_arr[2]));
                }
            }
            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }
            //获取全场打折
            $all_hold_filter['disabled'] = 0;
            $all_hold_filter['agent_shop_id'] = $value['agent_shop_id'];
            $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.list', $all_hold_filter)['data'][0];
            $value['all_hold_info'] = $all_hold_info['activity_value'];


            if(isset($value['agentShopActivityData'])){
                if(count($value['agentShopActivityData'])>2){
                    //首页线下店列表中的线下店最多只显示2条信息
                    $value['agentShopActivityData'] = array_slice($value['agentShopActivityData'],0,2);
                }else{
                    continue;
                }
            }else{
                continue;
            }
        }
        return json_encode($agentShopData);

    }
    //获取首页的搜索分类标签
    public static function getAjaxHomeTagData(){
        $agent_cat = app::get('topshop')->rpcCall('supplier.agent.category.list');
        $agent_cat_arr = array();
        $agent_cat_arr['0-0'] = '全部美食';
        foreach($agent_cat['data'] as $cat_v){
            $id = '0-'.$cat_v['agent_category_id'];
            $agent_cat_arr[$id] = $cat_v['agent_category_name'];
        }
        $arr = array(array(
            'subtype'=>$agent_cat_arr
        ),array(
            'subtype'=>array(
                '1-0'=>'附近',
                '1-1'=>'按距离排序'
                /*'1-1'=>'500米内',
                '1-2'=>'1000米内',
                '1-3'=>'3000米内',
                '1-4'=>'5000米内',
                '1-5'=>'5000米外',*/
            )
        ),array(
            'subtype'=>array(
                '2-0'=>'智能排序',
                '2-1'=>'人气优先',
                '2-2'=>'人均从低到高',
                '2-3'=>'人均从高到低',
            )
        ),array(
            'subtype'=>array(
                '3-0'=>'人均消费',
                '3-1'=>'0-50',
                '3-2'=>'50-100',
                '3-3'=>'100-200',
                '3-4'=>'200以上'
            )
        ));
        return $arr;
    }
    //将距离进行格式化
    public function mToKm($number){
        if($number>=0 && $number <50){
            $v='很近';
        }elseif($number>=50 && $number<1000){
            $v=$number.'米';
        }elseif($number>=1000 && $number<100000){
            $subNumber = $number / 1000;
            $number = number_format($subNumber,1,".","");
            $v=$number.'千米';
        }else{
            $v='>100千米';
        }

        return $v;
    }

    //搜索框
    public function search(){
        $search_keywords = input::get('keyword', '请输入商户名、地点或菜名');
        $pagedata['keyword'] = $search_keywords;
        //获取当前店铺id
        $shop_id = input::get('shop_id', -1);
        $pagedata['shop_id'] = $shop_id;
        return view::make('topwap/supplier/search.html',$pagedata);
    }
    //米粒儿二期获取搜索框提交的关键字所对应的线下店数据接口
    public function getAjaxSearchData(){

        //获取搜索框的关键字信息
        $keyword = input::get('keyword', '');
        $page = input::get('curpage', 1);
        $shop_id = input::get('shop_id',-1);
        $params = array();
        //用户坐标
        if(isset($_SESSION['latitude']) && isset($_SESSION['longitude'])){
            if(!empty($_SESSION['longitude'])){
                $params['lon'] = $_SESSION['longitude'];
            }
            if(!empty($_SESSION['latitude'])){
                $params['lat'] = $_SESSION['latitude'];
            }
        }
        //模拟用户坐标
        /*$params['lon'] = '120.415219';
        $params['lat'] = '36.087816';*/
        if(!empty($params['lon'])&&!empty($params['lat'])){
            $params['order_by'][] = ' distance ';
        }
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        $params['keyword'] = $keyword;
        //$params['shop_id'] = $shop_id;
        if($shop_id >0){
            $params['shop_id'] = $shop_id;
        }
        $params['fields'] = 'agent_shop_id,supplier_id,shop_id,name,type,district,disabled,deleted,agent_category_id,shop_rank,shop_consumption,shop_description,agent_img_src,addr_desc,carouse_image_list';
        try {
            //supplier.shop.agent.list
            $agentShopData = app::get('topshop')->rpcCall('supplier.shop.agent.search.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach($agentShopData['data'] as &$value){
            //获取线下店的区县信息
            $value['district_info'] = '';
            if(!empty($value['district'])){
                $district_arr = explode('/',$value['district']);
                if(isset($district_arr[2])){
                    $value['district_info'] = app::get('topshop')->rpcCall('logistics.area',array('area'=>$district_arr[2]));
                }
            }
            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }

            //获取全场打折
            $all_hold_filter['disabled'] = 0;
            $all_hold_filter['agent_shop_id'] = $value['agent_shop_id'];
            $all_hold_info = app::get('topwap')->rpcCall('supplier.agent.activity.list', $all_hold_filter)['data'][0];
            $value['all_hold_info'] = $all_hold_info['activity_value'];
            if(isset($value['agentShopActivityData'])){
                if(count($value['agentShopActivityData'])>2){
                    //首页线下店列表中的线下店最多只显示2条信息
                    $value['agentShopActivityData'] = array_slice($value['agentShopActivityData'],0,2);
                }else{
                    continue;
                }
            }else{
                continue;
            }
        }

        return json_encode($agentShopData);


    }

    //线下店分类列表
    public function agentCatList(){
        $agentcat = input::get('agentcat', '');
        $shop_id = input::get('shop_id',-1);
        if($agentcat=='')
        {
            echo '参数错误,agentcat不为空';
            die;
        }
        //$pagedata['agentcat'] = $agentcat;
        $pagedata['agentcat'] = '0-'.$agentcat.',1-0,2-0,3-0';
        $pagedata['shop_id'] = $shop_id;
        //获取首页搜索分类的标签
        $pagedata['tag_data'] = topwap_ctl_supplier_index::getAjaxHomeTagData();
        return view::make('topwap/supplier/list.html',$pagedata);
    }
    //分类线下店数据接口
    // param:pdType
    //param:curpage
    public function getAjaxAgentCatData(){


        $page = input::get('curpage', 1);
        $shop_id = input::get('shop_id',-1);
        $params = array();
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        //$params['shop_id'] = $shop_id;
        if($shop_id >0){
            $params['shop_id'] = $shop_id;
        }
        kernel::single('base_session')->start();
        //用户坐标
        if(isset($_SESSION['latitude']) && isset($_SESSION['longitude'])){
            if(!empty($_SESSION['longitude'])){
                $params['lon'] = $_SESSION['longitude'];
            }
            if(!empty($_SESSION['latitude'])){
                $params['lat'] = $_SESSION['latitude'];
            }
        }

        //pdType=0-0
        $pdType = input::get('pdType', '');
        //默认是按照置顶优先排序
        $params['order_by'][] = ' agent_shop_sum_up.top desc ';
        //处理首页需要获取的线下店列表信息
        if(!empty($pdType)){
            $pdType_sum_arr = explode(',',$pdType);
            if(count($pdType_sum_arr)>0){
                foreach($pdType_sum_arr as $p_s_a_value){
                    if(!empty($p_s_a_value)){
                        $pdType_arr = explode('-',$p_s_a_value);
                        if(count($pdType_arr)==2){
                            if($pdType_arr[0]=='0'){
                                //按照分类查询
                                if($pdType_arr[1]!='0'){
                                    //根据分类查询数据
                                    $params['agent_category_id'] = $pdType_arr[1];
                                }
                            }

                            if($pdType_arr[0]=='1'){
                                //按照距离查询
                                if($pdType_arr[1]=='1'){
                                    //根据距离查询数据
                                    if(!empty($params['lon'])&&!empty($params['lat'])){
                                        $params['order_by'][] = 'distance ';
                                    }
                                }

                            }

                            if($pdType_arr[0]=='2'){
                                //按照综合查询
                                if($pdType_arr[1]=='1'){//人气优先
                                    $params['trade_offline_flag'] = true;
                                    $params['order_by'][] = 'agent_shop_sum_up.sotagentshopid desc';
                                }
                                if($pdType_arr[1]=='2'){//人均从低到高
                                    //$params['multiple_order'] = '2';
                                    $params['order_by'][] = 'agent_shop_sum_up.shop_consumption';
                                }
                                if($pdType_arr[1]=='3'){//人均从高到低
                                    $params['order_by'][] = 'agent_shop_sum_up.shop_consumption desc';
                                }
                            }
                            if($pdType_arr[0]=='3'){
                                //按照人均消费查询
                                if($pdType_arr[1]=='1'){//0-50
                                    $params['shop_consumption_where'] = '0-50';
                                }
                                if($pdType_arr[1]=='2'){//50-100
                                    $params['shop_consumption_where'] = '50-100';
                                }
                                if($pdType_arr[1]=='3'){//100-200
                                    $params['shop_consumption_where'] = '100-200';
                                }
                                if($pdType_arr[1]=='4'){//200以上
                                    $params['shop_consumption_where'] = '200-9999';
                                }
                            }
                        }
                    }
                }
            }
        }
        //默认是按照置顶优先排序,排序、创建时间排序
        $params['order_by'][] = ' agent_shop_sum_up.order_sort,agent_shop_sum_up.write_time desc';
        $params['fields'] = 'agent_shop_id,supplier_id,shop_id,name,type,district,disabled,deleted,agent_category_id,shop_rank,shop_consumption,shop_description,agent_img_src,addr_desc,carouse_image_list,top,order_sort,write_time';
        try {
            //supplier.shop.agent.list
            $agentShopData = app::get('topshop')->rpcCall('supplier.shop.agent.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach($agentShopData['data'] as &$value){
            //获取线下店的区县信息
            $value['district_info'] = '';
            if(!empty($value['district'])){
                $district_arr = explode('/',$value['district']);
                if(isset($district_arr[2])){
                    $value['district_info'] = app::get('topshop')->rpcCall('logistics.area',array('area'=>$district_arr[2]));
                }
            }
            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }
            if(isset($value['agentShopActivityData'])){
                if(count($value['agentShopActivityData'])>2){
                    //首页线下店列表中的线下店最多只显示2条信息
                    $value['agentShopActivityData'] = array_slice($value['agentShopActivityData'],0,2);
                }else{
                    continue;
                }
            }else{
                continue;
            }
        }
        return json_encode($agentShopData);
    }
}