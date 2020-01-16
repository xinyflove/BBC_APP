<?php

/**
 * User: 王海超
 * Date: 2018/10/29
 * Desc: 915米粒大数据看板
 * shop_id = 7 915店铺
 */

class syslmgw_ctl_rice extends syslmgw_emic_controller
{

	/* action_name (par1, par2, par3)
	* 昨日店铺转化
	* author by wanghaichao
	* date 2018/10/29
	*/
	public function stats(){
		if(input::get('start_time') && input::get('end_time')){		
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$end_time=strtotime(date('Y-m-d 0:0:0',time()));  //结束时间 
			$start_time=$end_time-24*3600;//开始的时间
		}
		$params['start_time|bthan']=$start_time;
		$params['end_time|lthan']=$end_time;
		$params['shop_id']=7;   //915米粒店铺id
		//$count=app::get('statistic')->model('trend')->getRow('sum(pv_count) as count',$params);   //访问量		
        $date = date('Ymd', strtotime('-1 day'));
		$uv=redis::scene('traffic')->hget('webuv:all_7', $date);
		$objTrade=app::get('systrade')->model('trade');
		//总的交易额和总的下单量
        $filter = array(
            'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
       //     'cancel_status' => array('NO_APPLY_CANCEL', 'FAILS'),
            'shop_id' => 7,
			'pay_time|bthan'=>$start_time,
			'pay_time|lthan'=>$end_time,
        );
        $total = $objTrade->getRow("sum(payment) as total, count(tid) as count", $filter);
		//echo "<pre>";print_r($params);die();
		$data['sale_rate']=round(($total['total']/$total['count']),2);                    //每单价格
		$data['payment']=round($total['total'],2);                    //总价格
		$data['count']=$total['count'];                                       //总订单量
		$data['pv_count']=$uv;                                 //浏览量
		$data['conversion_rate']=round(($total['count']/$uv*100),2);   //转化率
        $this->splash('200',$data,'请求成功!');
	}

	
	/*
	* 昨日店铺转化,dataV数据
	* author by wanghaichao
	* date 2019/4/8
	*/
	public function dataVstats(){
		if(input::get('start_time') && input::get('end_time')){		
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$end_time=strtotime(date('Y-m-d 0:0:0',time()));  //结束时间 
			$start_time=$end_time-24*3600;//开始的时间
		}
		$params['start_time|bthan']=$start_time;
		$params['end_time|lthan']=$end_time;
		$params['shop_id']=7;   //915米粒店铺id
		//$count=app::get('statistic')->model('trend')->getRow('sum(pv_count) as count',$params);   //访问量		
        $date = date('Ymd', strtotime('-1 day'));
		$uv=redis::scene('traffic')->hget('webuv:all_7', $date);
		$objTrade=app::get('systrade')->model('trade');
		//总的交易额和总的下单量
        $filter = array(
            'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
       //     'cancel_status' => array('NO_APPLY_CANCEL', 'FAILS'),
            'shop_id' => 7,
			'pay_time|bthan'=>$start_time,
			'pay_time|lthan'=>$end_time,
        );
        $total = $objTrade->getRow("sum(payment) as total, count(tid) as count", $filter);
		//echo "<pre>";print_r($params);die();
		$data['sale_rate']=round(($total['total']/$total['count']),2).'/单';                    //每单价格
		$data['payment']='￥'.round($total['total'],2);                    //总价格
		$data['count']=$total['count'];                                       //总订单量
		$data['pv_count']=$uv;                                 //浏览量
		$data['conversion_rate']=round(($total['count']/$uv*100),2).'%';   //转化率
		$value=input::get('value');
		$return[0]['value']=$data[$value];
		$return[0]['url']="";
		echo json_encode($return);        
	}

	
	/* action_name (par1, par2, par3)
	* 总订单数/总订单金额/今日/昨日统计等
	* author by wanghaichao
	* date 2018/10/29
	*/
	//public function statistic(){
		
	//}
	
