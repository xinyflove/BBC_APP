<?php
class topwap_ctl_miniprogram_goods extends topwap_controller
{

    //小程序首页获取用户经纬度并存入session
    public function setUserGPS(){
        //kernel::single('base_session')->start();

        /*if(!empty($_SESSION['mini_latitude']) && !empty($_SESSION['mini_longitude'])){
            return response::json([
                'err_no'=>1001,
                'data'=>[
                    'mini_latitude'=>$_SESSION['mini_latitude'],
                    'mini_longitude'=>$_SESSION['mini_longitude'],
                ],
                'message'=>'用户经纬度已经设置'
            ]);
        }*/
        //获取用户的经纬度
        $latitude = input::get('mini_latitude', '');
        $longitude = input::get('mini_longitude', '');
        if(empty($latitude) || empty($longitude)){
            return response::json([
                'err_no'=>1001,
                'data'=>[
                ],
                'message'=>'用户经纬度不能为空'
            ]);
        }
        $_SESSION['mini_latitude'] = $latitude;
        $_SESSION['mini_longitude'] = $longitude;

        return response::json([
            'err_no'=>0,
            'data'=>[
                'mini_latitude'=>$_SESSION['mini_latitude'],
                'mini_longitude'=>$_SESSION['mini_longitude'],
            ],
            'message'=>'用户经纬度设置成功'
        ]);
    }

    /**
     * lon  经度
     * lat 纬度
     * search_keyword 搜索关键字
     * pageNum 页码
     * good_status   商品列表状态：最新（new）、附近（nearby）、最热（hot）
     * 获取小程序首页的商品列表
     */

    public function goodsList(){

        //商品列表状态：最新、附近、最热
        $good_status = input::get('good_status', 'new');
        $page = input::get('pageNum', 1);

        $params = array();
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        $params['lon'] = input::get('lon', '12');
        $params['lat'] = input::get('lat', '12');
        $params['search_keyword'] = input::get('search_keyword', '');
        if(empty($params['lon']) || empty($params['lat'])){
            return response::json([
                'err_no'=>1001,
                'data'=>[
                    'mini_latitude'=>$params['lat'],
                    'mini_longitude'=>$params['lon'],
                ],
                'message'=>'用户经纬度不能为空'
            ]);
        }

        //处理首页需要获取的线下店列表信息
        if(!empty($good_status)){
            //获取最新商品列表
            if($good_status == 'new'){
                //$params['order_by'][] = 'smp.mini_program_good_id desc ';
                $params['order_by'][] = 'smp.order_sort desc,smp.modified_time desc,smp.mini_program_good_id desc';
            }
            //获取附近商品列表
            if($good_status == 'nearby'){
                //根据距离查询数据
                if(!empty($params['lon'])&&!empty($params['lat'])){
                    $params['order_by'][] = 'distance ';
                }
            }
            //获取最热商品列表
            if($good_status == 'hot'){
                $params['order_by'][] = 'smp.order_sort desc ';
            }
        }
        $params['fields'] = 'type,good_name,goods_id,image_url,good_tags';
        try {
            //supplier.shop.agent.list
            $goodsData = app::get('topshop')->rpcCall('supplier.miniprogram.goods.api.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach($goodsData['data'] as &$value){

            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }
            if($value['type']==1){
                //活动&折扣
                //syssupplier_agent_activity
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getList('activity_value', ['agent_activity_id'=>$value['goods_id']]);

                $value['data']['desc'] = $activityInfo[0]['activity_value'].'折';  //描述
                $value['data']['count'] = 0;  //销量
                $value['data']['store'] = 0; //库存
                $value['data']['price'] = 0;

            }
            if($value['type']==2){
                //限量购券
                //sysitem_item_store 库存    sysitem_item_count 销量
                $storeModel = app::get('sysitem')->model('item_store');
                $storeInfo = $storeModel->getRow('store', ['item_id'=>$value['goods_id']]);

                $realstoreInfo = kernel::single('sysitem_item_redisStore')->getItemStore(array($value['goods_id']));
                $value['data']['store'] = $realstoreInfo[$value['goods_id']]['realStore']?$realstoreInfo[$value['goods_id']]['realStore']:0; //库存
                $countModel = app::get('sysitem')->model('item_count');
                $countInfo = $countModel->getRow('paid_quantity', ['item_id'=>$value['goods_id']]);
                $value['data']['count'] = $countInfo['paid_quantity'];  //销量

                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('sub_title,price', ['item_id'=>$value['goods_id']]);
                $value['data']['price'] = number_format($itemInfo['price'], 2);
                $value['data']['desc'] = mb_substr($itemInfo['sub_title'],0,5, 'utf-8');  //描述
            }
        }
        return json_encode($goodsData);
    }


