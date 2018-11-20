<?php
/*处理主播等相关信息的
* author by wanghaichao
* date 2018/7/17
*/
class topshop_ctl_live_compere extends topshop_controller{
    public $limit = 10;
	public function index(){
        $page = input::get('page', 1);
        $params['page_no'] = $page;
		$params['shop_id']=$this->shopId;
		$params['page_size']=$this->limit;
        $this->contentHeaderTitle = app::get('topshop')->_('主播管理');
		$params['compere_name']=input::get('compere_name');
        try {
            $compereList = app::get('topshop')->rpcCall('compere.list', $params);
            //$supplierList = array_bind_key($supplierList, 'supplier_id');
        } catch (\Exception $e) {
            echo '参数错误:' . $e->getMessage();
            die;
        }
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_live_compere@index', [ 'page' => time()]),
            'current' => $compereList['current_page'],
            'use_app' => 'topshop',
            'total' => $compereList['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $compereList['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $compereList['data'];
        return $this->page('topshop/live/compere/list.html', $pagedata);
	}

	/* 
	* 添加主播
	* author by wanghaichao
	* date 2018/7/17
	*/
	public function edit(){
		$id=input::get('id');
        $url = url::action('topshop_ctl_live_compere@index');
		$objCompere=app::get('sysshop')->model('compere');
		if($id){
			$pagedata=$objCompere->getRow('*',array('id'=>$id,'shop_id'=>$this->shopId));
			if(empty($pagedata)){
				echo "<script>window.location.href='".$url."'</script>";
			}
			
			$params['compere_id']=$id;
			$params['page_no'] = 1;
			$params['page_size']=1000;
			$itemsList = app::get('topshop')->rpcCall('compere.item.list', $params);
			foreach($itemsList['data'] as $k=>$v){
				$item_sort[$v['item_id']]=$v['sort'];
			}
            $notItems = array_column($itemsList['data'], 'item_id');
            $pagedata['notEndItem'] =  json_encode($notItems,true);
			$pagedata['item_sort']=json_encode($item_sort,true);
			//echo "<pre>";print_r($pagedata);die();
		}
		return $this->page('topshop/live/compere/edit.html', $pagedata);
	}

	/* action_name (par1, par2, par3)
	* 保存主播信息的
	* author by wanghaichao
	* date 2018/7/17
	*/
	public function save(){
		$data=input::get();
		$sort=$data['sort'];
		if(!$data['compere_name']){
            $msg = '请填写主播姓名';
            return $this->splash('error','',$msg,true);
		}
		if(!$data['describe']){
            $msg = '请填写主播简介';
            return $this->splash('error','',$msg,true);
		}
        if($data['compere_sort']=='')
        {
            $msg = '请填写排序';
            return $this->splash('error','',$msg,true);
        }
		if(!is_numeric($data['compere_sort'])){
            $msg = '排序请使用数字';
            return $this->splash('error','',$msg,true);
		}
		if(!$data['item_id']){
			$msg="请选择主播推荐商品";
            return $this->splash('error','',$msg,true);
		}
		$data['shop_id']=$this->shopId;
		$compere=app::get('sysshop')->model('compere');
		if($data['id']){
			$data['modified_time']=time();
			$msg='修改主播成功';
		}else{
			$data['create_time']=time();
			$data['is_del']=2;
			$msg='添加主播成功';
		}
		$data['sort']=$data['compere_sort'];
		if($data['id']){
			$compere_id=$data['id'];
			$compere->save($data);    //保存主播信息
		}else{
			$compere_id=$compere->insert($data);
		}
		$insertItemSql=$this->insertCompereItem($data['item_id'],$compere_id,$sort);   
		app::get('base')->database()->executeUpdate($insertItemSql);  //插入推荐商品表
        $this->sellerlog('添加/修改主播。主播名是 '.$data['compere_name']);
        $url = url::action('topshop_ctl_live_compere@index');
        return $this->splash('success',$url,$msg,true);
	}
	
	/* action_name (par1, par2, par3)
	* 主播推荐商品插入表中
	* author by wanghaichao
	* date 2018/7/19
	*/
	public function insertCompereItem($items,$compere_id,$sort){
		//先删除原来的关联商品
		app::get('sysshop')->model('compere_item')->delete(array('compere_id'=>$compere_id));
		$insertSql="INSERT INTO sysshop_compere_item (`shop_id`,`compere_id`,`item_id`,`sort`,`create_time`,`modified_time`) VALUES ";
		$shop_id=$this->shopId;
		$create_time=time();
		$modified_time='';
		foreach($items as $k=>$item_id){
			$insertSql.="('{$shop_id}','{$compere_id}','{$item_id}','{$sort[$item_id]}','{$create_time}','{$modified_time}'),";
		}
		$insertSql=substr($insertSql,0,-1);
		return $insertSql;
	}

	/* action_name (par1, par2, par3)
	* 设置主播推荐商品
	* author by wanghaichao
	* date 2018/7/18
	*/
	public function item(){
		$compere_id=input::get('id');
		$filter['shop_id']=$this->shopId;
		$filter['id']=$compere_id;
		$compere=app::get('sysshop')->model('compere')->getRow('id',$filter);
		if(empty($compere)){
            echo '无权查看该主播推荐的商品信息';
			die();
		}
		//取出该主播推荐的商品的列表
		$params['compere_id']=$compere_id;
        $page = input::get('page', 1);
        $params['page_no'] = $page;
		$params['shop_id']=$this->shopId;
		$params['page_size']=$this->limit;
        $data = app::get('topshop')->rpcCall('compere.item.list', $params);
        //分页处理
        $pagers = array(
            'link' => url::action('topshop_ctl_live_compere@index', [ 'page' => time()]),
            'current' => $data['current_page'],
            'use_app' => 'topshop',
            'total' => $data['page_total'],
            'token' => time(),
        );
        $pagedata['total'] = $data['data_count'];
        $pagedata['pagers'] = $pagers;
        $pagedata['data'] = $data['data'];
        return $this->page('topshop/live/compere/item.html', $pagedata);
	}
	
	/* action_name (par1, par2, par3)
	* 删除主播
	* author by wanghaichao
	* date 2018/7/19
	*/
	public function 	deletecompere(){
		$id=input::get('id');
		//删除主播关联的商品
		app::get('sysshop')->model('compere_item')->delete(array('compere_id'=>$id));
		//删除主播
		app::get('sysshop')->model('compere')->delete(array('id'=>$id));

        return $this->splash('success','','删除成功!',true);
	}
	
	/* action_name (par1, par2, par3)
	* 更改主播排序的
	* author by wanghaichao
	* date 2018/7/19
	*/
	public function compereSort(){
		$filter['id']=input::get('id');
		$params['sort']=input::get('sort');
		app::get('sysshop')->model('compere')->update($params,$filter);
        //return $this->splash('success','','删除成功!',true);
	}

}
?>