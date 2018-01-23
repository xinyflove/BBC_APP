<?php
class sysactivityvote_api_gift_gain_delete{

    public $apiDescription = "删除赠品获得记录";
    public $use_strict_filter = true; // 是否严格过滤参数
    public function getParams()
    {
        $data['params'] = array(
            // 'active_id' => ['type' => 'int','valid'=>'required','desc'=>'活动id','msg'=>'活动id必填'],
            'shop_id' => ['type' => 'int','valid'=>'required','desc'=>'店铺id','msg'=>'店铺id必填'],
            'gift_gain_id' => ['type' => 'int','valid'=>'required','desc'=>'赠品获得记录id','msg'=>'赠品获得记录id必填'],

        );
        return $data;
    }

    public function delete($params)
    {
        $objGiftGain = kernel::single('sysactivityvote_gift_gain');

        if(empty($params['shop_id']) || empty($params['gift_gain_id']))
        {
            throw \LogicException('参数错误！');
        }

        //保存报名信息
        $db = app::get('sysactivityvote')->database();
        $db->beginTransaction();

        try{

            if( !$objGiftGain->deleteGiftGain($params) )
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
