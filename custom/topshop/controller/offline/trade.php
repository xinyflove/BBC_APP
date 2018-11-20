<?php
/**
 * Created by PhpStorm.
 * @desc:
 * @author: admin
 * @date: 2018-03-10 19:14
 */

class topshop_ctl_offline_trade extends topshop_controller{
    public $limit = 20;

    /**
     * @name index
     * @desc 线下付款订单
     * @return html
     * @author: wudi tvplaza
     * @date: 9/23/2017 5:01 PM
     */
    public function index()
    {
        //订单屏显状态
        $orderStatusList = array(
            '0' => '全部',
            '1' => '待付款',
            '2' => '已完成',
            '3' => '已取消',
            '4' => '已关闭',
        );
        $status = (int)input::get('status');
        $status = in_array($status, array_keys($orderStatusList)) ? $status : 0;

        // $pagedata['status'] = $orderStatusList;
        $pagedata['filter']['status'] = $status;
        if(input::get('useSessionFilter'))
        {
            $filer = $this->__getTradeSearchFilter();
            $pagedata['filter'] = $filer;
        }

        $pagedata['status'] = $orderStatusList;
        $pagedata['useSessionFilter'] = input::get('useSessionFilter');

        $this->contentHeaderTitle = app::get('topshop')->_('线下店收款列表');

        return $this->page('topshop/offline/trade/list.html', $pagedata);
    }

    /**
     * @desc 获取订单列表
     * @return mixed
     * @author: wudi tvplaza
     * @date: 201707311530
     */
    public function search()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('线下付款订单查询');

        $tradeStatus = array(
            'WAIT_BUYER_PAY' => '待付款',
            'TRADE_FINISHED' => '已完成',
            'TRADE_CLOSED'   => '已取消',
            'TRADE_CLOSED_BY_SYSTEM'=>'已关闭'
        );
        $postFilter = input::get();
        if($postFilter['useSessionFilter'])
        {
            $postFilter = $this->__getTradeSearchFilter();
            $postFilter['status'] = input::get('status');
        } else {
            $this->__saveTradeSearchFilter($postFilter);
        }
        $limit = $this->limit;
        $page = $postFilter['pages'] ? $postFilter['pages'] : 1;
        unset($postFilter['pages']);
        $filter = $this->_checkParams($postFilter);
        $sqlWhereString=implode(' AND ',$filter);
        $count=db::connection()->createQueryBuilder()
            ->select('count(*) as total')
            ->from('systrade_offline_trade','A')
            ->leftJoin('A','syssupplier_agent_shop','B','A.agent_shop_id=B.agent_shop_id')
            ->leftJoin('A','sysuser_account','C','A.user_id=C.user_id')
            ->leftJoin('A','sysshop_supplier','D','B.supplier_id=D.supplier_id')
            ->where($sqlWhereString)
            ->execute()->fetch();
        $offset=$limit*($page-1);
        $tradeList=db::connection()->createQueryBuilder()
            ->select('A.*,C.mobile,D.supplier_name,D.company_name,B.name')
            ->from('systrade_offline_trade','A')
            ->leftJoin('A','syssupplier_agent_shop','B','A.agent_shop_id=B.agent_shop_id')
            ->leftJoin('A','sysuser_account','C','A.user_id=C.user_id')
            ->leftJoin('A','sysshop_supplier','D','B.supplier_id=D.supplier_id')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('A.created_time','desc')
            ->where($sqlWhereString)
            ->execute()->fetchAll();
        foreach($tradeList as $tk => $tv){
            $tradeList[$tk]['status_src']=$tradeStatus[$tv['status']];
        }
        $pagedata['useSessionFilter'] = input::get('useSessionFilter');
        $pagedata['orderlist'] =$tradeList;
        $pagedata['count'] =$count['total'];
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['pagers'] = $this->__pager($postFilter,$page,$count['total']);
        return view::make('topshop/offline/trade/item.html', $pagedata);
    }

    /**
     * @name _checkParams
     * @desc 参数格式化
     * @param $filter
     * @return array
     * @author: wudi tvplaza
     * @date: 2018-03-11 14:06
     */
    private function _checkParams($filter)
    {
        $sqlWhere=array();
        if($filter['status'] == '0'){
            unset($filter['status']);
        }
        $statusLUT = array(
            '1' => 'WAIT_BUYER_PAY',
            '2' => 'TRADE_FINISHED',
            '3' => 'TRADE_CLOSED',
            '4' => 'TRADE_CLOSED_BY_SYSTEM',
        );
        foreach($filter as $key=>$value)
        {
            if(!$value) {
                unset($filter[$key]);
                continue;
            }

            if($key == 'created_time')
            {
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $sqlWhere[] = 'A.created_time >= '.strtotime($times['0']);
                    $sqlWhere[] = 'A.created_time < '.(strtotime($times['1'])+86400);
                }
            }

            if($key=='status' && $value)
            {
                //if($value <= 5 || ($value==8))
                if(in_array($value,array_keys($statusLUT)))
                {
                    $sqlWhere[] = "A.status = '".$statusLUT[$value]."'";
                }
            }
            #c
            if($key == 'mobile'){
                $sqlWhere[]="C.mobile = '".$value."'";
            }
            #d
            if($key == 'supplier_name'){
                $sqlWhere[] = "(D.supplier_name like '%".$value."%' or D.company_name like '%".$value."%')";
            }
            #b
            if($key == 'offline_shop'){
                $sqlWhere[]= "(B.name like '%".$value."%' or A.title like '%".$value."%')";
            }

            if($key == 'tid'){
                $sqlWhere[] = "A.tid = ".$value;
            }
        }
        $sqlWhere[]="D.shop_id = ".$this->shopId;

        return $sqlWhere;
    }

    /**
     * @name __pager
     * @desc
     * @param $postFilter
     * @param $page
     * @param $count
     * @return array
     * @author: wudi tvplaza
     * @date:9/23/2017 4:26 PM
     */
    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_offline_trade@search',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;

    }

    /**
     * 修改订单价格页面
     * @return
     */
    public function modifyPrice()
    {
        $tids = input::get('tid');
        $params['tid'] = $tids;
        $params['fields'] = "total_fee,post_fee,discount_fee,payment,points_fee,tid,receiver_state,receiver_city,receiver_district,receiver_address,orders.pic_path,orders.title,orders.item_id,orders.spec_nature_info,orders.price,orders.num,orders.total_fee,orders.discount_fee,orders.part_mjz_discount,orders.oid,orders.adjust_fee";
        $pagedata['trade_detail'] = app::get('topshop')->rpcCall('trade.get',$params,'seller');
        $pagedata['trade_detail']['order_money'] = $pagedata['trade_detail']['payment'] - $pagedata['trade_detail']['post_fee'];
        $pagedata['cur_symbol']=app::get('topc')->rpcCall('currency.get.symbol',array());
        return view::make('topshop/trade/modify_price.html', $pagedata);
    }

    /**
     * @name __saveTradeSearchFilter
     * @desc save filter in session
     * @param $filter
     * @author: wudi tvplaza
     * @date: 2018-03-11 15:31
     */
    private function __saveTradeSearchFilter($filter)
    {
        $_SESSION['topshop_offline_trade_search_filter'] = $filter;
    }

    /**
     * @name __getTradeSearchFilter
     * @desc get filter from session
     * @param $filter
     * @return mixed
     * @author: wudi tvplaza
     * @date: 2018-03-11 15:32
     */
    private function __getTradeSearchFilter($filter)
    {
        return $_SESSION['topshop_offline_trade_search_filter'];
    }

}