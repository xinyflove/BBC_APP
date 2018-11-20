<?php
class topshop_ctl_lighticon_gift extends topshop_controller {
    public function giftList()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('奖品列表');
    	$filter = input::get();
    	if(!$filter['pages'])
    	{
    		$filter['pages'] = 1;
    	}
        // $filter['activity_id'] = 1;
    	$pageSize = 10;
    	$params = array(
    		'shop_id' => $this->shopId,
    	    'page_no' => intval($filter['pages']),
    	    'activity_id' => intval($filter['activity_id']),
    	    'page_size' => $pageSize,
    	    'order_by' => 'modified_time desc',
            'fields' => '*',
    	    'status' => '0',
    	);

    	$giftListData = app::get('topshop')->rpcCall('actlighticon.gift.list', $params, 'seller');
        $pagedata['activity_id'] = intval($filter['activity_id']);
    	$pagedata['search_keywords'] = $filter['search_keywords'];

        $count = $giftListData['count'];
        $pagedata['giftList'] = $giftListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_lighticon_gift@giftList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($giftListData);
    	return $this->page('topshop/lighticon/gift/list.html', $pagedata);
    }
    //
    public function giftEdit()
    {
    	$this->contentHeaderTitle = app::get('topshop')->_('编辑奖品');

        $activityId = intval(input::get('activity_id'));

        if(!$activityId)
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }
        // 获取活动规则信息
        $giftParams = array(
            'activity_id' => $activityId,
            'shop_id'=>$this->shopId,
            // 'gift_id'=>$giftId,
            'fields' => '*',
        );
        // $pagedata['gift_id'] = $giftId;
        $pagedata['gift'] = app::get('topshop')->rpcCall('actlighticon.gift.list', $giftParams, 'seller');
        if(empty($pagedata['gift']['data'])) {
            for ($i = 1; $i <= 16; $i++) {
                $pagedata['gift']['data']['id' . strval($i)] =
                [
                    'gift_id' => 'id' . strval($i),
                    'gift_name' => '',
                    'image' => '',
                    'gift_total' => 0,
                    'percentage' => 0,
                    'gain_total' => 0,
                    'need_deliver' => 0,
                ];
            }
        }

        $pagedata['activity_id'] = $activityId;
        return $this->page('topshop/lighticon/gift/edit.html', $pagedata);
    }

    public function giftSave()
    {
        $params = input::get();
        $apiData['gift'] = json_encode($params['gift']);
        $apiData['shop_id'] = $this->shopId;
        $apiData['activity_id'] = (int)$params['activity_id'];
        try
        {
            $result = app::get('topshop')->rpcCall('actlighticon.gift.save', $apiData, 'seller');
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            // $url = url::action('topshop_ctl_lighticon_gift@giftList', array('activity_id'=>$params['activity_id']));
            return $this->splash('error',null,$msg,true);
        }
        // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
        $url = url::action('topshop_ctl_lighticon_gift@giftEdit', array('activity_id'=>$params['activity_id']));
        $msg = app::get('topshop')->_('奖品保存成功');
        return $this->splash('success',$url,$msg,true);

    }

    public function giftStatus()
    {
        $giftId = input::get('gift_id', false);
        if( !$giftId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['gift_id'] = (int)$giftId;
            $params['shop_id'] = (int)$this->shopId;
            $params['status'] = 1;//删除
            app::get('topshop')->rpcCall('actlighticon.gift.changestatus',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        // $this->sellerlog('删除图标。账号ID是 '.$giftId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_lighticon_gift@giftList');
        return $this->splash('success',$url,$msg,true);
    }
}