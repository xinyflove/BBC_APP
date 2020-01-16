<?php
/**
 * User: xinyufeng
 * Time: 2018-10-25 10:30
 * Desc: 广电优选商品列表
 */
class topshop_ctl_mall_maker extends topshop_controller {

	public $limit=20;
	/* action_name (par1, par2, par3)
	* 创客结算列表
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function index(){
        $this->contentHeaderTitle = app::get('topshop')->_('创客提现列表');

        $postdata=input::get();
        $params['shop_id']=$this->shopId;
		$params['page_size']=$this->limit;
		if($postdata['seller_id']){
			$params['seller_id']=$postdata['seller_id'];
			$pagedata['seller_id']=$postdata['seller_id'];
		}
		if($postdata['seller_name']){
			$params['seller_name']=$postdata['seller_name'];
			$pagedata['seller_name']=$postdata['seller_name'];
		}
		if($postdata['status']){
			$params['status']=$postdata['status'];
			$pagedata['status']=$postdata['status'];
		}

		if($postdata['cart_number']){
			$params['cart_number']=strtoupper($postdata['cart_number']);
			$pagedata['cart_number']=$postdata['cart_number'];
		}
		$params['page_no']=$postdata['pages']?$postdata['pages']:1;
        $data = app::get('topshop')->rpcCall('mall.seller.cash.list', $params);
		// echo "<pre>";print_r($data);die();
		$pageTotal=ceil($data['total_found']/$this->limit);
		$postdata['pages']=time();
        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_maker@index',$postdata),
            'current'=>$data['currentPage'],
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['paymentPendingCount'] = $data['paymentPendingCount'];
        $pagedata['paymentSuccessCount'] = $data['paymentSuccessCount'];
        $pagedata['data'] = $data['list'];
		$pagedata['count']=$data['total_found'];
		$pagedata['pagers']=$pagers;
		$pagedata['shop_id']=$this->shopId;
        return $this->page('topshop/mall/maker/list.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 佣金提现记录
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function cash(){
		$shop_id=$this->shopId;
		$were='a.shop_id='.$shop_id.' and a.deleted=0';
        //分页查询
        $listsBuilder=db::connection()->createQueryBuilder();
        $sellerList = $listsBuilder->select('a.seller_id,b.name')
            ->from('sysmaker_shop_rel_seller', 'a')
            ->where($were)
            ->leftjoin('a', 'sysmaker_seller', 'b', 'b.seller_id = a.seller_id')
            //->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])
            ->execute()->fetchAll();
		$pagedata['seller']=$sellerList;
		$pagedata['seller_id']=input::get('seller_id');
        return $this->page('topshop/mall/maker/add.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 保存创客提现佣金
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function save(){
		$shop_id=$this->shopId;
		$postdata=input::get();
		$seller_id=$postdata['seller_id'];
		if($postdata['payment']<=0 && $shop_id!=46){
            return $this->splash('error','','提现金额不能小于0',true);
		}
		//创客一共有的佣金
		if($shop_id==46){
			$seller_commission=app::get('sysclearing')->model('seller_settlement_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		}else{
			$seller_commission=app::get('sysclearing')->model('seller_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		}
		//创客已提现过的佣金
		$has_commission=app::get('sysmaker')->model('cash')->getRow('SUM(payment) as has_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id,'status|in'=>array('success','pending')));
		//剩余的佣金
		$sy_commission=$seller_commission['total_commission']-$has_commission['has_commission'];
		if($postdata['payment']>$sy_commission){
            return $this->splash('error','','该创客剩余佣金为:'.$sy_commission.'元,结算金额不能大于剩余佣金',true);
		}
		$bankinfo=app::get('sysmaker')->model('seller_bank')->getRow('bank_name,card_number',array('seller_id'=>$seller_id));
		if($bankinfo){
			$data['bank_name']=$bankinfo['bank_name'];
			$data['card_number']=$bankinfo['card_number'];
		}
		$data['payment']=$postdata['payment'];
		$data['seller_id']=$postdata['seller_id'];
		$data['shop_id']=$shop_id;
		$data['remark']=$postdata['remark'];
		$data['status']='success';
		$data['type']='sys';
		$data['create_time']=time();
		$res=app::get('sysmaker')->model('cash')->insert($data);
		if($res){
			$log="创客{$seller_id}提现{$postdata['payment']}元";
            $this->sellerlog($log);
			$url=url::action('topshop_ctl_mall_maker@index');
			return $this->splash('success',$url,'提现成功!',true);
		}

	}

	/* action_name (par1, par2, par3)
	* 本店铺创客列表
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function mlist(){
		$this->contentHeaderTitle = app::get('topshop')->_('创客列表');

        $params=input::get();
       //$params['shop_id']=$this->shopId;
        $builderWhere="a.shop_id=".$this->shopId.' and a.deleted=0 and c.disabled=0 and c.deleted=0 and c.status="success"';
        //记录总数

		if($params['name']){
			$builderWhere.=" and b.name like '%".$params['name']."%'";
			$pagedata['name']=$params['name'];
		}
		if($params['mobile']){
			$builderWhere.=" and b.mobile like '%".$params['mobile']."%'";
			$pagedata['mobile']=$params['mobile'];
		}
		if($params['id_card_no']){
			$builderWhere.=" and b.id_card_no like '%".$params['id_card_no']."%'";
			$pagedata['id_card_no']=$params['id_card_no'];
		}
		if($params['cart_number']){
			$builderWhere.=" and b.cart_number like '%".$params['cart_number']."%'";
			$pagedata['cart_number']=$params['cart_number'];
		}
		if($params['pname']){
			$sellerModel = app::get('sysmaker')->model('seller');
			$_pids = $sellerModel->getList('seller_id', array('name|has'=>$params['pname']));
			$_pid_arr[] = 0;
			if($_pids)
			{
				foreach ($_pids as $v)
				{
					$_pid_arr[] = $v['seller_id'];
				}
			}
			$builderWhere.=" and b.pid in (".implode(',', $_pid_arr).")";
			$pagedata['pname']=$params['pname'];
		}
		if($params['pmobile']){
			$sellerModel = app::get('sysmaker')->model('seller');
			$_pids = $sellerModel->getList('seller_id', array('mobile|has'=>$params['pmobile']));
			$_pid_arr[] = 0;
			if($_pids)
			{
				foreach ($_pids as $v)
				{
					$_pid_arr[] = $v['seller_id'];
				}
			}
			$builderWhere.=" and b.pid in (".implode(',', $_pid_arr).")";
			$pagedata['pmobile']=$params['pmobile'];
		}
		if ($params['status']){
			$builderWhere.=" and a.status='".$params['status']."'";
			$pagedata['status']=$params['status'];
		}
        $countBuilder = db::connection()->createQueryBuilder();
        $count=$countBuilder->select('count(a.seller_id)')
            ->from('sysmaker_shop_rel_seller', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
			->leftjoin('b', 'sysmaker_account', 'c', 'b.seller_id = c.seller_id')
            ->where($builderWhere)
            ->execute()->fetchColumn();


        $page=$params['pages']?$params['pages']:1;

        $limit=$this->limit;
        $pageTotal=ceil($count/$limit);
        $currentPage =($pageTotal < $page) ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $params['pages']=time();

		if($count==0){
			$list=array();
		}else{

			//分页查询
			$listsBuilder=db::connection()->createQueryBuilder();
			$list = $listsBuilder->select('a.*,b.name,b.id_card_no,b.registered,b.pid,b.mobile,b.cart_number')
				->from('sysmaker_shop_rel_seller', 'a')
				->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
				->leftjoin('b', 'sysmaker_account', 'c', 'b.seller_id = c.seller_id')
				->where($builderWhere)
				->setFirstResult($offset)
				->setMaxResults($limit)
				->orderBy('a.created_time', 'DESC')
				//->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])   //orderby先去掉,有需要再加上
				->execute()->fetchAll();

		}
		if($list)
		{
			$objSeller = kernel::single('sysmaker_data_seller');
			foreach ($list as &$v)
			{
				$v['id_card_no'] || $v['id_card_no'] = '无';
				if($v['registered'])
				{
					$_addr = explode(':', $v['registered']);
					$v['registered'] = $_addr[0];
				}
				else
				{
					$v['registered'] = '无';
				}
				$v['pid'] || $v['pid'] = '';
				if($v['pid'])
				{
					$v['pname'] = $objSeller->getPName($v['pid']);
				}
			}
			unset($v);
		}

        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_maker@mlist',$params),
            'current'=>$currentPage,
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['data'] = $list;
        $pagedata['shopInfo'] = $this->shopInfo;
        $pagedata['pagers']=$pagers;
		$pagedata['count']=$count;
		//echo "<pre>";print_r($pagedata);die();
        return $this->page('topshop/mall/maker/mlist.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 创客审核逻辑
	* author by wanghaichao
	* date 2018/11/16
	*/
	public function audit(){
		$data=input::get();
		if(empty($data['seller_id'])){
			return $this->splash('error','','请选择要审核的创客',true);
		}
		if($data['status']=='refuse' && empty($data['reason'])){
			return $this->splash('error','','请填写拒绝原因',true);
		}
		$data['shop_id']=$this->shopId;
		$res=app::get('sysmaker')->model('shop_rel_seller')->save($data);
		if(res){
			return $this->splash('success','','审核成功',true);
		}else{
			return $this->splash('error','','审核失败',true);
		}
	}

