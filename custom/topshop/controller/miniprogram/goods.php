<?php
/**
 * Created by PhpStorm.
 * User: jiangyunhan
 * Date: 2018-07-25
 * Time: 11:16
 */

class topshop_ctl_miniprogram_goods extends topshop_controller {

    //米粒小程序商品列表
    public function index()
    {

        $page = input::get('page', 1);
        $this->contentHeaderTitle = app::get('topshop')->_('小程序商品列表');

        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_miniprogram_goods@index'),
                'title' => app::get('topshop')->_('小程序商品管理')
            ],
        );
        $pagedata = array();
        $params = array();
        $params['page_no'] = $page;


        try {
            $miniprogram_goods = app::get('topshop')->rpcCall('supplier.miniprogram.goods.list', $params);;
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach($miniprogram_goods['data'] as &$miniprogram_good){

            //1是折扣，2是限量购券
            /*if($miniprogram_good['type']=='1'){

                $miniprogram_good['typeinfo'] = '折扣&活动';
            }
            if($miniprogram_good['type']=='2'){

                $miniprogram_good['typeinfo'] = '限量购券';
            }*/



            if($miniprogram_good['type']=='1'){
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getRow('activity_name,agent_shop_id', ['agent_activity_id'=>$miniprogram_good['goods_id']]);

                $miniprogram_good['selectdGoodInfo'] =  $activityInfo['activity_name'];
                $agent_name_info = app::get('topshop')->rpcCall('supplier.agent.shop.get',['agent_shop_id'=>$activityInfo['agent_shop_id']]);
                $miniprogram_good['shop_name'] = $agent_name_info['name'];
                $miniprogram_good['typeinfo'] = '折扣&活动';

            }
            if($miniprogram_good['type']=='2'){
                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('title,shop_id', ['item_id'=>$miniprogram_good['goods_id']]);

                $shopModel = app::get('sysshop')->model('shop');
                $shopInfo = $shopModel->getRow('shop_name', ['shop_id'=>$itemInfo['shop_id']]);
                $miniprogram_good['shop_name'] = $shopInfo['shop_name'];
                $miniprogram_good['selectdGoodInfo'] =  $itemInfo['title'];
                $miniprogram_good['typeinfo'] = '限量购券';

            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_miniprogram_goods@index', ['page' => time()]),
            'current' => $miniprogram_goods['current_page'],
            'use_app' => 'topshop',
            'total' => $miniprogram_goods['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $miniprogram_goods['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $miniprogram_goods;

        return $this->page('topshop/miniprogram/goods.html',$pagedata);
    }

    /**
     * 小程序商品搜索
     */
    public function goodSearch()
    {
        $page = input::get('page', 1);
        $name = input::get('good_name');
        $name = trim($name);
        $this->contentHeaderTitle = app::get('topshop')->_('小程序商品列表');
        // 面包屑
        $this->runtimePath = array(
            [
                'url' => url::action('topshop_ctl_index@index'),
                'title' => app::get('topshop')->_('首页')
            ],
            [
                'url' => url::action('topshop_ctl_miniprogram_goods@index'),
                'title' => app::get('topshop')->_('小程序商品管理')
            ],
        );
        $pagedata = array();

        $params = array();

        $params['page_no'] = $page;
        $params['filter'] = [
            'good_name|has' => $name
        ];
        try {
            $miniprogram_goods = app::get('topshop')->rpcCall('supplier.miniprogram.goods.list', $params);;
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }

        foreach($miniprogram_goods['data'] as &$miniprogram_good){

            //1是折扣，2是限量购券
            if($miniprogram_good['type']=='1'){
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getRow('activity_name', ['agent_activity_id'=>$miniprogram_good['goods_id']]);
                $miniprogram_good['selectdGoodInfo'] =  $activityInfo['activity_name'];
                $miniprogram_good['typeinfo'] = '折扣&活动';
            }
            if($miniprogram_good['type']=='2'){
                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('title,image_default_id', ['item_id'=>$miniprogram_good['goods_id']]);
                $miniprogram_good['selectdGoodInfo'] =  $itemInfo['title'];


                $miniprogram_good['typeinfo'] = '限量购券';
            }
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_miniprogram_goods@index', ['page' => time()]),
            'current' => $miniprogram_goods['current_page'],
            'use_app' => 'topshop',
            'total' => $miniprogram_goods['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $miniprogram_goods['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $miniprogram_goods;
        $pagedata['search_keywords']['name'] = $name;
        return $this->page('topshop/miniprogram/goods.html', $pagedata);
    }

    /**
     * 编辑排序
     */
    public function order_sort()
    {
        try{
            $input = input::get();
            if(!$input['pk'])
            {
                throw new \LogicException('主键id不能为空');
            }
            if(!$input['value'])
            {
                throw new \LogicException('排序值不能为空');
            }
            $model = app::get('syssupplier')->model('mini_program');
            $update_res = $model->update(['order_sort'=>$input['value']],['mini_program_good_id'=>$input['pk']]);
            if(!is_integer($update_res))
            {
                throw new \RuntimeException('更新排序失败');
            }
            return $this->splash('success',null,'排序成功！',true);
        }catch (\Exception $exception)
        {
            return $this->splash('error',null,$exception->getMessage(),true);
        }
    }

    //设置商品的显示状态
    public function setDisabled()
    {
        $input = input::get();
        if(!empty($input['good_id']) && isset($input['disabled']))
        {
            $miniMdl = app::get('syssupplier')->model('mini_program');
            $miniMdl->update(array('disabled'=>$input['disabled']), array('mini_program_good_id'=>$input['good_id']));
            $msg = '操作成功';
            return $this->splash('success', null, $msg, true);
        }

        $msg = '操作失败';
        return $this->splash('error', null, $msg, true);
    }
    public function edit()
    {

        $this->contentHeaderTitle = app::get('topshop')->_('添加小程序商品');
        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_miniprogram_goods@index'),'title' => app::get('topshop')->_('小程序商品管理')],
            ['title' => app::get('topshop')->_('添加小程序商品')],
        );

        if( input::get('mini_program_good_id') )
        {
            $params['mini_program_good_id'] = input::get('mini_program_good_id');

            $data = app::get('topshop')->rpcCall('supplier.miniprogram.goods.list',$params);

            if( $data )
            {
                $pagedata['data'] = $data['data'][0];
            }
        }
        //1是折扣，2是限量购券
        if(input::get('type')==1){
            //获取到关联商品的名称
            if( $data['data'][0]['goods_id'] ){
                $activityModel = app::get('syssupplier')->model('agent_activity');
                $activityInfo = $activityModel->getRow('activity_name,agent_shop_id', ['agent_activity_id'=>$data['data'][0]['goods_id']]);
                $pagedata['selectdGoodInfo'] =  $activityInfo['activity_name'];

                $agent_name_info = app::get('topshop')->rpcCall('supplier.agent.shop.get',['agent_shop_id'=>$activityInfo['agent_shop_id']]);
                $pagedata['agent_shop_name'] = $agent_name_info['name'];

                $pagedata['selectdGoodId'] =  $data['data'][0]['goods_id'];
            }
            return $this->page('topshop/miniprogram/editactivity.html', $pagedata);
        }elseif (input::get('type')==2){
            //获取到关联商品的名称
            if( $data['data'][0]['goods_id'] ){
                $itemModel = app::get('sysitem')->model('item');
                $itemInfo = $itemModel->getRow('title,image_default_id', ['item_id'=>$data['data'][0]['goods_id']]);
                $pagedata['selectdGoodInfo'] =  $itemInfo['title'];
                $pagedata['selectdGoodId'] =  $data['data'][0]['goods_id'];
                $pagedata['image_default_id'] =  base_storager::modifier($itemInfo['image_default_id']);
            }
            return $this->page('topshop/miniprogram/editcoupon.html', $pagedata);
        }else{
            $msg = '商品类型错误';
            $url = url::action('topshop_ctl_miniprogram_goods@index');
            return $this->splash('success',$url,$msg,true);
        }

    }

    public function save()
    {

        try
        {
            if( input::get('mini_program_good_id') )
            {
                //修改
                $params = input::get();
                //1是折扣，2是限量购券
                if($params['type']==1){
                    if(count($params['params']['agent_id']) != 1){
                        if(count($params['params']['agent_id']) > 1){
                            $msg = '只能选取一个商品';
                            return $this->splash('error','',$msg,true);
                        }
                        if(count($params['params']['agent_id']) == 0){
                            $params['modified_time'] = time();
                            //$params['goods_id'] = $params['item_id'][0];
                            $params['mini_program_good_id'] = input::get('mini_program_good_id');
                            app::get('topshop')->rpcCall('supplier.miniprogram.goods.update',$params);
                            $msg = '修改小程序商品信息成功';
                        }
                    }else{
                        $params['modified_time'] = time();
                        $params['goods_id'] = $params['params']['agent_id'][0];
                        $param = array();
                        //根据活动的id获取活动对应的线下店id
                        $param['agent_activity_id'] = $params['goods_id'];
                        $activity = app::get('topshop')->rpcCall('supplier.shop.agent.activity.list',$param);
                        //根据线下店id获取线下店的经纬度
                        if(isset($activity['data'][0]['agent_shop_id'])){
                            $agentModel = app::get('syssupplier')->model('agent_shop');
                            $agentInfo = $agentModel->getList('lat,lon', ['agent_shop_id'=>$activity['data'][0]['agent_shop_id']]);

                            if(isset($agentInfo[0]['lat'])){
                                $lat = $agentInfo[0]['lat'];
                            }
                            if(isset($agentInfo[0]['lon'])){
                                $lon = $agentInfo[0]['lon'];
                            }
                        }
                        $params['lat'] = isset($lat)?$lat:0;
                        $params['lon'] = isset($lon)?$lon:0;

                        $params['mini_program_good_id'] = input::get('mini_program_good_id');
                        app::get('topshop')->rpcCall('supplier.miniprogram.goods.update',$params);
                        $msg = '修改小程序商品信息成功';
                    }
                }
                if($params['type']==2){
                    if(count($params['item_id']) != 1){
                        if(count($params['item_id']) > 1){
                            $msg = '只能选取一个商品';
                            return $this->splash('error','',$msg,true);
                        }
                        if(count($params['item_id']) == 0){
                            $params['modified_time'] = time();
                            //$params['goods_id'] = $params['item_id'][0];
                            $params['type'] = $params['type'];
                            $params['mini_program_good_id'] = input::get('mini_program_good_id');
                            app::get('topshop')->rpcCall('supplier.miniprogram.goods.update',$params);
                            $msg = '修改小程序商品信息成功';
                        }
                    }else{
                        $params['modified_time'] = time();
                        $params['goods_id'] = $params['item_id'][0];
                        //说明商品有变化
                        //根据卡券获取供应商id
                        $itemModel = app::get('sysitem')->model('item');
                        $itemInfo = $itemModel->getList('supplier_id', ['item_id'=>$params['goods_id']]);
                        if(isset($itemInfo[0]['supplier_id'])){
                            //根据供货商的id获取经纬度
                            $supplierModel = app::get('sysshop')->model('supplier');
                            $supplierInfo = $supplierModel->getList('lat,lon', ['supplier_id'=>$itemInfo[0]['supplier_id']]);

                            if(isset($supplierInfo[0]['lat'])){
                                $lat = $supplierInfo[0]['lat'];
                            }
                            if(isset($supplierInfo[0]['lon'])){
                                $lon = $supplierInfo[0]['lon'];
                            }
                        }
                        $params['lat'] = isset($lat)?$lat:0;
                        $params['lon'] = isset($lon)?$lon:0;
                        $params['type'] = $params['type'];
                        $params['mini_program_good_id'] = input::get('mini_program_good_id');
                        app::get('topshop')->rpcCall('supplier.miniprogram.goods.update',$params);
                        $msg = '修改小程序商品信息成功';
                    }
                }



            }
            else
            {
                //新增
                $params = input::get();
                if($params['type']==1){
                    if(count($params['params']['agent_id']) != 1){
                        $msg = '只能选取一个商品';
                        return $this->splash('error','',$msg,true);
                    }else{
                        $params['write_time'] = time();
                        $params['modified_time'] = time();
                        $params['goods_id'] = $params['params']['agent_id'][0];
                        //echo $params['goods_id'];exit;
                        $param = array();
                        //根据活动的id获取活动对应的线下店id
                        $param['agent_activity_id'] = $params['goods_id'];
                        $activity = app::get('topshop')->rpcCall('supplier.shop.agent.activity.list',$param);
                        //var_dump($activity['data'][0]['agent_shop_id']);exit;
                        //$agent_shop_id = $activity['data'][0]['agent_shop_id'];
                        //根据线下店id获取线下店的经纬度
                        if(isset($activity['data'][0]['agent_shop_id'])){
                            $agentModel = app::get('syssupplier')->model('agent_shop');
                            $agentInfo = $agentModel->getList('lat,lon', ['agent_shop_id'=>$activity['data'][0]['agent_shop_id']]);

                            if(isset($agentInfo[0]['lat'])){
                                $lat = $agentInfo[0]['lat'];
                            }
                            if(isset($agentInfo[0]['lon'])){
                                $lon = $agentInfo[0]['lon'];
                            }
                        }
                        $params['lat'] = isset($lat)?$lat:0;
                        $params['lon'] = isset($lon)?$lon:0;

                        app::get('topshop')->rpcCall('supplier.miniprogram.goods.add',$params);
                        $msg = '添加小程序商品信息成功';
                    }
                }
                if($params['type']==2){
                    if(count($params['item_id']) != 1){
                        $msg = '只能选取一个商品';
                        return $this->splash('error','',$msg,true);
                    }else{
                        $params['write_time'] = time();
                        $params['modified_time'] = time();
                        $params['goods_id'] = $params['item_id'][0];
                        //根据卡券获取供应商id
                        $itemModel = app::get('sysitem')->model('item');
                        $itemInfo = $itemModel->getList('supplier_id', ['item_id'=>$params['goods_id']]);
                        if(isset($itemInfo[0]['supplier_id'])){
                            //根据供货商的id获取经纬度
                            $supplierModel = app::get('sysshop')->model('supplier');
                            $supplierInfo = $supplierModel->getList('lat,lon', ['supplier_id'=>$itemInfo[0]['supplier_id']]);

                            if(isset($supplierInfo[0]['lat'])){
                                $lat = $supplierInfo[0]['lat'];
                            }
                            if(isset($supplierInfo[0]['lon'])){
                                $lon = $supplierInfo[0]['lon'];
                            }
                        }
                        $params['lat'] = isset($lat)?$lat:0;
                        $params['lon'] = isset($lon)?$lon:0;

                        app::get('topshop')->rpcCall('supplier.miniprogram.goods.add',$params);
                        $msg = '添加小程序商品信息成功';
                    }
                }



            }
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $url = url::action('topshop_ctl_miniprogram_goods@index');
        return $this->splash('success',$url,$msg,true);
    }

    public function delete()
    {
        $miniId = input::get('mini_program_good_id', false);
        if( !$miniId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }
        try
        {
            $params['mini_program_good_id'] = $miniId;
            app::get('topshop')->rpcCall('supplier.miniprogram.goods.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $msg = '删除成功';
        $url = url::action('topshop_ctl_miniprogram_goods@index');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * @return html
     * 退款说明
     */
    public function noRefunds()
    {
        $post_data = input::get();
        unset($post_data['s']);
        $noRefundModel = app::get('syssupplier')->model('mini_explain');
        if(!empty($post_data))
        {
            if($post_data['desc_id'])
            {
                $data['refund_desc'] = $post_data['refund_content'];
                $noRefundModel->update($data, ['shop_id'=>$this->shopId]);
            }
            else
            {
                $data['refund_desc'] = $post_data['refund_content'];
                $data['shop_id'] = $this->shopId;
                $noRefundModel->insert($data);
            }
        }
        $filter['shop_id'] = $this->shopId;
        $page_data['desc_info'] = $noRefundModel->getRow('*',$filter);
        return $this->page('topshop/miniprogram/noRefunds.html', $page_data);
    }


    //首页banner设置
    public function indexBanner(){
        $post_data = input::get();

        $bannerModel = app::get('syssupplier')->model('mini_banner');
        if(!empty($post_data))
        {
            $merge_arr = array();
            foreach ($post_data['banner_link'] as $key=>$banner_link){
                $merge_arr[$key]['banner_link'] = $banner_link;
                $merge_arr[$key]['banner_pic'] = $post_data['banner_pic'][$key];
            }
            if($post_data['id'])
            {
                $data['banner_desc'] = json_encode($merge_arr);
                $bannerModel->update($data, ['shop_id'=>$this->shopId]);
            }
            else
            {
                $data['banner_desc'] = json_encode($merge_arr);
                $data['shop_id'] = $this->shopId;
                $bannerModel->insert($data);
            }



            $msg = '保存成功';
            $url = url::action('topshop_ctl_miniprogram_goods@indexBanner');
            return $this->splash('success',$url,$msg,true);
        }
        $filter['shop_id'] = $this->shopId;
        $page_data = $bannerModel->getRow('*',$filter);
        //dd($page_data);//array(3) { ["id"]=> int(1) ["shop_id"]=> int(7) ["banner_desc"]=> string(7) "sdsdddd" }
        $page_data['banner_desc'] = json_decode($page_data['banner_desc']);

        $this->contentHeaderTitle = app::get('topshop')->_('小程序banner设置');

        return $this->page('topshop/miniprogram/banner_setting.html', $page_data);
    }


}