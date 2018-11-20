<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_sysstat_itemtrade extends topshop_controller
{
	/**
	 * 商品销售分析
	 * @param null
	 * @return null
	 */
	public function index()
	{
		$postSend = input::get();
		$type = $postSend['sendtype'];

		$objFilter = kernel::single('sysstat_data_filter');
		$params = $objFilter->filter($postSend);

		$itemtime = array('starttime'=>$postSend['itemtime']);
		if(!$postSend || !in_array($postSend['sendtype'],array('yesterday','beforday','week','month','selecttime')))
		{
			$type='yesterday';
		}
		$postSend['sendtype'] = $type;
		//api参数
		$all = $this->__getParams('all',$postSend,'item');
		$notAll = $this->__getParams('notall',$postSend,'item',$params);

		$itemInfo = app::get('topshop')->rpcCall('sysstat.data.get',$notAll,'seller');

		$topParams = array('inforType'=>'item','timeType'=>$type,'starttime'=>$postSend['itemtime'],'limit'=>5);
		$topFiveItem = app::get('topshop')->rpcCall('sysstat.data.get',$topParams,'seller');

		//获取页面显示的时间
		$pagetimes = app::get('topshop')->rpcCall('sysstat.datatime.get',$all,'seller');
		//api的参数
		$countAll = $this->__getParams('all',$postSend,'itemcount');
		//处理翻页数据
		$countData = app::get('topshop')->rpcCall('sysstat.data.get',$countAll,'seller');
		$count = $countData['count'];
		if($type == 'selecttime')
		{
			$pagetime = $pagetimes['before'];
		}
		else
		{
			$pagetime = $pagetimes;
		}

		if($count>0) $total = ceil($count/$params['limit']);
		$current = $postSend['pages'] ? $postSend['pages'] : 1;
		$pagedata['limits'] = $params['limit'];
		$pagedata['pages'] = $current;
		$postSend['pages'] = time();
		$pagedata['pagers'] = array(
			'link'=>url::action('topshop_ctl_sysstat_itemtrade@index',$postSend),
			'current'=>$current,
			'total'=>$total,
			'use_app' => 'topshop',
			'token'=>$postSend['pages']
		);
		$pagedata['sendtype'] = $type;
		$pagedata['itemInfo'] = $itemInfo['sysTrade']?$itemInfo['sysTrade']:$itemInfo['sysTradeData'];

		$pagedata['traffic_disabled'] = config::get('stat.disabled');
		if(!$pagedata['traffic_disabled']){
			$itemids = implode(',',array_column($pagedata['itemInfo'], 'item_id'));
			if($itemids){
				$apiData = $notAll;
				$apiData['itemids'] = $itemids;
				$pagedata['uvData'] = app::get('topshop')->rpcCall('sysstat.traffic.data.get',$apiData);
			}
		}

		$pagedata['pagetime'] = $pagetime;
		$pagedata['topFiveItem'] = $topFiveItem['sysTrade'];

		$this->contentHeaderTitle = app::get('topshop')->_('运营报表-商品销售分析');
		return $this->page('topshop/sysstat/itemtrade.html', $pagedata);
	}

	/**
	 * 异步获取数据  图表用
	 * @param null
	 * @return array
	 */

	public function ajaxTrade()
	{
		$postData = input::get();

		$orderBy = $postData['trade'].' '.'DESC';
		$postData['orderBy'] = $orderBy;
		$postData['limit'] = 10;

		$grapParams = $this->__getParams('itemgraphall',$postData,'item');
		$datas =  app::get('topshop')->rpcCall('sysstat.data.get',$grapParams,'seller');

		$ajaxdata = array('dataInfo'=>$data,'datas'=>$datas);

		return response::json($ajaxdata);
	}

	//api参数组织
	private function __getParams($type,$postSend,$objType,$data=null)
	{
		if($type=='all')
		{
			$params = array(
				'inforType'=>$objType,
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['itemtime'],
			);
		}
		elseif($type=='notall')
		{
			$params = array(
				'inforType'=>$objType,
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['itemtime'],
				'limit'=>intval($data['limit']),
				'start'=>intval($data['start'])
			);
		}
		elseif($type=='itemgraphall')
		{
			$params = array(
				'inforType'=>$objType,
				'tradeType'=>$postSend['trade'],
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['itemtime'],
				'dataType'=>$type,
				'limit'=>intval($postSend['limit']),
				'orderBy'=>$postSend['orderBy'],
			);
		}
		return $params;
	}


    /**
     * 商品实际售出统计(按照发货时间）
     * @return
     */
    public function detail()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('运营报表-商品出库分析');

        $filter['shop_id'] = $this->shopId;

        $postSend = utils::_filter_input(input::get());

        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['createtime_than']  = strtotime($timeArray[0])-3600*24;//开始前一天
            $filter['createtime_lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['createtime_than']  = strtotime(date('Y/m/d',time()-3600*24*8));//开始前一天
            $filter['createtime_lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
        }

        $result = app::get('topshop')->rpcCall('item.get.settle',$filter);

        $pagedata['item_unit_settle'] = $result['item_unit_settle'];
        $pagedata['item_price_unit_settle'] = $result['item_price_unit_settle'];
        $pagedata['post_fee'] = $result['post_fee'];
        $pagedata['sale_fee'] = $result['sale_fee'];
        $pagedata['post_fee_detail']=$result['post_fee_detail'];

        return $this->page('topshop/sysstat/settlement_detail.html', $pagedata);
    }

    /**
     * @name confirmDetail
     * @desc 商家端按照确认收货统计数据
     * @return html
     * @author: wudi tvplaza
     * @date:
     */
    public function confirmDetail(){
        $this->contentHeaderTitle = app::get('topshop')->_('平台结算明细');
        $postSend=input::get();
        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['account_time|than']  = strtotime($timeArray[0]);//开始前一天
            $filter['account_time|lthan'] = strtotime($timeArray[1]) + 3600*24;//结束后一天
        }
        else
        {
            $filter['account_time|than']  = strtotime(date('Y/m/d',time()));//开始前一天
            $filter['account_time|lthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
            $postSend['timearea']=$pagedata['timearea'];
        }
        $limit = 20;
        $filter['shop_id']=$this->shopId;
        $count=app::get('sysclearing')->model('settlement_supplier_detail')->count($filter);
        $totalPage=ceil($count/$limit);
        $page_no=$postSend['pages']?$postSend['pages']:1;
        $offset=($page_no-1)*$limit;
        $data=app::get('sysclearing')->model('settlement_supplier_detail')->getList('*',$filter,$offset,$limit,'account_time');
        foreach($data as $sk => $sv){
            $data[$sk]['account_type']=$this->__getAccuntType($sv['account_type']);
			
			/*modify_2018/11/8_by_wanghaichao_start*/
			/*
			*修改集采商城的
			*/
			if ($data[$sk]['supplier_type']==3){
				$data[$sk]['supplier_id']=$this->__getShopSrc($sv['supplier_id']);
				$data[$sk]['supplier_type']='集采商城';	
			}else{
				$data[$sk]['supplier_id']=$this->__getSupplierSrc($sv['supplier_id']);
				$data[$sk]['supplier_type']=$this->__getSupplierFromSrc($sv['supplier_type']);	
			}		
			/*modify_2018/11/8_by_wanghaichao_end*/
			
            $data[$sk]['trade_type']=$this->__getTradeTypeSrc($sv['trade_type']);
            $data[$sk]['incoming_type']=$this->__getIncomingType($sv['incoming_type']);
        }
        $pagedata['data']=$data;
        $pagedata['limits'] = $limit;
        $pagedata['pages'] = $page_no;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_sysstat_itemtrade@confirmDetail',$postSend),
            'current'=>$page_no,
            'total'=>$totalPage,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );
        return $this->page('topshop/sysstat/confirm_detail.html', $pagedata);
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

    private function __getSupplierFromSrc($fromValue){
        if($fromValue==0){
            $from='店铺供应商';
        }elseif($fromValue==1){
            $from='平台供应商';
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

    private function __getIncomingType($incomingType){
        if($incomingType==0){
            return '运费';
        }
        $taxConfig=config::get('tax');
        $curTax=$taxConfig[$incomingType];
        return $curTax['src'];
    }

    public function settledetail(){

        $this->contentHeaderTitle = app::get('topshop')->_('运营报表-商品出库明细');
        $filter['shop_id'] = $this->shopId;
        $postSend = utils::_filter_input(input::get());

        if($postSend['timearea'])
        {
            $pagedata['timearea'] = $postSend['timearea'];
            $timeArray = explode('-', $postSend['timearea']);
            $filter['account_time|bthan']  = strtotime($timeArray[0]);
            $filter['account_time|sthan'] = strtotime($timeArray[1]);
        }
        else
        {
            $filter['account_time|bthan']  = strtotime(date('Y/m/d',time()-3600*24*7));//开始前一天
            $filter['account_time|sthan'] = strtotime(date('Y/m/d',time()));//结束后一天
            $pagedata['timearea'] = date('Y/m/d', time()-3600*24*7) . '-' . date('Y/m/d', time()-3600*24);
        }

        $mdlItemSettle=app::get('sysstat')->model("item_settle_statics_detail");
        $totalPayment=$mdlItemSettle->getRow('sum(payment) as totalpayment,sum(postfee) as totalpostfee',$filter,'account_time');
        $pagedata['totalpayment']=$totalPayment['totalpayment'];
        $pagedata['totalpostfee']=$totalPayment['totalpostfee'];
        $pageLimit=30;
        $count=$mdlItemSettle->count($filter);
        if($count>0) $total = ceil($count/$pageLimit);
        $current = $postSend['pages'] ? $postSend['pages'] : 1;
        $data =$mdlItemSettle->getList('*',$filter,$pageLimit*($current-1),$pageLimit);

        $pagedata['limits'] = $pageLimit;
        $pagedata['pages'] = $current;
        $postSend['pages'] = time();
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_sysstat_itemtrade@settledetail',$postSend),
            'current'=>$current,
            'total'=>$total,
            'use_app' => 'topshop',
            'token'=>$postSend['pages']
        );

        $pagedata['list']=$data;


        //$pagedata['item_unit_settle'] = $result['item_unit_settle'];
        //$pagedata['item_price_unit_settle'] = $result['item_price_unit_settle'];
        //$pagedata['post_fee'] = $result['post_fee'];
        //$pagedata['sale_fee'] = $result['sale_fee'];
        //$pagedata['post_fee_detail']=$result['post_fee_detail'];

        return $this->page('topshop/sysstat/settle_detail.html', $pagedata);
    }


}
