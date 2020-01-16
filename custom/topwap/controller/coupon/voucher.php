<?php
/**
 *  王海超 2017/7/28  start 
 *  虚拟商品卡券控制器
 *  
 */

class topwap_ctl_coupon_voucher extends topwap_ctl_member{

    public $voucherStatus = array(
		'0' => 'WAIT_WRITE_OFF',    //待核销
		'1' => 'WRITE_FINISHED',    //已核销
		'2' => 'GIVEN',      		//已赠送
		'3' => 'SUCCESS',      		//已退款
		'4'=> 'HAS_OVERDUE',        //已过期
    );

	//获取卡券的
	public function index(){
        $filter = input::get();
		$filter['s']='0';
        $pagedata = $this->__getVoucher($filter);
		$pagedata['app']=isset($_GET['app'])?$_GET['app']:'';
        if($filter['keyword']){
            $pagedata['keyword']=$filter['keyword'];
        }
		$pagedata['status']=0;
		$pagedata['confirm_type'] = $filter['confirm_type'] ? $filter['confirm_type'] : 0;
        return $this->page('topwap/coupon/voucher/list.html', $pagedata);
	}

    public function voucherList()
    {
        $filter = input::get();
        $pagedata = $this->__getVoucher($filter);
        if($filter['keyword']){
            $pagedata['keyword']=$filter['keyword'];
        }
        $pagedata['state']=$filter['s'];
		$pagedata['app']=isset($_GET['app'])?$_GET['app']:'';
        return $this->page('topwap/coupon/voucher/list.html', $pagedata);
    }



	//获取卡券列表的
	public function __getVoucher_old($postdata){
		if(isset($this->voucherStatus[$postdata['s']]))
        {
			$status=$this->voucherStatus[$postdata['s']];
        }else{
			$staus="";
		}
        $params['status'] = $status;
        $params['user_id'] = userAuth::id();
        $params['page_no'] = intval($postdata['pages']) ? intval($postdata['pages']) : 1;
        $params['page_size'] = intval($this->limit);
        $params['order_by'] = 'careated_time desc';
        $params['fields'] = '*';
        $voucherlist = app::get('topwap')->rpcCall('voucher.get.list',$params);
        $count = $voucherlist['count'];
        $voucherlist = $voucherlist['list'];

        foreach( $voucherlist as $key=>$row)
        {
            $voucherlist[$key]['is_buyer_rate'] = false;

            foreach( $row['order'] as $orderListData )
            {
                if( $row['buyer_rate'] == '0' && $row['status'] == 'TRADE_FINISHED' )
                {
                    $voucherlist[$key]['is_buyer_rate'] = true;
                    break;
                }
            }

            unset($voucherlist[$key]['order']);
            $voucherlist[$key]['order'][0] = $row['order'];

            if( !$voucherlist[$key]['is_buyer_rate'] && $postdata['s'] == 4 )
            {
                unset($voucherlist[$key]);
            }

            /*add_start_gurundong_2017/11/3*/
            //获取商品图片
            $item_where = [
                'shop_id'=>$row['shop_id'],
                'item_id'=>$row['item_id'],
                'fields'=>[
                    'rows'=>'image_default_id,title'
                ]
            ];
            $item_info = app::get('topwap')->rpccall('item.get',$item_where);
            $voucherlist[$key]['pic_path'] = base_storager::modifier($item_info['image_default_id']);
            /*add_end_gurundong_2017/11/3*/
        }

        $pagedata['vouchers'] = $voucherlist;
        $pagedata['pagers'] = $this->__pages($postdata['pages'],$postdata,$count);
        $pagedata['count'] = $count;
        $pagedata['title'] = "卡券";  //标题
        $pagedata['status'] =$postdata['s'];  //状态
        return $pagedata;
		
	}

