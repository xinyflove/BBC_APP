<?php

/**
 * Class topshop_ctl_trade_voucher
 * 优惠券状态
 * 未核销 -> WAIT_WRITE_OFF
 * 已核销 -> WRITE_FINISHED
 * 已过期 -> HAS_OVERDUE
 * 已赠送 -> GIVEN
 * 退款中 -> REFUNDING
 * 已退款 -> SUCCESS
 * 赠送中 -> GIVING
 */
class topshop_ctl_trade_voucher extends topshop_controller{
    public $limit = 10;

    /**
     * @desc 商家后台交易列表
     * @return html
     * @author: shopEx
     * @updater: wudi tvpalza
     * @date: 201707310847,增加虚拟商品核销状态
     *
     */
    public function index()
    {
        //订单屏显状态
        $orderStatusList = array(
            '0' => '全部',
            '1' => '未核销',
            '2' => '已核销',
            '3' => '已过期',
            /*add_2017-11-23_by_xinyufeng_start*/
            '4' => '已赠送',
            '5' => '退款中',
            '6' => '已退款'
            /*add_2017-11-23_by_xinyufeng_end*/
        );

        $status = (int)input::get('status');
        $status = in_array($status, array_keys($orderStatusList)) ? $status : 0;

        $pagedata['status'] = $orderStatusList;
        $pagedata['filter']['status'] = $status;
        $pagedata['shop_type'] = $this->shopInfo['shop_type'];
        $this->contentHeaderTitle = app::get('topshop')->_('卡券核销列表');
        return $this->page('topshop/trade/voucher/list.html', $pagedata);
    }

    /**
     * @desc 获取订单列表
     * @return mixed
     * @author: wudi tvplaza
     * @date: 201707311530
     */
    public function search()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('虚拟订单查询');
        $postFilter = input::get();
        $postFilter['shop_id']=$this->shopId;
		/*add_2018/9/11_by_wanghaichao_start*/
		//此时必定为核销的,修改input readonly 属性不能传参问题
		if($postFilter['time_type']=='write'){
			$postFilter['status']=2;
		}
		/*add_2018/9/11_by_wanghaichao_end*/
		
		//echo "<pre>";print_R($postFilter);die();
        $filter = $this->_checkParams($postFilter);
        $objMdlVoucher=app::get('systrade')->model('voucher');
        $count=$objMdlVoucher->count($filter);

        $page =  $filter['pages'] ? $filter['pages'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $result=$objMdlVoucher->batch_dump($filter,'*','default',$offset,$limit,null);
        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['count']=$count;
        $pagedata['list']=$result;
        $pagedata['pagers']=$this->__pager($filter,$currentPage,$count);
        return view::make('topshop/trade/voucher/item.html', $pagedata);
    }

    //延长优惠券使用日期
    public function ajaxChangeTime()
    {
        $id = input::get('voucher_id');
        $item_id = input::get('item_id');
        $batch_open = input::get('batch_open');
        $endTime = input::get('end_time');
        if(!$id)
        {
            return json_encode(['status'=>false,'message'=>'id不能为空']);
        }
        if(!$endTime)
        {
            return json_encode(['status'=>false,'message'=>'时间不能为空']);
        }
        if($batch_open == 1)
        {
            if(!$item_id)
            {
                return json_encode(['status'=>false,'message'=>'商品ID不能为空']);
            }
            $objMdlItem = app::get('sysitem')->model('item');

            if(!$objMdlItem->count(['item_id'=>$item_id,'shop_id'=>$this->shopId])){
                $msg = app::get('sysitem')->_('只能修改本店铺商品');
                return json_encode(['status'=>false,'message'=>$msg]);
            }
        }
        $endTime = strtotime($endTime);
        /** @var base_db_model $objMdlVoucher */
        try{
            $objMdlVoucher=app::get('systrade')->model('voucher');
            if($batch_open == 1)
            {
                $batch_res = $objMdlVoucher->update(['end_time'=>$endTime],['item_id'=>$item_id, 'shop_id'=>$this->shopId, 'status'=>'WAIT_WRITE_OFF']);
                if(is_int($batch_res) && $batch_res>0)
                {
                    return json_encode(['status'=>true,'message'=>'批量修改成功']);
                }
            }
            else
            {
                $res = $objMdlVoucher->update(['end_time'=>$endTime],['voucher_id'=>$id]);
                if(is_int($res) && $res>0)
                {
                    return json_encode(['status'=>true,'message'=>'修改成功']);
                }
            }
        }catch (Exception $exception)
        {
            return json_encode(['status'=>false,'message'=>$exception->getMessage()]);
        }
    }

    /**
     * @desc 格式化分页数据
     * @param $postFilter
     * @param $page
     * @param $count
     * @return array
     * @author: wudi tvplaza
     * @date: 201708121756
     */
    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_trade_voucher@search',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;

    }

    /**
     * @desc 格式化传参
     * @param $filter
     * @return mixed
     * @author: wudi tvplaza
     * @date: 201708121755
     */
    private function _checkParams($filter)
    {
        $statusLUT = array(
            /*modify_2017-11-23_by_xinyufeng_start*/
            //'1' => 'WAIT_WRITE_OFF',
            '1' => array('WAIT_WRITE_OFF', 'GIVING'),
            /*modify_2017-11-23_by_xinyufeng_end*/
            '2' => 'WRITE_FINISHED',
            '3' => 'HAS_OVERDUE',
            /*add_2017-11-23_by_xinyufeng_start*/
            '4' => 'GIVEN',//已赠送
            '5' => 'REFUNDING',//退款中
            '6' => 'SUCCESS'//已退款
            /*add_2017-11-23_by_xinyufeng_end*/
        );
        foreach($filter as $key=>$value)
        {
            if(!$value) unset($filter[$key]);
            if($key=='status' && $value)
            {
                if(in_array($value,array_keys($statusLUT)))
                {
                    /*modify_2017-11-23_by_xinyufeng_start*/
                    //$filter['status'] = $statusLUT[$value];
                    if($value == 1){
                        $filter['status|in'] = $statusLUT[$value];
						/*add_2017/11/29_by_wanghaichao_start*/
						$filter['end_time|bthan']=time();						
						/*add_2017/11/29_by_wanghaichao_end*/
                        unset($filter['status']);
                    }elseif($value==3){		
						/*add_2017/11/29_by_wanghaichao_start*/
						//添加对已过期的判断
						$filter['end_time|sthan']=time();
						unset($filter['status']);
						/*add_2017/11/29_by_wanghaichao_end*/
					}else{
                        $filter['status'] = $statusLUT[$value];
                    }
                    /*modify_2017-11-23_by_xinyufeng_end*/
                }
            }
        }
		/*add_2018/9/11_by_wanghaichao_start*/
		//修改搜索的时间问题
		if($filter['time_type']=='write'){
			$filter['filter_time']=$filter['create_time'];
		}
		/*add_2018/9/11_by_wanghaichao_end*/
        if($filter['filter_time'])
        {
            $times = array_filter(explode('-',$filter['filter_time']));
            if($times){
                if($filter['time_type']=='write'){
                    $filter['write_time_start'] = strtotime($times['0']);
                    $filter['write_time_end'] = strtotime($times['1'])+86400;
                }else{
                    $filter['created_time_start'] = strtotime($times['0']);
                    $filter['created_time_end'] = strtotime($times['1'])+86400;
                    $filter['status']='WRITE_FINISHED';
                }
                unset($filter['create_time']);
            }
        }
        unset($filter['time_type']);
        return $filter;
    }
}

