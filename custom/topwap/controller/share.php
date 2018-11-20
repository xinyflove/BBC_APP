<?php
/**
 * tvplaza
 * 图片分享处理
 * @小超
 * @2017/9/26
 */
use Endroid\QrCode\QrCode;
class topwap_ctl_share extends topwap_controller {

	public function share(){

		$shop_id=input::get('shop_id');
        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shop_id));   //店铺信息
	//	echo "<pre>";print_r(base_storager::modifier($shopdata['shop_logo']));die();
        $share = $this->__getExtSetting($shop_id);   //店铺信息
		//$share=shopWidgets::getWapInfo('shareimg',$shop_id);
		$shareimg=$share['params']['shareimg'];	
		if(userAuth::check()){
			$user_info=userAuth::getUserInfo();
		}else{
			$user_info['login_account']='';
		}
		if($shareimg['is_showname']!='1'){   //不显示昵称
			$user_info['login_account']='';
		}
		if(empty($shareimg['bg_img'])){
			$shareimg['bg_img']="/images/default/bg_img.jpg";
		}
		if(empty($shareimg['share_describe'])){
			$shareimg['share_describe']="我在".$shopdata['shop_name']."官方店发现了一个好东西";
		}
		if(empty($shareimg['shop_code'])){
			$shareimg['shop_code']=$this->__qrCode($shop_id);
		}
		$params['shop_id']=$shop_id;								//店铺id
		$params['user_id']=$user_info['userId'];            //用户id
		$params['user_name']=$user_info['login_account']; //用户名
		$params['shop_code']=base_storager::modifier($shareimg['shop_code']);//二维码地址
		//$params['shop_code']=$shareimg['shop_code'];
		$params['bg_img']=base_storager::modifier($shareimg['bg_img']);//背景地址
		$params['share_describe']=$shareimg['share_describe'];//描述
		$imgurl="../public/images/shareimg/{$shop_id}_{$user_info['userId']}.png";  //图片路径
		//echo "<pre>";print_r($params);die();
		//if(!is_file($imgurl)){    //如果已经存在这个图片,就不生成了
		$res=app::get('topwap')->rpcCall('share.img',$params);		   //处理图片接口
		file_put_contents($imgurl,$res);   //上传至服务器
		//}
		$pagedata['imgurl']="/images/shareimg/{$shop_id}_{$user_info['userId']}.png";
		$pagedata['title']=$shopdata['shop_name'];
		
		//微信分享的
		$weixin['imgUrl']= base_storager::modifier($shopdata['shop_logo']);
		$weixin['linelink']= url::action("topwap_ctl_newshop@index",array('shop_id'=>$shop_id));
		$weixin['shareTitle']=$share['params']['share']['shopcenter_title'];
		$weixin['descContent']=$share['params']['share']['shopcenter_describe'];
		$pagedata['weixin']=$weixin;
        return view::make('topwap/shareimg/index.html', $pagedata);
	}

	
    private function __qrCode($shop_id)
    {
        //$url = url::action("topwap_ctl_shopcenter@index",array('shop_id'=>$shop_id));
		//echo "<pre>";print_r($url);die();
		$url='http://www.baidu.com';
	   $code_url="../public/images/shareimg/{$shop_id}_code.png"; 
	   if(is_file($code_url)){
			return "/images/shareimg/{$shop_id}_code.png";
	   }
       $result= getQrcodeUri($url, 80, 10);
	   $code_url="../public/images/shareimg/{$shop_id}_code.png"; 
		copy($result,$code_url);   //上传至服务器
		return "/images/shareimg/{$shop_id}_code.png";
    }

}