    /**
     * 推荐
     * num 推荐商品的数量
     * lon  经度
     * lat 纬度
     * 支付成功&失败之后的推荐商品列表
     */
    public function recommendList(){

        $params = array();
        $params['page_no'] = 1;
        $params['page_size'] = input::get('num', 3);
        $params['lon'] = input::get('lon', '');
        $params['lat'] = input::get('lat', '');
        if(empty($params['lon']) || empty($params['lat'])){
            return response::json([
                'err_no'=>1001,
                'data'=>[

                ],
                'message'=>'参数错误'
            ]);
        }
        //获取最热商品列表,推荐商品按照order_sort 排序
        //$params['order_by'][] = 'smp.mini_program_good_id desc ';
        $params['order_by'][] = 'smp.order_sort desc,smp.modified_time desc,smp.mini_program_good_id desc';
        $params['fields'] = 'type,good_name,goods_id,image_url,good_tags';
        try {
            //supplier.shop.agent.list
            $goodsData = app::get('topshop')->rpcCall('supplier.miniprogram.goods.api.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach($goodsData['data'] as &$value){

            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }
            if($value['type']==1){
                //活动&折扣
                //syssupplier_agent_activity
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getList('activity_value', ['agent_activity_id'=>$value['goods_id']]);

                $value['data']['desc'] = $activityInfo[0]['activity_value'].'折';  //描述
                $value['data']['count'] = 0;  //销量
                $value['data']['store'] = 0; //库存
                $value['data']['price'] = 0;

            }
            if($value['type']==2){
                //限量购券
                //sysitem_item_store 库存    sysitem_item_count 销量
                $storeModel = app::get('sysitem')->model('item_store');
                $storeInfo = $storeModel->getRow('store', ['item_id'=>$value['goods_id']]);
                //$value['data']['store'] = $storeInfo['store']?$storeInfo['store']:0; //库存
                $realstoreInfo = kernel::single('sysitem_item_redisStore')->getItemStore(array($value['goods_id']));
                $value['data']['store'] = $realstoreInfo[$value['goods_id']]['realStore']?$realstoreInfo[$value['goods_id']]['realStore']:0; //库存
                $countModel = app::get('sysitem')->model('item_count');
                $countInfo = $countModel->getRow('paid_quantity', ['item_id'=>$value['goods_id']]);
                $value['data']['count'] = $countInfo['paid_quantity'];  //销量

                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('sub_title,price', ['item_id'=>$value['goods_id']]);
                $value['data']['price'] = number_format($itemInfo['price'], 2);
                $value['data']['desc'] = mb_substr($itemInfo['sub_title'],0,5, 'utf-8');  //描述
            }
        }
        return json_encode($goodsData['data']);

    }

    /**
     * lon  经度
     * lat 纬度
     * 关键字搜索获取小程序商品列表
     */
    /*public function searchList(){
        //商品列表状态：最新、附近、最热
        $good_status = input::get('good_status', 'new');
        $page = input::get('pageNum', 1);

        $params = array();
        $params['page_no'] = $page;
        $params['page_size'] = 10;
        $params['lon'] = input::get('lon', '');
        $params['lat'] = input::get('lat', '');
        $params['search_keyword'] = input::get('search_keyword', '');
        if(empty($params['lon']) || empty($params['lat']) || empty($params['search_keyword'])){
            return response::json([
                'err_no'=>1001,
                'data'=>[

                ],
                'message'=>'参数错误'
            ]);
        }

        //处理首页需要获取的线下店列表信息
        if(!empty($good_status)){
            //获取最新商品列表
            if($good_status == 'new'){
                $params['order_by'][] = 'smp.order_sort desc,smp.modified_time asc ';
            }

        }

        $params['fields'] = 'type,good_name,goods_id,image_url,good_tags';
        try {
            //supplier.shop.agent.list
            $goodsData = app::get('topshop')->rpcCall('supplier.miniprogram.goods.api.list', $params);
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        foreach($goodsData['data'] as &$value){

            if(isset($value['distance'])){
                $distance = (int)$value['distance'];
                $value['distance'] = $this->mToKm($distance);
            }
            if(isset($value['agent_img_src'])){
                $value['agent_img_src'] = base_storager::modifier($value['agent_img_src']);
            }
            if($value['type']==1){
                //活动&折扣
                //syssupplier_agent_activity
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getList('activity_value', ['agent_activity_id'=>$value['goods_id']]);

                $value['data']['desc'] = $activityInfo[0]['activity_value'].'折';  //描述
                $value['data']['count'] = 0;  //销量
                $value['data']['store'] = 0; //库存

            }
            if($value['type']==2){
                //限量购券
                //sysitem_item_store 库存    sysitem_item_count 销量
                $storeModel = app::get('sysitem')->model('item_store');
                $storeInfo = $storeModel->getRow('store', ['item_id'=>$value['goods_id']]);
                $value['data']['store'] = $storeInfo['store']?$storeInfo['store']:0; //库存

                $countModel = app::get('sysitem')->model('item_count');
                $countInfo = $countModel->getRow('paid_quantity', ['item_id'=>$value['goods_id']]);
                $value['data']['count'] = $countInfo['paid_quantity'];  //销量

                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('sub_title', ['item_id'=>$value['goods_id']]);
                $value['data']['desc'] = mb_substr($itemInfo['sub_title'],0,5, 'utf-8');  //描述
            }
        }

        return json_encode($goodsData);
    }*/
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
            $v='很远';
        }

        return $v;
    }


    //获取小程序首页banner列表信息
    public function bannerList(){

        $post_data = input::get();
        $page_data = array();
        if($post_data['shop_id']){
            $bannerModel = app::get('syssupplier')->model('mini_banner');
            $filter['shop_id'] = $post_data['shop_id'];
            $page_data = $bannerModel->getRow('banner_desc',$filter);
            if(isset($page_data['banner_desc'])){
                $page_data['banner_desc'] = json_decode($page_data['banner_desc']);
                $page_data['code'] = '200';
            }else{
                $page_data['code'] = '400';
            }
        }else{
            $page_data['code'] = '400';
        }
        return json_encode($page_data);

    }





}