	/* action_name (par1, par2, par3)
	* 新的获取卡券的逻辑
	* author by wanghaichao
	* date 2017/11/23 
	*/
	public function __getVoucher($postdata){
		if(isset($this->voucherStatus[$postdata['s']]))
        {
			$status=$this->voucherStatus[$postdata['s']];
        }else{
            $status="";
		}
        $params['status'] = $status;
        $params['confirm_type'] = trim($postdata['confirm_type']);
        $params['user_id'] = userAuth::id();
        $params['page_no'] = intval($postdata['pages']) ? intval($postdata['pages']) : 1;
        $params['page_size'] = intval($this->limit);
        $params['order_by'] = 'careated_time desc';
        $params['fields'] = '*';
        $voucherlist = app::get('topwap')->rpcCall('voucher.get.list',$params);
        $count = $voucherlist['count'];
        $voucherlist = $voucherlist['list'];
        foreach( $voucherlist as $key=>$row)
        {
            //获取商品图片
            $item_where = [
                'shop_id'=>$row['shop_id'],
                'item_id'=>$row['item_id'],
                'fields'=>[
                    'rows'=>'image_default_id,title,is_ticket,confirm_type'
                ]
            ];
            $item_info = app::get('topwap')->rpccall('item.get',$item_where);
            $voucherlist[$key]['pic_path'] = base_storager::modifier($item_info['image_default_id']);
			
			/*modify_2018/10/11_by_wanghaichao_start*/
			/*	
			$voucherlist[$key]['item_title']=$item_info['title'];
			*/
			//加入判断,当是套票的时候
			if($item_info['is_ticket']==1){
				$ticket=app::get('sysitem')->model('ticket')->getRow('title',array('id'=>$row['ticket_id']));
				$voucherlist[$key]['item_title']=$ticket['title'];
			}else{
				$voucherlist[$key]['item_title']=$item_info['title'];
			}
			
			/*modify_2018/10/11_by_wanghaichao_end*/
			
			$shop= app::get('topwap')->rpcCall('shop.get',array('fields'=>'shop_mold,shop_name','shop_id'=>$row['shop_id']));
			if($shop['shop_mold']=='tv'){
				$voucherlist[$key]['mold_class']='icon_small_tv';
			}elseif($shop['shop_mold']=='broadcast'){
				$voucherlist[$key]['mold_class']='icon_fm101';
			}else{
				$voucherlist[$key]['mold_class']='icon_other_tv';
			}
			$voucherlist[$key]['shop_name']=$shop['shop_name'];
			$voucherlist[$key]['end_time']=date('Y.m.d',$row['end_time']);
			$orderInfo=app::get('systrade')->model('order')->getRow('spec_nature_info',array('oid'=>$row['oid']));
			$voucherlist[$key]['spec_nature_info']=$orderInfo['spec_nature_info'];
			$voucherlist[$key]['confirm_type']=$item_info['confirm_type'];
        }
		//echo "<pre>";print_r($voucherlist);die();
        $pagedata['vouchers'] = $voucherlist;
        $pagedata['pagers'] = $this->__pages($postdata['pages'],$postdata,$count);
        $pagedata['count'] = $count;
        $pagedata['title'] = "卡券";  //标题
        $pagedata['status'] =$postdata['s'];  //状态
		$pagedata['signPackage']=$this->getWxINfo();
        return $pagedata;
	}



    /**
     * @brief 订单详情
     *
     * @return
     */
    public function detail()
    {
        $this->setLayoutFlag('vocher_detail');
        $params['oid'] = input::get('oid');
        $params['user_id'] = userAuth::id();
        $pagedata=$this->getData($params);
        //获取发货信息
        //$pagedata['logi'] = app::get('topwap')->rpcCall('delivery.get',array('tid'=>$params['tid']));
        $pagedata['title'] = "卡券详情";  //标题
        //$pagedata['point_rate'] = app::get('topc')->rpcCall('point.setting.get',['field'=>'point.deduction.rate']);
        return $this->page('topwap/coupon/voucher/detail.html', $pagedata);
    }


