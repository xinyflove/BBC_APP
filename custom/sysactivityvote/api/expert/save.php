<?php
class sysactivityvote_api_expert_save{

    public $apiDescription = "保存专家";
    public $use_strict_filter = true; // 是否严格过滤参数
    public function getParams()
    {
        $data['params'] = array(
            'active_id' => ['type' => 'int','valid'=>'required','desc'=>'活动id','msg'=>'活动id必填'],
            'shop_id' => ['type' => 'int','valid'=>'required','desc'=>'店铺id','msg'=>'店铺id必填'],
            'expert_id' => ['type' => 'int','valid'=>'','desc'=>'专家id','msg'=>''],
            'expert_name' => ['type' => 'string','valid'=>'required','desc'=>'专家名称','msg'=>'专家名称必填'],
            'expert_profile' => ['type' => 'string','valid'=>'required','desc'=>'专家一句话介绍','msg'=>'一句话介绍必填'],
            'expert_desc' => ['type' => 'string','valid'=>'required','desc'=>'专家简介','msg'=>'简介必填'],
            'comment_password' => ['type' => 'string','valid'=>'','desc'=>'专家评审密码','msg'=>'评审密码必填'],
            'comment_password_confirm' => ['type' => 'string','valid'=>'','desc'=>'评审确认密码','msg'=>'评审确认密码必填'],
            'expert_avatar' => ['type' => 'string','valid'=>'required','desc'=>'专家头像','msg'=>'专家头像必填'],

        );
        return $data;
    }

    public function save($params)
    {
        $objexpert = kernel::single('sysactivityvote_expert');

        if($params['comment_password'] !== $params['comment_password_confirm'])
        {
            throw new \LogicException('评审密码两次输入不一致！');
        }

        //保存报名信息
        $db = app::get('sysactivityvote')->database();
        $db->beginTransaction();

        try{

            $registerData = array(
                'active_id' => $params['active_id'],
                'shop_id' => $params['shop_id'],
                'expert_name' => $params['expert_name'],
                'expert_profile' => $params['expert_profile'],
                'expert_desc' => $params['expert_desc'],
                'comment_password' => $params['comment_password'],
                'expert_avatar' => $params['expert_avatar'],
                'create_time' =>time(),
                'modified_time' =>time(),
            );

            if($params['expert_id'])
            {
                $registerData['expert_id'] = $params['expert_id'];
                unset($registerData['create_time']);
            }

            if( !$objexpert->saveExpert($registerData) )
            {
                throw new \LogicException('专家保存失败！');
            }

            $db->commit();
        }catch(LogicException $e){
            $db->rollback();
            throw $e;
        }
        return true;
    }

}
