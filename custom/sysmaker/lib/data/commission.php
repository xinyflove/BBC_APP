<?php
/**
 * User: zhangshu
 * Date: 2018/11/14
 * Desc: 主持人佣金相关
 */
class sysmaker_data_commission
{
    private $db;

    public function __construct()
    {
        $this->db = app::get('base')->database();
    }

    /**
     * 主持人佣金明细列表
     *
     * @param $filter
     * @param int $offset
     * @param int $limit
     * @return mixed
     */
    public function getSellerCommissionList($filter, $offset = 0, $limit = 10)
    {
        $qb = $this->db->createQueryBuilder();
        $fields = 'id, seller_id, title, oid, seller_commission, trade_type, trade_time';
        $qb->select($fields)
            ->from('sysclearing_seller_billing_detail');
        if ($filter['seller_id']) {
            $qb->where('seller_id = ' . $qb->createPositionalParameter($filter['seller_id']));
        }

        $list = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('trade_time', 'desc')
            ->execute()
            ->fetchAll();
        if ($list) {
            foreach ($list as $key => $value) {
                if ('pay' == $value['trade_type']) {
                    $list[$key]['trade_type'] = '支付';
                } else if ('refunds' == $value['trade_type']) {
                    $list[$key]['trade_type'] = '退款';
                }
                $list[$key]['trade_time'] = date('Y-m-d H:i:s', $value['trade_time']);
                $list[$key]['seller_commission'] = number_format($value['seller_commission'], 2);
            }
        }

        return $list;
    }

    /**
     * 主持人累计佣金
     *
     * @param $sellerId
     * @return int
     */
    public function getSellerCommissionCount($sellerId)
    {
        if (empty($sellerId)) {
            throw new LogicException('参数seller_id不能为空');
        }
        $qb = $this->db->createQueryBuilder();
        $fields = 'sum(seller_commission) as count';
        $res = $qb->select($fields)
            ->from('sysclearing_seller_billing_detail')
            ->where('seller_id = ' . $qb->createPositionalParameter($sellerId))
            ->execute()->fetch();
        return number_format($res['count'], 2);
    }

    /**
     * 获取主持人佣金详情
     *
     * @param $id
     * @return array
     */
    public function getSellerCommissionDetail($id)
    {
        if (empty($id)) {
            throw new LogicException('参数id不能为空');
        }
        $qb = $this->db->createQueryBuilder();
        $fields = 'id, oid, trade_type, trade_time, title, price, num, payment, seller_commission';
        $detail = $qb->select($fields)
            ->from('sysclearing_seller_billing_detail')
            ->where('id = ' . $qb->createPositionalParameter($id))
            ->execute()
            ->fetch();
        if ($detail) {
            $detail['seller_commission'] = number_format($detail['seller_commission'], 2);
            $detail['price'] = number_format($detail['price'], 2);
            $detail['payment'] = number_format($detail['payment'], 2);
            $detail['trade_time'] = date('Y-m-d H:i:s', $detail['trade_time']);
            if ('pay' == $detail['trade_type']) {
                $detail['trade_type'] = '支付';
            } else if ('refunds' == $detail['trade_type']) {
                $detail['trade_type'] = '退款';
            }
            return $detail;
        }
        return [];
    }
	
