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
        $oid = input::get('oid');
        $pageData['detail'] = $this->makerCommissionObj->getSellerCommissionDetail($oid);
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

}