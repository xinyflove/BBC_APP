<?php
/**
 * Created by PhpStorm.
 * User: Caffrey
 * Date: 2016/12/9
 * Time: 15:20
 */
class testapp_ctl_user extends desktop_controller{

    /**
     * 用户管理列表
     * @return mixed
     */
    public function lists(){
        $actions = array(
            array(
                'label'=>app::get('testapp')->_('用户添加'),
                'href'=>'?app=testapp&ctl=user&act=create',
                'target'=>'dialog::{title:\''.app::get('testapp')->_('用户添加').'\',width:600,height:250}'
            ),
            array(
                'label'=>app::get('testapp')->_('用户导入'),
                'href'=>'?app=testapp&ctl=user&act=upload',
                'target'=>'dialog::{title:\''.app::get('testapp')->_('用户导入').'\',width:600,height:250}'
            ),
            array(
                'label'=>app::get('testapp')->_('用户导入模版'),
                'href'=>'/csvs/inners/phonetpl.xlsx?',
                'target'=>'_blank'
            ),
        );

        $params = array(
            'title' => app::get('testapp')->_('用户列表'),
            'actions'=> $actions
        );

        return $this->finder('testapp_mdl_user', $params);
    }

    /**
     * 用户添加修改页面
     * @param $userId
     */
    public function add($userId){
        $pagedata = array();

        if( $userId )
        {
            $userInfo = app::get('testapp')->model('user')->getUserRow($userId);
            $pagedata['userInfo'] = $userInfo;
        }

        $levelInfo = app::get('testapp')->model('level')->getLevelOptions();
        $pagedata['levelInfo'] = $levelInfo;

        return $this->page('testapp/user/add.html', $pagedata);
    }

    public function edit($userId){
        $pagedata = array();

        if( $userId )
        {
            $userInfo = app::get('testapp')->model('user')->getUserRow($userId);
            $pagedata['userInfo'] = $userInfo;
        }

        $levelInfo = app::get('testapp')->model('level')->getLevelOptions();
        $pagedata['levelInfo'] = $levelInfo;

        return $this->page('testapp/user.html', $pagedata);
    }

    /**
     * 用户保存
     */
    public function saveUser(){
        $this->begin();
        $data = $_POST;

        $data['valid_over'] = strtotime($data['valid_over'] . ' 23:59:59');
        $data['valid_begin'] = strtotime($data['valid_begin'] . ' 00:00:00');

        $cdres = $this->_checkData($data);
        if(!$cdres['s']){
            $this->end($cdres['s'],$cdres['m']);
        }

        $inr = app::get('testapp')->model('user')->isPhoneRepeat($data['phone'], $data['user_id']);
        if($inr){
            $this->end(false,'用户手机号已存在');
        }

        if(empty($data['user_id'])){
            $flag = kernel::single('testapp_data_user')->add($data,$msg);
            $this->adminlog("添加用户[用户ID:{$flag}]", $flag ? 1 : 0);
        }else{
            $flag = kernel::single('testapp_data_user')->update($data,$msg);
            $this->adminlog("更新用户[用户ID:{$data['user_id']}]", $flag ? 1 : 0);
        }

        $this->end($flag,$msg);
    }

    /**
     * 用户删除页面
     * @param $user_id
     */
    public function delPage($user_id){
        $pagedata['user_id'] = $user_id;
        return $this->page('testapp/user_del.html', $pagedata);
    }

    /**
     * 用户删除
     */
    public function delete(){
        $user_id = input::get('user_id');
        $this->begin();
        try{
            $delFlag = app::get('testapp')->model('user')->doDelete($user_id);
            $this->adminlog("删除用户[用户ID:{$user_id}]", 1);
        }catch(Exception $e){
            $this->adminlog("删除用户[用户ID:{$user_id}]", 0);
            $msg = $e->getMessage();
            $this->end(false,$msg);
        }
        $this->end(true,app::get('testapp')->_('删除用户成功'));
    }

    public function upload(){
        return $this->page('testapp/upload.html');
    }

