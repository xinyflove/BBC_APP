<?php
class sysactivityvote_api_expert_comment_delete{

    public $apiDescription = "删除专家点评";
    public $use_strict_filter = true; // 是否严格过滤参数
    public function getParams()
    {
        $data['params'] = array(
            // 'active_id' => ['type' => 'int','valid'=>'required','desc'=>'活动id','msg'=>'活动id必填'],
            // 'shop_id' => ['type' => 'int','valid'=>'required','desc'=>'店铺id','msg'=>'店铺id必填'],
            'expert_comment_id' => ['type' => 'int','valid'=>'required','desc'=>'专家点评id','msg'=>'专家点评id,以,号分隔'],

        );
        return $data;
    }

    public function delete($params)
    {
        $objComment = kernel::single('sysactivityvote_expert_comment');

        if($params['expert_comment_id'])
        {
            $filter['expert_comment_id'] = explode(',',$params['expert_comment_id']);
        }

        $db = app::get('sysactivityvote')->database();
        $db->beginTransaction();

        try{

            if( !$objComment->deleteComment($params) )
            {
                throw \LogicException('专家删除失败！');
            }

            $db->commit();
        }catch(LogicException $e){
            $db->rollback();
            throw $e;
        }
        return true;
    }

}
