<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_sysstat_suppliertrade extends topshop_controller
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

		$supplierTime = array('starttime'=>$postSend['suppliertime']);
		if(!$postSend || !in_array($postSend['sendtype'],array('yesterday','beforday','week','month','selecttime')))
		{
			$type='yesterday';
		}
		$postSend['sendtype'] = $type;
		//api参数
		$all = $this->__getParams('all',$postSend,'supplier');

		$notAll = $this->__getParams('notall',$postSend,'supplier',$params);

		$supplierInfo = app::get('topshop')->rpcCall('sysstat.data.get',$notAll,'seller');

        $topParams = array('inforType'=>'supplier','timeType'=>$type,'starttime'=>$postSend['suppliertime'],'limit'=>10);
		//排行版
		$topFiveSupplier = app::get('topshop')->rpcCall('sysstat.data.get',$topParams,'seller');
		//获取页面显示的时间
		$pagetimes = app::get('topshop')->rpcCall('sysstat.datatime.get',$all,'seller');
		//api的参数
		$countAll = $this->__getParams('all',$postSend,'suppliercount');
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
			'link'=>url::action('topshop_ctl_sysstat_suppliertrade@index',$postSend),
			'current'=>$current,
			'total'=>$total,
			'use_app' => 'topshop',
			'token'=>$postSend['pages']
		);
		$pagedata['sendtype'] = $type;
		$pagedata['supplierInfo'] = $supplierInfo['sysTrade']?$supplierInfo['sysTrade']:$supplierInfo['sysTradeData'];
        $pagedata['pagetime'] = $pagetime;
        $pagedata['topFiveSupplier'] = $topFiveSupplier['sysTrade'];

        $this->contentHeaderTitle = app::get('topshop')->_('运营报表-供应商销售分析');
		return $this->page('topshop/sysstat/suppliertrade.html', $pagedata);
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
		$grapParams = $this->__getParams('suppliergraphall',$postData,'supplier');
		$datas =  app::get('topshop')->rpcCall('sysstat.data.get',$grapParams,'seller');

		$ajaxdata = array('dataInfo'=>$data,'datas'=>$datas);

		return response::json($ajaxdata);
	}

    /**
     * @desc 供应商统计页面
     * @return string
     * @author: wudi tvplaza
     * @date: 2017-
     */
	public function getItemTopTen(){
        $params=input::get();
        $qb = app::get('systrade')->database()->createQueryBuilder();
        $qb->select('shop_id,supplier_id,shop_name,cat_name,title,pic_path,itemurl,sum(amountnum) as amountnum,sum(amountprice) as amountprice')
            ->from('sysstat_desktop_item_statics')
            ->where('shop_id='.$params['shop_id'].' and supplier_id='.$params['supplier_id'])//卡券付款后的所有状态都标记为已完成
            ->setFirstResult(0)
            ->setMaxResults(10)
            ->groupBy('item_id')
            ->addOrderBy('amountnum', 'desc');
        $rows = $qb->execute()->fetchAll();
        return $this->splash('succ','',$rows);
    }

    private function __getTimeType($type){
        switch ($type) {
            case 'yesterday':
                return array('yesterday'=>strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))),
                    'beforeweek'=>strtotime(date('Y-m-d 00:00:00', strtotime('-8 day')))
                );
                break;
            case 'beforday':
                return array('beforday'=>strtotime(date('Y-m-d 00:00:00', strtotime('-2 day'))),
                    'beforeweek'=>strtotime(date('Y-m-d 00:00:00', strtotime('-9 day')))
                );
                break;
            case 'beforeweek':
                return strtotime(date('Y-m-d 00:00:00', strtotime('-8 day')));
                break;
            case 'week':
                return strtotime(date('Y-m-d 00:00:00', strtotime('-7 day')));
                break;
            case 'month':
                return strtotime(date('Y-m-d 00:00:00', strtotime('-30 day')));
                break;
            case 'selecttime':
                return $this->getSelectTime($filter);
                break;
            case 'select':
                return $this->getSelectTime($filter);
                break;
            case 'comparebefore':
                return strtotime(date('Y-m-d 00:00:00', strtotime('-3 day')));
                break;
            case 'compareweek':
                return strtotime(date('Y-m-d 00:00:00', strtotime('-8 day')));
                break;
        }
    }

    private function getSelectTime($filter)
    {
        $start = explode('-', $filter['starttime']);
        $end = explode('-', $filter['endtime']);
        $selectTime = array(
            'before'=>array('start'=>strtotime($start[0]),'end'=>strtotime($start[1])),
            'after'=>array('start'=>strtotime($end[0]),'end'=>strtotime($end[1]))
        );
        return $selectTime;
    }

	//api参数组织
	private function __getParams($type,$postSend,$objType,$data=null)
	{
		if($type=='all')
		{
			$params = array(
				'inforType'=>$objType,
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['suppliertime'],
			);
		}
		elseif($type=='notall')
		{
			$params = array(
				'inforType'=>$objType,
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['suppliertime'],
				'limit'=>intval($data['limit']),
				'start'=>intval($data['start'])
			);
		}
		elseif($type=='suppliergraphall')
		{
			$params = array(
				'inforType'=>$objType,
				'tradeType'=>$postSend['trade'],
				'timeType'=>$postSend['sendtype'],
				'starttime'=>$postSend['suppliertime'],
				'dataType'=>$type,
				'limit'=>intval($postSend['limit']),
				'orderBy'=>$postSend['orderBy'],
			);
		}
		return $params;
	}

}
