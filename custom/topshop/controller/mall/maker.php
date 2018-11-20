<?php
/**
 * User: xinyufeng
 * Time: 2018-10-25 10:30
 * Desc: 广电优选商品列表
 */
class topshop_ctl_mall_maker extends topshop_controller {
	
	public $limit=3;
	/* action_name (par1, par2, par3)
	* 主持人结算列表
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function index(){
        $this->contentHeaderTitle = app::get('topshop')->_('主持人提现');

        $postdata=input::get();
        $params['shop_id']=$this->shopId;
		$params['page_size']=$this->limit;
		if($postdata['seller_id']){
			$params['seller_id']=$postdata['seller_id'];
			$pagedata['seller_id']=$postdata['seller_id'];
		}
		$params['page_no']=$postdata['pages']?$postdata['pages']:1;
        $data = app::get('topshop')->rpcCall('mall.seller.cash.list', $params);
		$pageTotal=ceil($data['total_found']/$this->limit);
		$postdata['pages']=time();
        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_maker@index',$postdata),
            'current'=>$data['currentPage'],
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['data'] = $data['list'];
		$pagedata['count']=$data['total_found'];
		$pagedata['pagers']=$pagers;
        return $this->page('topshop/mall/maker/list.html', $pagedata);
	}
	
	/* action_name (par1, par2, par3)
	* 佣金提现记录
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function cash(){
		$shop_id=$this->shopId;
		$were='a.shop_id='.$shop_id.' and a.deleted=0';
        //分页查询
        $listsBuilder=db::connection()->createQueryBuilder();
        $sellerList = $listsBuilder->select('a.seller_id,b.name')
            ->from('sysmaker_shop_rel_seller', 'a')
            ->where($were)
            ->leftjoin('a', 'sysmaker_seller', 'b', 'b.seller_id = a.seller_id')
            //->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])
            ->execute()->fetchAll();
		$pagedata['seller']=$sellerList;
		$pagedata['seller_id']=input::get('seller_id');
        return $this->page('topshop/mall/maker/add.html', $pagedata);
	}
	
	/* action_name (par1, par2, par3)
	* 保存主持人提现佣金
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function save(){
		$postdata=input::get();
		$seller_id=$postdata['seller_id'];
		$shop_id=$this->shopId;
		//主持人一共有的佣金
		$seller_commission=app::get('sysclearing')->model('seller_billing_detail')->getRow('SUM(seller_commission) AS total_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		//主持人已提现过的佣金
		$has_commission=app::get('sysmaker')->model('cash')->getRow('SUM(payment) as has_commission',array('seller_id'=>$seller_id,'shop_id'=>$shop_id));
		//剩余的佣金
		$sy_commission=$seller_commission['total_commission']-$has_commission['has_commission'];
		if($postdata['payment']>$sy_commission){
            return $this->splash('error','','该主持人剩余佣金为:'.$sy_commission.'元,结算金额不能大于剩余佣金',true);
		}
		$data['payment']=$postdata['payment'];
		$data['seller_id']=$postdata['seller_id'];
		$data['shop_id']=$shop_id;
		$data['remark']=$postdata['remark'];
		$data['create_time']=time();
		$res=app::get('sysmaker')->model('cash')->insert($data);
		if($res){
			$log="主持人{$seller_id}提现{$postdata['payment']}元";
            $this->sellerlog($log);
			$url=url::action('topshop_ctl_mall_maker@index',$postdata);
			return $this->splash('success',$url,'提现成功!',true);	
		}

	}
	
	/* action_name (par1, par2, par3)
	* 本店铺主持人列表
	* author by wanghaichao
	* date 2018/11/15
	*/
	public function mlist(){

		$this->contentHeaderTitle = app::get('topshop')->_('主持人列表');

        $params=input::get();
       //$params['shop_id']=$this->shopId;
        $builderWhere="a.shop_id=".$this->shopId.' and a.deleted=0';
        //记录总数

		if($params['name']){
			$builderWhere.=" and b.name like '%".$params['name']."%'";
			$pagedata['name']=$params['name'];
		}
		if ($params['status']){
			$builderWhere.=" and a.status='".$params['status']."'";
			$pagedata['status']=$params['status'];
		}
        $countBuilder = db::connection()->createQueryBuilder();
        $count=$countBuilder->select('count(a.seller_id)')
            ->from('sysmaker_shop_rel_seller', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
            ->where($builderWhere)
            ->execute()->fetchColumn();


        $page=$params['pages']?$params['pages']:1;

        $limit=$this->limit;
        $pageTotal=ceil($count/$limit);
        $currentPage =($pageTotal < $page) ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $params['pages']=time();

		if($count==0){
			$list=array();
		}else{
			
			//分页查询
			$listsBuilder=db::connection()->createQueryBuilder();
			$list = $listsBuilder->select('a.*,b.name')
				->from('sysmaker_shop_rel_seller', 'a')
				->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
				->where($builderWhere)
				->setFirstResult($offset)
				->setMaxResults($limit)
				//->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])   //orderby先去掉,有需要再加上
				->execute()->fetchAll();

		}
		
        $pagers = array(
            'link'=>url::action('topshop_ctl_mall_maker@mlist',$params),
            'current'=>$currentPage,
            'use_app' => 'topshop',
            'total'=>$pageTotal,
            'token'=>time(),
        );
        $pagedata['data'] = $list;
        $pagedata['shopInfo'] = $this->shopInfo;
        $pagedata['pagers']=$pagers;
        return $this->page('topshop/mall/maker/mlist.html', $pagedata);
	}
	
	/* action_name (par1, par2, par3)
	* 主持人审核逻辑
	* author by wanghaichao
	* date 2018/11/16
	*/
	public function audit(){
		$data=input::get();
		if(empty($data['seller_id'])){
			return $this->splash('error','','请选择要审核的主持人',true);	
		}
		if($data['status']=='refuse' && empty($data['reason'])){
			return $this->splash('error','','请填写拒绝原因',true);	
		}
		$data['shop_id']=$this->shopId;
		$res=app::get('sysmaker')->model('shop_rel_seller')->save($data);
		if(res){
			return $this->splash('success','','审核成功',true);
		}else{
			return $this->splash('error','','审核失败',true);	
		}
	}
	
	/* action_name (par1, par2, par3)
	* 删除主持人
	* author by wanghaichao
	* date 2018/11/16
	*/
	public function mdelete(){
		$data['seller_id']=input::get('seller_id');
		$data['shop_id']=$this->shopId;
		$data['deleted']=1;
		app::get('sysmaker')->model('shop_rel_seller')->save($data);
		return $this->splash('succes','','删除成功',true);
	}

}
?>