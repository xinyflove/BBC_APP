<?php

class topshop_ctl_account_blacklist extends topshop_controller {
	public $limit=10;
	public function __construct($pp){
        parent::__construct($app);
		if($this->shopInfo['blacklist']=='off'){
			echo "<p style='font-size:20px;line-height:100px;text-align:center'>没有开通黑名单功能<p>";die();
		}
	}
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('黑名单用户管理');

        $params=input::get();
        $params['shop_id']=$this->shopId;
        //$filter=$this->__chekSupplierFilter($params);
        $count=app::get('sysshop')->model('blacklist')->count($params);
        $page=$params['pages']?$params['pages']:1;

        $limit=$this->limit;
        $pageTotal=ceil($count/$limit);
        $currentPage =($pageTotal < $page) ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $params['page_no']=$offset;
        $params['page_size']=$limit;
        $filter['pages']=time();

        $data = app::get('topshop')->rpcCall('shop.blacklist', $params);
        $pagers = array(
            'link'=>url::action('topshop_ctl_account_blacklist@index',$filter),
            'current'=>$currentPage,
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['data'] = $data;
        $pagedata['shopInfo'] = $this->shopInfo;
        $pagedata['pagers']=$pagers;
        $pagedata['mobile']=$params['mobile'];
        return $this->page('topshop/account/blacklist/list.html', $pagedata);
    }
	
	/* action_name (par1, par2, par3)
	* 添加黑名单
	* author by wanghaichao
	* date 2018/12/4
	*/
	public function addBlack(){
        $this->contentHeaderTitle = app::get('topshop')->_('添加黑名单用户');
        $pageData=array();
        return $this->page('topshop/account/blacklist/addblack.html',$pageData);
	}
 	
    /* action_name (par1, par2, par3)
    * 搜索会员
    * author by wanghaichao
    * date 2018/12/4
    */

    public function searchMember(){
        $postFilter = input::get();
        if(empty($postFilter['mobile'])){
            return $this->splash('error','','请填写用户手机号');
        }else{
            $data = app::get('topshop')->rpcCall('shop.blacklist.member',['mobile'=>$postFilter['mobile'],'shop_id'=>$this->shopId]);
            $pagedata['data'] = $data;
            $pagedata['shopid']=$this->shopId;
            return view::make('topshop/account/blacklist/searchmember.html', $pagedata);
        }
    }
	
	/* action_name (par1, par2, par3)
	* 保存黑名单功能
	* author by wanghaichao
	* date 2018/12/4
	*/
	public function save(){
		$postdata=input::get();
		if(empty($postdata['user_id']) || empty($postdata['mobile']) || empty($postdata['reason'])){
            return $this->splash('error','','请填写拉黑原因,要拉黑的用户等');
		}
		$data['user_id']=$postdata['user_id'];
		$data['mobile']=$postdata['mobile'];
		$data['shop_id']=$this->shopId;
		$data['reason']=$postdata['reason'];
		$data['create_time']=time();
		$data['deleted']=0;
		$blackObj=app::get('sysshop')->model('blacklist');
		$black=$blackObj->getRow('id',array('shop_id'=>$this->shopId,'user_id'=>$data['user_id'],'deleted'=>0));
		if($black){
            return $this->splash('error','','该用户已被拉黑');
		}
		$res=$blackObj->insert($data);
		if($res){
            return $this->splash('success','','已移入黑名单');
		}else{
            return $this->splash('error','','拉黑失败,请稍后尝试');
		}
	}
	
	/* action_name (par1, par2, par3)
	* 从黑名单删除功能
	* author by wanghaichao
	* date 2018/12/5
	*/
	public function delBlack(){
		$id=input::get('id');
		if(empty($id)){
            return $this->splash('error','','请选择要删除的用户');
		}
		$data['id']=$id;
		$data['shop_id']=$this->shopId;
		$data['deleted']=1;
		$res=app::get('sysshop')->model('blacklist')->save($data);
		if($res){
            return $this->splash('success','','已从黑名单中删除');
		}else{
            return $this->splash('error','','移除失败,请稍后尝试');
		}
	}
}