	/* action_name (par1, par2, par3)
	* 删除创客
	* author by wanghaichao
	* date 2018/11/16
	*/
	public function mdelete(){
		$data['seller_id']=input::get('seller_id');
		$data['shop_id']=$this->shopId;
		$data['deleted']=1;
		app::get('sysmaker')->model('shop_rel_seller')->save($data);
		return $this->splash('succes','','删除成功',true);
	}

	/**
	* 创客二维码下载
	* author by wanghaichao
	* date 2019/7/31
	*/
	public function qrcode(){
		$seller_id=input::get('seller_id');
		$seller=app::get('sysmaker')->model('seller')->getRow('name', array('seller_id'=>$seller_id));
        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId(),'fields'=>'qr_bg'));
		if(!empty($shopdata['qr_bg'])){
			$params['bg_img']=$shopdata['qr_bg'];
		}else{
			$params['bg_img']='../public/app/topmaker/statics/images/bg.png';
		}
		$params['qr_code']=$this->__qrCode($seller_id,$params['bg_img']);
		//$imageInfo = getimagesize($params['bg_img']);
		//var_dump($imageInfo);die();
		//$params['bg_img']=$this->toBase64($params['bg_img']);
		//print_r($params['bg_img']);die();
		//$params['bg_img']='../public/app/topwap/statics/images/couponbg.png';
		//$params['bg_img']="/images/shareimg/3_78.png";
		//echo "<pre>";print_r($params);die();
		//$params['bg_img']='../public/images/bg.png';
		//$res=app::get('topshop')->rpcCall('seller.qrcode',$params);		   //处理图片接口
		//echo "<pre>";var_dump($res);die();
		$imgurl="../public/images/seller/{$seller_id}_code.png";  //图片路径
		//file_put_contents($imgurl,$res);   //上传至服务器

		header( "Content-Disposition:  attachment;  filename={$seller['name']}-邀请二维码.png"); //告诉浏览器通过附件形式来处理文件
		header('Content-Length: ' . filesize($imgurl)); //下载文件大小
		readfile($imgurl);
	}

	/**
	* 生成二维码的
	* author by wanghaichao
	* date 2019/7/31
	*/
	private function __qrCode($seller_id,$bgImg)
    {
        $url = url::action("topwap_ctl_maker_index@tickethome",array('seller_id'=>$seller_id));
		//echo "<pre>";print_r($url);die();
		$code_url="../public/images/seller/{$seller_id}_code.png";
		if(is_file($code_url)){
			unlink("../public/images/seller/{$seller_id}_code.png");
		}
		$result= getQrcodeUri($url,430, 10);
		$code_url="../public/images/seller/{$seller_id}_code.png";
		copy($result,$code_url);   //上传至服务器
		$logo='../public/app/topmaker/statics/images/logo.png';
		if($logo){
			$QR = imagecreatefromstring(file_get_contents( $code_url ));
			$logo = imagecreatefromstring(file_get_contents($logo));
			$QR_width = imagesx($QR);//二维码图片宽度
			$QR_height = imagesy($QR);//二维码图片高度
			$logo_width = imagesx($logo);//logo图片宽度
			$logo_height = imagesy($logo);//logo图片高度
			$logo_qr_width = $QR_width / 5;
			$scale = $logo_width/$logo_qr_width;
			$logo_qr_height = $logo_height/$scale;
			$from_width = ($QR_width - $logo_qr_width) / 2;//重新组合图片并调整大小
			imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width,$logo_qr_height, $logo_width, $logo_height);//输出图片

			imagepng($QR, $code_url);
		}

		$bgImg = '../public/app/topmaker/statics/images/bg.png';
		$backgroupImg = imagecreatefromstring(file_get_contents($bgImg));
		$newQR = imagecreatefromstring(file_get_contents($code_url));
		//获取新的尺寸
		list($width, $height) = getimagesize($code_url);
		$new_width = 430;
		$new_height = 430;
		//重新组合图片并调整大小
		imagecopyresampled($backgroupImg,$newQR,110, 339, 0, 0,$new_width, $new_height, $width, $height);//输出图片
		imagepng($backgroupImg, $code_url);

		return "../public/images/seller/{$seller_id}_code.png";
    }

	/**
	* 创客提现审核通过的
	* author by wanghaichao
	* date 2019/8/1
	*/
	public function cashAudit(){

        try{
            $cashModel = app::get('sysmaker')->model('cash');
            $data=input::get();
            if(empty($data['id'])){
                throw new \LogicException("请选择要审核的记录");
            }
            if($data['status']=='refuse' && empty($data['remark'])){
                throw new \LogicException("请输入拒绝原因");
            }

            if(($data['status'] == 'success') && empty($data['settlementType'])){
                throw new \LogicException("请选择打款方式");
            }

            if($data['status'] == 'refuse'){
                $update = [
                    'status' => $data['status'],
                    'remark' => $data['remark'],
                    'check_time' => time(),
                    // 'pay_time' => time(),
                ];
                $cashModel->update($update, ['id' => $data['id'], 'shop_id' => $this->shopId]);
                return $this->splash('success', '', '操作成功', true);

            }else if($data['status'] == 'success' ){
                // 1 线上付款到微信钱包结算
                if($data['settlementType'] == 1){
                    $cashPaymentModel = app::get('sysmaker')->model('cash_payment');
                    $cashPayment = $cashPaymentModel->getRow("id,status", ['cash_id' => $data['id'], 'shop_id' => $this->shopId]);
                    if($cashPayment && ($cashPayment['status'] == 'success')){
                        throw new \LogicException("此申请单已打款成功，打款单号{$cashPayment['id']}");
                    }
                    $cash = $cashModel->getRow('seller_id,openid,payment', ['id' => $data['id'], 'shop_id' => $this->shopId]);
                    $sellerModel = app::get('sysmaker')->model('seller');
                    $seller = $sellerModel->getRow('name', ['seller_id' => $cash['seller_id']]);

                    $shop_info = app::get('ectools')->rpcCall('shop.get', ['shop_id' => $this->shopId]);
                    $shop_payment = unserialize($shop_info['payment']);

                    if($shop_payment['wxEnterprisePayment']['open'] != 1){
                        throw new \LogicException('请先到支付方式管理页面，配置并开启“微信企业付款到个人钱包”');
                    }

                    $payment = [];
                    $payment['trade_no'] = $this->getCasgPaymentId();
                    $payment['mch_id'] = $shop_payment['wxEnterprisePayment']['mchid'];
                    $payment['mer_key'] = $shop_payment['wxEnterprisePayment']['key'];
                    $payment['amount'] = $cash['payment'];
                    $payment['openid'] = $cash['openid'];
                    $payment['user_name'] = $seller['name'];
                    $payment['desc'] = '创客佣金提现';
                    $payment['cert'] = [
                        'apiclient_cert_file' => $shop_payment['wxEnterprisePayment']['apiclient_cert_file'],
                        'apiclient_key_file' => $shop_payment['wxEnterprisePayment']['apiclient_key_file'],
                    ];
                    // jj($payment);
                    $pay_res = kernel::single('ectools_payment_plugin_wxservicepayapi')->enterprisePayment($payment);

                    $cashPaymentModel = app::get('sysmaker')->model('cash_payment');
                    $cashPayment = [
                        'id' => $payment['trade_no'],
                        'cash_id' => $data['id'],
                        'seller_id' => $cash['seller_id'],
                        'seller_name' => $seller['name'],
                        'shop_id' => $this->shopId,
                        'payment' => $cash['payment'],
                        'status' => $pay_res['status'],
                        'remark' => $pay_res['message'],
                        'thirdparty_payment_no' => $pay_res['payment_no'],
                        'openid' => $cash['openid'],
                        'create_time' => time(),
                        'payment_time' => strtotime($pay_res['payment_time']),
                    ];
                    $cashPaymentModel->insert($cashPayment);

                    if($pay_res['status'] == 'fall'){
                        throw new \LogicException($pay_res['message']);
                    }
                    $data['pay_time'] = strtotime($pay_res['payment_time']);
                    $data['remark'] = $data['remark'] . '打款成功，微信交易流水号为' . $pay_res['payment_no'];
                }else{
                    $data['pay_time'] = time();
                }
                $update = [
                    'settlement_type' => $data['settlementType'],
                    'settlement_status' => 2,
                    'status' => $data['status'],
                    'remark' => $data['remark'],
                    'pay_time' => $data['pay_time'],
                    'check_time' => time(),
                ];
                $cashModel->update($update, ['id' => $data['id'], 'shop_id' => $this->shopId]);
                return $this->splash('success', '', '操作成功', true);
            }
        }catch(\Exception $e){
            return $this->splash('error', '', $e->getMessage(), true);
        }
    }


    protected function getCasgPaymentId()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (string) sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime . mt_rand(100, 999);
    }

	/**
	* 查看详情的
	* author by wanghaichao
	* date 2019/8/26
	*/
	public function detail(){
		$seller_id=input::get('seller_id');
		$seller=app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$seller_id));
		$bank=app::get('sysmaker')->model('seller_bank')->getRow('*',array('seller_id'=>$seller_id));
		$status=app::get('sysmaker')->model('shop_rel_seller')->getRow('status',array('seller_id'=>$seller_id,'shop_id'=>$this->shopId));
		$pagedata['seller']=$seller;
		$pagedata['bank']=$bank;
		$pagedata['seller_id']=$seller_id;
		$pagedata['status']=$status['status'];
        return $this->page('topshop/mall/maker/detail.html', $pagedata);
	}

}