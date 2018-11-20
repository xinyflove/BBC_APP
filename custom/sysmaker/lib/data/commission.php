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
     * @param $oid
     * @return array
     */
    public function getSellerCommissionDetail($oid)
    {
        if (empty($oid)) {
            throw new LogicException('参数oid不能为空');
        }
        $qb = $this->db->createQueryBuilder();
        $fields = 'oid, trade_type, trade_time, title, price, num, payment, seller_commission';
        $detail = $qb->select($fields)
            ->from('sysclearing_seller_billing_detail')
            ->where('oid = ' . $qb->createPositionalParameter($oid))
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
		$order=$orderObj->getRow('SUM(payment) AS payment',$order_filter);
		//未结算佣金
		$unsettled_com=number_format(($order['payment']*$maker_rate/100),2);
		//待收货佣金
		$confirm_filter=array(
			'seller_id'=>$seller_id,          //主持人
			'status'=>'WAIT_BUYER_CONFIRM_GOODS',  //待收货状态
		);
		$confirm=$orderObj->getRow('SUM(payment) AS count',$confirm_filter);
		$confirm_com=number_format(($confirm['count']*$maker_rate/100),2);
		return array('cash_com'=>number_format($cash_com,2),'has_com'=>$has_com,'unsettled_com'=>$unsettled_com,'confirm_com'=>$confirm_com);
	}


}
