<?php
/**
 * Auth: jiangyunhan
 * Time: 2018-11-14
 * Desc: 创客店铺设置
 */
class topmaker_ctl_setting extends topmaker_controller {

    public function indexMaker()
    {
        $sellerdata = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$this->sellerId));
        $pagedata['seller'] = $sellerdata;

        return view::make('topmaker/maker/setting.html',$pagedata);
    }

    /**
     *保存小店设置的信息
     */
    public function saveMaker(){

        $input = input::all();

        //获取创客的头像和店招图片
        $maker_avatar = app::get('image')->model('images')->getRow('url', array('target_id'=>$this->sellerId,'target_type'=>'maker','img_type'=>'maker'));
        $maker_avatar_img = $maker_avatar['url'];

        $maker_banner = app::get('image')->model('images')->getRow('url', array('target_id'=>$this->sellerId,'target_type'=>'maker','img_type'=>'banner'));
        $maker_banner_img = $maker_banner['url'];

        $name = $input['name'];
        $description = $input['description'];

        $res = app::get('sysmaker')->model('seller')->getRow('*', array('seller_id'=>$this->sellerId));
        
        if($res){
            $update['shop_name'] = $name;
            $update['shop_description'] = $description;
            $update['avatar'] = $maker_avatar_img;
            $update['shop_brand'] = $maker_banner_img;
            $result = app::get('sysmaker')->model('seller')->update($update, array('seller_id'=>$this->sellerId));
        }else{
            $insert['shop_name'] = $name;
            $insert['shop_description'] = $description;
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