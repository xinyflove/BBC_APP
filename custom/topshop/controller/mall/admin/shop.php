<?php
/**
 * User: xinyufeng
 * Time: 2018-10-26 10:30
 * Desc: 广电优选店铺页面配置
 */
class topshop_ctl_mall_admin_shop extends topshop_controller {
	
	/* action_name (par1, par2, par3)
	* 设置页面
	* author by wanghaichao
	* date 2018/11/27
	*/
    public function setting()
    {
		$pagedata=app::get('sysshop')->model('mall_setting')->getRow('*',array('shop_id'=>$this->shopId));
		$pagedata['banner']=unserialize($pagedata['banner']);
		$pagedata['ad_pic']=unserialize($pagedata['ad_pic']);
		$item_id=explode(',',$pagedata['items']);
		foreach($item_id as $k=>&$v){
			$v=intval($v);
		}
		$pagedata['notEndItem'] =  json_encode($item_id,false);
        $this->contentHeaderTitle = app::get('topshop')->_('店铺首页配置');
        return $this->page('topshop/mall/admin/setting.html', $pagedata);
    }
	
	/* action_name (par1, par2, par3)
	* 保存页面
	* author by wanghaichao
	* date 2018/11/27
	*/
	public function save(){
		$postdata=input::get();
		$postdata['banner']=serialize($postdata['banner']);
		$postdata['ad_pic']=serialize($postdata['ad_pic']);
		$postdata['items']=implode(',',$postdata['item_id']);
		unset($postdata['item_sku'],$postdata['item_id']);
		if(empty($postdata['id'])){
			$postdata['create_time']=time();
		}else{
			$postdata['modified_time']=time();
		}
		$postdata['shop_id']=$this->shopId;
		$res=app::get('sysshop')->model('mall_setting')->save($postdata);
		if($res){
			$msg = '保存成功';
			return $this->splash('success', null, $msg, true);
		}else{
			$msg = '保存失败';
			return $this->splash('success', null, $msg, true);
		}
	}
}