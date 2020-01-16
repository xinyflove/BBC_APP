<?php

/**
 * @brief 商家商品管理
 */
class topshop_ctl_item extends topshop_controller {

    public $limit = 10;
    public $exportLimit = 100;


    public function add()
    {
        $pagedata['is_lm'] = $this->isLm;
        $shopInfo=$this->shopInfo;
        $pagedata['accounting_type']=$shopInfo['accounting_type'];
        $pagedata['offline']=$shopInfo['offline'];
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',array('shop_id'=>$this->shopId,'fields'=>'cat_id,cat_name,is_leaf,parent_id,level'));
        $pagedata['shopId'] = $this->shopId;

        // 获取物流模板列表
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpls'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $pagedata['dlytmpls']['data'] = array_bind_key($pagedata['dlytmpls']['data'],'template_id');
        /*start whc_商铺获取银行卡信息_2017/9/7*/
        $pagedata['banks']=$this->getBanks();
        /*end*/
        /*add_20170913_by_wudi_start*/
        $supplierparams['shop_id'] = $this->shopId;
        $supplierparams['is_audit'] = 'PASS';
        $pagedata['supplier'] = app::get('topshop')->rpcCall('supplier.shop.list',$supplierparams);   //获取供应商信息
        $taxRateCfg=config::get('tax');
        $pagedata['taxrate']=$taxRateCfg;
        /*add_20170913_by_wudi_end*/
        /*add_2017/9/23_by_wanghaichao_start 获取供应商信息*/
        $params['shop_id'] = $this->shopId;
        $params['is_audit'] = 'PASS';
        $pagedata['supplier'] = app::get('topshop')->rpcCall('supplier.shop.list',$params);   //获取供应商信息

        /*add_2017/9/23_by_wanghaichao_end*/
        $this->contentHeaderTitle = app::get('topshop')->_('添加商品');
		$pagedata['count']=1;
        $is_hm_supplier = $hm_supplier_id = $this->checkHuiminSupplierLogin();
        $hm_hidden_class = '';
        if($is_hm_supplier) {
            $hm_hidden_class = 'hidden';
            $hm_supplier_info = app::get('sysshop')->model('supplier')->getRow('supplier_name, supplier_id',['supplier_id'=>$hm_supplier_id,'is_audit'=>'PASS']);
            $pagedata['item']['supplier_name'] = $hm_supplier_info['supplier_name'];
            $pagedata['item']['supplier_id'] = $hm_supplier_info['supplier_id'];
        }
		$pagedata['hm_hidden_class']=$hm_hidden_class;
		$pagedata['is_hm_shop']=$this->isHmShop;
        $pagedata['is_hm_supplier'] = $is_hm_supplier;
        return $this->page('topshop/item/edit.html', $pagedata);
    }

    /**
     * ajax动态加载物流模板
     */
    public function ajaxTmpl(){
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $data = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        exit(json_encode((array)$data));
    }

    //商品库存报警设置
    public function storePolice()
    {
        $shopId = $this->shopId;
        $params['shop_id'] = $shopId;
        $params['fields'] = 'police_id,policevalue';

        $storePolice = app::get('topshop')->rpcCall('item.store.info',$params);
        $pagedata['storePolice'] = $storePolice;
        $this->contentHeaderTitle = app::get('topshop')->_('设置商品库存报警数');
        return $this->page('topshop/item/storepolice.html', $pagedata);
    }
    //保存库存报警
    public function saveStorePolice()
    {
        $storePolice = intval(input::get('storepolice'));
        $policeId = intval(input::get('police_id'));
        $url = url::action('topshop_ctl_item@storePolice');
        try
        {
            $validator = validator::make(
                [$storePolice],
                ['required|integer|min:1|max:99999'],
                ['库存预警值必填!|库存预警值必须为整数!|库存预警值最小为1!|库存预警值最大为99999!']
            );
            $validator->newFails();
        }
        catch( \LogicException $e )
        {
            return $this->splash('error', $url, $e->getMessage(), true);
        }
        $shopId = $this->shopId;
        $params['shop_id'] = $shopId;
        $params['policevalue'] = $storePolice;
        if(!is_null($policeId))
        {
            $params['police_id'] = $policeId;
        }
        try
        {
            app::get('topshop')->rpcCall('item.store.police.add',$params);
        }
        catch( \LogicException $e )
        {
            return $this->splash('error', null, $e->getMessage(), true);
        }
        $this->sellerlog('库存预警设置。');
        return $this->splash('success', $url, '保存成功', true);
    }

    public function edit()
    {
        $pagedata['return_to_url'] = request::server('HTTP_REFERER');
        $itemId = intval(input::get('item_id'));
        $pagedata['shopId'] = $this->shopId;
        $pagedata['is_lm'] = $this->isLm;

        // 店铺关联的商品品牌列表
        // 商品详细信息
        $params['item_id'] = $itemId;
        $params['shop_id'] = $this->shopId;
        $params['fields'] = "*,sku,item_store,item_status,item_count,item_desc,item_nature,spec_index";
        $pagedata['item'] = app::get('topshop')->rpcCall('item.get',$params);

        //如果是惠民店铺，则取出相应的类别
        if($this->isHmShop) {
            $hm_cat_model = app::get('sysitem')->model('item_hm_cat');
            $hm_cat_info = $hm_cat_model->getRow('*', ['item_id' => $itemId]);
            $hm_cat_id = $hm_cat_info['four_cat_code'];
            $pagedata['item']['hm_cat_id'] = $hm_cat_id;
        }

        // 商家分类及此商品关联的分类标示selected
        $scparams['shop_id'] = $this->shopId;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $selectedShopCids = explode(',', $pagedata['item']['shop_cat_id']);
        foreach($pagedata['shopCatList'] as &$v)
        {
            if($v['children'])
            {
                foreach($v['children'] as &$vv)
                {
                    if(in_array($vv['cat_id'], $selectedShopCids))
                    {
                        $vv['selected'] = true;
                    }
                }
            }
            else
            {
                if(in_array($v['cat_id'], $selectedShopCids))
                {
                    $v['selected'] = true;
                }
            }
        }

        // 获取运费模板列表
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpls'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $pagedata['dlytmpls']['data'] = array_bind_key($pagedata['dlytmpls']['data'],'template_id');
        /*start whc_商铺获取银行卡信息_2017/9/7*/
        if($pagedata['item']['bank_ids']!=''){
            $pagedata['item']['bank_ids']=explode(',',$pagedata['item']['bank_ids']);
        }
        $pagedata['banks']=$this->getBanks();
        /*end*/
        /*add_20170913_by_wudi_start*/
        $supplierparams['shop_id'] = $this->shopId;
        $supplierparams['is_audit'] = 'PASS';
        $pagedata['supplier'] = app::get('topshop')->rpcCall('supplier.shop.list',$supplierparams);

        //获取供应商信息
        $taxRateCfg=config::get('tax');
        $pagedata['taxrate']=$taxRateCfg;
        /*add_20170913_by_wudi_end*/
        /*add_2017/9/23_by_wanghaichao_start 获取供应商信息*/
        $supplier_name = app::get('sysshop')->model('supplier')->getRow('supplier_name',['supplier_id'=>$pagedata['item']['supplier_id'],'is_audit'=>'PASS']);
        $pagedata['item']['supplier_name'] = $supplier_name['supplier_name'];
        /*add_2017/9/23_by_wanghaichao_end*/
        //获取线下店详情
        $agent_shop_ids = explode(',', $pagedata['item']['agent_shop_id']);
        $agent_shop_ids = array_filter($agent_shop_ids,function($v){
            return !empty($v);
        });
        $pagedata['item']['agent_shop_id'] = implode(',', $agent_shop_ids);
        if(is_array($agent_shop_ids)){
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.search',['agent_shop_id'=>$agent_shop_ids]);
            $agentTemp = array();
            foreach ($agentShopData as $agent_k => $agent_v)
            {
                $agentTemp[] = $agent_v['name'];
            }
            $agent_shop_names = implode(',',$agentTemp);
        }else{
            $agent_shop_names = '';
        }
        $pagedata['item']['agent_shop_names'] = $agent_shop_names;
        $pagedata['offline'] = $this->shopInfo['offline'];

        /*add_2017/9/24_by_wanghaichao_start*/
        $pagedata['valid_time']=date('Y/m/d H:i',$pagedata['item']['start_time']).'-'.date('Y/m/d H:i',$pagedata['item']['end_time']);
        if($pagedata['item']['sell_time']){
            $pagedata['sell_time']=date('Y/m/d H:i',$pagedata['item']['sell_time']);
        }
        if($pagedata['item']['sell_time_end']){
            $pagedata['sell_time_end']=date('Y/m/d H:i',$pagedata['item']['sell_time_end']);
        }
        /*add_2017/9/24_by_wanghaichao_end*/

        /*add_2018/6/14_by_王衍生_start*/
        if( $pagedata['item']['video_dir'] )
        {
            unset($params);
            $params['url'] = $pagedata['item']['video_dir'];
            $params['fields'] = '*';

            $video_url_arr = app::get('topshop')->rpcCall('video.item.list', $params);
            // if 为兼容早期视频没有入视频表的视频
            if(!$video_url_arr){
                $video_url_arr[0]['url'] = $pagedata['item']['video_dir'];
            }
            $pagedata['item']['video_dir'] = $video_url_arr;
        }
        /*add_2018/6/14_by_王衍生_end*/
		/*add_2018/10/9_by_wanghaichao_start*/
		$ticket=app::get('sysitem')->model('ticket')->getList('*',array('item_id'=>$itemId));
		if($ticket){
			foreach($ticket as $k=>&$v){
				$supplier=app::get('sysshop')->model('supplier')->getRow('supplier_name',array('supplier_id'=>$v['supplier_id']));
				$v['supplier_name']=$supplier['supplier_name'];
			}
			$pagedata['count']=count($ticket);
			$pagedata['ticket']=$ticket;
		}
		/*add_2018/10/9_by_wanghaichao_end*/
        $pagedata['is_hm_shop'] = $this->isHmShop;
        $is_hm_supplier = $this->checkHuiminSupplierLogin();
        $hm_hidden_class = '';
        if($is_hm_supplier) {
            $hm_hidden_class = 'hidden';
        }
        $pagedata['hm_hidden_class'] = $hm_hidden_class;
        $pagedata['is_hm_supplier'] = $is_hm_supplier;
        $this->contentHeaderTitle = app::get('topshop')->_('编辑商品');
        return $this->page('topshop/item/edit.html', $pagedata);
    }
    /**
     * add
     * 获取该商铺银行卡信息
     * @parmas 无
     * whc
     * 2017/9/7
     * start
     **/

