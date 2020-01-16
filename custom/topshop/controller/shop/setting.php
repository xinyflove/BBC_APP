<?php
class topshop_ctl_shop_setting extends topshop_controller{

    public function index()
    {		
        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId()),'seller');
        $pagedata['shop'] = $shopdata;
        $pagedata['im_plugin'] = app::get('sysconf')->getConf('im.plugin');
        $this->contentHeaderTitle = app::get('topshop')->_('店铺设置');
        return $this->page('topshop/shop/setting.html', $pagedata);
    }

    public function saveSetting()
    {
        $postData = input::get();
		$postData['shop_id']=shopAuth::getShopId();
		if($postData['maker_rate']>100 || $postData['maker_rate']<0){
			return $this->splash('error',null,'创客佣金请填写小于100的正数');
		}
        $validator = validator::make(
            [$postData['shop_descript']],['max:200'],['店铺描述不能超过200个字符!']
        );
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',null,$error[0]);
            }
        }
        try
        {
            $result = app::get('topshop')->rpcCall('shop.update',$postData);
            if( $result )
            {
                $msg = app::get('topshop')->_('设置成功');
                $result = 'success';
            }
            else
            {
                $msg = app::get('topshop')->_('设置失败');
                $result = 'error';
            }
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $result = 'error';
        }
        $this->sellerlog('编辑店铺配置。如店铺logo,描述等');
        $url = url::action('topshop_ctl_shop_setting@index');
        return $this->splash($result,$url,$msg,true);

    }
	
	/* ext_setting()
	* 扩展的配置,手机端分享页面,活动图片等配置
	* author by wanghaichao
	* date 2017/9/25
	*/
	public function ext_setting(){
		$shop_id=$this->shopId;
        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId()),'seller');
        $pagedata['shop'] = $shopdata;
		$extsetting= app::get('topshop')->rpcCall('shop.extsetting.get',array('shop_id'=>$shop_id,'use_platform'=>'wap'));
		$pagedata['setting']=$extsetting['params'];
		$pagedata['setting_id']=$extsetting['setting_id'];
        $this->contentHeaderTitle = app::get('topshop')->_('店铺其他配置');
        return $this->page('topshop/shop/ext_setting.html', $pagedata);
	}

	/* save_ext_setting ()
	 * 保存扩展的配置的
	* author by wanghaichao
	* date 2017/9/25
	*/
	public function save_ext_setting(){
		$post=input::get();
		$setting_id=$post['setting_id'];
		unset($post['setting_id']);
		$shop_id=$this->shopId;
		$settingObj=app::get('sysshop')->model('setting');
		$data['shop_id']=$shop_id;
		$data['params']=serialize($post);
		$data['use_platform']='wap';
        try
        {
			if($setting_id){
				$setting_id=$post['setting_id'];
				$res=$settingObj->update($data,array('setting_id'=>$setting_id));
			}else{
				$data['create_time']=time();
				$res=$settingObj->insert($data);
			}
			if($res){
				$result='success';
				$msg='保存成功!';
			}else{
				$result='error';
				$msg='保存失败!';
			}
		}
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $result = 'error';
        }
		
        return $this->splash($result,$url,$msg,true);
	}

	/* action_name (par1, par2, par3)
	* 设置创客佣金
	* author by wanghaichao
	* date 2018/11/22
	*/
	public function setCommission(){
        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId()),'seller');
        $pagedata['shop'] = $shopdata;
        $this->contentHeaderTitle = app::get('topshop')->_('创客佣金设置');
        return $this->page('topshop/shop/setCommission.html', $pagedata);
	}
	
	/**
	* 二维码背景
	* author by wanghaichao
	* date 2019/7/31
	*/
	public function bgqr(){
        $shopdata = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>shopAuth::getShopId(),'fields'=>'qr_bg'));
        $pagedata['shop'] = $shopdata;
        $this->contentHeaderTitle = app::get('topshop')->_('创客二维码背景设置');
        return $this->page('topshop/shop/bgqr.html', $pagedata);
	}
	
	/**
	* 下载背景图
	* author by wanghaichao
	* date 2019/8/7
	*/
	public function download(){
		
        $filename = input::get('file');
        //设置头信息
        /*if(input::get('type') == 'yulan')
        {
            $filename = 'http://www.tvplaza.cn/'.$filename;
        }*/

        header('Content-Disposition:attachment;filename=' . basename($filename));
        header('Content-Length:' . filesize($filename));
        //读取文件并写入到输出缓冲
        readfile($filename);
	}
	

}


