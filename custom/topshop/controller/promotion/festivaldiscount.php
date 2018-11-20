<?php
class topshop_ctl_promotion_festivaldiscount extends topshop_controller {

    public function list_festivaldiscount()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('会员日管理');
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
        $festivaldiscountListData = app::get('topshop')->rpcCall('promotion.festivaldiscount.list', $params,'seller');
        $count = $festivaldiscountListData['total'];
        $pagedata['festivaldiscountList'] = $festivaldiscountListData['data'];

        //处理翻页数据
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $gradeList = app::get('topshop')->rpcCall('user.grade.list', ['shop_id' => $this->shopId]);
        // 组织会员等级的key,value的数组，方便取会员等级名称
        $gradeKeyValue = array_bind_key($gradeList, 'grade_id');

        // 增加列表中会员等级名称字段
        foreach($pagedata['festivaldiscountList'] as &$v)
        {
            $valid_grade = explode(',', $v['valid_grade']);

            $checkedGradeName = array();
            foreach($valid_grade as $gradeId)
            {
                $checkedGradeName[] = $gradeKeyValue[$gradeId]['grade_name'];
            }
            $v['valid_grade_name'] = implode(',', $checkedGradeName);
            $v['condition_value'] = $this->condition($v['condition_value']);
        }

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');

