<?php
class topshop_ctl_activity_gift extends topshop_controller {

    /**
     * 赠品列表
     * @return html
     * @auth xinyufeng
     */
    public function index()
    {
        $active_id = input::get('active_id');
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'赠品管理';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        /*获取数据列表开始*/
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 10;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'fields' =>'*',
            'shop_id'=> $this->shopId,
            'active_id'=> $active_id,
            'gift_name' => $filter['gift_name'] ? $filter['gift_name'] : '',
            'supplier_id' => $filter['supplier_id'] ? $filter['supplier_id'] : 0,
            'orderBy'=> ' create_time DESC',
        );

        $giftListData = app::get('topshop')->rpcCall('sysactivityvote.gift.data.list', $params);
        /*获取数据列表结束*/

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['giftList'] = $giftListData['data'];

        /*处理翻页数据开始*/
        $count = $giftListData['total'];
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_gift@index', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');
        /*处理翻页数据结束*/

        $pagedata['supplier'] = $this->__getSupplierList();   //获取供应商信息

        $pagedata['search'] = array(
            'gift_name' => $filter['gift_name'] ? $filter['gift_name'] : '',
            'supplier_id' => $filter['supplier_id'] ? $filter['supplier_id'] : 0,
        );
        
        return $this->page('topshop/activity/gift/list.html', $pagedata);
    }

    /**
     * 添加/编辑赠品
     * @return html
     * @auth xinyufeng
     */
    public function edit_gift()
    {
        $params = input::get();

        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'添加/编辑赠品';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        $gift_valid_time = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //赠品有效期默认时间
        $showData = array();
        $showData['gift_total'] = 0;
        $showData['gain_total'] = 0;

        if(!empty($params['gift_id']))
        {
            $objMdlGift = app::get('sysactivityvote')->model('gift');
            $showData = $objMdlGift->getRow('*',array('gift_id'=>$params['gift_id']));
            $showData['images'] = $showData['list_image'] ? explode(',', $showData['list_image']) : '';
            $gift_valid_time = date('Y/m/d H:i', $showData['valid_start_time']) . '-' . date('Y/m/d H:i', $showData['valid_end_time']);
        }

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['gift_id'] = $params['gift_id'];
        $pagedata['supplier'] = $this->__getSupplierList();   //获取供应商信息
        $pagedata['gift_valid_time'] = $gift_valid_time;
        $pagedata['gift'] = $showData;

        return $this->page('topshop/activity/gift/edit.html', $pagedata);
    }

    /**
     * 保存赠品
     * @return string
     * @auth xinyufeng
     */
    public function save_gift()
    {
        $params = input::get();

        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        if(empty($activeInfo)) return $this->splash('error','','此活动不存在',true);

        $saveData = array();
        $saveData['shop_id'] = $this->shopId;
        $saveData['active_id'] = $params['active_id'];
        $saveData['gift_name'] = $params['gift']['gift_name'];
        $saveData['supplier_id'] = $params['gift']['supplier_id'];
        $saveData['gift_profile'] = $params['gift']['gift_profile'];
        $saveData['gift_total'] = $params['gift']['gift_total'];
        $saveData['gain_total'] = $params['gift']['gain_total'];
        $saveData['gift_desc'] = $params['gift']['gift_desc'];
        $saveData['gift_wap_desc'] = $params['gift']['gift_wap_desc'];

        $giftTimeArray = explode('-', $params['gift_valid_time']);//赠品有效期
        $saveData['valid_start_time']  = strtotime($giftTimeArray[0]);
        $saveData['valid_end_time'] = strtotime($giftTimeArray[1]);

        $saveData['image_default_id'] = $params['listimages'][0] ? $params['listimages'][0] : '';
        $saveData['list_image'] = $params['listimages'] ? implode(',', $params['listimages']) : '';
        $saveData['deleted'] = 0;
        if(!empty($params['gift_id'])) {
            $saveData['gift_id'] = $params['gift_id'];
            $saveData['modified_time'] = time();
        }else{
            $saveData['create_time'] = time();
            $saveData['modified_time'] = $saveData['create_time'];
        }

        try
        {
            if($saveData['gift_id']){
                //更新
                $objMdlGift = app::get('sysactivityvote')->model('gift');
                $result = $objMdlGift->update($saveData, array('gift_id'=>$saveData['gift_id']));

            }else{
                //新增
                $objMdlGift = app::get('sysactivityvote')->model('gift');
                $result = $objMdlGift->insert($saveData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();

            if($params['gift_id'])
            {
                $url = url::action('topshop_ctl_activity_gift@edit_game', array('active_id'=>$active_id,'gift_id'=>$params['gift_id']));
            }
            else{
                $url = url::action('topshop_ctl_activity_gift@index',array('active_id'=>$active_id));
            }
            return $this->splash('error',$url,$msg,true);
        }

        $this->sellerlog('添加/编辑赠品。赠品名称是 '.$saveData['gift_name']);
        $url = url::action('topshop_ctl_activity_gift@index',array('active_id'=>$active_id));
        $msg = app::get('topshop')->_('保存赠品成功');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 删除赠品
     * @return string
     * @auth xinyufeng
     */
    public function delete_gift()
    {
        $gift_id = input::get('gift_id');

        /*有关联数据无法删除 开始*/
        $objMdlGiftGain = app::get('sysactivityvote')->model('gift_gain');
        $giftGainInfo = $objMdlGiftGain->getRow('gift_gain_id',array('gift_id'=>$gift_id));
        if(!empty($giftGainInfo)){
            return $this->splash('error', '', '此赠品有关联数据，无法删除。(请先删除此赠品的获取记录)', true);
        }
        /*有关联数据无法删除 结束*/

        $url = url::action('topshop_ctl_activity_gift@index');
        try
        {
            $objMdlGift = app::get('sysactivityvote')->model('gift');
            $res = $objMdlGift->delete(array('gift_id'=>$gift_id));
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $this->sellerlog('删除赠品。赠品ID是 '.$gift_id);
        $msg = app::get('topshop')->_('删除赠品成功');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 获取供应商信息列表
     * @return array
     * @auth xinyufeng
     */
    private function __getSupplierList()
    {
        $supplierparams['shop_id'] = $this->shopId;
        $supplierparams['is_audit'] = 'PASS';
        $tmpData = app::get('topshop')->rpcCall('supplier.shop.list',$supplierparams);
        $supplierList = array();
        foreach ($tmpData as $v){
            $supplierList[$v['supplier_id']] = $v;
        }
        return $supplierList;
    }
    
    public function giftGainList()
    {
    	$this->contentHeaderTitle = app::get('topwap')->_('赠品获取记录');
    	$filter = input::get();
    	if(!$filter['pages'])
    	{
    		$filter['pages'] = 1;
    	}
        if(!$filter['active_id'])
        {
            throw new \LogicException(app::get('topshop')->_('异常操作！'));
        }
    	$pageSize = 10;

    	$params = array(
    		'shop_id' => $this->shopId,
    	    'page_no' => intval($filter['pages']),
    	    'active_id' => ($filter['active_id']),
    	    'page_size' => $pageSize,
    	    'order_by' => 'modified_time desc',
            'fields' => '*',
            // 'status' => '',
    	    'deleted' => '0',
    	);
        $exportFilter['active_id'] = $filter['active_id'];
        if($filter['user_name'])
        {
            $params['user_name'] = $filter['user_name'];
            $pagedata['user_name'] = $filter['user_name'];
            $userId = app::get('topshop')->rpcCall('user.get.account.id', ['user_name' => $filter['user_name']], 'seller');
            if($userId)
            {
                $exportFilter['user_id'] = array_shift($userId);
            }
        }

        if($filter['gift_id'])
        {
            $params['gift_id'] = $filter['gift_id'];
            $pagedata['gift_id'] = $filter['gift_id'];
            $exportFilter['gift_id'] = $params['gift_id'];
        }

        if(isset($filter['status']) && $filter['status'] != '')
        {
            $params['status'] = $filter['status'];
            $pagedata['status'] = $filter['status'];
            $exportFilter['status'] = $params['status'];
        }

        $pagedata['exportFilter'] = json_encode($exportFilter);
        // jj($params);
        $giftGainListData = app::get('topshop')->rpcCall('activity.vote.gift.gain.list', $params, 'seller');
    	$pagedata['active_id'] = intval($filter['active_id']);

        $count = $giftGainListData['count'];
        $pagedata['giftGainList'] = $giftGainListData['data'];

        $giftParams = [
                        'active_id' => ($filter['active_id']),
                    ];

        $pagedata['giftList'] = app::get('topshop')->rpcCall('sysactivityvote.gift.list', $giftParams, 'seller');

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_gift@giftGainList', $filter),
            'current'=>$current,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
    	// jj($giftGainListData);
    	return $this->page('topshop/activity/gift/gain/list.html', $pagedata);
    }

    // 
    // public function expertEdit()
    // {
    // 	$this->contentHeaderTitle = app::get('topshop')->_('编辑专家');

    //     $activityId = intval(input::get('active_id'));
    //     $expertId = intval(input::get('gift_gain_id'));

    //     if(!$activityId)
    //     {
    //         throw new \LogicException(app::get('topshop')->_('异常操作！'));
    //     }

    //     //面包屑
    //     $this->runtimePath = array(
    //         ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
    //         ['url'=> url::action('topshop_ctl_activity_gift@expertList', ['active_id' => $activityId]),'title' => app::get('topshop')->_('专家管理')],
    //         ['title' => app::get('topshop')->_('编辑专家')],
    //     );

    //     if($expertId)
    //     {
    //     	// 获取活动规则信息
    //     	$expertParams = array(
    //     	    'active_id' => $activityId,
    //     	    'shop_id'=>$this->shopId,
    //     	    'gift_gain_id'=>$expertId,
    //     	    'fields' => '*',
    //     	);
    //     	$pagedata['gift_gain_id'] = $expertId;
    //     	$pagedata['expert'] = app::get('topshop')->rpcCall('activity.gift.expert.get', $expertParams, 'seller');
    //     }
    //     $pagedata['active_id'] = $activityId;
    //     return $this->page('topshop/activity/gift/expert/edit.html', $pagedata);
    // }

    // public function expertSave()
    // {
    //     $params = input::get();
    //     $apiData = $params['expert'];
    //     $apiData['shop_id'] = $this->shopId;
    //     $apiData['active_id'] = (int) $params['active_id'];
    //     $apiData['gift_gain_id'] = (int) $params['gift_gain_id'];

    //     try
    //     {
    //         // 活动报名保存
    //         $result = app::get('topshop')->rpcCall('activity.gift.expert.save', $apiData, 'seller');
    //     }
    //     catch(\LogicException $e)
    //     {
    //         $msg = $e->getMessage();
    //         $url = url::action('topshop_ctl_activity_gift@expertList', array('active_id'=>$params['active_id']));
    //         return $this->splash('error',$url,$msg,true);
    //     }
    //     // $this->sellerlog('申请活动。活动ID是 '.$apiData['activity_id']);
    //     $url = url::action('topshop_ctl_activity_gift@expertList', array('active_id'=>$params['active_id']));
    //     $msg = app::get('topshop')->_('专家保存成功');
    //     return $this->splash('success',$url,$msg,true);

    // }

    public function giftGainDelete()
    {
        $giftGainId = input::get('gift_gain_id', false);
        if( !$giftGainId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['gift_gain_id'] = (int)$giftGainId;
            $params['shop_id'] = (int)$this->shopId;
            app::get('topshop')->rpcCall('activity.vote.gift.gain.delete',$params);
        }
        
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $this->sellerlog('删除子获得赠品记录。账号ID是 '.$giftGainId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_activity_gift@giftGainList');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 奖品说明
     * @return html
     */
    public function giftGainExplain()
    {
        $this->contentHeaderTitle = app::get('topwap')->_('赠品说明');
        $active_id = input::get('active_id');
        $activity_data = app::get('topshop')->rpcCall('sysactivityvote.active.get', ['active_id'=>$active_id]);
        $pagedata = [
            'data'=>$activity_data
        ];
        return $this->page('topshop/activity/gift/explain.html',$pagedata);
    }

    /**
     * 奖品说明编辑
     */
    public function giftExplainEdit()
    {
        $input = input::get();
        $active_id = $input['active_id'];
        $gift_wap_rule = $input['gift_wap_rule'];
        /** @var base_db_model $sysactivityvote_active */
        $sysactivityvote_active = app::get('sysactivityvote')->model('active');
        $sysactivityvote_active->update(['gift_wap_rule'=>$gift_wap_rule],['active_id'=>$active_id]);
        return $this->splash('success',action('topshop_ctl_activity_vote@index'),'更新成功',true);
    }
}