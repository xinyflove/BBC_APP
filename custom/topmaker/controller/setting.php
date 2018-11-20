<?php
/**
 * Auth: jiangyunhan
 * Time: 2018-11-14
 * Desc: 创客店铺设置
 */
class topmaker_ctl_setting extends topmaker_controller {

    public function indexMaker()
    {
        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        $sellerdata = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$targetId));
        $pagedata['seller'] = $sellerdata;

        //echo '<pre>';var_dump($pagedata);die;

        return view::make('topmaker/maker/setting.html',$pagedata);
    }

    /**
     *
     */
    public function saveMaker(){

        $input = input::all();
        //获取创客的头像和店招图片
        $maker_avatar = app::get('image')->model('images')->getRow('url', array('target_id'=>$input['maker_id'],'target_type'=>'maker','img_type'=>'maker'));
        $maker_avatar_img = $maker_avatar['url'];

        $maker_banner = app::get('image')->model('images')->getRow('url', array('target_id'=>$input['maker_id'],'target_type'=>'maker','img_type'=>'banner'));
        $maker_banner_img = $maker_banner['url'];

        $name = $input['name'];


        pamAccount::setAuthType('sysmaker');
        $targetId = pamAccount::getAccountId();

        $res = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$targetId));
        
        if($res){
            $update['name'] = $name;
            $update['avatar'] = $maker_avatar_img;
            $update['shop_brand'] = $maker_banner_img;
            $result = app::get('sysmaker')->model('seller')->update($update, array('seller_id'=>$input['maker_id']));
        }else{
            $insert['name'] = $name;
            $insert['avatar'] = $maker_avatar_img;
            $insert['shop_brand'] = $maker_banner_img;
            $insert['mobile'] = '';

            $result = app::get('sysmaker')->model('seller')->save($insert);
        }
        if($result){
            echo json_encode(['code'=>true]);
        }else{
            echo json_encode(['code'=>false]);
        }

    }




}