	/*
	* 获取主持人已提现拥挤和可提现佣金
	* author by wanghaichao
	* date 2018/11/19
	*/
	public function getSellerStatistic($seller_id){
		$orderObj=app::get('systrade')->model('order');
		//先计算已经提现的佣金
		$params['seller_id']=$seller_id;
		$com=app::get('sysmaker')->model('cash')->getRow('SUM(payment) AS total',$params);
		$has_com=number_format($com['total'],2);
		//总佣金
		$total_com=$this->getSellerCommissionCount($seller_id);
		//可提现佣金
		$cash_com=$total_com-$has_com;
		//待收货佣金
		$seller=app::get('sysmaker')->model('shop_rel_seller')->getRow('shop_id',$params);
		$seller['fields']='maker_rate';
		$shopinfo=app::get('topmaker')->rpcCall('shop.get', $seller);
		//echo "<pre>";print_r($shopinfo);die();
		$maker_rate=$shopinfo['maker_rate'];
		//未结算订单
		//今天开始时间
		$order_filter['pay_time|than']=strtotime(date('Y-m-d 00:00:00', time()));
		$order_filter['pay_time|lthan']=time();
		$order_filter['seller_id']=$seller_id;
		$order_filter['status']= array('WAIT_SELLER_SEND_GOODS', 'WAIT_BUYER_CONFIRM_GOODS', 'TRADE_FINISHED', 'WRITE_PARTIAL', 'PARTIAL_SHIPMENT', 'WAIT_WRITE_OFF','HAS_OVERDUE');
		$order=$orderObj->getList('payment,maker_rate',$order_filter);
		foreach($order as $k=>$v){
			$unsettled_com+=$v['payment']*($v['maker_rate']/100);
		}
		//未结算佣金
		$unsettled_com=number_format($unsettled_com,2);
		//待收货佣金
		$confirm_filter=array(
			'seller_id'=>$seller_id,          //主持人
			'status'=>'WAIT_BUYER_CONFIRM_GOODS',  //待收货状态
		);
		$confirm=$orderObj->getList('payment,maker_rate',$confirm_filter);
		foreach($confirm as $k=>$v){
			$confirm_com+=$v['payment']*($v['maker_rate']/100);
		}
		$confirm_com=number_format($confirm_com,2);
		return array('cash_com'=>number_format($cash_com,2),'has_com'=>$has_com,'unsettled_com'=>$unsettled_com,'confirm_com'=>$confirm_com);
	}
	
	/* action_name (par1, par2, par3)
	* 获取今日访客
	* author by wanghaichao
	* date 2018/11/22
	*/
	public function todayVisit($seller_id){
		$filter['create_time|than']=strtotime(date('Y-m-d 00:00:00', time()));
		$filter['create_time|lthan']=time();
		$filter['seller_id']=$seller_id;
		$visit=app::get('sysmaker')->model('visit')->getRow('count(id) AS total',$filter);
		return $visit['total']?$visit['total']:0;
	}
	
	/**
	* 获取提现列表
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function cashList($filter, $offset = 0, $limit = 10){
		$qb = $this->db->createQueryBuilder();
        $fields = 'id, seller_id, payment, remark, create_time, status, bank_name,card_number';
        $qb->select($fields)
            ->from('sysmaker_cash');
        if ($filter['seller_id']) {
            $qb->where('seller_id = ' . $qb->createPositionalParameter($filter['seller_id']));
        }

        $list = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('create_time', 'desc')
            ->execute()
            ->fetchAll();

        if ($list) {
            foreach ($list as $key => $value) {               
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['create_time']);
                //$list[$key]['seller_commission'] = number_format($value['seller_commission'], 2);
            }
        }
        return $list;
	}
	
	/**
	* 获取票务系统创客的可提现佣金和总佣金
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function getTicketSellerCommission($seller_id){
		$params['seller_id']=$seller_id;
		//$params['shop_id']='46';
		//总佣金
		$total=app::get('sysclearing')->model('seller_settlement_billing_detail')->getRow('SUM(seller_commission) as total',$params);
        $total_com = number_format($total['total'],2,".","");
		$params['status|in']=array('success','pending');
		//已提现佣金
		$com=app::get('sysmaker')->model('cash')->getRow('SUM(payment) AS total',$params);
        $has_com = number_format($com['total'],2,".","");
        //可提现佣金
		$cash_com=$total_com-$has_com;
		$cash_com=round($cash_com,2);
		return array('cash_com'=>$cash_com,'total_com'=>$total_com,'has_com'=>$has_com);
	}
	
	/**
	* 获取佣金列表
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function ticketCommissionList($filter, $offset, $limit){
		$qb = $this->db->createQueryBuilder();
        $fields = 'id, seller_id, seller_commission,tid,voucher_id,create_time,write_time';
        $qb->select($fields)
            ->from('sysclearing_seller_settlement_billing_detail');
        if ($filter['seller_id']) {
            $qb->where('seller_id = ' . $qb->createPositionalParameter($filter['seller_id']));
        }

        $list = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('create_time', 'desc')
            ->execute()
            ->fetchAll();

        if ($list) {
            foreach ($list as $key => $value) {               
                $list[$key]['create_time'] = date('Y-m-d H:i:s', $value['write_time']);
                $list[$key]['tid']=substr_replace($value['tid'], '*****', 3, 5);
            }
        }
        return $list;
	}


}