	private function getData($params){
		$voucher=app::get('systrade')->model('voucher');   //卡券
		$orderObj=app::get('systrade')->model('order');    //子订单 
		$supplier=app::get('sysshop')->model('supplier');  //供应商
		
		//取出卡券的列表
		$voucher_list=$voucher->getList('voucher_id,voucher_code,supplier_id,status,start_time,end_time',$params);
		/*add_2017/10/20_by_wanghaichao_start*/
		if(!$voucher_list){
			$voucher_list=app::get('systrade')->model('voucher_history')->getList('voucher_id,voucher_code,supplier_id,status,start_time,end_time',$params);
			//$type=
		}
		/*add_2017/10/20_by_wanghaichao_end*/
		foreach($voucher_list as $k=>$v){
			$data['start_time']=date('Y-m-d',$v['start_time']);
			$data['end_time']=date('Y-m-d',$v['end_time']);
		}
		//取出订单数据
		$order=$orderObj->getRow('oid,tid,shop_id,item_id,title,price,num,pic_path,supplier_id,payment',$params);
		/*add_2017/10/20_by_wanghaichao_start*/
		if(!$order){
			$order=app::get('systrade')->model('order_history')->getRow('oid,tid,shop_id,item_id,title,price,num,pic_path,supplier_id,payment',$params);
		}
		/*add_2017/10/20_by_wanghaichao_end*/

        /*add_start_gurundong_2017/11/3*/
        //获取商品图片
        $item_where = [
            'shop_id'=>$order['shop_id'],
            'item_id'=>$order['item_id'],
            'fields'=>[
                'rows'=>'image_default_id,confirm_type'
            ]
        ];
        $item_info = app::get('topwap')->rpccall('item.get',$item_where);
        $order['pic_path'] = $item_info['image_default_id'];
		$order['confirm_type']=$item_info['confirm_type'];
        /*add_end_gurundong_2017/11/3*/

		//取出供应商的信息
		$supplier=$supplier->getRow('supplier_name,supplier_id,company_name',array('supplier_id'=>$order['supplier_id'],'is_audit'=>'PASS'));
		//取出已经使用的张数
		$params['status']="WRITE_FINISHED";  //已经核销的
		$voucher_use=$voucher->getList('voucher_id',$params);
		/*add_2017/10/20_by_wanghaichao_start*/
		if(empty($voucher_use)){	
			$voucher_use=app::get('systrade')->model('voucher_history')->getList('voucher_id',$params);
		}
		/*add_2017/10/20_by_wanghaichao_end*/
		$data['voucher_list']=$voucher_list;
		$data['order']=$order;
		$data['supplier']=$supplier;
		$data['voucher_use']=count($voucher_use);
		return $data;
	}

	public function getewm(){
		$voucher_id=input::get('voucher_id');
		$code=app::get('systrade')->model('voucher')->getRow('code',array('voucher_id'=>$voucher_id));
		/*add_2017/10/20_by_wanghaichao_start*/		
		if(empty($code)){
			$code=app::get('systrade')->model('voucher_history')->getRow('code',array('voucher_id'=>$voucher_id));
		}
		/*add_2017/10/20_by_wanghaichao_end*/
		$ewm=base_storager::modifier($code['code']);
		return $this->splash('success','',$ewm,true);
	}

