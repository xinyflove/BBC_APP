<?php
class topshop_ctl_activity_game extends topshop_controller {

    /**
     * 参赛信息列表
     * @return html
     * @auth xinyufeng
     */
    public function index()
    {
        $active_id = input::get('active_id');
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'参赛信息管理';
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
            'game_name' => $filter['game_name'] ? $filter['game_name'] : '',
            'game_number' => $filter['game_number'] ? $filter['game_number'] : '',
            'cat_id' => $filter['cat_id'] ? $filter['cat_id'] : 0,
            'orderBy'=> ' order_sort ASC, create_time DESC',
        );
        if(isset($filter['is_game']) && $filter['is_game']!=-1) $params['is_game'] = $filter['is_game'];

        $gameListData = app::get('topshop')->rpcCall('sysactivityvote.game.list', $params);
        /*获取数据列表结束*/

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['activeInfo'] = $activeInfo;
        $pagedata['gameList'] = $gameListData['data'];

        /*处理翻页数据开始*/
        $count = $gameListData['total'];
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_game@index', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');
        /*处理翻页数据结束*/

        $game_type = app::get('sysactivityvote')->model('type')
            ->getList('type_id,type_name', array('deleted'=>0), 0, 1000, ' type_id DESC');
        $game_type = array_column($game_type,'type_name','type_id');
        $pagedata['game_type'] = $game_type;

        $pagedata['GameCatList'] = $this->__getGameCatList($active_id);

        $pagedata['search'] = array(
            'game_name' => $filter['game_name'] ? $filter['game_name'] : '',
            'game_number' => $filter['game_number'] ? $filter['game_number'] : '',
            'cat_id' => $filter['cat_id'] ? $filter['cat_id'] : 0,
            'is_game' => isset($filter['is_game']) ? $filter['is_game'] : -1,
        );

        return $this->page('topshop/activity/game/list.html', $pagedata);
    }

    /**
     * 添加/编辑参赛信息
     * @return html
     * @auth xinyufeng
     */
    public function edit_game()
    {
        $params = input::get();
        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'添加/编辑参赛信息';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        $showData = array();
        $showData['game_poll'] = 0;
        $showData['order_sort'] = 0;
        $showData['is_game'] = 0;

        if(!empty($params['game_id']))
        {
            $objMdlGame = app::get('sysactivityvote')->model('game');
            $showData = $objMdlGame->getRow('*',array('game_id'=>$params['game_id']));
            $showData['images'] = $showData['list_image'] ? explode(',', $showData['list_image']) : '';
        }

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['activeInfo'] = $activeInfo;

        $pagedata['GameCatList'] = $this->__getGameCatList($active_id);
        $selectedGameCids = explode(',', $showData['cat_id']);
        foreach($pagedata['GameCatList'] as &$v)
        {
            if($v['children'])
            {
                foreach($v['children'] as &$vv)
                {
                    if(in_array($vv['cat_id'], $selectedGameCids))
                    {
                        $vv['selected'] = true;
                    }
                }
            }
            else
            {
                if(in_array($v['cat_id'], $selectedGameCids))
                {
                    $v['selected'] = true;
                }
            }
        }

        $typeList = $this->__getTypeList();
        $pagedata['typeList'] = $typeList;

        $pagedata['game_id'] = $params['game_id'];
        $pagedata['game'] = $showData;
        
        return $this->page('topshop/activity/game/edit.html', $pagedata);
    }

    /**
     * 保存参赛信息
     * @return
     * @auth xinyufeng
     */
    public function save_game()
    {
        $params = input::get();
        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        if(empty($activeInfo)) return $this->splash('error','','此活动不存在',true);

        /*必填字段限制 开始*/
        if($params['game']['cat_id'] == 'null' || empty($params['game']['cat_id']))
        {
            return $this->splash('error','','请选择参赛分类',true);
        }
        if(empty($params['game']['type_id']))
        {
            return $this->splash('error','','请选择参赛类型',true);
        }
        /*必填字段限制 结束*/

        $saveData = array();
        $saveData['shop_id'] = $this->shopId;
        $saveData['active_id'] = $params['active_id'];
        $saveData['game_name'] = $params['game']['game_name'];
        $saveData['game_number'] = $params['game']['game_number'];
        $saveData['cat_id'] = $params['game']['cat_id'];
        $saveData['type_id'] = $params['game']['type_id'];
        $saveData['image_default_id'] = $params['listimages'][0] ? $params['listimages'][0] : '';
        $saveData['list_image'] = $params['listimages'] ? implode(',', $params['listimages']) : '';
        $saveData['game_profile'] = $params['game']['game_profile'];
        $saveData['game_poll'] = $params['game']['game_poll'];
        $saveData['order_sort'] = $params['game']['order_sort'];
        $saveData['is_game'] = $params['game']['is_game'];
        $saveData['game_desc'] = $params['game']['game_desc'];
        $saveData['game_wap_desc'] = $params['game']['game_wap_desc'];
        $saveData['deleted'] = 0;
        if(!empty($params['game_id'])) {
            /*modify_20180222_by_fanglongji_start*/
            /*
            $saveData['game_id'] = $params['game_id'];
            $saveData['modified_time'] = time();
            $saveData['total_poll'] = $saveData['game_poll'] + $saveData['actual_poll'];
            */
            $actualMdlGame = app::get('sysactivityvote')->model('game');
            $actual_data = $actualMdlGame->getRow('*',array('game_id'=>$params['game_id']));
            $saveData['game_id'] = $params['game_id'];
            $saveData['modified_time'] = time();
            $saveData['total_poll'] = $saveData['game_poll'] + $actual_data['actual_poll'];
            /*modify_20180222_by_fanglongji_end*/
        }else{
            $saveData['create_time'] = time();
            $saveData['modified_time'] = $saveData['create_time'];
        }

        try
        {
            if($saveData['game_id']){
                //更新
                $objMdlGame = app::get('sysactivityvote')->model('game');
                $result = $objMdlGame->update($saveData, array('game_id'=>$saveData['game_id']));

            }else{
                //新增
                $objMdlGame = app::get('sysactivityvote')->model('game');
                $result = $objMdlGame->insert($saveData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();

            if($params['game_id'])
            {
                $url = url::action('topshop_ctl_activity_game@edit_game', array('active_id'=>$active_id,'game_id'=>$params['game_id']));
            }
            else{
                $url = url::action('topshop_ctl_activity_game@index',array('active_id'=>$active_id));
            }
            return $this->splash('error',$url,$msg,true);
        }

        $this->sellerlog('添加/编辑参赛信息。参赛名称是 '.$saveData['game_name']);
        $url = url::action('topshop_ctl_activity_game@index',array('active_id'=>$active_id));
        $msg = app::get('topshop')->_('保存参赛信息成功');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 删除参赛信息
     * @return string
     * @auth xinyufeng
     */
    public function delete_game()
    {
        $game_id = input::get('game_id');

        /*有关联数据无法删除 开始*/
        if($this->__getGameRelateData($game_id)){
            return $this->splash('error', '', '此参赛信息有关联数据，无法删除', true);
        }
        /*有关联数据无法删除 结束*/

        $url = url::action('topshop_ctl_activity_game@index');
        try
        {
            $objMdlGame = app::get('sysactivityvote')->model('game');
            $res = $objMdlGame->delete(array('game_id'=>$game_id));
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $this->sellerlog('删除参赛信息。参赛信息ID是 '.$game_id);
        $msg = app::get('topshop')->_('删除参赛信息成功');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 获取分类下拉框列表数据
     * @param $active_id
     * @return mixed
     * @auth xinyufeng
     */
    private function __getGameCatList($active_id)
    {
        $params = array(
            'page_no' => 1,
            'page_size' => 1000,
            'fields' =>'*',
            'shop_id'=> $this->shopId,
            'active_id'=> $active_id,
            'orderBy'=> ' order_sort ASC, create_time DESC',
        );

        $catListData = app::get('topshop')->rpcCall('sysactivityvote.cat.list', $params);
        $tmpData = $catListData['data'];

        foreach( $tmpData as $row )
        {
            if( $row['level'] == '1' )
            {
                $data[$row['cat_id']] = $row;
            }
            else
            {
                $children[$row['parent_id']][$row['cat_id']] = $row;
            }
        }

        foreach( $children as $parentId=>$val )
        {
            if( $data[$parentId] )
            {
                $data[$parentId]['children'] = $children[$parentId];
            }
            else
            {
                foreach( $val as $catId=>$row )
                {
                    $data[$catId] = $row;
                }
            }
        }

        return $data;
    }

    /**
     * 获取类型下拉框列表数据
     * @return mixed
     * @auth xinyufeng
     */
    private function __getTypeList()
    {
        $objMdlType = app::get('sysactivityvote')->model('type');
        $typeList = $objMdlType->getList('type_id,type_name', array('deleted'=>0), 0, 1000, ' type_id DESC');
        return $typeList;
    }

    /**
     * 获取参赛的关联数据
     * @param $game_id
     * @return bool
     * @auth xinyufeng
     */
    private function __getGameRelateData($game_id)
    {
        //参赛详情参数表
        $objMdlGameDetail = app::get('sysactivityvote')->model('game_detail');
        $gameDetailInfo = $objMdlGameDetail->getRow('game_id',array('game_id'=>$game_id));
        if($gameDetailInfo) return true;

        //投票表
        $objMdlVote = app::get('sysactivityvote')->model('vote');
        $voteInfo = $objMdlVote->getRow('vote_id',array('game_id'=>$game_id));
        if($voteInfo) return true;

        //专家评价参数表
        $objMdlExpertComment = app::get('sysactivityvote')->model('expert_comment');
        $expertCommentInfo = $objMdlExpertComment->getRow('expert_comment_id',array('game_id'=>$game_id));
        if($expertCommentInfo) return true;

        return false;
    }
}