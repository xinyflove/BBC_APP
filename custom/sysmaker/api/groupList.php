<?php
/* 
* 获取协会列表
* author by wanghaichao
* date 2019/9/2
*/
class sysmaker_api_groupList{

    public $apiDescription = "获取协会列表";

    public function getParams()
    {
        $return['params'] = array(
			'shop_id' => ['type'=>'int','valid'=>'','description'=>'店铺id','example'=>'店铺id','default'=>''],
			'name'=>['type'=>'string','valid'=>'','description'=>'协会名称','example'=>'协会名称','default'=>''],
			'contact'=>['type'=>'string','valid'=>'','description'=>'审核状态','example'=>'审核状态','default'=>''],
			'mobile'=>['type'=>'string','valid'=>'','description'=>'联系电话','example'=>'联系电话','default'=>''],
            'page_no' => ['type'=>'int','valid'=>'int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
        );
        return $return;
    }
	public function getList($params){
			
		$fields="*";

		if($params['shop_id']){
			$sqlWhere[]="shop_id=".$params['shop_id'];
		}
		if ($params['name']){
			$sqlWhere[]="name like '%".$params['name']."%'";
		}
		if ($params['mobile']){
			$sqlWhere[]="mobile = '".$params['mobile']."'";
		}

		if ($params['contact']){
			$sqlWhere[]="contact = '".$params['contact']."'";
		}
        //分页
        $pageNo = $params['page_no'];
        $pageSize = $params['page_size'];
		$orderBy=$params['order_by'];
        unset($params['page_no'],$params['page_size'],$params['order_by'],$params['oauth']);

        //批量处理其他参数，关键词存在时仅仅处理status和tid
        foreach($params as $k=>$val)
        {
            if(is_null($val))
            {
                unset($params[$k]);
                continue;
            }
        }


        //筛选条件
		if(empty($sqlWhere)){
			$builderWhere='1';
		}else{
			$builderWhere="1 and ".implode(' and ',$sqlWhere);
		}
        //记录总数
        $countBuilder = db::connection()->createQueryBuilder();
        $count=$countBuilder->select('count(group_id)')
            ->from('sysmaker_group')
            ->where($builderWhere)
            ->execute()->fetchColumn();
        //分页使用
        $page =  $pageNo ? $pageNo : 1;
        $limit = $pageSize ? $pageSize : 10;
        $pageTotal = ceil($count/$limit);
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        if($currentPage < 1){$currentPage=1;}
        $offset = ($pageNo-1) * $limit;
        //分页查询
        $listsBuilder=db::connection()->createQueryBuilder();
        $list = $listsBuilder->select($fields)
            ->from('sysmaker_group')
            ->where($builderWhere)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            //->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])   //orderby先去掉,有需要再加上
             ->execute()->fetchAll();

        //$list = array_bind_key($itemLists,'item_id');

		
        $data['list'] = $list;
        $data['total_found'] = $count;
		$data['currentPage']=$currentPage;
        return $data;
	}
}