	/* action_name (par1, par2, par3)
	* 商品销量排行
	* author by wanghaichao
	* date 2018/10/29
	*/
	public function itemSales(){
		$type=input::get('type');
		if(isset($type) && $type=='all'){
			$starttime=strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
            $endtime=strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')));
			$notAll=array( 
				'inforType' =>'item',
				'timeType' =>'yesterday',
				'starttime' =>'',
				'limit' => 10,
				'start' => 0,
				//'shop_id'=>7,
			);

		$sql="SELECT sum(so.num) AS amountnum, so.item_id,si.title,si.shop_id FROM systrade_order AS so LEFT JOIN systrade_trade AS st ON so.tid = st.tid LEFT JOIN sysitem_item AS si ON so.item_id = si.item_id WHERE 1 AND st.created_time >= ".$starttime." AND st.created_time < ".$endtime." AND st. STATUS IN ('WRITE_PARTIAL', 'WAIT_WRITE_OFF', 'WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'HAS_OVERDUE') GROUP BY so.item_id ORDER BY amountnum DESC limit 0,10";
		$db = app::get('systrade')->database();
		$itemInfo=$db->executeQuery($sql)->fetchAll();
		$data=$itemInfo;
		}else{
			$notAll=array( 
				'inforType' =>'item',
				'timeType' =>'yesterday',
				'starttime' =>'',
				'limit' => 10,
				'start' => 0,
				'shop_id'=>7,
			);
			$itemInfo = app::get('syslmgw')->rpcCall('sysstat.data.get',$notAll);
			$data = $itemInfo['sysTrade']?$itemInfo['sysTrade']:$itemInfo['sysTradeData'];		
		}
        $this->splash('200',$data,'请求成功!');
	}

	/* action_name (par1, par2, par3)
	* dataV商品销量排行
	* author by wanghaichao
	* date 2019/4/8
	*/
	public function dataVitemSales(){
		$notAll=array( 
			'inforType' =>'item',
			'timeType' =>'yesterday',
			'starttime' =>'',
			'limit' => 10,
			'start' => 0,
			'shop_id'=>7,
		);
		$itemInfo = app::get('syslmgw')->rpcCall('sysstat.data.get',$notAll);
		$data = $itemInfo['sysTrade']?$itemInfo['sysTrade']:$itemInfo['sysTradeData'];
		$return=array();
		foreach($data as $k=>$v){
			$return[$k]['value']=$v['amountnum'];
			$return[$k]['content']=$v['title'];
		}
		
		echo json_encode($return);
        //$this->splash('200',$data,'请求成功!');
	}