    /**
     * 分页处理
     * @param int $current 当前页
     *
     * @return $pagers
     */
    private function __pages($current,$filter,$count)
    {
        //处理翻页数据
        $current = ($current && $current <= 100 ) ? $current : 1;

        if( $count > 0 ) $totalPage = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topwap_ctl_coupon_voucher@ajaxVoucherShow',$filter),
            'current'=>intval($current),
            'total'=>intval($totalPage),
        );
        return $pagers;
    }


    public function ajaxVoucherShow()
    {
        $postdata = input::get();
        $pagedata = $this->__getVoucher($postdata);
		if($pagedata['vouchers']){
			$data['html'] = view::make('topwap/coupon/voucher/listitem.html',$pagedata)->render();
		}else{
            $data['html'] = view::make('topwap/empty/trade-voucher.html',$pagedata)->render();
		}
		$pagedata['app']=$postdata['app'];
        $data['pagers'] = $pagedata['pagers'];
        $data['success'] = true;
        return response::json($data);exit;
    }
	
	/* action_name (par1, par2, par3)
	* 转赠其他人逻辑
	* author by wanghaichao
	* date 2017/11/20
	*/
	public function giveVoucher(){
		$voucher_id=input::get('voucher_id');
		$filter['voucher_id']=$voucher_id;
		$filter['user_id']=userAuth::id();
		$voucherObj=app::get('systrade')->model('voucher');
		$voucher=$voucherObj->getRow('status',$filter);
		if(!$voucher || $voucher['status']!='WAIT_WRITE_OFF'){
			return $this->splash('error', null, '该卡券不能赠送给别人', true);
		}
		if($voucher_id){
			$res=$voucherObj->update(array('status'=>'GIVING'),$filter); //赠送中
		}
		if($res){
			return $this->splash('success', null, '赠送成功!', true);
		}else{
			return $this->splash('error', null, '赠送失败!', true);
		}
	}
	
	/* action_name (par1, par2, par3)
	* 撤销赠送的逻辑
	* author by wanghaichao
	* date 2017/11/24
	*/
	public function revokeVoucher(){
		$voucher_id=input::get('voucher_id');
		$filter['voucher_id']=$voucher_id;
		$filter['user_id']=userAuth::id();
		$voucherObj=app::get('systrade')->model('voucher');
		$voucher=$voucherObj->getRow('status',$filter);
		if(!$voucher || $voucher['status']!='GIVING'){
			return $this->splash('error', null, '该卡券不能被撤销', true);
		}
		if($voucher_id){
			$res=$voucherObj->update(array('status'=>'WAIT_WRITE_OFF'),$filter); //赠送中
		}
		if($res){
			return $this->splash('success', null, '撤销成功!', true);
		}else{
			return $this->splash('success', null, '赠送失败!', true);
		}
	}
	

	public function getWxINfo()
	{
		$appId = app::get('site')->getConf('site.appId');
		$appsecret = app::get('site')->getConf('site.appSecret');
		$timestamp = time();
		$jsapi_ticket = $this->make_ticket($appId,$appsecret);
		$nonceStr = $this->make_nonceStr();
		//if (strstr($_GET['url'],'activity-itemdetail')) {
		//	$url = $_GET['url'].'&g='.$_GET['g'];
		//}else{
		//	$url = $_GET['url'];
		//}
		
		$http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';

		$url = $http_type.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$signature = $this->make_signature($nonceStr,$timestamp,$jsapi_ticket,$url);

		$signPackage = array(
			"appId"     => $appId,
			"appsecret" => $appsecret,
			"nonceStr"  => $nonceStr,
			"timestamp" => $timestamp,
			"signature" => $signature,
		);

		return $signPackage;

	}

	public function make_nonceStr()
	{
		$codeSet = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		for ($i = 0; $i<16; $i++) {
			$codes[$i] = $codeSet[mt_rand(0, strlen($codeSet)-1)];
		}
		$nonceStr = implode($codes);
		return $nonceStr;
	}

	public function make_signature($nonceStr,$timestamp,$jsapi_ticket,$url)
	{
		$tmpArr = array(
		'noncestr' => $nonceStr,
		'timestamp' => $timestamp,
		'jsapi_ticket' => $jsapi_ticket,
		'url' => $url
		);
		ksort($tmpArr, SORT_STRING);
		$string1 = http_build_query( $tmpArr );
		$string1 = urldecode( $string1 );
		$signature = sha1( $string1 );
		return $signature;
	}

	public function make_ticket($appId,$appsecret)
	{
		// access_token 应该全局存储与更新，以下代码以写入到文件中做示例
		$data = json_decode(file_get_contents(DATA_DIR."/wxshare/access_token.json"));
		if (!is_dir(DATA_DIR.'/wxshare')) {
			mkdir(DATA_DIR.'/wxshare', 0755, true);
		}
		if ($data->expire_time < time()) {
			$TOKEN_URL="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$appId."&secret=".$appsecret;
			$json = file_get_contents($TOKEN_URL);
			$result = json_decode($json,true);
			$access_token = $result['access_token'];
			if ($access_token) {
				$data->expire_time = time() + 7000;
				$data->access_token = $access_token;
				$fp = fopen(DATA_DIR."/wxshare/access_token.json", "w");
				fwrite($fp, json_encode($data));
				fclose($fp);
			}
		}else{
			$access_token = $data->access_token;
		}

		// jsapi_ticket 应该全局存储与更新，以下代码以写入到文件中做示例
		$data = json_decode(file_get_contents(DATA_DIR."/wxshare/jsapi_ticket.json"));
		if ($data->expire_time < time()) {
			$ticket_URL="https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=".$access_token."&type=jsapi";
			$json = file_get_contents($ticket_URL);
			$result = json_decode($json,true);
			$ticket = $result['ticket'];
			if ($ticket) {
				$data->expire_time = time() + 7000;
				$data->jsapi_ticket = $ticket;
				$fp = fopen(DATA_DIR."/wxshare/jsapi_ticket.json", "w");
				fwrite($fp, json_encode($data));
				fclose($fp);
			}
		}else{
			$ticket = $data->jsapi_ticket;
		}
		return $ticket;
	}




}