    public function doUpload(){
        $this->begin();

        if ($_FILES["file"]["error"] > 0)
        {
            $this->end(false,app::get('testapp')->_('上传文件失败'));
            return;
        }
        else
        {
            if(empty($_FILES["file"]["name"])){
                $this->end(false,app::get('testapp')->_('请选择csv文件'));
                return;
            }

            $tmp_name = $_FILES["file"]["tmp_name"];
            $file_name = $_FILES["file"]["name"];

            $file_name_arr = explode('.',$file_name);
            $ext = end($file_name_arr);
            if($ext != 'csv' ){
                $this->end(false,app::get('testapp')->_('请选择csv类型文件'));
                return;
            }

            $path = 'csvs/inners/'.$file_name;
            $res = move_uploaded_file($tmp_name, $path);
            if($res){
                //$this->end(false,app::get('testapp')->_('上传文件成功'));
                $result = array(
                    'success' => '成功：上传文件成功',
                    'redirect' => '',
                    'splash' => true,
                    'data' => $file_name,
                );
                return json_encode($result);
            }else{
                $this->end(false,app::get('testapp')->_('上传文件失败'));
            }
        }
    }

    /*public function import(){
        return $this->page('testapp/import.html');
    }*/

    public function doImport(){
        $this->begin();

        if(empty($_POST['file_name'])){
            $this->end(false,app::get('testapp')->_('导入文件失败'));
        }

        $file_name = $_POST['file_name'];
        $path = 'csvs/inners/'.$file_name;

        $f = fopen($path, 'r');

        $phones = array();
        $i = 0;
        while(!feof($f))
        {
            // 过滤第一行
            $line = fgets($f);
            if($i)
            {
                $line = trim($line);
                if(!empty($line))
                {
                    $line = trim($line);
                    $line_arr = explode(',', $line);

                    if(count($line_arr) > 0)
                    {
                        $levelName = empty($line_arr[1]) ? '' : trim($line_arr[1]);
                        $levelId = app::get('testapp')
                            ->model('level')->getLevelIdByLevelName($levelName);
                        $valid_begin = empty($line_arr[2]) ? '2017-01-01' : trim($line_arr[2]);
                        $valid_over = empty($line_arr[3]) ? '2017-12-31' : trim($line_arr[3]);

                        $valid_over = strtotime($valid_over . ' 23:59:59');
                        $valid_begin = strtotime($valid_begin . ' 00:00:00');

                        $data = array(
                            'phone' => $line_arr[0],
                            'level_id' => $levelId,
                            'valid_begin' => $valid_begin,
                            'valid_over' => $valid_over,
                        );

                        $cdres = $this->_checkData($data);
                        if(!$cdres['s']){
                            $this->end($cdres['s'],$cdres['m']);
                        }

                        $userInfo = app::get('testapp')
                            ->model('user')->getUserRowByPhone($data['phone']);

                        if(empty($userInfo)){
                            $flag = kernel::single('testapp_data_user')->add($data,$msg);
                            $this->adminlog("批量导入用户[用户ID:{$flag}]", $flag ? 1 : 0);
                            if(!$flag){
                                $this->end(false, $msg);
                            }
                        }
                    }

                    $phones[] = trim($line);
                }
            }
            $i++;
        }

        unlink($path);
        $this->end(true, '导入成功');
    }

    protected function _checkData($data)
    {
        if($data['level_id'] == 0){
            return array('s' => false, 'm' => '请选择等级');
        }

        $search ='/^1[3-9]\d{9}$/';
        if(!preg_match($search,$data['phone'])) {
            return array('s' => false, 'm' => '请填写正确的手机号码');
        }

        if($data['valid_over'] <= time()){
            return array('s' => false, 'm' => '有效期结束时间应该大于当前日期');
        }
        if($data['valid_over'] <= $data['valid_begin']){
            return array('s' => false, 'm' => '有效期结束时间应该大于有效期开始时间日期');
        }

        return array('s' => true, 'm' => '');
    }
}