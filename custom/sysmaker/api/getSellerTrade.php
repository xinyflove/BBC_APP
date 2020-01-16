<?php
/* 
* 获取主持人的销售额
* author by wanghaichao
* date 2018/6/22
*/
class sysmaker_api_getSellerTrade{

    public $apiDescription = "获取主持人的销售额";

    public function getParams()
    {
        $return['params'] = array(
			'seller_id'=>['type'=>'int','valid'=>'required','description'=>'主持人的id','example'=>'主持人id','default'=>''],
        );
        return $return;
    }

    /**
     * @param $params
	 * @wanghaichao
	 * @date 2018/11/20
     */
    public function get($params){
		//$tradeObj=app::get('systrade')->model('trade');
        $db = db::connection()->createQueryBuilder();
		//$filter['status']=array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE');
		$start_time=strtotime(date('Y-m-d 0:0:0',time()));
		$end_time=time();      //今日订单
		$where="a.status IN ('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE') AND b.seller_id=".$params['seller_id'];
		$todayWhere=$where." AND a.pay_time>".$start_time." AND a.pay_time<".$end_time;
		$today= $db->select('COUNT(a.tid) AS today_count')
            ->from('systrade_trade', 'a')
            ->where($todayWhere)
            ->leftjoin('a', 'systrade_order', 'b', 'b.tid = a.tid')
            ->execute()->fetch();
		//$today=$tradeObj->getRow('COUNT(tid) AS today_count',$filter);
		//本月第一天
		$moth_start=strtotime(date('Y-m-1 0:0:0',time()));

		$mothWhere=$where." AND a.pay_time>".$moth_start." AND a.pay_time<".$end_time;
		
        $mdb = db::connection()->createQueryBuilder();

		$moth= $mdb->select('COUNT(a.tid) AS moth_count')
            ->from('systrade_trade', 'a')
            ->where($mothWhere)
            ->leftjoin('a', 'systrade_order', 'b', 'b.tid = a.tid')
            ->execute()->fetch();

		//$moth=$tradeObj->getRow('COUNT(tid) AS moth_count',$filter);
		
		unset($filter['pay_time|than']);
		
        $tdb = db::connection()->createQueryBuilder();
		
		$total= $tdb->select('COUNT(a.tid) AS total')
            ->from('systrade_trade', 'a')
            ->where($where)
            ->leftjoin('a', 'systrade_order', 'b', 'b.tid = a.tid')
            ->execute()->fetch();

		//累计所有的订单
		//$total=$tradeObj->getRow('COUNT(tid) AS total',$filter);
		$result['today']=$today['today_count'];
		$result['moth']=$moth['moth_count'];
		$result['total']=$total['total'];
		return $result;
    }
}
