<?php
class topshop_ctl_activity_game_detail extends topshop_controller {

    /**
     * 编辑参赛详情
     * @return html
     * @auth xinyufeng
     */
    public function edit_game_detail()
    {
        $params = input::get();

        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_name',array('active_id'=>$active_id,'deleted'=>0));

        $game_id = $params['game_id'];
        $game_id = $game_id ? $game_id : 0;
        $objMdlGame = app::get('sysactivityvote')->model('game');
        $gameInfo = $objMdlGame->getRow('game_name,type_id',array('game_id'=>$game_id,'deleted'=>0));

        $title = "[{$activeInfo['active_name']}][{$gameInfo['game_name']}]".'参赛详情';
        $this->contentHeaderTitle = app::get('topshop')->_($title);

        $objMdlGameDetail = app::get('sysactivityvote')->model('game_detail');
        $gameDetailInfo = $objMdlGameDetail->getRow('*',array('game_id'=>$game_id,'deleted'=>0));
        if(!empty($gameDetailInfo)){
            switch ($gameInfo['type_id']){
                case 1://菜品
                    if($gameDetailInfo['base_desc']) {
                        $gameDetailInfo['base_desc'] = unserialize($gameDetailInfo['base_desc']);
                        $gameDetailInfo['base_desc']['label_arr'] = implode(';', $gameDetailInfo['base_desc']['label_arr']);
                    }
                    if($gameDetailInfo['work_desc']) {
                        $gameDetailInfo['work_desc'] = unserialize($gameDetailInfo['work_desc']);
                        $work_desc = array();
                        foreach ($gameDetailInfo['work_desc'] as $wdv){
                            $work_desc[] = $wdv['name'].'-'.$wdv['value'];
                        }
                        $gameDetailInfo['work_desc'] = implode(';', $work_desc);
                    }
                    if($gameDetailInfo['base_list_image']) {
                        $gameDetailInfo['base_list_image'] = unserialize($gameDetailInfo['base_list_image']);
                    }
                    if($gameDetailInfo['recommend_reason_desc']) {
                        $gameDetailInfo['recommend_reason_desc'] = unserialize($gameDetailInfo['recommend_reason_desc']);
                    }
                    break;
                case 2://饭店
                    if($gameDetailInfo['base_desc']) {
                        $gameDetailInfo['base_desc'] = unserialize($gameDetailInfo['base_desc']);
                    }
                    if($gameDetailInfo['work_desc']) {
                        $gameDetailInfo['work_desc'] = unserialize($gameDetailInfo['work_desc']);
                    }
                    if($gameDetailInfo['base_list_image']) {
                        $gameDetailInfo['base_list_image'] = unserialize($gameDetailInfo['base_list_image']);
                        $base_list_image = array();
                        $base_list_image_name = array();
                        foreach ($gameDetailInfo['base_list_image'] as $blik => $bliv){
                            if(!empty($bliv['title'])){
                                $base_list_image_name[] = $bliv['title'];
                            }
                            if(!empty($bliv['image'])){
                                $base_list_image[] = $bliv['image'];
                            }
                        }
                    }
                    if($gameDetailInfo['work_list_image']) {
                        $gameDetailInfo['work_list_image'] = unserialize($gameDetailInfo['work_list_image']);
                    }
                    if($gameDetailInfo['recommend_reason_desc']) {
                        $gameDetailInfo['recommend_reason_desc'] = unserialize($gameDetailInfo['recommend_reason_desc']);
                    }
                    break;
                case 3://人物
                    if($gameDetailInfo['base_desc']) {
                        $gameDetailInfo['base_desc'] = unserialize($gameDetailInfo['base_desc']);
                    }
                    if($gameDetailInfo['work_desc']) {
                        $gameDetailInfo['work_desc'] = unserialize($gameDetailInfo['work_desc']);
                        $gameDetailInfo['work_desc'] = implode(';', $gameDetailInfo['work_desc']);
                    }
                    if($gameDetailInfo['base_list_image']) {
                        $gameDetailInfo['base_list_image'] = unserialize($gameDetailInfo['base_list_image']);
                    }
                    if($gameDetailInfo['work_list_image']) {
                        $gameDetailInfo['work_list_image'] = unserialize($gameDetailInfo['work_list_image']);
                    }
                    if($gameDetailInfo['recommend_reason_desc']) {
                        $gameDetailInfo['recommend_reason_desc'] = unserialize($gameDetailInfo['recommend_reason_desc']);
                    }
                    break;
            }

        }

        if($gameInfo['type_id'] != $gameDetailInfo['type_id']){
            //如果参赛类型与参赛详情类型不一致
            unset($gameDetailInfo);
        }

        $pagedata = array();
        $pagedata['active_id'] = $active_id;
        $pagedata['game_id'] = $game_id;
        $pagedata['gameInfo'] = $gameInfo;
        $pagedata['game_detail'] = $gameDetailInfo;
        if($gameInfo['type_id'] == 2){
            $pagedata['base_list_image'] = $base_list_image;
            $pagedata['base_list_image_name'] = implode(';', $base_list_image_name);
            $pagedata['work_list_image'] = $gameDetailInfo['work_list_image'];
            $pagedata['recommend_reason_desc'] = $gameDetailInfo['recommend_reason_desc'];
        }
        if($gameInfo['type_id'] == 3){
            $pagedata['base_list_image'] = $gameDetailInfo['base_list_image'];
            $pagedata['work_list_image'] = $gameDetailInfo['work_list_image'];
        }

        return $this->page('topshop/activity/game/detail/edit.html', $pagedata);
    }