    public function getBanks(){
        $shop_id=$this->shopId;
        $sql="SELECT sb.bank_id,sb.bank_name FROM sysbankmember_member AS sm LEFT JOIN sysbankmember_bank AS sb ON sm.bank_id=sb.bank_id WHERE sm.shop_id=".$shop_id." AND sm.deleted!=1 AND  sb.deleted!=1";
        $banks=app::get('base')->database()->executeQuery($sql)->fetchAll();
        return $banks;
    }
    /*end*/

    public function itemList()
    {
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');

        $status = input::get('status',false);
        $pages =  input::get('pages',1);
        $hm_supplier_id = $is_hm_supplier = $this->checkHuiminSupplierLogin();
        $pagedata['status'] = $status;
        $filter = array(
            'shop_id' => $this->shopId,
            'approve_status' => $status,
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
        );
        //如果是惠民供应商登陆，则只筛选这个供应商的商品
        if($hm_supplier_id) {
            $filter['supplier_id'] = $hm_supplier_id;
        }
        $shopCatId = input::get('shop_cat_id',false);
        if( $shopCatId )
        {
            $filter['shop_cat_id'] = $shopCatId;
            $pagersFilter['shop_cat_id'] = $shopCatId;
        }
        $filter['fields'] = 'item_id,list_time,modified_time,title,image_default_id,price,approve_status,store,dlytmpl_id,nospec,is_virtual,init_shop_id,init_item_id,init_is_change';
        $filter['orderBy'] = 'modified_time desc';
        //库存报警判断
        if($status=='oversku')
        {
            $params['shop_id'] = $this->shopId;
            $params['fields'] = 'policevalue';
            $storePolice = app::get('topshop')->rpcCall('item.store.info',$params);
            $filter['store'] = $storePolice['policevalue']?$storePolice['policevalue']:0;
            $itemsList = app::get('topshop')->rpcCall('item.store.police',$filter);
        }
        else
        {

			/*modify_2018/6/22_by_wanghaichao_start*/
			/*
			* 增加主持人判断
			* $itemsList = app::get('topshop')->rpcCall('item.search',$filter);
 			*/
			if($this->sellerInfo['is_compere']==1){
				unset($filter['shop_id']);
				$filter['seller_id']=$this->sellerId;
				$filter['fields']='b.item_id,b.title,b.image_default_id,b.supply_price,b.nospec,b.price,c.store,b.shop_id,a.created_time,a.modified_time,d.approve_status,d.list_time,d.delist_time';
				$itemsList=app::get('topshop')->rpcCall('mall.get.selleritem',$filter);
				//echo "<pre>";print_r($itemList);die();
			}else{
                $filter['init_item_id'] = 0;// 过滤掉代卖商品
				$itemsList = app::get('topshop')->rpcCall('item.search',$filter);
			}
			/*modify_2018/6/22_by_wanghaichao_end*/
        }
        foreach ($itemsList['list'] as $k=>$v)
        {
            // 此处可优化为前端成生二维码
            $itemsList['list'][$k]['qr_code'] = $this->__qrCode($v['item_id'],90);
            // 王衍生-2018/06/21-start
            if(!$v['init_shop_id'] && !$v['init_item_id']){
                // 如果是原始商品，查看推送到了选货商城的信息
                $mall_item = app::get('sysmall')->rpcCall('mall.item.get',['fields' => '*', 'item_id' => $v['item_id']]);

                $itemsList['list'][$k]['mall_data'] = $mall_item;
            }
            // 王衍生-2018/06/21-end

        }
        $pagedata['item_list'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];

        $totalPage = ceil($itemsList['total_found']/$this->limit);
        $pagersFilter['pages'] = time();
        $pagersFilter['status'] = $status;
        $pagers = array(
            'link'=>url::action('topshop_ctl_item@itemList',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        $pagedata['pagers'] = $pagers;

        //获取当前店铺商品分类
        $catparams['shop_id'] = $this->shopId;
        //$catparams['fields'] = 'cat_id,cat_name';
        $itemCat = app::get('topshop')->rpcCall('shop.cat.get', $catparams);
        $pagedata['item_cat'] = $itemCat;

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        //获取运费模板
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpl'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $this->contentHeaderTitle = app::get('topshop')->_('商品列表');
        $pagedata['setting'] = app::get('sysconf')->getConf('shop.goods.examine');
        $pagedata['exportLimit'] = $this->exportLimit;
		/*add_2018/6/22_by_wanghaichao_start*/
		//把seller_id和is_compere传进去
		$pagedata['seller_id']=$this->sellerId;
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
        $pagedata['is_lm'] = $this->isLm;
        $pagedata['is_hm_shop'] = $this->isHmShop;
        $pagedata['is_hm_supplier'] = $is_hm_supplier;

		/*add_2018/6/22_by_wanghaichao_end*/
        return $this->page('topshop/item/list.html', $pagedata);
    }
    /**
     * 商品二维码生成，指向手机端
     * @param $itemId
     * @return string
     */
    private function __qrCode($itemId,$size = 60)
    {

		/*modify_2018/6/22_by_wanghaichao_start*/
		/*
		* 如果是主持人的话加入主持人id
		* $url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
		*/
		if($this->sellerInfo['is_compere']==1){
			$url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId,'seller_id'=>$this->sellerId));
		}else{
			$url = url::action("topwap_ctl_item_detail@index",array('item_id'=>$itemId));
		}
		/*modify_2018/6/22_by_wanghaichao_end*/

        return getQrcodeUri($url, $size, 10);
    }
    /**
     * 商品二维码下载
     */
    public function qrDown()
    {
        $item_id = input::get('item_id');
        $item_mdl = app::get('sysitem')->model('item');
        $item_detail = $item_mdl->getRow('title',['item_id'=>$item_id]);
        $img_64 = $this->__qrCode($item_id,200);
        preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_64, $result);
        $data = base64_decode(str_replace($result[1], '', $img_64));
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Type: image/png");
        header("Content-Disposition: attachment; filename=".$item_detail['title'].".png;" );
        header("Content-Transfer-Encoding: binary");
        header("Content-Length: ".strlen($data));
        ob_start();
        echo $data;
        ob_end_flush();
        die;
    }
    //商品搜所
    public function searchItem()
    {
        $filter = input::get();

        if($filter['min_price']&&$filter['max_price'])
        {
            if($filter['min_price']>$filter['max_price'])
            {
                $msg = app::get('topshop')->_('最大值不能小于最小值！');
                return $this->splash('error', null, $msg, true);
            }
        }
        $pages =  $filter['pages'] ? $filter['pages'] : 1;
        $params = array(
            'shop_id' => $this->shopId,
            'search_keywords' => $filter['item_title'],
            'min_price' => $filter['min_price'],
            'max_price' => $filter['max_price'],
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
            'orderBy' =>'modified_time desc',
        );
        $hm_supplier_id = $this->checkHuiminSupplierLogin();
        //如果是惠民供应商登陆，则只筛选这个供应商的商品
        if($hm_supplier_id) {
            $filter['supplier_id'] = $hm_supplier_id;
        }

        if($filter['use_platform'] >= 0)
        {
            $params['use_platform'] = $filter['use_platform'];
        }
        if($filter['item_cat'] && $filter['item_cat'] > 0)
        {
            $params['search_shop_cat_id'] = (int)$filter['item_cat'];
        }
        if($filter['item_no'])
        {
            $params['bn'] = $filter['item_no'];
        }
        if($filter['status'])
        {
            $params['approve_status'] = $filter['status'];
        }

        if($filter['dlytmpl_id']&&$filter['dlytmpl_id']>0)
        {
            $pagedata['dlytmpl_id'] = $params['dlytmpl_id'] = $filter['dlytmpl_id'];
        }

        $pagedata['use_platform'] = $filter['use_platform'];
        $pagedata['min_price'] = $filter['min_price'];
        $pagedata['max_price'] = $filter['max_price'];
        $pagedata['search_keywords'] = $filter['item_title'];
        $pagedata['item_cat_id'] = $filter['item_cat'];
        $pagedata['item_no'] = $filter['item_no'];
        $pagedata['status'] = isset($filter['status']) ? $filter['status'] : '';
		$params['self_item']=true;
        $params['fields'] = 'item_id,list_time,modified_time,title,image_default_id,price,approve_status,store,dlytmpl_id,nospec';
		//库存报警判断
        if($filter['status']=='oversku')
        {
            $pparams['shop_id'] = $this->shopId;
            $pparams['fields'] = 'policevalue';
            $storePolice = app::get('topshop')->rpcCall('item.store.info',$pparams);
            $params['store'] = $storePolice['policevalue']?$storePolice['policevalue']:0;
            $itemsList = app::get('topshop')->rpcCall('search.item.oversku',$params);
        }
        else
        {
			/*modify_2018/6/22_by_wanghaichao_start*/
			/*
			* 增加主持人判断
			* $itemsList = app::get('topshop')->rpcCall('item.search',$filter);
 			*/
			//echo "<pre>";print_r($filter);die();
			if($this->sellerInfo['is_compere']==1){
				//echo "<pre>";print_r($filter);die();
				unset($filter['shop_id']);
				$filter['seller_id']=$this->sellerId;
				$filter['approve_status']=$filter['status'];
				$filter['fields']='b.item_id,b.title,b.image_default_id,b.nospec,b.supply_price,b.price,c.store,b.shop_id,a.created_time,a.modified_time,d.approve_status,d.list_time,d.delist_time';
				$itemsList=app::get('topshop')->rpcCall('mall.get.selleritem',$filter);
				//echo "<pre>";print_r($itemList);die();
			}else{
				$item_filter=$params;
				$item_filter['fields'] = 'item_id,list_time,modified_time,title,image_default_id,price,approve_status,store,dlytmpl_id,nospec,is_virtual,init_shop_id,init_item_id';
				$item_filter['orderBy'] = 'modified_time desc';
				$itemsList = app::get('topshop')->rpcCall('item.search',$item_filter);
			}

			/*modify_2018/6/22_by_wanghaichao_end*/
            //$itemsList = app::get('topshop')->rpcCall('item.search',$params);
        }
		foreach ($itemsList['list'] as $k=>$v)
        {
            // 此处可优化为前端成生二维码
            $itemsList['list'][$k]['qr_code'] = $this->__qrCode($v['item_id'],90);
            // 王衍生-2018/06/21-start
            if(!$v['init_shop_id'] && !$v['init_item_id']){
                // 如果是原始商品，查看推送到了选货商城的信息
                $mall_item = app::get('sysmall')->rpcCall('mall.item.get',['fields' => '*', 'item_id' => $v['item_id']]);

                $itemsList['list'][$k]['mall_data'] = $mall_item;
            }
            // 王衍生-2018/06/21-end

        }
        //获取运费模板
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpl'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);


        $pagedata['item_list'] = $itemsList['list'];
        $pagedata['total'] = $itemsList['total_found'];

        $totalPage = ceil($itemsList['total_found']/$this->limit);
        $pagersFilter['pages'] = time();
        $pagersFilter['min_price'] = $filter['min_price'];
        $pagersFilter['max_price'] = $filter['max_price'];
        $pagersFilter['use_platform'] = $filter['use_platform'];
        $pagersFilter['item_title'] = $filter['item_title'];
        $pagersFilter['item_cat'] = $filter['item_cat'];
        $pagersFilter['item_no'] = $filter['item_no'];
        $pagersFilter['dlytmpl_id'] = $filter['dlytmpl_id'];
        if(isset($filter['status']))
        {
            $pagersFilter['status'] =  $filter['status'];
        }

        $pagers = array(
            'link'=>url::action('topshop_ctl_item@searchItem',$pagersFilter),
            'current'=>$pages,
            'use_app' => 'topshop',
            'total'=>$totalPage,
            'token'=>time(),
        );
        $pagedata['pagers'] = $pagers;

        //获取当前店铺商品分类
        $catparams['shop_id'] = $this->shopId;
        $itemCat = app::get('topshop')->rpcCall('shop.cat.get', $catparams);
        $pagedata['item_cat'] = $itemCat;

        // 是否在搜索中
        $pagedata['is_search'] = true;
        // 表格切换条件
        $searchParams = $pagersFilter;
        $searchArr = array();
        $searchTmp = array();
        if($searchParams)
        {
            unset($searchParams['pages']);
            if(isset($searchParams['status']))
            {
                unset($searchParams['status']);
            }
            if(app::get('sysconf')->getConf('shop.goods.examine')){
                $status = array(
                    'onsale' => app::get('topshop')->_('上架中'),
                    'instock' => app::get('topshop')->_('仓库中'),
                    'oversku' => app::get('topshop')->_('库存报警'),
                    'pending' => app::get('topshop')->_('待审核'),
                    'refuse' => app::get('topshop')->_('审核失败'),
                );
            }else{
                $status = array(
                    'onsale' => app::get('topshop')->_('上架中'),
                    'instock' => app::get('topshop')->_('仓库中'),
                    'oversku' => app::get('topshop')->_('库存报警'),
                );
            }

            foreach ($status as $k=>$v)
            {
                $searchParams['status'] = $k;
                $searchTmp['status'] = $k;
                $searchTmp['url'] = url::action('topshop_ctl_item@searchItem', $searchParams);
                $searchTmp['label'] = $v;
                $searchArr[] = $searchTmp;
            }
        }
		/*add_2018/6/22_by_wanghaichao_start*/
		//判断是否是主持人,如果是主持人,去掉一些权限
		if($this->sellerInfo['is_compere']==1){
			unset($searchArr[2],$searchArr[3],$searchArr[4]);
		}
		/*add_2018/6/22_by_wanghaichao_end*/
        $pagedata['search_arr'] = $searchArr;
        $pagedata['setting'] = app::get('sysconf')->getConf('shop.goods.examine');
        $this->contentHeaderTitle = app::get('topshop')->_('商品列表');
        $pagedata['exportLimit'] = $this->exportLimit;
		/*add_2018/6/22_by_wanghaichao_start*/
		//把seller_id和is_compere传进去qr_code
		$pagedata['seller_id']=$this->sellerId;
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		/*add_2018/6/22_by_wanghaichao_end*/
		//echo "<pre>";print_r($pagedata['search_arr']);die();
        $pagedata['is_lm'] = $this->isLm;
        return $this->page('topshop/item/list.html', $pagedata);

    }
    public function storeItem()
    {
        $postData = input::get();
        //dump($postData);die;
        if(empty($postData['item']['item_id'])){
            $itemFrom=$postData['item']['is_clearing'];
        }else{
            $itemInfo=app::get('sysitem')->model('item')->getRow('is_clearing, supplier_id',array('item_id'=>$postData['item']['item_id']));
            $itemFrom=$itemInfo['is_clearing'];
            $hm_supplier_id = $this->checkHuiminSupplierLogin();
            if($hm_supplier_id && $hm_supplier_id != $itemInfo['supplier_id']) {
                return $this->splash('error','','只允许更改自己店铺的商品');
            }
        }
        /*add_201711241355_wudi_start:barcode required*/
        if($itemFrom==1){
            $skuInfo=json_decode($postData['item']['sku'],true);
            if(empty($skuInfo)){
                if(empty($postData['item']['barcode'])){
                    return $this->splash('error','','平台供货类型的商品条码不能为空');
                }
            }else{
                $skuArra=json_decode($postData['item']['sku'],true);
                if(count($skuArra) > 1){
                    foreach($skuArra as $val){
                        if(empty($val['barcode'])){
                            return $this->splash('error','','平台供货类型的商品条码不能为空');
                        }
                    }
                }else{
                    if(empty($postData['item']['barcode'])){
                        return $this->splash('error','','平台供货类型的商品条码不能为空');
                    }
                }

            }
        }
        /*add_201711241355_wudi_end*/
        /*add_20170913_by_wudi_start*/
        $taxRate=config::get('tax');
        $postData['item']['tax_rate']=$taxRate[$postData['item']['incoming_type']]['value'];
        if(empty($postData['item']['item_id']) && empty($postData['item']['tax_rate']) && $postData['item']['tax_rate']!==0 && $postData['item']['tax_rate']!=='0'){
            return $this->splash('error','','收入类型对应的税率不能为空');
        }
        /*add_20170913_by_wudi_end*/
        try
        {
            // 检查参数
            $this->_checkPost($postData);
            // 格式化参数
            $postData = $this->_formatItemData($postData);
            $result = app::get('topshop')->rpcCall('item.create',$postData);
            if($result['res'])
            {
                $this->sellerlog('保存商品。名称是'.$postData['title']);
                $url = input::get('return_to_url') ? : url::action('topshop_ctl_item@itemList');
                $msg = app::get('topshop')->_('保存成功');

                //如果是惠民店铺，则存储相关的惠民商品分类信息
                if($this->isHmShop) {
                    $hm_four_cat_name = '';
                    $hm_second_cat_name = '';
                    $hm_second_cat_code = '';
                    $hm_four_cat_code = $postData['hm_cat_id'];
                    $hm_obj = kernel::single('ectools_huimin');
                    $hm_cat_list = $hm_obj->getItemCateList($this->shopId);
                    foreach($hm_cat_list as $hcl) {
                        foreach($hcl['children'] as $child1) {
                            foreach($child1['children'] as $child2) {
                                if($child2['code'] == trim($hm_four_cat_code)) {
                                    $hm_four_cat_name = $child2['sortName'];
                                    $hm_second_cat_name = $hcl['sortName'];
                                    $hm_second_cat_code = $hcl['code'];
                                }
                            }
                        }
                    }
                    $hm_insert_data['item_id'] = $result['params']['item_id'];
                    $hm_insert_data['second_cat_code'] = $hm_update_data['second_cat_code'] = $hm_second_cat_code;
                    $hm_insert_data['second_cat_name'] = $hm_update_data['second_cat_name'] = $hm_second_cat_name;
                    $hm_insert_data['four_cat_code']   = $hm_update_data['four_cat_code'] = $hm_four_cat_code;
                    $hm_insert_data['four_cat_name']   = $hm_update_data['four_cat_name'] = $hm_four_cat_name;
                    $hm_cat_model = app::get('sysitem')->model('item_hm_cat');
                    $hm_has_count = $hm_cat_model->count(['item_id' => $result['params']['item_id']]);
                    if($hm_has_count > 0) {
                        $hm_cat_model->update($hm_update_data, ['item_id' => $result['params']['item_id']]);
                    } else {
                        $hm_cat_model->insert($hm_insert_data);
                    }
                }

                /*添加商品到广电优选start @auth:xinyfueng;time:2019-02-25*/
                if(app::get('sysconf')->getConf('shop.goods.examine'))// 如果开启商品审核
                {
                    // 如果是实物商品 或 虚拟商品并且开启虚拟商品审核
                    if($postData['is_virtual'] == '0' || ($postData['is_virtual'] == '1' && app::get('sysitem')->getConf('virtual.goods.check') == 'true'))
                    {
                        // 添加or更新商品到广电优选
                        $apiData['item_id'] = $result['params']['item_id'];
                        $apiData['shop_id'] = $this->shopId;
                        $apiData['sale_type'] = 0;
                        app::get('topshop')->rpcCall('mall.item.push', $apiData);
                    }
                }
                /*添加商品到广电优选end*/

                /*下架代售和优选商品start @auth:xinyfueng;*/
                // 如果是商品更新则下架代售和优选商品
                if($postData['item_id'])
                {
                    app::rpcCall('mall.item.soldout', array('item_id' => $postData['item_id'], 'shop_id' => $postData['shop_id']));
                }
                /*下架代售和优选商品end*/
                
                kernel::single('sysitem_events_listeners_kingdeeMateriel')->handle($result['params']['item_id'],true);
                return $this->splash('success', $url, $msg, true);
            }
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

    // 初步判断数据合法性
    private function _checkPost($postData)
    {
        if($this->isHmShop) {
            if(empty($postData['hm_cat_id']) || !isset($postData['hm_cat_id'])) {
                throw new LogicException('惠民商品分类必须选择');
            }
        }
        if(empty($postData['item']['supplier_id']) || !isset($postData['item']['supplier_id']))
        {
            throw new LogicException('供货商必填，请选择供货商');
        }
        if(mb_strlen($postData['item']['title'],'UTF-8') > 50)
        {
            throw new Exception('商品名称至多50个字符');
        }

		/*add_2018/10/11_by_wanghaichao_start*/
		//加入但商品多个券的判断
		if($postData['item']['is_ticket']==1){
			if(empty($postData['ticket'])){
				throw new Exception('必须填写相应的标题和供货商');
			}
			foreach($postData['ticket'] as $k=>$tick){
				if(empty($tick['title'])){
					throw new Exception('必须填写相应的标题');
				}
				if(empty($tick['supplier_id'])){
					throw new Exception('必须填写相应的供货商');
				}
			}
		}
		/*add_2018/10/11_by_wanghaichao_end*/


        if(!implode(',', $postData['item']['shop_cids']))
        {
            throw new Exception('店铺分类至少选择一项');
        }

        if($postData['spec_value'])
        {
            foreach($postData['spec_value'] as $val)
            {
                if(mb_strlen($val,'UTF-8') > 20)
                {
                    throw new Exception('销售属性名称至多20个字符');
                }
            }
        }
        /* add_start_gurundong_2018/01/19 */
        //验证核销方式
        if($postData['item']['confirm_type'] == 1)
        {
            $validator_1 = validator::make(
                [
                    //核销方式
                    'confirm_type'=>$postData['item']['confirm_type'],
                    //有偿劵或无偿劵
                    'agent_price'=>$postData['item']['agent_price'],
                    //线下店使用范围
                    'agent_shop_id'=>$postData['item']['agent_shop_id'],
                ],
                [
                    'confirm_type'=>'required',
                    'agent_price'=>'required',
                    'agent_shop_id'=>'required',
                ],
                [
                    'confirm_type'=>'请选择核销方式',
                    'agent_price'=>'请选择有偿劵或无偿劵',
                    'agent_shop_id'=>'请选择线下店使用范围',
                ]
            );
            if ($validator_1->fails())
            {
                $error_msg = $validator_1->errors();
                foreach ($error_msg->all() as $message) {
                    throw new LogicException($message);
                }
            }
            $validator_2 = validator::make(
                [
                    //核销类别
                    'agent_type'=>$postData['item']['agent_type'],
                    //领券数量限制
                //    'limit_quantity'=>$postData['item']['limit_quantity'],
                ],
                [
                    'agent_type'=>'required',
                //    'limit_quantity'=>'required|numeric|min:0',
                ],
                [
                    'agent_type'=>'请选择核销类别',
                //    'limit_quantity'=>'请输入领券数量限制',
                ]
            );
            if ($validator_2->fails())
            {
                $error_msg = $validator_2->errors();
                foreach ($error_msg->all() as $message) {
                    throw new LogicException($message);
                }
            }
            //如果核销类别为代金券的后续判断
            if($postData['item']['agent_type'] === 'CASH_VOCHER')
            {
                $validator_3 = validator::make(
                    [
                        //使用数量限制
                        'agent_use_limit'=>$postData['item']['agent_use_limit'],
                    ],
                    [
                        'agent_use_limit'=>'required|numeric|min:0',
                    ],
                    [
                        'agent_use_limit'=>'请输入使用数量限制',
                    ]
                );
                if ($validator_3->fails())
                {
                    $error_msg = $validator_3->errors();
                    foreach ($error_msg->all() as $message) {
                        throw new LogicException($message);
                    }
                }
            }elseif ($postData['item']['agent_type'] === 'DISCOUNT')
            {
                $validator_4 = validator::make(
                    [
                        //卡券最大抵扣金额
                        'agent_use_limit'=>$postData['item']['max_deduct_price'],
                        //优惠的金额或者折扣的数值
                        'deduct_price'=>$postData['item']['deduct_price'],
                    ],
                    [
                        'agent_use_limit'=>'required|numeric|min:0',
                        'deduct_price'=>'required|numeric|min:0',
                    ],
                    [
                        'agent_use_limit'=>'请输入卡券最大抵扣金额',
                        'deduct_price'=>'请输入优惠的金额或者折扣的数值',
                    ]
                );
                if ($validator_4->fails())
                {
                    $error_msg = $validator_4->errors();
                    foreach ($error_msg->all() as $message) {
                        throw new LogicException($message);
                    }
                }
            }elseif ($postData['item']['agent_type'] === 'REDUCE')
            {
                $validator_4 = validator::make(
                    [
                        //卡券的最低消费金额限制
                        'min_consum'=>$postData['item']['min_consum'],
                        //优惠的金额或者折扣的数值
                        'deduct_price'=>$postData['item']['deduct_price'],
                    ],
                    [
                        'min_consum'=>'required|numeric|min:0',
                        'deduct_price'=>'required|numeric|min:0',
                    ],
                    [
                        'min_consum'=>'请输入卡券的最低消费金额限制',
                        'deduct_price'=>'请输入优惠的金额或者折扣的数值',
                    ]
                );
                if ($validator_4->fails())
                {
                    $error_msg = $validator_4->errors();
                    foreach ($error_msg->all() as $message) {
                        throw new LogicException($message);
                    }
                }
            }
        }
        /* add_end_gurundong_2018/01/19 */
    }

    // 格式化添加商品接口需要的数据
    private function _formatItemData($postData)
    {
        $data = [];
        $data['shop_id'] = $this->shopId;
        $data['cat_id'] = $postData['cat_id'];
        $data['hm_cat_id'] = $postData['hm_cat_id'];
        $data['brand_id'] = $postData['item']['brand_id'];
        $data['shop_cat_id'] = implode(',', $postData['item']['shop_cids']);
        $data['title'] = htmlspecialchars($postData['item']['title']);
        $data['sub_title'] = htmlspecialchars($postData['item']['sub_title']);

        $data['bn'] = $postData['item']['bn'];
        $data['price'] = $postData['item']['price'];
        $data['cost_price'] = $postData['item']['cost_price'] ? : 0;
        $data['mkt_price'] = $postData['item']['mkt_price'] ? : 0;
        // 添加供货价 王衍生
        $data['supply_price'] = $postData['item']['supply_price'] ? : 0;
        $data['maker_ratio'] = $postData['item']['maker_ratio'] ? : 0;
        $data['group_ratio'] = $postData['item']['group_ratio'] ? : 0;
        /*add_2017/12/11_by_wanghaichao_start*/
        $data['bank_price'] = $postData['item']['bank_price'] ? : 0;     //银行卡商品价格
        $data['only_bank'] = $postData['item']['only_bank'];  //是否仅限银行卡商品
        /*add_2017/12/11_by_wanghaichao_end*/

        $data['show_mkt_price'] = $postData['item']['show_mkt_price'] ? 1 : 0;

        $data['weight'] = $postData['item']['weight'] ? : 0;
        $data['unit'] = $postData['item']['unit'];
        $data['list_image'] = $postData['listimages'] ? implode(',', $postData['listimages']) : '';
        $data['images'] = $postData['images']; //颜色属性的关联图片
        $data['order_sort'] = 0; // 目前未用到

        $data['has_discount'] = 0; // 目前未用到

        /*modify_2017/9/24_by_wanghaichao_start*/
        /*  $data['is_virtual'] = 0; // 目前未用到	*/
        //重新启用是否为虚拟商品字段
        $data['is_virtual'] = $postData['item']['is_virtual'];
        /*modify_2017/9/24_by_wanghaichao_end*/

        $data['is_timing'] = 0; // 目前未用到
        $data['nospec'] = $postData['item']['nospec'] ? 1: 0;

        $data['spec'] = $postData['item']['spec'];
        $data['spec_value'] = $postData['spec_value'];
        $data['nature_props'] = $postData['item']['nature_props'];
        $data['params'] = $postData['item']['params'];
        $data['itemParams'] = $postData['itemParams'];
        $data['sub_stock'] = $postData['item']['sub_stock'];

        $data['outer_id'] = 0;$postData['item']['outer_id']; //目前未用到
        $data['is_offline'] = 0; // 目前未用到
        $data['barcode'] = $postData['item']['barcode'];
        $data['use_platform'] = $postData['item']['use_platform'];
        $data['dlytmpl_id'] = $postData['item']['dlytmpl_id'];

        $data['approve_status'] = 'instock'; // 编辑后默认下架
        $data['list_time'] = $postData['item']['list_time'];
        $data['item_id'] = $postData['item']['item_id'];
        /*add_20170924_by_wudi_start*/
        $data['sku'] = $postData['item']['sku'];
        /*add_20170924_by_wudi_end*/
        /*add_start_gurundong_20171030*/
        $data['livehot_img'] = $postData['item']['livehot_img'];
        /*end_start_gurundong_20171030*/

        /*add_20170913_by_wudi_start*/
        $data['supplier_id']=$postData['item']['supplier_id'];
        $data['incoming_type']=$postData['item']['incoming_type'];
        $data['tax_rate']=$postData['item']['tax_rate'];
        $data['is_clearing']=$postData['item']['is_clearing'];
        /*add_20170913_by_wudi_end*/

        //编辑单品时，将商品库存赋值给货品库存
        $sku = json_decode($data['sku'],1);
        if( $sku && $data['nospec'])
        {
            foreach($sku as $key=>&$val)
            {
                $val['store'] = $postData['item']['store'];
            }
            $data['sku'] = json_encode($sku);
        }

        $data['desc'] = $this->__removeScript($postData['item']['desc']);

        $data['wap_desc'] = $this->__removeScript($postData['item']['wap_desc']);
        $data['store'] = $postData['item']['store'];//单品时候需要

        /*start whc_判断是否有bank_id和是否设置了银行卡商品_2017/9/7*/
        if(isset($postData['bank_id']) && !empty($postData['bank_id']) && $postData['is_bank']==1){
            $data['bank_ids']=implode(',',$postData['bank_id']);
            $data['is_bank']=$postData['is_bank'];
        }else{
            $data['bank_ids']='';
            $data['is_bank']=0;
        }
        /*end*/
        /*add_2017/9/24_by_wanghaichao_start*/
        //添加修改虚拟商品,  定时售卖,  定时结束售卖等信息
        if($postData['item']['valid_time'] && $postData['item']['is_virtual']==1){
            $valid_time=$postData['item']['valid_time'];
            $time=explode('-',$valid_time);
            $data['start_time']=strtotime($time[0]);   //处理时间
            $data['end_time']=strtotime($time[1]);      //虚拟商品时处理时间
            unset($postData['item']['valid_time']);
        }
        if($postData['sell_open']==1){
            $data['sell_time']=strtotime($postData['item']['sell_time']);
        }else{
            $data['sell_time']='';
        }
        if($postData['sell_end']==1){
            $data['sell_time_end']=strtotime($postData['item']['sell_time_end']);
        }else{
            $data['sell_time_end']='';
        }
        if($postData['item']['is_virtual']==1){
            $data['dlytmpl_id']=0;
        }
        /*add_2017/11/1_by_wanghaichao_start*/
        //设置用户限购数量
        if($postData['is_limit']==1){
            $data['limit_quantity']=$postData['item']['limit_quantity'];
        }else{
            $data['limit_quantity']=0;
        }
        /*add_2017/11/1_by_wanghaichao_end*/
        $data['description']=$postData['item']['description'];
        $data['supplier_id']=$postData['item']['supplier_id'];
        $data['right_logo']=$postData['item']['right_logo'];
        /*add_2017/9/24_by_wanghaichao_end*/
        // start add 王衍生 20170925 商品视频路径
        // $data['video_dir']=$postData['item']['video_dir'];
        $data['video_dir']=$postData['item']['video_dir'] ? implode(',', $postData['item']['video_dir']) : '';
        // end add 王衍生 20170925
        /*add_20180308_by_fanglongji_start*/
        $data['is_cross'] = $postData['item']['is_cross'];
        /*add_20180308_by_fanglongji_end*/
        /* add_start_gurundong_2018/01/19 */
        $data['confirm_type'] = $postData['item']['confirm_type'];
        $data['agent_price'] = $postData['item']['agent_price'];
        $data['agent_type'] = $postData['item']['agent_type'];
        $data['agent_use_limit'] = $postData['item']['agent_use_limit'];
        if(!empty($postData['item']['agent_shop_id']))
        {
            $postData['item']['agent_shop_id'] = ','.$postData['item']['agent_shop_id'].',';
        }else{
            $postData['item']['agent_shop_id'] = ',';
        }

        $data['agent_shop_id'] = $postData['item']['agent_shop_id'];
        $data['max_deduct_price'] = $postData['item']['max_deduct_price'];
        $data['min_consum'] = $postData['item']['min_consum'];
        $data['deduct_price'] = $postData['item']['deduct_price'];
        $data['source_house'] = $postData['item']['source_house'];
        $data['operate_type'] = $postData['item']['operate_type'];
        $data['shelf_life']   = $postData['item']['shelf_life'];
        $data['inner_code']   = $postData['item']['inner_code'];
        $data['inner_num']    = $postData['item']['inner_num'];
        $data['material_code']   = $postData['item']['material_code'];
        $data['consign_ratio']   = $postData['item']['consign_ratio'];
        $data['consign_settlement']   = $postData['item']['consign_settlement'];
        $data['kingdee_incoming_type'] = $postData['item']['kingdee_incoming_type'];
        $data['tax_block_code']   = $postData['item']['tax_block_code'];
        /* add_end_gurundong_2018/01/19 */
		/*add_2018/10/9_by_wanghaichao_start*/
		//处理一个虚拟商品生成多个卡券的逻辑
		if($postData['item']['is_ticket']==1 && $postData['item']['is_virtual']==1){
			$data['ticket']=$postData['ticket'];
		}else{
			$data['ticket']='';
		}
		$data['is_ticket']=$postData['item']['is_ticket'];
		/*add_2018/10/9_by_wanghaichao_end*/

        return $data;
    }

    public function setItemStatus(){

        $postData = input::get();
        try
        {
            if(!$itemId = $postData['item_id'])
            {
                $msg = app::get('topshop')->_('商品id不能为空');
                return $this->splash('error',null,$msg,true);
            }

            /*add_by_xinyufeng_2018-07-04_start*/
            // 获取商品的原始商品id和更新状态
            $itemInfo = app::get('sysitem')->model('item')->getRow('title,init_item_id,init_is_change,shop_cat_id,is_virtual', array('item_id'=>$itemId));
            /*add_by_xinyufeng_2018-07-04_end*/

            if($postData['type'] == 'tosale')
            {
                /*add_by_xinyufeng_2018-07-04_start*/
                if($itemInfo['init_item_id'])
                {
                    // 原始商品信息
                    $mallItem = app::get('sysmall')->model('item')->getRow('status,sale_type', array('item_id'=>$itemInfo['init_item_id']));
                    // 判断原始商品是否在售
                    if($mallItem['status'] != 'onsale')
                    {
                        $msg = app::get('topshop')->_("原始商品 [{$itemInfo['title']}] 未在售");
                        return $this->splash('error',null,$msg,true);
                    }
                    // 判断原始商品是否在售
                    if($mallItem['sale_type'] != 0)
                    {
                        $msg = app::get('topshop')->_("原始商品 [{$itemInfo['title']}] 只允许主持人代售");
                        return $this->splash('error',null,$msg,true);
                    }
                    // 判断原始商品有数据有变化
                    if($itemInfo['init_is_change'] == 1)
                    {
                        $msg = app::get('topshop')->_("原始商品 [{$itemInfo['title']}] 有数据有变化,请更新");
                        return $this->splash('error',null,$msg,true);
                    }
                    // 判断代售商品是否有店铺分类
                    if(empty($itemInfo['shop_cat_id']))
                    {
                        $msg = app::get('topshop')->_('请先选择店铺分类');
                        return $this->splash('error',null,$msg,true);
                    }
                }
                /*add_by_xinyufeng_2018-07-04_end*/

                $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shopId),'seller');
                if( empty($shopdata) || $shopdata['status'] == "dead" )
                {
                    $msg = app::get('topshop')->_('抱歉，您的店铺处于关闭状态，不能发布(上架)商品');
                    return $this->splash('error',null,$msg,true);
                }
                // 如果平台开启商品审核，过滤商品 @auth:xinyufeng;time:2019-02-22
                if(app::get('sysconf')->getConf('shop.goods.examine')){
                    // 如果是虚拟商品，并且没有开启虚拟商品审核 或 代售商品 直接上架成功
                    if(($itemInfo['is_virtual'] == '1' && app::get('sysitem')->getConf('virtual.goods.check') == 'false') || $itemInfo['init_item_id'])
                    {
                        $status = 'onsale';
                        $msg = app::get('topshop')->_('上架成功');
                    }
                    else
                    {
                        $status = 'pending';
                        $msg = app::get('topshop')->_('提交审核成功');
                    }
                }else{
                    $status = 'onsale';
                    $msg = app::get('topshop')->_('上架成功');
                }
            }
            elseif($postData['type'] == 'tostock')
            {
                $status = 'instock';
                $msg = app::get('topshop')->_('下架成功');
            }
            else
            {
                return $this->splash('error',null,'非法操作!', true);
            }

            $itemstatus = app::get('topc')->rpcCall('item.get',array('item_id'=>$itemId,'fields'=>'item_id,approve_status'));

            if($status =='instock' || $itemstatus['approve_status'] != 'onsale' ){
                $params['item_id'] = intval($itemId);
                $params['shop_id'] = intval($this->shopId);
                $params['approve_status'] = $status;
                app::get('topshop')->rpcCall('item.sale.status',$params);
            }

            $queue_params['item_id'] = intval($itemId);
            $queue_params['shop_id'] = intval($this->shopId);
            $queue_params['status'] = $status;
            event::fire('item.notify', array($queue_params));

            $this->sellerlog('操作商品状态。改为 '.$status);
            $url = input::get('return_to_url') ? : url::action('topshop_ctl_item@itemList');

            /*add_by_xinyufeng_2018-07-04_start*/
            // 处理原始商品下架后,代售和选货商品状态
            if($postData['type'] == 'tostock' && !$itemInfo['init_item_id'])
            {
                if($postData['item_id'])
                {
                    app::rpcCall('mall.item.soldout', array('item_id' => $itemId, 'shop_id' => $this->shopId));
                }
            }
            /*add_by_xinyufeng_2018-07-04_end*/

            /*添加商品审核记录开始 @auth:xinyufeng;time:2019-02-25*/
            if(app::get('sysconf')->getConf('shop.goods.examine') && $status == 'pending' && $itemstatus['approve_status'] != 'onsale')
            {
                // 调用添加审核记录接口
                app::rpcCall('item.check.create', array('item_id' => $itemId, 'shop_id' => $this->shopId));
            }
            /*添加商品审核记录结束*/

            return $this->splash('success', $url, $msg, true);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null,$e->getMessage(), true);
        }
    }

    public function deleteItem()
    {
        $postData = input::get();
        //订单状态
        $orderStatus = array('WAIT_BUYER_PAY', 'WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS');

        try
        {
            if(!$itemId = $postData['item_id'])
            {
                $msg = app::get('topshop')->_('商品id不能为空');
                return $this->splash('error',null,$msg, true);
            }

            //判断商品所在订单的状态
            $orderParams = array();
            $orderParams['item_id'] = (int)$itemId;
            $orderParams['fields'] = 'status';
            $orderList = app::get('topshop')->rpcCall('trade.order.list.get', $orderParams);
            if($orderList)
            {
                $orderArrStatus = array_column($orderList, 'status');
                foreach ($orderStatus as $status)
                {
                    if(in_array($status, $orderArrStatus))
                    {
                        $msg = app::get('topshop')->_('商品存在未完成的订单，不能删除');
                        return $this->splash('error',null,$msg, true);
                    }
                }
            }

            /*add_by_xinyufeng_2018-06-25_start*/
            // 判断是否含有代售商品
            $com_count = app::get('sysitem')->model('item')->count(array('init_item_id'=>$itemId, 'init_shop_id'=>$this->shopId));
            if($com_count)
            {
                $msg = app::get('topshop')->_('商品存在代售商品，不能删除');
                return $this->splash('error',null,$msg, true);
            }
            /*add_by_xinyufeng_2018-06-25_end*/

            app::get('topshop')->rpcCall('item.delete',array('item_id'=>intval($itemId),'shop_id'=>intval($this->shopId)));
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('删除商品。 商品ID是'.$itemId);
        return $this->splash('success',null,'删除成功', true);
    }

    public function ajaxGetBrand($cat_id)
    {
        $params['shop_id'] = $this->shopId;
        $params['cat_id'] = input::get('cat_id');
        try
        {
            $brand = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        return response::json($brand);exit;
    }

    public function ajaxGetDlytmpls()
    {
        // 获取物流模板列表
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        return response::json($pagedata);exit;
    }

    // 批量更新商品的运费模板
    public function updateItemDlytmpl()
    {
        $postData = input::get();

        try
        {
            if(!$itemIds = $postData['itemids'])
            {
                $msg = app::get('topshop')->_('请至少选择一个商品！');
                return $this->splash('error',null,$msg, true);
            }
            if(!$dlytmpId = $postData['dlytmpl_id'])
            {
                $msg = app::get('topshop')->_('没有选择运费模板！');
                return $this->splash('error',null,$msg, true);
            }
            foreach($itemIds as $itemId)
            {
                if($itemId)
                {
                    app::get('topshop')->rpcCall('item.update.dlytmpl', array('item_id'=>intval($itemId), 'dlytmpl_id'=>intval($dlytmpId),'shop_id'=>intval($this->shopId)));
                }
            }
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('批量更新商品关联的运费模板。');
        $url = url::action('topshop_ctl_item@itemList');
        return $this->splash('success',$url,'更新运费模板成功', true);
    }

    /*updateItemPaidQuantity()
     * 函数说明：更改商品的销量
     * authorbyfanglongji
     * 2017-11-01
     */

    public function updateItemPaidQuantity()
    {
        $postData = input::get();
        $url = request::server('HTTP_REFERER');
        try
        {
            if(!$itemId=$postData['item']['item_id'])
            {
                $msg = app::get('topshop')->_('商品id不能为空');
                return $this->splash('error',null,$msg, true);
            }
            app::get('topshop')->rpcCall('item.update.PaidQuantity',array('item_id'=>intval($itemId),'paid_quantity'=>intval($postData['item']['paid_quantity']),'shop_id'=>$this->shopId));
        }
        catch(Exception $e)
        {
            return $this->splash('error',null, $e->getMessage(), true);
        }
        $this->sellerlog('修改商品销量。 商品ID是'.$itemId);
        return $this->splash('success',$url,'修改成功', true);
    }

    /* modifyItemPaidQuqntity()
     * 函数说明：更改商品的销量的弹出框
     * authorbyfanglongji
     * 2017-11-01
     */
    public function modifyItemPaidQuqntity()
    {
        $item_id = input::get('item_id');
        $params = array(
            'item_id' => $item_id,
            'fields' => 'title,paid_quantity,image_default_id,shop_id',
        );

        $pagedata['item'] = app::get('sysitem')->rpcCall('item.get',$params);
        return view::make('topshop/item/modify_paid_quantity.html', $pagedata);
    }

    /**
     * 搜索供应商
     */
    public function search_supplier()
    {
        $supplier = input::get('supplier');
        $default_action = input::get('default_action');
        $params = [
            'shop_id'=>$this->shopInfo['shop_id']
        ];
        $is_hm_supplier = $this->checkHuiminSupplierLogin();
        if($is_hm_supplier) {
            $params['supplier_id'] = $is_hm_supplier;
        }
        if(!empty($supplier))
        {
            $params['keyword'] = $supplier;
        }
        if($default_action)
        {
            $params['default_action'] = true;
        }
        if(empty($supplier))
        {
            $params['default_action'] = true;
        }
//        if(empty($supplier)){
//            return $this->splash('error','','请输入关键词',true);
//        }else{
//            $data = app::get('sysshoop')->rpcCall('supplier.shop.search',['keyword'=>$supplier,'shop_id'=>$this->shopInfo['shop_id']]);
//        dump($params);die;
        $params['is_audit']='PASS';
        $data = app::get('sysshoop')->rpcCall('supplier.shop.search',$params);
        $pagedata = array();
        $pagedata['data'] = $data;
        $pagedata['shopid']=$this->shopId;
		$pagedata['type']=input::get('type');
        $html = (string)view::make('topshop/item/supplier_search.html', $pagedata);
        return $this->splash('success','',$html,true);
//        }
    }

    /**
     * 线下店列表
     */
    public function agent_shop_list()
    {
        $pagedata = array();
        $supplier = input::get('supplier_id');
        $params = array();
        $params['shop_id'] = $this->shopId;
        $params['supplier_id'] = $supplier;
        try {
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.list', $params);
            $supplierList = app::get('topshop')->rpcCall('supplier.shop.list', ['shop_id'=>$this->shopId]);
            $supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:'.$e->getMessage();die;
        }
        foreach ($agentShopData['data'] as &$v){
            $v['supplier_name'] = $supplierList[$v['supplier_id']]['supplier_name'];
            if($v['type'] === 'HOME'){
                $v['type'] = '总店';
            }else {
                $v['type'] = '分店';
            }
        }
        $pagedata['data'] = $agentShopData;
        $html = (string)view::make('topshop/item/agent_shop_search.html',$pagedata);
        return $this->splash('success','',$html,true);
    }

    /**
     * 编辑代售商品
     * @return html
     * @auth xinyufeng
     */
    public function edit_agent()
    {
        $pagedata['return_to_url'] = request::server('HTTP_REFERER');
        $itemId = intval(input::get('item_id'));
        $pagedata['shopId'] = $this->shopId;

        // 店铺关联的商品品牌列表
        // 商品详细信息
        $params['item_id'] = $itemId;
        $params['shop_id'] = $this->shopId;
        $params['fields'] = "*,sku,item_store,item_status,item_count,item_desc,item_nature,spec_index";
        $pagedata['item'] = app::get('topshop')->rpcCall('item.get',$params);

        // 商家分类及此商品关联的分类标示selected
        $scparams['shop_id'] = $this->shopId;
        $scparams['fields'] = 'cat_id,cat_name,is_leaf,parent_id,level';
        $pagedata['shopCatList'] = app::get('topshop')->rpcCall('shop.cat.get',$scparams);
        $selectedShopCids = explode(',', $pagedata['item']['shop_cat_id']);
        foreach($pagedata['shopCatList'] as &$v)
        {
            if($v['children'])
            {
                foreach($v['children'] as &$vv)
                {
                    if(in_array($vv['cat_id'], $selectedShopCids))
                    {
                        $vv['selected'] = true;
                    }
                }
            }
            else
            {
                if(in_array($v['cat_id'], $selectedShopCids))
                {
                    $v['selected'] = true;
                }
            }
        }

        // 获取运费模板列表
        $tmpParams = array(
            'shop_id' => $this->shopId,
            'status' => 'on',
            'fields' => 'shop_id,name,template_id',
        );
        $pagedata['dlytmpls'] = app::get('topshop')->rpcCall('logistics.dlytmpl.get.list',$tmpParams);
        $pagedata['dlytmpls']['data'] = array_bind_key($pagedata['dlytmpls']['data'],'template_id');
        /*start whc_商铺获取银行卡信息_2017/9/7*/
        if($pagedata['item']['bank_ids']!=''){
            $pagedata['item']['bank_ids']=explode(',',$pagedata['item']['bank_ids']);
        }
        $pagedata['banks']=$this->getBanks();
        /*end*/

        // 收入类型列表
        $taxRateCfg=config::get('tax');
        $pagedata['taxrate']=$taxRateCfg;

        /*add_2017/9/23_by_wanghaichao_start 获取供应商信息*/
        $supplier_name = app::get('sysshop')->model('supplier')->getRow('supplier_name',['supplier_id'=>$pagedata['item']['supplier_id'],'is_audit'=>'PASS']);
        $pagedata['item']['supplier_name'] = $supplier_name['supplier_name'];
        /*add_2017/9/23_by_wanghaichao_end*/

        //获取线下店详情
        $agent_shop_ids = explode(',', $pagedata['item']['agent_shop_id']);
        $agent_shop_ids = array_filter($agent_shop_ids,function($v){
            return !empty($v);
        });
        $pagedata['item']['agent_shop_id'] = implode(',', $agent_shop_ids);
        if(is_array($agent_shop_ids)){
            $agentShopData = app::get('topshop')->rpcCall('supplier.agent.shop.search',['agent_shop_id'=>$agent_shop_ids]);
            $agentTemp = array();
            foreach ($agentShopData as $agent_k => $agent_v)
            {
                $agentTemp[] = $agent_v['name'];
            }
            $agent_shop_names = implode(',',$agentTemp);
        }else{
            $agent_shop_names = '';
        }
        $pagedata['item']['agent_shop_names'] = $agent_shop_names;
        $init_shop = app::get('sysshop')->model('shop')->getRow('offline', array('shop_id' => $pagedata['item']['init_shop_id']));
        $pagedata['offline'] = $init_shop['offline'];

        /*add_2017/9/24_by_wanghaichao_start*/
        $pagedata['valid_time']=date('Y/m/d H:i',$pagedata['item']['start_time']).'-'.date('Y/m/d H:i',$pagedata['item']['end_time']);
        if($pagedata['item']['sell_time']){
            $pagedata['sell_time']=date('Y/m/d H:i',$pagedata['item']['sell_time']);
        }
        if($pagedata['item']['sell_time_end']){
            $pagedata['sell_time_end']=date('Y/m/d H:i',$pagedata['item']['sell_time_end']);
        }
        /*add_2017/9/24_by_wanghaichao_end*/

        /*add_2018/6/14_by_王衍生_start*/
        if( $pagedata['item']['video_dir'] )
        {
            unset($params);
            $params['url'] = $pagedata['item']['video_dir'];
            $params['fields'] = '*';

            $video_url_arr = app::get('topshop')->rpcCall('video.item.list', $params);
            // if 为兼容早期视频没有入视频表的视频
            if(!$video_url_arr){
                $video_url_arr[0]['url'] = $pagedata['item']['video_dir'];
            }
            $pagedata['item']['video_dir'] = $video_url_arr;
        }
        /*add_2018/6/14_by_王衍生_end*/

        $this->contentHeaderTitle = app::get('topshop')->_('编辑代售商品');

        return $this->page('topshop/item/edit_agent.html', $pagedata);
    }

    /**
     * 保存代售商品
     * @return string
     * @auth xinyufeng
     */
    public function storeItemAgent()
    {
        $postData = input::get();

        try
        {
            // 检查参数
            $this->_checkPost($postData);
            $itemFilter = array(
                'item_id' => $postData['item']['item_id'],
                'shop_id' => $this->shopId,
            );
            $itemData = array(
                'price' => $postData['item']['price'],  // 销售价
                'dlytmpl_id' => $postData['item']['dlytmpl_id'],  // 运费模板id
                'shop_cat_id' => ','.implode(',', $postData['item']['shop_cids']).',', // 店铺中分类
            );
            $item_res = app::get('sysitem')->model('item')->update($itemData, $itemFilter);
            // 下架商品
            $statusData = array(
                'approve_status' => 'instock',
                'delist_time' => time(),
            );
            app::get('sysitem')->model('item_status')->update($statusData, $itemFilter);

            if($item_res && !empty($postData['item']['sku']))
            {
                $sku_obj = json_decode($postData['item']['sku']);
                $skuMdl = app::get('sysitem')->model('sku');
                if(count($sku_obj) == 1)
                {
                    $sku_info = reset($sku_obj);
                    $skuFilter = array(
                        'sku_id' => $sku_info->sku_id,
                        'item_id' => $sku_info->item_id,
                        'shop_id' => $sku_info->shop_id,
                    );
                    $skuData = array(
                        'price' => $postData['item']['price'],
                    );
                    $sku_res = $skuMdl->update($skuData, $skuFilter);
                    unset($skuData, $skuFilter);
                }
                else
                {
                    foreach ($sku_obj as $sku_o)
                    {
                        $skuFilter = array(
                            'sku_id' => $sku_o->sku_id,
                            'item_id' => $sku_o->item_id,
                            'shop_id' => $sku_o->shop_id,
                        );
                        $skuData = array(
                            'price' => $sku_o->price,
                        );
                        $sku_res = $skuMdl->update($skuData, $skuFilter);
                        unset($skuData, $skuFilter);
                    }
                }

                $this->sellerlog('保存商品。名称是'.$postData['title']);
                $url = input::get('return_to_url') ? : url::action('topshop_ctl_item@itemList');
                $msg = app::get('topshop')->_('保存成功');

                return $this->splash('success', $url, $msg, true);
            }
        }
        catch (Exception $e)
        {
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }

	/* action_name (par1, par2, par3)
	* ajax用来返回选择供货商的html
	* author by wanghaichao
	* date 2018/10/10
	*/
	public function modeSupplier(){
        $supplier = input::get('supplier');
        $default_action = input::get('default_action');
        $params = [
            'shop_id'=>$this->shopInfo['shop_id']
        ];
        if(!empty($supplier))
        {
            $params['keyword'] = $supplier;
        }


		$params['default_action'] = true;
		$params['is_audit']='PASS';
        $data = app::get('sysshoop')->rpcCall('supplier.shop.search',$params);
        $pagedata = array();
        $pagedata['data'] = $data;
        $pagedata['shopid']=$this->shopId;
		$pagedata['supplier_id']=input::get('supplier_id');
		$pagedata['key']=input::get('key');
		return view::make('topshop/item/modesupplier.html', $pagedata);
	}

    /**
     * @return string
     * 同步金蝶库存
     */
	public function ajaxSyncKingdeeInventory()
    {
        $item_id = input::get('item_id');
        try
        {
            kernel::single('sysclearing_kingdeeCurrentStock')->singleSync($item_id);
        }
        catch(Exception $e) {
            return $this->splash('error', null, $e->getMessage(), true);
        }
        return $this->splash('success', null, '同步成功', true);
    }
}


