<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_clearing_settlement extends topshop_controller
{
    public $limit = 10;

    /**
     * 结算汇总
     * @return
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('商家结算汇总');

        $filter['shop_id'] = $this->shopId;

        $postSend = input::get();
        $page = $postSend['page'] ? $postSend['page'] : 1;

        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['settlement_time_than']  = strtotime($timeArray[0]);
            $filter['settlement_time_lthan'] = strtotime($timeArray[1]);
        }
        else
        {
            $filter['settlement_time_than']  = strtotime(date('Y-m-01 00:00:00', strtotime('-1 month')));
            $filter['settlement_time_lthan'] = strtotime(date('Y-m-t  23:59:59', strtotime('-1 month')));
            $pagedata['timearea'] = date('Y/m/01', strtotime('-1 month')).'-'.date('Y/m/t', strtotime('-1 month'));
        }

        if($postSend['settlement_type'])
        {
            $filter['settlement_status'] = $postSend['settlement_type'];
            $pagedata['settlement_type'] = $postSend['settlement_type'];
        }
        $filter['page_no'] = $page;
        $filter['page_size'] = $this->limit;

        try{
            $settlement_list = app::get('topshop')->rpcCall('clearing.getList',$filter);
        }
        catch(\LogicException $e)
        {
            $settlement_list = array();
        }
        $list = $settlement_list['list'];
        $count = $settlement_list['count'];
        foreach ($list as $key => $value)
        {
            $list[$key]['timearea'] = date('Y/m/d',$value['account_start_time']).'-'.date('Y/m/d',$value['account_end_time']);
        }

        $pagedata['settlement_list'] = $list;
        $pagedata['count'] = $count;

        //处理翻页数据
        $pagedata['limits'] = $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_clearing_settlement@index',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);

        return $this->page('topshop/clearing/settlement.html', $pagedata);
    }

    /**
     * 结算明细
     * @return
     */
    public function detail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('商家结算明细');

        $filter['shop_id'] = $this->shopId;

        $postSend = utils::_filter_input(input::get());
        $page = $postSend['page'] ? $postSend['page'] : 1;

        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['settlement_time_than']  = strtotime($timeArray[0]);
            $filter['settlement_time_lthan'] = strtotime($timeArray[1]);
        }
        else
        {
            $filter['settlement_time_than']  = strtotime("-7 day",strtotime(date('Y-m-d')));
            $filter['settlement_time_lthan'] = strtotime(date('Y-m-d'));
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time());
        }

        if($postSend['settlement_type'])
        {
            $filter['settlement_type'] = $postSend['settlement_type'];
            $pagedata['settlement_type'] = $postSend['settlement_type'];
        }
        $filter['page_no'] = $page;
        $filter['page_size'] = $this->limit;

        $result = app::get('topshop')->rpcCall('clearing.detail.getlist',$filter);
        $pagedata['settlement_detail_list'] = $this->__getTradePaymentType($result['list']);
        $pagedata['count'] = $result['count'];

        //处理翻页数据
        $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_clearing_settlement@detail',$postSend);
        $pagedata['pagers'] = $this->__pagers($pagedata['count'],$page,$limit,$link);
        return $this->page('topshop/clearing/settlement_detail.html', $pagedata);
    }

    private function __getTradePaymentType($list)
    {
        if(!$list)
        {
            return array();
        }
        $tids = array_column($list, 'tid');
        $tids = implode(',', $tids);
        $params['tids'] = $tids;
        $params['fields'] = 'pay_name';
        $params['status'] = 'succ';
        $data = app::get('topshop')->rpcCall('trade.payment.list', $params);

        foreach($list as &$row)
        {
            $row['pay_type'] = '--';
            if($row['settlement_fee']>=0)
            {
                $row['pay_type'] = $data[$row['tid']]['pay_name'] ? $data[$row['tid']]['pay_name'] : '--';
            }
        }

        return $list;
    }
    private function __pagers($count,$page,$limit,$link)
    {
        if($count>0)
        {
            $total = ceil($count/$limit);
        }
        $pagers = array(
            'link'=>$link,
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;
    }

    /**
     * @name settleDaily
     * @desc 商家端平台结算日报表
     * @author: wudi tvplaza
     * @date: 2017-12-25 11:00
     */
    public function settleDaily(){
        $this->contentHeaderTitle = app::get('topshop')->_('平台结算日报');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['account_time|bthan']  = strtotime($timeArray[0]);//开始前一天
            $filter['account_time|sthan'] = strtotime($timeArray[1]) + 3600*24-1;//结束后一天
        }
        else
        {
            $filter['account_time|bthan']  = strtotime(date('Y/m/d',time()))-3600*24*7;//开始前一天
            $filter['account_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }

        $limit = 20;
        $filter['shop_id']=$this->shopId;
        $count=app::get('sysclearing')->model('settlement_supplier_daily')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('settlement_supplier_daily')->getList('*',$filter,$offset,$limit,'account_time');

        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			
			/*modify_2018/7/9_by_wanghaichao_start*/
			/*
            $data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
			*/
			if($data[$sk]['supplier_type']==3){
				$data[$sk]['supplier_id']=$this->__getShopSrc($sv['supplier_id']);
			}else{
				$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
			}
			/*modify_2018/7/9_by_wanghaichao_end*/
			
            $data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
            $data[$sk]['settlement_status']=$this->_getSettleStatusSrc($sv['settlement_status']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@settleDaily',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
        return $this->page('topshop/clearing/settle_daily.html', $pagedata);
    }

    public function billDetail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('平台结算明细（以收付款时间进行结算！）');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['trade_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['trade_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['trade_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['trade_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }

        if($postSend['logistics_customer'] != 'all') {
            $filter['logistics_customer'] = $postSend['logistics_customer'];
            $pagedata['logistics_customer'] = $postSend['logistics_customer'];
        }
        $limit = 20;
        $filter['shop_id']=$this->shopId;
        $count=app::get('sysclearing')->model('settlement_billing_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('settlement_billing_detail')->getList('*',$filter,$offset,$limit,'trade_time');
        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			
			/*modify_2018/7/9_by_wanghaichao_start*/
			/*
            $data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
            $data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
			*/
			if($data[$sk]['supplier_type']==3){
				$data[$sk]['supplier_id']=$this->__getShopSrc($sv['supplier_id']);
			}else{
				$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
			}
			/*modify_2018/7/9_by_wanghaichao_end*/
			$data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@billDetail',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
        return $this->page('topshop/sysstat/confirm_bill.html', $pagedata);
    }

    private function __getAccuntType($accountType){
        if($accountType=='on'){
            $accountTypeSrc='商家收款';
        }elseif($accountType=='off'){
            $accountTypeSrc='平台收款';
        }else{
            $accountTypeSrc='数据异常';
        }
        return $accountTypeSrc;
    }

    private function __getSupplierSrc($supplierId){
        $filter=array('supplier_id'=>$supplierId);
        $supplierCompanyName=app::get('sysshop')->model('supplier')->getRow('company_name',$filter);
        return $supplierCompanyName['company_name'];
    }

    private function __getSupplierFromSrc($fromValue){
        if($fromValue==0){
            $from='店铺供应商';
        }elseif($fromValue==1){
            $from='平台供应商';
        }elseif($fromValue==3){
            $from='广电优选';
        }else{
			$from='数据错误';
		}
        return $from;
    }

    private function __getTradeTypeSrc($tradeType){
        if($tradeType=='pay'){
            $tradeTypeSrc='支付';
        }elseif($tradeType=='refunds'){
            $tradeTypeSrc='退款';
        }else{
            $tradeTypeSrc='数据错误';
        }

        return $tradeTypeSrc;
    }

    /**
     * @name __getIncomingType
     * @desc
     * @param $incomingType
     * @return string
     * @author: wudi tvplaza
     * @date: 2017-12-25 12:38
     */
    private function __getIncomingType($incomingType){
        if($incomingType==0){
            return '运费';
        }
        $taxConfig=config::get('tax');
        $curTax=$taxConfig[$incomingType];
        return $curTax['src'];
    }

    private function _getSettleStatusSrc($status){
        switch($status){
            case 1:$src='未结算';break;
            case 2:$src='已结算';break;
            case 3:$src='提现待审核';break;
            case 4:$src='提现审核通过';break;
            case 5:$src='提现审核未通过';break;
            case 6:$src='提现成功';break;
            case 7:$src='提现失败';break;
            default:$src="异常";
        }

        return $src;
    }
	
	
	/* __getShopSrc (par1, par2, par3)
	* 获取店铺名称,根据店铺id
	* author by wanghaichao
	* date 2018/7/3
	*/
	private function __getShopSrc($shop_id){
        $filter=array('shop_id'=>$shop_id);
        $supplierCompanyName=app::get('sysshop')->model('shop')->getRow('shop_name',$filter);
        return $supplierCompanyName['shop_name'];
	}


	/* action_name (par1, par2, par3)
	* 创客结算
	* author by wanghaichao
	* date 2018/6/26
	*/
	public function sellerDetail(){

		/*$filter="a.seller_id !=0 or a.init_shop_id!=0 and a.tid!='1603031417280002'";

		$listsBuilder=db::connection()->createQueryBuilder();
        $payTradeIds = $listsBuilder->select('a.tid')
            ->from('systrade_order', 'a')
            ->where($filter)
            ->leftjoin('a', 'systrade_trade', 'b', 'b.tid = a.tid')
			->groupBy('a.tid')
            ->execute()->fetchAll();
        if(!empty($payTradeIds)){
            foreach($payTradeIds as $tid){
                $payClearing = app::get('systrade')->rpcCall('clearing.sellerbilling.add',$tid);
			}
        }
        //取消退款
        //$refundTradeInfo=app::get('ectools')->model('refunds')->getList('tid,oid,finish_time as trade_time',array_merge(array('status'=>'succ'),$refundfilter));


			
        $refundTradeInfo=app::get('ectools')->model('refunds')->getList('tid,oid,finish_time as trade_time',array_merge(array('status'=>'succ'),$refundfilter));


        if(!empty($refundTradeInfo)){
            foreach($refundTradeInfo as $refund){
                $refund['billing_type']=2;
                $refundCleaing = app::get('systrade')->rpcCall('clearing.sellerbilling.add',$refund);
            }
        }*/


		//$aa=kernel::single('sysclearing_tasks_sellerbilling');
		//$aa->exec();


        $this->contentHeaderTitle = app::get('topshop')->_('创客结算明细（以收付款时间进行结算！）');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['trade_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['trade_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['trade_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['trade_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }

		//$filter='';
        $limit = 20;
		if($this->sellerInfo['is_compere']==1){
			$filter['seller_id']=$this->sellerId;
		}elseif($postSend['seller_id']){
			$filter['seller_id']=$postSend['seller_id'];
			$filter['shop_id']=$this->shopId;
		}else{
			$filter['shop_id']=$this->shopId;
			$filter['seller_id|noequal']='0';
		}
        $count=app::get('sysclearing')->model('seller_billing_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('seller_billing_detail')->getList('*',$filter,$offset,$limit,'trade_time');
        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			if(!empty($sv['init_shop_id'])){
				$data[$sk]['supplier_id']=$this->__getShopSrc($sv['init_shop_id']);
				$data[$sk]['supplier_type']='广电优选';
			}else{
				$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
				$data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
			}
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
			$data[$sk]['seller_id']=$this->__getSellerName($sv['seller_id']);
			$data[$sk]['group_name']=$this->__getGroupName($sv['group_id']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@sellerDetail',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		$pagedata['sellerInfo']=$this->getSellerInfo();
		$pagedata['find_seller_id']=$postSend['seller_id'];
        return $this->page('topshop/clearing/confirm_seller_detail.html', $pagedata);
	}
	
	/**
	* 获取协会的名称
	* author by wanghaichao
	* date 2019/9/10
	*/
	public function __getGroupName($group_id){
		if (empty($group_id) || $group_id==0)
		{
			return '--';
		}
		$group=app::get('sysmaker')->model('group')->getRow('name',array('group_id'=>$group_id));
		return $group['name'];
	}


	/* 
	* 集采商城的结算情况,供货的商城(b)的结算详情
	* author by wanghaichao
	* date 2018/7/3
	*/
	public function collectionDetail(){
        $this->contentHeaderTitle = app::get('topshop')->_('本店铺推送商品结算明细（以收付款时间进行结算！）');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['trade_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['trade_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['trade_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['trade_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }
		if($postSend['shop_id']){
			$filter['shop_id']=$postSend['shop_id'];
		}
		//$filter='';
        $limit = 20;
		$filter['init_shop_id']=$this->shopId;
        $count=app::get('sysclearing')->model('seller_billing_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('seller_billing_detail')->getList('*',$filter,$offset,$limit,'trade_time');
        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
			$data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
			$data[$sk]['shop_id']=$this->__getShopSrc($sv['shop_id']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@collectionDetail',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
		$pagedata['childShopInfo']=$this->getChlidShop();
		$pagedata['child_shop_id']=$postSend['shop_id'];
        return $this->page('topshop/clearing/confirm_push_detail.html', $pagedata);
	}

	/* 
	* 本店铺拉取的商品结算
	* author by wanghaichao
	* date 2018/7/3
	*/


	public function pullCollectionDetail(){
		
        $this->contentHeaderTitle = app::get('topshop')->_('本店铺拉取商品结算明细（以收付款时间进行结算！）');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['trade_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['trade_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['trade_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['trade_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }
		//$filter='';
        $limit = 20;
		$filter['shop_id']=$this->shopId;
		if(!empty($postSend['init_shop_id'])){
			$filter['init_shop_id']=$postSend['init_shop_id'];
		}else{
			$filter['init_shop_id|noequal']='0';
		}
        $count=app::get('sysclearing')->model('seller_billing_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('seller_billing_detail')->getList('*',$filter,$offset,$limit,'trade_time');
        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			if(!empty($sv['init_shop_id'])){
				$data[$sk]['supplier_id']=$this->__getShopSrc($sv['init_shop_id']);
				$data[$sk]['supplier_type']='广电优选';
			}else{
				$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
				$data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);
			}
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@pullCollectionDetail',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
		$pagedata['initShopInfo']=$this->getInitShop();
		$pagedata['init_shop_id']=$postSend['init_shop_id'];

        return $this->page('topshop/clearing/confirm_pull_detail.html', $pagedata);
	}
	
	/* 
	* 获取结算时拉取本店铺的商品的店铺
	* author by wanghaichao
	* date 2018/7/6
	*/
	public function getChlidShop(){
		$shop_id=$this->shopId;
		$builderWhere='init_shop_id='.$shop_id;
		
        $listsBuilder=db::connection()->createQueryBuilder();
        $childShop = $listsBuilder->select('shop_id')
            ->from('sysclearing_seller_billing_detail')
            ->where($builderWhere)
            ->GroupBy('shop_id')
            ->execute()->fetchAll();
		//$childShop[1]['shop_id']=8;
		$params=array();
		foreach($childShop as $k=>$v){
			$params['shop_id'][]=$v['shop_id'];
		}
		$shopInfo=app::get('sysshop')->model('shop')->getList('shop_id,shop_name',$params);
		return $shopInfo;
		//$childShop=app::get('sysclearing')->model('seller_billing_detail')->getList('shop_id',$filter);
		//echo "<pre>";print_r($childShop);die();
	}
	
	/* action_name (par1, par2, par3)
	* 本店拉取的商品的店铺
	* author by wanghaichao
	* date 2018/7/6
	*/
	public function getInitShop(){
		$shop_id=$this->shopId;
		$builderWhere='shop_id='.$shop_id;
		
        $listsBuilder=db::connection()->createQueryBuilder();
        $initShop = $listsBuilder->select('init_shop_id')
            ->from('sysclearing_seller_billing_detail')
            ->where($builderWhere)
            ->GroupBy('init_shop_id')
            ->execute()->fetchAll();		
		//$initShop[1]['init_shop_id']=8;
		$params=array();
		foreach($initShop as $k=>$v){
			$params['shop_id'][]=$v['init_shop_id'];
		}
		$shopInfo=app::get('sysshop')->model('shop')->getList('shop_id,shop_name',$params);
		//$postfee['shop_id']='999999';
		//$postfee['shop_name']='运费';
		//$shopInfo[]=$postfee;
		return $shopInfo;
	}

	/* action_name (par1, par2, par3)
	* 获取创客信息
	* author by wanghaichao
	* date 2018/11/14
	*/
	public function getSellerInfo(){
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
		return $sellerList;
	}
	
	/* action_name (par1, par2, par3)
	* 获取创客姓名
	* author by wanghaichao
	* date 2018/11/14
	*/
	public function __getSellerName($seller_id){
		$seller=app::get('sysmaker')->model('seller')->getRow('name',array('seller_id'=>$seller_id));
		return $seller['name'];
	}
	
	/**
	* 票务系统创客结算
	* author by wanghaichao
	* date 2019/8/7
	*/
	public function ticketSellerBill(){
        $this->contentHeaderTitle = app::get('topshop')->_('创客结算明细（以核销时间进行结算！）');
        $postSend=input::get();
		$filter='1';
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter.=' and write_time > '.strtotime($timeArray[0]);//开始前一天
            $filter.=' and write_time <='.(strtotime($timeArray[1]) + 3600*24);//结束后一天
			
			
        }
        else
        {
            $filter.=' and write_time >'.strtotime(date('Y/m/d',time()));//开始前一天
            $filter.=' and write_time<='.strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }
		//echo "<pre>";print_r($postSend);die();
		//$filter='';
        $limit = 20;
		if($postSend['seller_name']){
			$filter.=' and b.name="'.$postSend['seller_name'].'"';
			$pagedata['seller_name']=$postSend['seller_name'];
		}else{
			$filter.=' and a.seller_id!=0';
		}
		$filter.=' and a.shop_id='.$this->shopId;
		//echo "<pre>";print_r($filter);die();
        $countBuilder = db::connection()->createQueryBuilder();
        $count=$countBuilder->select('count(a.id)')
            ->from('sysclearing_seller_settlement_billing_detail', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
            ->where($filter)
            ->execute()->fetchColumn();	



        //$count=app::get('sysclearing')->model('seller_settlement_billing_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
		
        $listsBuilder=db::connection()->createQueryBuilder();
        $data = $listsBuilder->select('a.*')
            ->from('sysclearing_seller_settlement_billing_detail', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
            ->where($filter)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
             ->execute()->fetchAll();


        //$data=app::get('sysclearing')->model('seller_settlement_billing_detail')->getList('*',$filter,$offset,$limit,'write_time');
        foreach($data as $sk => $sv){
			$sellerinfo=$this->__getSellerInfo($sv['seller_id']);
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
			$data[$sk]['seller_id']=$this->__getSellerName($sv['seller_id']);
			$data[$sk]['cart_number']=$sellerinfo['cart_number'];
			$data[$sk]['mobile']=$sellerinfo['mobile'];
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@ticketSellerBill',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		$pagedata['sellerInfo']=$this->getSellerInfo();
		$pagedata['find_seller_id']=$postSend['seller_id'];
		$pagedata['shop_id']=$this->shopId;
        return $this->page('topshop/clearing/ticket_seller_detail.html', $pagedata);
	}
	
	/**
	* 根据创客id获取创客的信息
	* author by wanghaichao
	* date 2019/8/27
	*/
	public function __getSellerInfo($seller_id){
		$seller=app::get('sysmaker')->model('seller')->getRow('name,cart_number,mobile',array('seller_id'=>$seller_id));
		return $seller;
	}
	
	/**
	* 创客佣金结算汇总的
	* author by wanghaichao
	* date 2019/9/4
	*/
	public function summary(){
		$this->contentHeaderTitle = app::get('topshop')->_('创客佣金结算汇总');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['created_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['created_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            //$filter['created_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['created_time|lthan'] =time();//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }

		//$filter='';write_time
        $limit = 20;
		if($postSend['seller_id']){
			$filter['seller_id']=$postSend['seller_id'];
			$filter['shop_id']=$this->shopId;
		}else{
			$filter['shop_id']=$this->shopId;
		}
		if ($postSend['seller_name'])
		{
			$filter['seller_name|has']=$postSend['seller_name'];
		}
		if ($postSend['group_name'])
		{
			$filter['group_name|has']=$postSend['group_name'];
		}
		
		if ($postSend['type'])
		{
			$filter['type']=$postSend['type'];
		}
        $count=app::get('sysclearing')->model('summary')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('summary')->getList('*',$filter,$offset,$limit,'created_time');

        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_clearing_settlement@summary',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
		$pagedata['is_compere']=$this->sellerInfo['is_compere'];
		$pagedata['sellerInfo']=$this->getSellerInfo();
		$pagedata['find_seller_id']=$postSend['seller_id'];
		$pagedata['shop_id']=$this->shopId;
		$pagedata['post']=$postSend;
        return $this->page('topshop/clearing/summary.html', $pagedata);
	}
	
	/**
	* 结算汇总中结算功能的
	* author by wanghaichao
	* date 2019/9/5
	*/
	public function settlement(){
		$post=input::get();
		$update['status']=$post['status'];
		$update['remark']=$post['remark'];
		$params['id']=$post['summary_id'];
		$res=app::get('sysclearing')->model('summary')->update($update,$params);
		if ($res)
		{
			return $this->splash('success','','结算成功',true);	
		}else{
			return $this->splash('error',''.'结算失败',true);
		}
	}


}