    /**
     * 保存参赛详情
     * @return string
     * @auth xinyufeng
     */
    public function save_game_detail()
    {
        $params = input::get();

        $active_id = $params['active_id'];
        $active_id = $active_id ? $active_id : 0;
        $objMdlActive = app::get('sysactivityvote')->model('active');
        $activeInfo = $objMdlActive->getRow('active_id',array('active_id'=>$active_id,'deleted'=>0));
        if(empty($activeInfo)) return $this->splash('error','','此活动不存在',true);

        $game_id = $params['game_id'];
        $game_id = $game_id ? $game_id : 0;
        $objMdlGame = app::get('sysactivityvote')->model('game');
        $gameInfo = $objMdlGame->getRow('game_id',array('game_id'=>$game_id,'deleted'=>0));
        if(empty($gameInfo)) return $this->splash('error','','此参赛信息不存在',true);

        $saveData = array();
        $saveData['game_id'] = $params['game_id'];
        $saveData['type_id'] = $params['type_id'];
        switch ($saveData['type_id']){
            case 1 ://菜品
                $base_desc = $params['game_detail']['base_desc'];
                $base_desc['label_arr'] = $base_desc['label_arr'] ? explode(';', $base_desc['label_arr']) : '';
                $saveData['base_desc'] = serialize($base_desc);

                $work_desc = $params['game_detail']['work_desc'];
                $work_desc = $work_desc ? explode(';', $work_desc) : '';
                if(!empty($work_desc)){
                    $_work_desc = array();
                    foreach ($work_desc as $wdv){
                        $wdv_arr = explode('-', $wdv);
                        $_name = $wdv_arr[0] ? $wdv_arr[0] : '';
                        $_value = $wdv_arr[1] ? $wdv_arr[1] : '';
                        $_work_desc[] = array('name'=>$_name, 'value'=>$_value);
                    }
                    $work_desc = $_work_desc;
                }
                $saveData['work_desc'] = serialize($work_desc);
                $saveData['base_list_image'] = $params['game_detail']['base_list_image'] ? serialize($params['game_detail']['base_list_image']) : '';
                $saveData['work_list_image'] = '';
                $saveData['recommend_reason_desc'] = $params['listimages'] ? serialize($params['listimages']) : '';
                break;
            case 2://饭店
                $saveData['base_desc'] = $params['game_detail']['base_desc'] ? serialize($params['game_detail']['base_desc']) : '';
                $saveData['work_desc'] = $params['game_detail']['work_desc'] ? serialize($params['game_detail']['work_desc']) : '';

                $base_list_image = $params['base_list_image'];
                $base_list_image_name = $params['base_list_image_name'] ? explode(';', $params['base_list_image_name']) : '';
                $base_list_image_arr = array();
                foreach ($base_list_image as $blik => $bliv){
                    $_title = $base_list_image_name[$blik] ? $base_list_image_name[$blik] : '';
                    $base_list_image_arr[$blik] = array('title'=>$_title,'image'=>$bliv);
                }
                $saveData['base_list_image'] = $base_list_image_arr ? serialize($base_list_image_arr) : '';

                $saveData['work_list_image'] = $params['work_list_image'] ? serialize($params['work_list_image']) : '';
                $saveData['recommend_reason_desc'] = $params['recommend_reason_desc'] ? serialize($params['recommend_reason_desc']) : '';
                break;
            case 3://人物
                $saveData['base_desc'] = $params['game_detail']['base_desc'] ? serialize($params['game_detail']['base_desc']) : '';

                $work_desc = $params['game_detail']['work_desc'];
                $work_desc = $work_desc ? explode(';', $work_desc) : '';
                $saveData['work_desc'] = serialize($work_desc);

                $saveData['base_list_image'] = $params['base_list_image'] ? serialize($params['base_list_image']) : '';
                $saveData['work_list_image'] = $params['work_list_image'] ? serialize($params['work_list_image']) : '';
                $saveData['recommend_reason_desc'] = $params['game_detail']['recommend_reason_desc'] ? serialize($params['game_detail']['recommend_reason_desc']) : '';
        }
        $current_time = time();
        $saveData['modified_time'] = $current_time;

        $objMdlGameDetail = app::get('sysactivityvote')->model('game_detail');
        $gameDetailInfo = $objMdlGameDetail->getRow('*',array('game_id'=>$game_id,'deleted'=>0));

        try
        {
            if(empty($gameDetailInfo)){
                //新增
                $saveData['create_time'] = $current_time;
                $result = $objMdlGameDetail->insert($saveData);
            }else{
                //更新
                $result = $objMdlGameDetail->update($saveData, array('game_id'=>$saveData['game_id']));
            }
        }
        catch(\LogicException $e)
        {
            $msg = $e->getMessage();

            if(!empty($gameDetailInfo))
            {
                $url = url::action('topshop_ctl_activity_game_detail@edit_game_detail', array('active_id'=>$active_id,'game_id'=>$game_id));
            }
            else{
                $url = url::action('topshop_ctl_activity_game@index',array('active_id'=>$active_id));
            }
            return $this->splash('error',$url,$msg,true);
        }

        $this->sellerlog('编辑参赛详情。参赛ID是 '.$game_id);
        $url = url::action('topshop_ctl_activity_game@index',array('active_id'=>$active_id));
        $msg = app::get('topshop')->_('保存参赛详情成功');
        return $this->splash('success',$url,$msg,true);
    }
}