<?php
/* 
* 获取主持人提现列表
* author by wanghaichao
* date 2018/11/15
*/
class sysmall_api_getSellerCash{

    public $apiDescription = "主持人提现列表";

    public function getParams()
    {
        $return['params'] = array(
			'shop_id' => ['type'=>'int','valid'=>'','description'=>'店铺id','example'=>'店铺id','default'=>''],
			'seller_id'=>['type'=>'int','valid'=>'','description'=>'主持人的id','example'=>'店铺id','default'=>''],

            'page_no' => ['type'=>'int','valid'=>'int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
            //'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的字段','example'=>'','default'=>''],

        );
        return $return;
    }


    /**
     * @param $params
     * @主持人提现列表
	 * @wanghaichao
	 * @date 2018/11/15
     */
    public function getList($params){
		
		$fields="a.*,b.name";

		if($params['shop_id']){
			$sqlWhere[]="a.shop_id=".$params['shop_id'];
		}
		if($params['seller_id']){
			$sqlWhere[]="a.seller_id=".$params['seller_id'];
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
        $count=$countBuilder->select('count(a.id)')
            ->from('sysmaker_cash', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
            ->where($builderWhere)
            ->execute()->fetchColumn();
        //分页使用
        $page =  $pageNo ? $pageNo : 1;
        $limit = $pageSize ? $pageSize : 10;
        $pageTotal = ceil($count/$limit);
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        if($currentPage < 1){$currentPage=1;}
        $offset = ($currentPage-1) * $limit;

        //分页查询
        $listsBuilder=db::connection()->createQueryBuilder();
        $list = $listsBuilder->select($fields)
            ->from('sysmaker_cash', 'a')
            ->leftjoin('a', 'sysmaker_seller', 'b', 'a.seller_id = b.seller_id')
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