	/* action_name (par1, par2, par3)
	* 验券/销售数量
	* author by wanghaichao
	* date 2018/10/29
	*/
	public function voucher(){
		if(input::get('start_time') && input::get('end_time')){		
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$end_time=strtotime(date('Y-m-d 0:0:0',time()));  //结束时间 
			$start_time=$end_time-24*3600*30;//开始的时间
		}

		$params['careated_time|bthan']=$start_time;
		$params['careated_time|lthan']=$end_time;
		$params['shop_id']=7;
		$params['status|noequal']="GIVEN";
		//先取出近三十天的券的销售数量
		$sale=app::get('systrade')->model('voucher')->getList('voucher_id,careated_time',$params);
		foreach($sale as $k=>$v){
			$date=date('m-d',$v['careated_time']);
			$data[$date][]=$v;
		}
		foreach($data as $k=>$v){
			$res[$k]['count']=count($v);
			$res[$k]['date']=$k;
		}
		$write_params['write_time|bthan']=$start_time;
		$write_params['write_time|lthan']=$end_time;
		$write_params['status']='WRITE_FINISHED';
		$write_params['shop_id']=7;
		$write=app::get('systrade')->model('voucher')->getList('voucher_id,write_time',$write_params);
		
		foreach($write as $k=>$v){
			$date=date('m-d',$v['write_time']);
			$writ[$date][]=$v;
		}
		foreach($writ as $k=>$v){
			$res[$k]['write_count']=count($v);
		}
        $this->splash('200',array_values($res),'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 销量趋势
	* author by wanghaichao
	* date 2018/10/30
	*/
	public function sales(){
		if(input::get('start_time') && input::get('end_time')){		
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$end_time=strtotime(date('Y-m-d 0:0:0',time()));  //结束时间 
			$start_time=$end_time-24*3600*30;//开始的时间
		}
		$params['pay_time|bthan']=$start_time;
		$params['pay_time|lthan']=$end_time;
		$type=input::get('type');
		if(!isset($type) || $type!='all'){
			$params['shop_id']=7;
		}
		$params['status'] = array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE');
		$trade=app::get('systrade')->model('trade')->getList('tid,payment,pay_time',$params);
		foreach($trade as $k=>$v){
			$date=date('m-d',$v['pay_time']);
			$data[$date][]=$v;
		}
		foreach($data as $k=>$v){
			foreach($v as $kk=>$vv){
				$res[$k]['payment']+=$vv['payment'];
			}
			$res[$k]['total']=count($v);
			$res[$k]['date']=$k;
		}
		foreach($res as $k=>&$v){
			$v['payment']=round(($v['payment']/10000),1);
		}
        $this->splash('200',array_values($res),'请求成功!');
	}
	
	/* action_name (par1, par2, par3)
	* 销量趋势,所有店铺的
	* author by wanghaichao
	* date 2019/7/12
	*/
	public function salesAll(){		
		if(input::get('start_time') && input::get('end_time')){		
			$start_time=strtotime(input::get('start_time'));
			$end_time=strtotime(input::get('end_time'));
		}else{
			$end_time=strtotime(date('Y-m-d 0:0:0',time()));  //结束时间 
			$start_time=$end_time-24*3600*30;//开始的时间
		}
		$params['created_time|bthan']=$start_time;
		$params['created_time|lthan']=$end_time;
		//$params['shop_id']=7;
		$params['status'] = array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE');
		$trade=app::get('systrade')->model('trade')->getList('tid,payment,created_time',$params);
		foreach($trade as $k=>$v){
			$date=date('m-d',$v['created_time']);
			$data[$date][]=$v;
		}
		foreach($data as $k=>$v){
			foreach($v as $kk=>$vv){
				$res[$k]['payment']+=$vv['payment'];
			}
			$res[$k]['total']=count($v);
			$res[$k]['date']=$k;
		}
		foreach($res as $k=>&$v){
			$v['payment']=round(($v['payment']/10000),1);
		}
        $this->splash('200',array_values($res),'请求成功!');
	}

	/* action_name (par1, par2, par3)
	* 统计订单数据
	* author by wanghaichao
	* date 2018/10/30
	*/
	public function statistic(){
		$objTrade = app::get('systrade')->model('trade');
		//总的交易额和总的下单量
        $filter = array(
            'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
            'shop_id' => 7
        );
        $total = $objTrade->getRow("sum(payment) as total, count(tid) as count", $filter);

        $start_time = input::get('start_time');
        $end_time = input::get('end_time');

		$time = time();
        if ($start_time && $end_time) {
            $today_filter = array(
                'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
                'shop_id' => 7,
                'pay_time|than' => strtotime($start_time),
                'pay_time|lthan' => strtotime($end_time),
            );
        } else {
            $today_filter = array(
                'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
                'shop_id' => 7,
                'pay_time|than' => strtotime(date('Y-m-d 00:00:00', $time)),
                'pay_time|lthan' => strtotime(date('Y-m-d 23:59:59', $time)),
            );
        }
        
		$today = $objTrade->getRow("sum(payment) as total, count(tid) as count", $today_filter);

        if ($start_time && $end_time) {
			
			$yet_filter=array(
				'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
				'shop_id' => 7,
				'pay_time|than' => strtotime($start_time)-24*3600,
				'pay_time|lthan' => strtotime($end_time)-24*3600,
				//'pay_time|than' => strtotime(date('Y-m-d 00:00:00', $time))-24*3600,
				//'pay_time|lthan' => strtotime(date('Y-m-d 23:59:59', $time))-24*3600,
			);
		}else{
			$yet_filter=array(
				'status' => array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE'),
				'shop_id' => 7,
				'pay_time|than' => strtotime(date('Y-m-d 00:00:00', $time))-24*3600,
				'pay_time|lthan' => strtotime(date('Y-m-d 23:59:59', $time))-24*3600,
			);
		}
		
		$yesterday = $objTrade->getRow("sum(payment) as total, count(tid) as count", $yet_filter);
		$ratio['count_ratio']=abs(round(($today['count']/$yesterday['count']*100),2));
		$ratio['pay_ratio']=abs(round(($today['total']/$yesterday['total']*100),2));
		if(($today['count']-$yesterday['count'])>0){
			$ratio['count_direction']='up';
		}else{
			$ratio['count_direction']='down';
		}
		if(($today['total']-$yesterday['total'])>0){
			$ratio['pay_direction']='up';
		}else{
			$ratio['pay_direction']='down';
		}
        $data = [
            'total' => $total,
            'today' => $today,
			'yesterday'=>$yesterday,
			'ratio'=>$ratio,
        ];
        $this->splash('200', $data, '请求成功');		
	}

}