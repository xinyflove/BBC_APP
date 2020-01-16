<?php
/**
* 协会管理
* author by wanghaichao
* date 2019/9/2
*/
class topshop_ctl_mall_group extends topshop_controller {
	public $limit=10;
	/**
	* 协会列表
	* author by wanghaichao
	* date 2019/9/2
	*/
	public function index(){
		$postdata=input::get();
        $params['shop_id']=$this->shopId;
		$params['page_size']=$this->limit;
		$params['page_no']=$postdata['pages']?$postdata['pages']:1;
        $data = app::get('topshop')->rpcCall('maker.group.list', $params);
		$pageTotal=ceil($data['total_found']/$this->limit);
		$postdata['pages']=time();
        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_group@index',$postdata),
            'current'=>$data['currentPage'],
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
		$pagedata['data']=$data['list'];
		$pagedata['pagers']=$pagers;
        return $this->page('topshop/mall/group/index.html', $pagedata);
	}
	
	/**
	* 创建协会
	* author by wanghaichao
	* date 2019/9/2
	*/
	public function create(){
        return $this->page('topshop/mall/group/create.html', $pagedata);
	}
	
	
	/**
	* 保存协会的
	* author by wanghaichao
	* date 2019/9/2
	*/
	public function save(){
		$post=input::get();
		if(empty($post['name'])){
            return $this->splash('error','','请输入协会名称',true);
		}
		if(empty($post['contact'])){
            return $this->splash('error','','请输入协会联系人',true);
		}
		if(empty($post['name'])){
            return $this->splash('error','','请输入协会联系方式',true);
		}
		$post['shop_id']=$this->shopId;
		$post['created_time']=time();
		$res=app::get('sysmaker')->model('group')->insert($post);
		if($res){
			$log="创建协会";
            $this->sellerlog($log);
			$url=url::action('topshop_ctl_mall_group@index');
			return $this->splash('success',$url,'创建成功!',true);	
		}

	}


}