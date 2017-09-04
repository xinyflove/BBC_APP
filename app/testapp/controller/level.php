<?php
/**
 * Created by PhpStorm.
 * User: CaffreyXin
 * Date: 2017/8/31
 * Time: 13:50
 */
class testapp_ctl_level extends desktop_controller{

    /**
     * 等级管理列表
     * @return mixed
     */
    public function lists(){
        $actions = array(
            array(
                'label'=>app::get('testapp')->_('等级添加'),
                'href'=>'?app=testapp&ctl=level&act=add',
                'target'=>"dialog::{title:'".app::get('testapp')->_('等级添加')."',width:600,height:250}"
            ),
        );

        $params = array(
            'title' => app::get('testapp')->_('等级列表'),
            'actions'=> $actions,
            'use_view_tab' => true,
        );

        return $this->finder('testapp_mdl_level', $params);
    }

    /**
     * 等级添加页面
     */
    public function add(){
        return view::make('testapp/level/add.html');
    }

    /**
     * 等级修改页面
     * @param $levelId
     */
    public function edit($levelId){
        $levelInfo = app::get('testapp')->model('level')->getLevelRow($levelId);
        $pagedata['levelInfo'] = $levelInfo;
        return $this->page('testapp/level/edit.html', $pagedata);
    }

    /**
     * 等级保存
     */
    public function saveLevel(){
        $this->begin();
        $data = $_POST;

        if($data['discount'] > 10){
            $this->end(false,'折扣在10以及10以内');
        }
        if($data['discount'] <= 0){
            $this->end(false,'折扣在0以上');
        }

        $objLevel = kernel::single('testapp_level');
        $inr = $objLevel->isNameRepeat($data['name'], $data['level_id']);
        if($inr){
            $this->end(false,'等级名称已存在');
        }

        if(empty($data['level_id'])){
            $flag = kernel::single('testapp_data_level')->add($data,$msg);
            $this->adminlog("添加等级[等级ID:{$data['level_id']}]", $flag ? 1 : 0);
        }else{
            $flag = kernel::single('testapp_data_level')->update($data,$msg);
            $this->adminlog("更新等级[等级ID:{$data['level_id']}]", $flag ? 1 : 0);
        }
        
        $this->end($flag,$msg);
    }

    /**
     * 等级删除页面
     * @param $level_id
     */
    public function delPage($level_id){
        $pagedata['level_id'] = $level_id;
        return $this->page('testapp/level_del.html', $pagedata);
    }

    /**
     * 等级删除
     */
    public function delete(){
        $level_id = input::get('level_id');
        $this->begin('?app=syscategory&ctl=admin_props&act=index');
        try{
            $delFlag = app::get('testapp')->model('level')->doDelete($level_id);
            $this->adminlog("删除等级[等级ID:{$level_id}]", 1);
        }catch(Exception $e){
            $this->adminlog("删除等级[分类ID:{$level_id}]", 0);
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }
        $this->end(true,app::get('testapp')->_('删除等级成功'));
    }
}
