<?php
class topshop_ctl_promotion_coupon extends topshop_controller {
    // 王衍生-2018/09/03-start
    // 发放优惠券事件
    protected $grant_event = [
        'register' => '用户注册',
        'benefit' => '会员权益礼包',
    ];
    // 王衍生-2018/09/03-end
    public function list_coupon()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('优惠券管理');
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
        );
        $couponListData = app::get('topshop')->rpcCall('promotion.coupon.list', $params,'seller');
        $count = $couponListData['count'];
        $pagedata['couponList'] = $couponListData['coupons'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_promotion_coupon@list_coupon', $filter),
            'current'=>$current,
            'total'=>$total,
            'use_app'=>'topshop',
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');

        return $this->page('topshop/promotion/coupon/index.html', $pagedata);
    }

    public function edit_coupon()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('新添/编辑优惠券');
        $apiData['coupon_id'] = input::get('coupon_id');
        $apiData['coupon_itemList'] = true;
        $pagedata['valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        $pagedata['cansend_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        if($apiData['coupon_id'])
        {
            $pagedata = app::get('topshop')->rpcCall('promotion.coupon.get', $apiData);
            $pagedata['valid_time'] = date('Y/m/d H:i', $pagedata['canuse_start_time']) . '-' . date('Y/m/d H:i', $pagedata['canuse_end_time']);
            $pagedata['cansend_time'] = date('Y/m/d H:i', $pagedata['cansend_start_time']) . '-' . date('Y/m/d H:i', $pagedata['cansend_end_time']);
            if($pagedata['shop_id']!=$this->shopId)
            {
                return $this->splash('error','','您没有权限编辑此优惠券',true);
            }
            $notItems = array_column($pagedata['itemsList'], 'item_id');
            $pagedata['notEndItem'] =  json_encode($notItems,true);
        }

        $valid_grade = explode(',', $pagedata['valid_grade']);
        $pagedata['gradeList'] = app::get('topshop')->rpcCall('user.grade.list', ['shop_id' => $this->shopId]);
        foreach($pagedata['gradeList'] as &$v)
        {
            if( in_array($v['grade_id'], $valid_grade) )
            {
                $v['is_checked'] = true;
            }
        }
        foreach( $pagedata['itemsList'] as $itemRow )
        {
            $pagedata['item_sku'][$itemRow['item_id']] = $itemRow['sku_ids'];
        }
		/*add_2018/5/25_by_wanghaichao_start*/
		if(strpos($pagedata['deduct_money'],'-')){
			$deduct_money=explode('-',$pagedata['deduct_money']);
			$pagedata['deduct_money1']=$deduct_money[0];
			$pagedata['deduct_money2']=$deduct_money[1];
		}else{
			$pagedata['deduct_money1']=$pagedata['deduct_money'];
			$pagedata['deduct_money2']=$pagedata['deduct_money'];
		}
		/*add_2018/5/25_by_wanghaichao_end*/
        $pagedata['all_grant_event'] = $this->grant_event;

        return $this->page('topshop/promotion/coupon/edit.html', $pagedata);
    }

    /**
     * @发放优惠券
     */
    public function grant_coupon()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('发放优惠券');
        $coupon_id = input::get('coupon_id');
        $pagedata['valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        $pagedata['cansend_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        if($coupon_id)
        {
            $pagedata = app::get('topshop')->rpcCall('promotion.coupon.get', ['coupon_id'=>$coupon_id]);
            $pagedata['valid_time'] = date('Y/m/d H:i', $pagedata['canuse_start_time']) . '-' . date('Y/m/d H:i', $pagedata['canuse_end_time']);
            $pagedata['cansend_time'] = date('Y/m/d H:i', $pagedata['cansend_start_time']) . '-' . date('Y/m/d H:i', $pagedata['cansend_end_time']);
            if($pagedata['shop_id']!=$this->shopId)
            {
                return $this->splash('error','','您没有权限编辑此优惠券',true);
            }
            $pagedata['coupon_id'] = $coupon_id;
        }
        return $this->page('topshop/promotion/coupon/grant.html', $pagedata);
    }

    //查看优惠券
    public function show_coupon(){
        $this->contentHeaderTitle = app::get('topshop')->_('查看优惠券');
        $apiData['coupon_id'] = input::get('coupon_id');
        $apiData['coupon_itemList'] = true;
        if($apiData['coupon_id']){
            $pagedata = app::get('topshop')->rpcCall('promotion.coupon.get',$apiData);
            $pagedata['valid_time'] = date('Y/m/d H:i',$pagedata['canuse_start_time']).' ~ '.date('Y/m/d H:i',$pagedata['canuse_end_time']);
            $pagedata['send_time'] = date('Y/m/d H:i',$pagedata['cansend_start_time']).' ~ '.date('Y/m/d H:i',$pagedata['cansend_end_time']);
            if($pagedata['shop_id'] != $this->shopId)
            {
                return $this->splash('error','您没有权限查看此优惠券',true);
            }
            $notItems = array_column($pagedata['itemsList'], 'item_id');
            $pagedata['notEndItem'] = json_encode($notItems,true);
        }

        $valid_grade  = explode(',', $pagedata['valid_grade']);
        $pagedata['gradeList'] = app::get('topshop')->rpcCall('user.grade.list',['shop_id' => $this->shopId]);
        $gradeIds = array_column($pagedata['gradeList'],'grade_id');
        if( !array_diff($gradeIds, $valid_grade))
        {
            $gradeStr = ' 所有会员';
        }
        else
        {
            foreach ($pagedata['gradeList'] as $member) {
                if(in_array($member['grade_id'],$valid_grade))
                {
                    $gradeStr .= $member['grade_name'].',';
                }
            }
            $gradeStr = rtrim($gradeStr,',');
        }
        $pagedata['grade_str'] = $gradeStr;
        $pagedata['ac'] = input::get('ac', '');
        $pagedata['all_grant_event'] = $this->grant_event;
        return $this->page('topshop/promotion/coupon/show.html',$pagedata);
    }

    public function save_coupon()
    {
        $params = input::get();
        $apiData = $params;
        $apiData['shop_id'] = $this->shopId;
        // 可使用的有效期
        $canuseTimeArray = explode('-', $params['valid_time']);
        $apiData['canuse_start_time']  = strtotime($canuseTimeArray[0]);
        $apiData['canuse_end_time'] = strtotime($canuseTimeArray[1]);
        // 可以领取的时间段
        $cansendTimeArray = explode('-', $params['cansend_time']);
        $apiData['cansend_start_time']  = strtotime($cansendTimeArray[0]);
        $apiData['cansend_end_time'] = strtotime($cansendTimeArray[1]);
        // 可以使用的会员等级
        $apiData['valid_grade'] = implode(',', $params['grade']);
        $couponRelItem = null;
        foreach( (array)$params['item_id'] as $key=>$itemId )
        {
            $itemData['item_id'] = $itemId;
            $itemData['sku_id'] = $params['item_sku'][$key];
            $couponRelItem[] = $itemData;
        }
        $apiData['coupon_rel_item'] = $couponRelItem ? json_encode($couponRelItem) : null;
        // 王衍生-2018/09/03-start
        $apiData['grant_event'] = implode('|', $apiData['grant_event']);
        // 王衍生-2018/09/03-end
        try
        {
            if($params['coupon_id'])
            {
                // 修改优惠券
                $result = app::get('topshop')->rpcCall('promotion.coupon.update', $apiData);
            }
            else
            {
                // 新添优惠券
                $result = app::get('topshop')->rpcCall('promotion.coupon.add', $apiData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            $url = url::action('topshop_ctl_promotion_coupon@edit_coupon', array('coupon_id'=>$params['coupon_id']));
            return $this->splash('error',$url,$msg,true);
        }
        $this->sellerlog('添加/修改优惠券。优惠券名称是 '.$apiData['coupon_name']);
        $url = url::action('topshop_ctl_promotion_coupon@list_coupon');
        $msg = app::get('topshop')->_('保存优惠券成功');
        return $this->splash('success',$url,$msg,true);
    }

    public function grant_coupon_code()
    {
        $shop_id = $this->shopId;
        $post_data = input::get();
        $coupon_id_string = trim($post_data['coupon_id']);
        $coupon_id_string = str_replace("，",",",$coupon_id_string);
        $coupon_id_array = explode(',', $coupon_id_string);
        $coupon_id_array = array_unique($coupon_id_array);
        $coupon_id_array = array_filter($coupon_id_array);

        /*处理发放给全部用户开始*/
        if($post_data['is_all_user'])
        {
            $bool = $this->_couponDistribute($coupon_id_array, $msg);
            $url = url::action('topshop_ctl_promotion_coupon@grant_coupon');
            if($bool)
            {
                return $this->splash('success', $url, '领取成功', true);
            }
            return $this->splash('error', $url, $msg, true);
        }
        /*处理发放给全部用户结束*/

        if(!$post_data['is_all_user'] && !$post_data['user_id'])
        {
            return $this->splash('error', '', '没有指定用户', true);
        }
        $grant_user_id_array = $post_data['user_id'];

        try
        {
            $db = app::get('systrade')->database();
            $db->beginTransaction();
            foreach($grant_user_id_array as $grant_user_id)
            {
                $userInfo = app::get('topc')->rpcCall('user.get.info',array('user_id'=>$grant_user_id),'buyer');
                foreach($coupon_id_array as $coupon_id)
                {
                    $validator = validator::make(
                        [$coupon_id],
                        ['numeric']
                    );
                    if ($validator->fails())
                    {
                        throw new \LogicException('领取优惠券参数错误!');
                    }

                    $coupon_info = app::get('topshop')->rpcCall('promotion.coupon.get', ['coupon_id'=>$coupon_id]);
                    if($coupon_info['shop_id']!=$this->shopId)
                    {
                        throw new \LogicException('您没有权限编辑ID为'.$coupon_id.'的优惠券!');
                    }
                    $apiData = array(
                        'coupon_id' => $coupon_id,
                        'user_id' =>$grant_user_id,
                        'shop_id' =>$shop_id,
                        'grade_id' =>$userInfo['grade_id'],
                    );

                    if(!app::get('topc')->rpcCall('user.coupon.getCode', $apiData))
                    {
                        throw new \LogicException('领取失败！');
                    }
                }
            }
            $db->commit();
            $url = url::action('topshop_ctl_promotion_coupon@grant_coupon');
            return $this->splash('success', $url, '领取成功', true);
        }
        catch(\LogicException $e)
        {
            $db->rollback();
            $msg = $e->getMessage();
            return $this->splash('error', '', $msg, true);
        }
    }

    public function submit_approve(){
        $apiData = input::get();
        try{
            $couponInfo = app::get('topshop')->rpcCall('promotion.coupon.get',$apiData);
            if($couponInfo['cansend_end_time'] <= time()){
                throw new \LogicException('您的活动已过期，无法提交审核!');
            }
            $result = app::get('topshop')->rpcCall('promotion.coupon.approve',$apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('更新优惠券。优惠券ID是 '.$apiData['coupon_id']);
        $url = url::action('topshop_ctl_promotion_coupon@list_coupon');
        $msg = app::get('topshop')->_('提交审核成功');
        return $this->splash('success', $url, $msg, true);
    }

    public function delete_coupon()
    {
        $apiData['shop_id'] = $this->shopId;
        $apiData['coupon_id'] = input::get('coupon_id');
        $url = url::action('topshop_ctl_promotion_coupon@list_coupon');
        try
        {
            app::get('topshop')->rpcCall('promotion.coupon.delete', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('删除优惠券。优惠券ID是 '.$apiData['coupon_id']);
        $msg = app::get('topshop')->_('删除优惠券成功');
        return $this->splash('success', $url, $msg, true);
    }

    public function cancel_coupon()
    {
        $apiData['shop_id'] = $this->shopId;
        $apiData['coupon_id'] = input::get('coupon_id');
        $url = url::action('topshop_ctl_promotion_coupon@list_coupon');
        try
        {
            app::get('topshop')->rpcCall('promotion.coupon.cancel', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('取消优惠券促销。优惠券促销ID是 '.$apiData['coupon_id']);
        $msg = app::get('topshop')->_('取消优惠券促销成功');
        return $this->splash('success', $url, $msg, true);
    }

    //根据商家id和3级分类id获取商家所经营的所有品牌
    public function getBrandList()
    {
        $shopId = $this->shopId;
        $catId = input::get('catId');
        $params = array(
            'shop_id'=>$shopId,
            'cat_id'=>$catId,
            'fields'=>'brand_id,brand_name,brand_url'
        );
        $brands = app::get('topshop')->rpcCall('category.get.cat.rel.brand',$params);
        return response::json($brands);
    }

    public function ajaxCouponList()
    {
        // 店铺优惠券信息,
        $params = array(
            'page_no' => input::get('pages',1),
            'page_size' => input::get('page_size',10),
            'fields' => 'deduct_money,coupon_name,coupon_id,canuse_start_time,canuse_end_time,cansend_start_time,cansend_end_time',
            'shop_id' => $this->shopId,
            'platform' => input::get('platform','pc'),
            'is_cansend' => 1,
        );

        $filter = input::get();

        $couponListData = app::get('topc')->rpcCall('promotion.coupon.list', $params);
        $count = $couponListData['count'];
        $pagedata['list'] = $couponListData['coupons'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_promotion_coupon@ajaxCouponList', $filter),
            'current'=>$current,
            'total'=>$total,
            'use_app'=>'topshop',
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        return view::make('topshop/promotion/coupon/ajaxList.html', $pagedata);
    }

    /**
     * 优惠券指定发放处理
     * @param $coupon_id_array
     * @param $msg
     * @return bool
     * @auth xinyufeng
     */
    protected function _couponDistribute($coupon_id_array, &$msg)
    {
        /*判断优惠券是否有效开始*/
        foreach($coupon_id_array as $coupon_id)
        {
            $validator = validator::make(
                [$coupon_id],
                ['numeric']
            );
            if ($validator->fails())
            {
                $msg = '领取优惠券参数错误!';
                return false;
            }

            $coupon_info = app::get('topshop')->rpcCall('promotion.coupon.get', ['coupon_id'=>$coupon_id]);
            if($coupon_info['shop_id'] != $this->shopId)
            {
                $msg = '您没有权限编辑ID为'.$coupon_id.'的优惠券!';
                return false;
            }

            $curr_time = time();
            if($coupon_info['cansend_start_time'] > $curr_time)
            {
                $msg = '未到领取时间!';
                return false;
            }
            if($coupon_info['cansend_end_time'] < $curr_time)
            {
                $msg = '领取时间已过!';
                return false;
            }
        }
        /*判断优惠券是否有效结束*/

        $queueData = array(
            'coupon_id_array' => $coupon_id_array,
            // 'shop_id' => $this->shopId,
            'page' => 1,
            'page_size' => 500,
        );
        queue::push('syspromotion_tasks_couponDistribute','syspromotion_tasks_couponDistribute', $queueData);

        return true;
        $accountMdl = app::get('sysuser')->model('account');
        $count = $accountMdl->count();
        $page_size = 1000;
        $pageTotal = ceil($count/$page_size);
        $orderBy = 'user_id ASC';
        $delay = 0;    //单位秒
        for ($i=0; $i<$pageTotal; $i++)
        {
            $userList = $accountMdl->getList('user_id', array(), $i*$page_size, $page_size, $orderBy);
            $grant_user_id_array = array_column($userList, 'user_id');

            if($grant_user_id_array)
            {
                $queueData['grant_user_id_array'] = $grant_user_id_array;
                $queueData['page'] = $i;
                //file_put_contents('log1.txt', var_export($queueData,true), FILE_APPEND);
                //queue::push('syspromotion_tasks_couponDistribute','syspromotion_tasks_couponDistribute', $queueData);
                queue::later('syspromotion_tasks_couponDistribute','syspromotion_tasks_couponDistribute', $queueData, $delay);

                // 测试使用
                //kernel::single('syspromotion_tasks_couponDistribute')->exec($queueData);

                $delay = $delay + 60;
                unset($queueData['grant_user_id_array']);
            }
        }

        return true;
    }
}

