<?php
/**
 * User: zhangshu
 * Date: 2018/11/14
 * Desc: 主持人佣金
 */
class topmaker_ctl_commission extends topmaker_controller
{
    private $makerCommissionObj;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->makerCommissionObj =  kernel::single('sysmaker_data_commission');
    }

    /**
     * 主持人佣金明细列表
     *
     * @return mixed
     */
    public function listData()
    {
        $pageData['count'] = $this->makerCommissionObj->getSellerCommissionCount($this->sellerId);
        return $this->page('topmaker/commission/list.html', $pageData);
    }

    /**
     * ajax 获取佣金明细列表
     */
    public function ajaxGetListData()
    {
        $params = input::get();
        // 获取的每页显示数量
        $limit = 10;
        if($params['page_size']) {
            $limit = intval($params['page_size']) > 0 ? intval($params['page_size']) : $limit;
        }
        // 获取当前页面数
        $offset = 0;
        if ($params['pages']) {
            $params['pages'] = intval($params['pages']) > 0 ? intval($params['pages']) : 1;
            $offset = ($params['pages'] - 1) * $limit;
        }

        $filter['seller_id'] = $this->sellerId;

        $list = $this->makerCommissionObj->getSellerCommissionList($filter, $offset, $limit);

        echo json_encode($list);
    }

    /**
     * 主持人佣金详情
     *
     * @return mixed
     */
    public function detail()
    {
        $id = input::get('id');
        $pageData['detail'] = $this->makerCommissionObj->getSellerCommissionDetail($id);
        return $this->page('topmaker/commission/detail.html', $pageData);
    }
	
	/* statistic (par1, par2, par3)
	* 分销佣金统计  总佣金,可提现佣金等
	* author by wanghaichao
	* date 2018/11/19
	*/
	public function statistic(){
		$seller_id=$this->sellerId;
		//累计佣金
		$pagedata['total']=$this->makerCommissionObj->getSellerCommissionCount($seller_id);
		//获取可提现佣金和已提现佣金
		$pagedata['data']=$this->makerCommissionObj->getSellerStatistic($seller_id);

        return $this->page('topmaker/commission/statistic.html', $pagedata);
	}
	
	/*cash()
	* 主持人提现明细
	* author by wanghaichao
	* date 2018/11/20 
	*/
	public function cash(){
		$seller_id=$this->sellerId;
		//获取可提现佣金和已提现佣金
		$pagedata=$this->makerCommissionObj->getSellerStatistic($seller_id);

        return $this->page('topmaker/commission/cash.html', $pagedata);
	}
	//动态获取数据	
	public function getCashList(){
		$seller_id=$this->sellerId;
		$postdata=input::get();
		$params['page_size']=$postdata['pageSize'];
		$params['seller_id']=$seller_id;
		$params['page_no']=$postdata['pageNum']?$postdata['pageNum']:1;
        $data = app::get('topshop')->rpcCall('mall.seller.cash.list', $params);
		foreach($data['list'] as $k=>&$v){
			$v['create_time']=date('Y-m-d H:i:s',$v['create_time']);
			$v['payment']=number_format($v['payment'],2);
		}
		echo json_encode($data['list']);
	}
	
	/* action_name (par1, par2, par3)
	* 访问统计等信息分析
	* author by wanghaichao
	* date 2018/11/20
	*/
	public function analyze(){
		$params['seller_id']=$this->sellerId;
		//app::get('topmaker')->rpcCall('seller.visit',$params);
		$pagedata['visit']=app::get('topmaker')->rpcCall('seller.visit.get',$params);  //访问统计
		$pagedata['sales']=app::get('topmaker')->rpcCall('seller.sales.get',$params);  //销售额统计
		$pagedata['trade']=app::get('topmaker')->rpcCall('seller.trade.get',$params); //订单统计
        return $this->page('topmaker/commission/analyze.html', $pagedata);
	}
	
	/* action_name (par1, par2, par3)
	* ajax获取访问统计和销售额统计
	* author by wanghaichao
	* date 2018/11/20
	*/
	public function ajaxStatics(){
		$time=input::get('time');
		$params['seller_id']=$this->sellerId;
		$params['start_time']=strtotime(date('Y-m-1',strtotime($time)));
		$params['end_time']=strtotime(date('Y-m-t 23:59:59',strtotime($time)));
		$pagedata['visit']=app::get('topmaker')->rpcCall('seller.visit.get',$params);  //访问统计
		$pagedata['sales']=app::get('topmaker')->rpcCall('seller.sales.get',$params);  //销售额统计
		$pagedata['visit']['days']=str_replace("'","",$pagedata['visit']['days']);
		$pagedata['sales']['days']=str_replace("'","",$pagedata['sales']['days']);
		echo json_encode($pagedata);
	}
	
	/**
	* 电视塔佣金明细的
	* author by wanghaichao
	* date 2019/8/3
	*/
	public function commissionList(){
		
        return $this->page('topmaker/commission/commissionlist.html', $pageData);
	}
	
	/**
	* ajax获取佣金列表的
	* author by wanghaichao
	* date 2019/8/5
	*/
	public function ajaxTicketCommissionList(){
		
        $params = input::get();
        // 获取的每页显示数量
        $limit = 10;
		//echo "<pre>";print_R($params);die();
        if($params['page_size']) {
            $limit = intval($params['page_size']) > 0 ? intval($params['page_size']) : $limit;
        }
        // 获取当前页面数
        $offset = 0;
        if ($params['pages']) {
            $params['pages'] = intval($params['pages']) > 0 ? intval($params['pages']) : 1;
            $offset = ($params['pages'] - 1) * $limit;
        }

        $filter['seller_id'] = $this->sellerId;

        $list = kernel::single('sysmaker_data_commission')->ticketCommissionList($filter, $offset, $limit);
		//echo "<pre>";print_r($list);die();
        echo json_encode($list);
	}

}