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
     * @modify: xinyufeng 2020-02-08
     */
    public function index()
    {
        $objMdlVoucher = app::get('systrade')->model('voucher');
        //订单屏显状态
        $orderStatusList = $objMdlVoucher::STATUS_INDEX_TITLE_LIST;

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
     * @modify: xinyufeng 2020-02-08
     */
    public function search()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('虚拟订单查询');
        $params = input::get();
        $params['shop_id'] = $this->shopId;

		/*add_2018/9/11_by_wanghaichao_start*/
		//此时必定为核销的,修改input readonly 属性不能传参问题
		if($params['time_type'] == 'write'){
            $params['status'] = 2;
		}
		/*add_2018/9/11_by_wanghaichao_end*/
        $filter = $this->_checkParams($params);
        if($this->loginSupplierId){
            $filter['supplier_id'] = $this->loginSupplierId;
        }
        $objMdlVoucher=app::get('systrade')->model('voucher');
        $count=$objMdlVoucher->count($filter);

        $page =  $filter['pages'] ? $filter['pages'] : 1;
        $limit = $this->limit;
        $pageTotal = ceil($count/$limit);
        $currentPage = ($pageTotal < $page) ? $pageTotal : $page; //防止传入错误页面，返回最后一页信息
        $offset = ($currentPage-1) * $limit;
        $result=$objMdlVoucher->batch_dump($filter,'*','default',$offset,$limit,null);
		foreach($result as $k=>$v){
			$seller=$this->getSellerInfo($v['oid']);		
			$result[$k]['seller_name']=$seller['name'];
			$result[$k]['seller_cart_number']=$seller['cart_number'];
			$result[$k]['seller_mobile']=$seller['mobile'];
		}

        $pagedata['image_default_id'] = kernel::single('image_data_image')->getImageSetting('item');
        $pagedata['count']=$count;
        $pagedata['list']=$result;
        $pagedata['pagers']=$this->__pager($filter,$currentPage,$count);
        $pagedata['status'] = $params['status'];
        return view::make('topshop/trade/voucher/item.html', $pagedata);
    }
	
	/**
	* 根据子订单id获取创客
	* author by wanghaichao
	* date 2019/9/10
	*/
	public function getSellerInfo($oid){
		$order=app::get('systrade')->model('order')->getRow('seller_id',array('oid'=>$oid));
		if (empty($order['seller_id']) || $order['seller_id']==0){
			return '';
		}
		$seller=app::get('sysmaker')->model('seller')->getRow('cart_number,name,mobile',array('seller_id'=>$order['seller_id']));
		return $seller;
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
                $filter = ['item_id'=>$item_id, 'shop_id'=>$this->shopId, 'status'=>'WAIT_WRITE_OFF'];
                if($this->loginSupplierId){
                    $filter['supplier_id'] = $this->loginSupplierId;
                }
                $batch_res = $objMdlVoucher->update(['end_time'=>$endTime], $filter);
                if(is_int($batch_res) && $batch_res>0)
                {
                    return json_encode(['status'=>true,'message'=>'批量修改成功']);
                }
            }
            else
            {
                $filter = ['voucher_id'=>$id];
                if($this->loginSupplierId){
                    $filter['supplier_id'] = $this->loginSupplierId;
                }
                $res = $objMdlVoucher->update(['end_time'=>$endTime], $filter);
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
     * @modify: xinyufeng 2020-02-08
     */
    private function _checkParams($filter)
    {
        // 移除空数据
        foreach($filter as $key=>$value)
        {
            if (empty($value))
            {
                unset($filter[$key]);
                continue;
            }
        }

        $_status = empty($filter['status']) ? 0 : $filter['status'];
        $_time_type = $filter['time_type'];
        unset($filter['status']);
        unset($filter['time_type']);

        if ($_time_type == 'write') $_status = 2;// 如果时间类型是核销时间 状态为2

        if ($_status > 0)
        {
            $objMdlVoucher = app::get('systrade')->model('voucher');
            $filter['status|in'] = $objMdlVoucher->getStatusByIndex($_status);
            switch ($_status)
            {
                case 1:// 未核销(在有效期内的 未核销、赠送中)
                    $filter['end_time|bthan'] = time();// 大于等于当前时间
                    break;
                case 3:// 已过期(不在有效期内的 未核销、赠送中、已过期)
                    $filter['end_time|lthan'] = time();// 小于当前时间
                    break;
            }
        }

        if(!empty($filter['filter_time']))
        {
            $time_arr = array_filter(explode('-', $filter['filter_time']));
            unset($filter['filter_time']);
            if ($_time_type == 'write')
            {
                $filter['write_time_start'] = strtotime($time_arr['0']);
                $filter['write_time_end'] = strtotime($time_arr['1']) + 86400;
            }
            else
            {
                $filter['created_time_start'] = strtotime($time_arr['0']);
                $filter['created_time_end'] = strtotime($time_arr['1']) + 86400;
            }
        }

        return $filter;
    }
}

