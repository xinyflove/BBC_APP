<?php
class topshop_ctl_activity_cat extends topshop_controller {

    /**
     * 投票分类列表
     * @return html
     * @auth xinyufeng
     */
    public function index()
    {
        $active_id = input::get('active_id');
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'投票分类管理';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        /*获取数据列表开始*/
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        $pageSize = 1000;
        $params = array(
            'page_no' => $filter['pages'],
            'page_size' => intval($pageSize),
            'fields' =>'*',
            'shop_id'=> $this->shopId,
            'active_id'=> $active_id,
            'orderBy'=> ' order_sort ASC, create_time DESC',
        );

        $catListData = app::get('topshop')->rpcCall('sysactivityvote.cat.list', $params);
        $catListData['data'] = $this->__catLevelList($catListData['data']);
        /*获取数据列表结束*/

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['activeInfo'] = $activeInfo;
        $pagedata['catList'] = $catListData['data'];
        
        /*处理翻页数据开始*/
        $count = $catListData['total'];
        $current = $filter['pages'] ? $filter['pages'] : 1;
        $filter['pages'] = time();
        if($count>0) $total = ceil($count/$pageSize);
        $pagedata['pagers'] = array(
            'link'=>url::action('topshop_ctl_activity_cat@index', $filter),
            'current'=>$current,
            'use_app'=>'topshop',
            'total'=>$total,
            'token'=>$filter['pages'],
        );

        $pagedata['now'] = time();
        $pagedata['total'] = $count;
        $pagedata['examine_setting'] = app::get('sysconf')->getConf('shop.promotion.examine');
        /*处理翻页数据结束*/
        
        return $this->page('topshop/activity/cat/list.html', $pagedata);
    }

    /**
     * 添加/编辑投票分类
     * @return html
     * @auth xinyufeng
     */
    public function edit_cat()
    {
        $params = input::get();

        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));
        $title = "[{$activeInfo['active_name']}]".'添加/编辑投票分类';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        $showData = array();
        $showData['order_sort'] = 0;
        $showData['personal_everyday_vote_limit'] = 0;
        $showData['game_personal_everyday_vote_limit'] = 0;
        $showData['parent_id'] = 0;
        $showData['level'] = 1;
        $showData['cat_path'] = ',';

        $objMdlCat = app::get('sysactivityvote')->model('cat');
        if(!empty($params['parent_id']))
        {
            $parentCatInfo = $objMdlCat->getRow('cat_id,level,cat_path',array('cat_id'=>$params['parent_id']));
            $showData['parent_id'] = $parentCatInfo['cat_id'];
            $showData['level'] = $parentCatInfo['level'] + 1;
            $showData['cat_path'] = $parentCatInfo['cat_path'] . $parentCatInfo['cat_id'] . ',';
        }

        if(!empty($params['cat_id']))
        {
            $showData = $objMdlCat->getRow('*',array('cat_id'=>$params['cat_id']));
        }

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['activeInfo'] = $activeInfo;
        $pagedata['cat_id'] = $params['cat_id'];
        $pagedata['cat'] = $showData;

        return $this->page('topshop/activity/cat/edit.html', $pagedata);
    }

    /**
     * 保存投票分类
     * @return string
     * @auth xinyufeng
     */
    public function save_cat()
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
        $saveData['parent_id'] = $params['cat']['parent_id'];
        $saveData['level'] = $params['cat']['level'];
        $saveData['cat_path'] = $params['cat']['cat_path'];
        $saveData['cat_name'] = $params['cat']['cat_name'];
        $saveData['order_sort'] = $params['cat']['order_sort'];
        $saveData['cat_image'] = $params['cat']['cat_image'];
        $saveData['personal_everyday_vote_limit'] = $params['cat']['personal_everyday_vote_limit'];
        $saveData['game_personal_everyday_vote_limit'] = $params['cat']['game_personal_everyday_vote_limit'];
        $saveData['deleted'] = 0;
        if(!empty($params['cat_id'])) {
            $saveData['cat_id'] = $params['cat_id'];
            $saveData['modified_time'] = time();
        }else{
            $saveData['create_time'] = time();
            $saveData['modified_time'] = $saveData['create_time'];
        }

        try
        {
            if($saveData['cat_id']){
                //更新
                $objMdlCat = app::get('sysactivityvote')->model('cat');
                $result = $objMdlCat->update($saveData, array('cat_id'=>$saveData['cat_id']));

            }else{
                //新增
                $objMdlCat = app::get('sysactivityvote')->model('cat');
                $result = $objMdlCat->insert($saveData);
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();

            if($params['cat_id'])
            {
                $url = url::action('topshop_ctl_activity_cat@edit_cat', array('active_id'=>$active_id,'cat_id'=>$params['cat_id']));
            }
            else{
                $url = url::action('topshop_ctl_activity_cat@index',array('active_id'=>$active_id));
            }
            return $this->splash('error',$url,$msg,true);
        }

        $this->sellerlog('添加/编辑投票分类。投票分类名称是 '.$saveData['cat_name']);
        $url = url::action('topshop_ctl_activity_cat@index',array('active_id'=>$active_id));
        $msg = app::get('topshop')->_('保存投票分类成功');
        return $this->splash('success',$url,$msg,true);
    }

    /**
     * 删除投票分类
     * @return string
     * @auth xinyufeng
     */
    public function delete_cat()
    {
        $cat_id = input::get('cat_id');

        /*有关联数据无法删除 开始*/
        if($this->__getCatRelateData($cat_id)){
            return $this->splash('error', '', '此活动分类有关联数据，无法删除', true);
        }
        /*有关联数据无法删除 结束*/

        $url = url::action('topshop_ctl_activity_cat@index');
        try
        {
            $objMdlCat = app::get('sysactivityvote')->model('cat');
            $res = $objMdlCat->delete(array('cat_id'=>$cat_id));
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', $url, $msg, true);
        }

        $this->sellerlog('删除投票分类。投票分类ID是 '.$cat_id);
        $msg = app::get('topshop')->_('删除投票分类成功');
        return $this->splash('success', $url, $msg, true);
    }

    /**
     * 获取投票分类的关联数据
     * @param $game_id
     * @return bool
     * @auth xinyufeng
     */
    private function __getCatRelateData($cat_id)
    {
        //参赛表
        $objMdlGame = app::get('sysactivityvote')->model('game');
        $gameInfo = $objMdlGame->getRow('game_id',array('cat_id'=>$cat_id));
        if($gameInfo) return true;

        return false;
    }

    /**
     * 整理分类列表
     * @param $active_id
     * @return mixed
     * @auth xinyufeng
     */
    private function __catLevelList($catListData)
    {
        foreach( $catListData as $row )
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
                $data[$parentId]['child'] = $children[$parentId];
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
}