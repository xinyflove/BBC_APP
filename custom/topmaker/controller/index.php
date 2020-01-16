<?php
/**
 * Auth: jiangyunhan
 * Time: 2018-11-14
 * Desc: 创客基础业务控制器
 */
class topmaker_ctl_index extends topmaker_controller {

    public function index()
    {

        //创客个人信息
        $pagedata['seller'] = array_merge($this->sellerInfo,$this->bindShop);

        //累计佣金
        $pagedata['seller_commission_count'] = kernel::single('sysmaker_data_commission')->getSellerCommissionCount($this->sellerId);


        //创客页面链接信息
        $pagedata['my_shop_url'] = url::action('topwap_ctl_maker_index@home',array('seller_id'=>$this->sellerId));//我的店铺

        $pagedata['commission_url'] = url::action('topmaker_ctl_commission@statistic');//分销佣金
        $pagedata['commission_list_url'] = url::action('topmaker_ctl_commission@listData');//佣金明细

        $pagedata['my_withdraw_url'] = url::action('topmaker_ctl_commission@cash');//提现明细
        $pagedata['my_shop_qrcode_url'] = url::action('topmaker_ctl_index@share');//推广二维码
        $pagedata['my_shop_goods_url'] = url::action('topmaker_ctl_goods@index');//自选商品
        $pagedata['my_shop_setting_url'] = url::action('topmaker_ctl_setting@indexMaker');//小店设置
		/*add_2018/11/22_by_wanghaichao_start*/
		//历史订单页面
		$pagedata['my_shop_analyze']=url::action('topmaker_ctl_commission@analyze');
		//获取可提现佣金和已提现佣金
		$pagedata['commission']=kernel::single('sysmaker_data_commission')->getSellerStatistic($this->sellerId);
		//累计订单
		$params['seller_id']=$this->sellerId;
		$pagedata['trade']=app::get('topmaker')->rpcCall('seller.trade.get',$params); //订单统计
		//今日访客
		$pagedata['today_visit']=kernel::single('sysmaker_data_commission')->todayVisit($this->sellerId);
		/*add_2018/11/22_by_wanghaichao_end*/
        return view::make('topmaker/maker/centerindex.html',$pagedata);

    }

    /**
     * 推广二维码
     *
     * @return mixed
     */
    public function share()
    {
		//echo "<pre>";print_r(123);die();
		/*add_判断是否有seller_id_by_wanghaichao_start*/
		if(input::get('seller_id')){
			$seller_id=input::get('seller_id');
		}else{
			$seller_id=$this->sellerId;
		}
		/*add__by_wanghaichao_end*/
		
        $makerSellerObj = kernel::single('sysmaker_data_seller');
        $seller = $makerSellerObj->getSellerInfo($seller_id);
        $pageData['seller'] = $seller;
        // 微信分享
        $linelink = url::action('topwap_ctl_maker_index@home', ['seller_id' => $seller_id]);
        $weixin['imgUrl'] = base_storager::modifier($seller['avatar'], 'm');
        $weixin['linelink'] = $linelink;
        $weixin['shareTitle'] = $seller['name'] . '-' . $seller['shop_name'];
        $weixin['descContent'] = $seller['shop_description'];
        $pageData['weixin'] = $weixin;
		$baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $pageData['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);

        return $this->page('topmaker/maker/share.html', $pageData);
    }
	
	/**
	* 电视塔页面首页
	* author by wanghaichao
	* date 2019/8/1
	*/
	public function ticketindex(){
		//kernel::single('sysclearing_tasks_ticketsellerbill')->exec();
		//print_r($this->sellerInfo);die();
        /*if($this->sellerInfo['account']['status'] == 'success' && $this->bindShop['status'] == 'success')
        {
            return redirect::action('topmaker_ctl_index@index');
        }*/
		/*$tradeInfo['user_id']=78;
		$user=app::get('sysuser')->model('account')->getRow('mobile',array('user_id'=>$tradeInfo['user_id']));
		$smg_params['type'] = 'ticket';
		$smg_params['mobile'] = trim($user['mobile']);
		$smg_params['url'] = url::action('topwap_ctl_coupon_voucher@index');
		app::get('systrade')->rpcCall('tem.send.exsms',$smg_params);*/
		$pagedata=kernel::single('sysmaker_data_commission')->getTicketSellerCommission($this->sellerId);
		//个人中心页面
		return $this->page('topmaker/maker/ticketindex.html',$pagedata);
	}
	
}