        return $this->page('topshop/promotion/festivaldiscount/index.html', $pagedata);
    }


    public function edit_festivaldiscount()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加/编辑会员日促销');

        $apiData['festivaldiscount_id'] = input::get('festivaldiscount_id');
        $apiData['festivaldiscount_itemList'] = true;
        $pagedata['valid_time'] = date('Y/m/d H:i', time()+60) . '-' . date('Y/m/d H:i', time()+120); //默认时间
        if($apiData['festivaldiscount_id'])
        {
            $pagedata = app::get('topshop')->rpcCall('promotion.festivaldiscount.get', $apiData);

            $pagedata['valid_time'] = date('Y/m/d H:i', $pagedata['start_time']) . '-' . date('Y/m/d H:i', $pagedata['end_time']);
            if($pagedata['shop_id']!= $this->shopId)
            {
                return $this->splash('error','','您没有权限编辑此会员日促销',true);
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

        if($pagedata['condition_value'])
        {
            $pagedata['condition_value'] = $this->condition($pagedata['condition_value']);
        }
        $pagedata['check_time'] = date('Y/m/d H:i:s', $pagedata['check_time']);
        foreach( $pagedata['itemsList'] as $itemRow )
        {
            $pagedata['item_sku'][$itemRow['item_id']] = $itemRow['sku_ids'];
        }
        return $this->page('topshop/promotion/festivaldiscount/edit.html', $pagedata);
    }

    //查看会员日促销
    public function show_festivaldiscount(){
        $this->contentHeaderTitle = app::get('topshop')->_('查看会员日促销 ');
        $apiData['festivaldiscount_id'] = input::get('festivaldiscount_id');
        $apiData['festivaldiscount_itemList'] = true;
        if($apiData['festivaldiscount_id'])
        {
            $pagedata = app::get('topshop')->rpcCall('promotion.festivaldiscount.get',$apiData);
            $pagedata['valid_time'] = date('Y/m/d H:i',$pagedata['start_time']).' ~ '.date('Y/m/d H:i',$pagedata['end_time']);
            if($pagedata['shop_id'] != $this->shopId)
            {
                return $this->splash('error','','您没有权限查看此会员日促销 ',true);
            }
            $notItems = array_column($pagedata['itemsList'], 'item_id');
            $pagedata['notEndItem'] = json_encode($notItems);
        }
        $valid_grade = explode(',', $pagedata['valid_grade']);
        $pagedata['gradeList'] = app::get('topshop')->rpcCall('user.grade.list', ['shop_id' => $this->shopId]);
        $gradeIds = array_column($pagedata['gradeList'],'grade_id');
        if( !array_diff($gradeIds, $valid_grade))
        {
            $gradeStr = '所有会员';
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
        if($pagedata['condition_value'])
        {
            $pagedata['condition_value'] = $this->condition($pagedata['condition_value']);
        }
        $pagedata['ac'] = input::get('ac');
        return $this->page('topshop/promotion/festivaldiscount/show.html',$pagedata);
    }

    public function condition($condition)
    {
        $condList = explode(',',$condition);
        foreach ($condList as $key => $value)
        {
            $condList[$key] = explode('|',$value);
        }
        return $condList;
    }

    public function save_festivaldiscount()
    {
        $params = input::get();

        $apiData['festivaldiscount_id'] = $params['festivaldiscount_id'];
        $apiData['festivaldiscount_name'] = $params['festivaldiscount_name'];
        $apiData['custom_tag'] = $params['custom_tag'];
        $apiData['limit_number'] = intval($params['limit_number']);
        $apiData['discount'] = intval($params['discount']);
        $apiData['join_limit'] = intval($params['join_limit']);
        $apiData['used_platform'] = intval($params['used_platform']);
        $apiData['free_postage'] = intval($params['free_postage']);
        //优惠规则描述
        if($params['role_desc'])
        {
            $apiData['festivaldiscount_desc'] = htmlspecialchars(strip_tags($params['role_desc']), ENT_QUOTES, 'UTF-8');
        }

        //新添加的字段condition_value
        $conditionValue = null;
        foreach((array)$params['limit_number'] as $k=>$v)
        {
            $joinfullminus = array();
            $joinfullminus['limit_number'] = $v;
            $joinfullminus['discount'] = $params['discount'][$k];
            $conditionValue[] = $joinfullminus;
        }
        $apiData['condition_value'] = $conditionValue ? json_encode($conditionValue) : null;
        $apiData['shop_id'] = $this->shopId;
        $timeArray = explode('-', $params['valid_time']);
        $apiData['start_time']  = strtotime($timeArray[0]);
        $apiData['end_time'] = strtotime($timeArray[1]);
        $apiData['valid_grade'] = implode(',', $params['grade']);

        $festivaldiscountRelItem = null;
        foreach( (array)$params['item_id'] as $key=>$itemId )
        {
            $itemData['item_id'] = $itemId;
            $itemData['sku_id'] = $params['item_sku'][$key];
            $festivaldiscountRelItem[] = $itemData;
        }
        $apiData['festivaldiscount_rel_item'] = $festivaldiscountRelItem ? json_encode($festivaldiscountRelItem) : null;

        try
        {
            if($params['festivaldiscount_id'])
            {
                // 修改会员日促销
                $result = app::get('topshop')->rpcCall('promotion.festivaldiscount.update', $apiData);
            }
            else
            {
                // 新添会员日促销
                $result = app::get('topshop')->rpcCall('promotion.festivaldiscount.add', $apiData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            if($params['festivaldiscount_id'])
            {
                $url = url::action('topshop_ctl_promotion_festivaldiscount@edit_festivaldiscount', array('festivaldiscount_id'=>$params['festivaldiscount_id']));
            }
            else{
                $url = url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount');
            }
            return $this->splash('error',$url,$msg,true);
        }
        $this->sellerlog('添加/修改会员日促销。会员日促销名称是 '.$apiData['festivaldiscount_name']);
        $url = url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount');
        $msg = app::get('topshop')->_('保存会员日促销成功');
        return $this->splash('success',$url,$msg,true);
    }

    //提交审核
    public function submit_approve(){
        $apiData = input::get();
        $festivaldiscountInfo = app::get('topshop')->rpcCall('promotion.festivaldiscount.get',$apiData);
        try{
            if($festivaldiscountInfo['end_time'] <= time()){
                throw new \LogicException('您的活动已过期，无法提交审核!');
            }
            $result = app::get('topshop')->rpcCall('promotion.festivaldiscount.approve',$apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('更新会员日促销。会员日促销ID是 '.$apiData['festivaldiscount_id']);
        $url = url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount');
        $msg = app::get('topshop')->_('提交审核成功');
        return $this->splash('success', $url, $msg, true);
    }

    public function delete_festivaldiscount()
    {
        $apiData['shop_id'] = $this->shopId;
        $apiData['festivaldiscount_id'] = input::get('festivaldiscount_id');
        $url = url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount');
        try
        {
            app::get('topshop')->rpcCall('promotion.festivaldiscount.delete', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('删除会员日促销。会员日促销ID是 '.$apiData['festivaldiscount_id']);
        $msg = app::get('topshop')->_('删除会员日促销成功');
        return $this->splash('success', $url, $msg, true);
    }

    public function cancel_festivaldiscount()
    {
        $apiData['shop_id'] = $this->shopId;
        $apiData['festivaldiscount_id'] = input::get('festivaldiscount_id');
        $url = url::action('topshop_ctl_promotion_festivaldiscount@list_festivaldiscount');
        try
        {
            app::get('topshop')->rpcCall('promotion.festivaldiscount.cancel', $apiData);
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }
        $this->sellerlog('取消会员日促销。会员日促销ID是 '.$apiData['festivaldiscount_id']);
        $msg = app::get('topshop')->_('取消会员日促销成功');
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
}

