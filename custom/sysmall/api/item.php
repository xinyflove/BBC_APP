<?php
/* 
* 获取选货商城里面的商品列表
* author by wanghaichao
* date 2018/6/19
*/
class sysmall_api_item{

    public $apiDescription = "获取选货商城里面的商品列表";

    public function getParams()
    {
        $return['params'] = array(
			'shop_id' => ['type'=>'int','valid'=>'','description'=>'店铺id','example'=>'店铺id','default'=>''],
            //'cat_id' => ['type'=>'string','valid'=>'','description'=>'商城类目id','example'=>'1,3','default'=>''],
            //'brand_id' => ['type'=>'string','valid'=>'','description'=>'品牌ID','example'=>'1,2,3','default'=>''],

            'item_title' => ['type'=>'string','valid'=>'','description'=>'搜索商品关键字','example'=>'','default'=>''],
			'use_platform'=>['type'=>'int','valid'=>'','description'=>'发布的平台','example'=>'发布平台','default'=>''],

			'min_price'=>['type'=>'price','valid'=>'','description'=>'最小价格','example'=>'','default'=>''],
			'max_price'=>['type'=>'price','valid'=>'','description'=>'最大价格','example'=>'','default'=>''],

			'item_no'=>['type'=>'int','valid'=>'','description'=>'商品货号','example'=>'发布平台','default'=>''],

            'create_time_start' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'查询指定时间内的交易创建时间开始yyyy-MM-dd'],
            'create_time_end' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'查询指定时间内的交易创建时间结束yyyy-MM-dd'],
            //'receiver_mobile' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人手机'],
            //'receiver_phone' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人电话'],
            //'receiver_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'收货人姓名'],
            //'user_name' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'会员用户名/手机号/邮箱'],
            'is_aftersale' => ['type'=>'bool', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'是否显示售后状态'],
            'pay_type' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'支付方式【offline、online】'],
            'shipping_type' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'配送类型'],

            'page_no' => ['type'=>'int','valid'=>'int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'要获取的字段','example'=>'','default'=>''],

        );
        $return['extendsFields'] = ['order','activity', 'delivery'];
        return $return;
    }


    /**
     * @param $params
     * @获取选货商城中的商品列表
	 * @wanghaichao
	 * @date 2018/6/19
     */
    public function getList($params){
		$fields=$params['fields'];
        //排序
       // $orderBy = $params['orderBy']?$params['order_by']:'created_time desc';
		//$params['search_keywords']="测试";
		//查询上架中的和没有删除的商品
		//$sqlWhere[]="a.status='onsale'";
		//$sqlWhere[]="a.deleted=0";
        //关键词查询
        if($params['item_title']){
            $keyword=$params['item_title'];
            $sqlWhere[]='b.title like "%'.$keyword.'%"';
           // unset($params['item_title']);
        }

        $pageNo = $params['page_no'];
        $pageSize = $params['page_size'];
        unset($params['fields'],$params['page_no'],$params['page_size'],$params['order_by'],$params['oauth']);

        //批量处理其他参数，关键词存在时仅仅处理status和tid
        foreach($params as $k=>$val)
        {
            if(is_null($val))
            {
                unset($params[$k]);
                continue;
            }
            //if($k == "status" || $k == "tid")
           // {
            //    $params[$k] = explode(',',$val);
            //    $filter_str=implode('","',$params[$k]);
          //      $sqlWhere[]='a.'.$k. ' in ("'.$filter_str.'")';
          //  }
        }

        //开始时间点
        if( $params['create_time_start'] )
        {
            $params['created_time|bthan'] = $params['create_time_start'];
            $sqlWhere[]='a.created_time > '.$params['create_time_start'];
            unset($params['create_time_start']);
        }

        //结束时间点
        if( $params['create_time_end'] )
        {
            $params['created_time|lthan'] = $params['create_time_end'];
            $sqlWhere[]='a.created_time < '.$params['create_time_end'];
            unset($params['create_time_end']);
        }
		
		if($params['min_price']){
			$sqlWhere[]="b.price>='".$params['min_price']."'";
		}
		
		if($params['max_price']){
			$sqlWhere[]="b.price<='".$params['max_price']."'";
		}

		if($params['use_platform'] && $params['use_platform']>=0){
			$sqlWhere[]="b.use_platform='{$params['use_platform']}'";
		}
		if($params['item_no']){
			$sqlWhere[]="b.bn='{$params['item_no']}'";
		}



        //组装sqlBuilderFilter
        //if($params['pay_type']) $sqlWhere[]='a.pay_type="'.$params['pay_type'].'"';
        //if($params['shipping_type']) $sqlWhere[]='a.shipping_type="'.$params['shipping_type'].'"';
       // if($params['receiver_phone']) $sqlWhere[]='a.receiver_phone="'.$params['receiver_phone'].'"';
        //if($params['receiver_mobile']) $sqlWhere[]='a.receiver_mobile="'.$params['receiver_mobile'].'"';
       // if($params['receiver_name']) $sqlWhere[]='a.receiver_name  like "%'.$params['receiver_name'].'%"';
        //筛选条件
		if(empty($sqlWhere)){
			$builderWhere='1';
		}else{
			$builderWhere="1 and ".implode(' and ',$sqlWhere);
		}
		//$params['shop_id']=3;
		if($params['shop_id']){
			$builderWhere="(b.shop_id ='{$params['shop_id']}' and ".$builderWhere.") or (".$builderWhere." and a.status='onsale' and a.deleted=0)";
		}
		//echo "<pre>";print_r($builderWhere);die();
        //记录总数
        $countBuilder = db::connection()->createQueryBuilder();
        $count=$countBuilder->select('count(distinct b.item_id)')
            ->from('sysitem_item', 'b')
            ->leftjoin('b', 'sysmall_item', 'a', 'a.item_id = b.item_id')
			->leftjoin('b','sysitem_item_store','c','c.item_id = b.item_id')
			->leftjoin('b','sysitem_item_status','d','d.item_id=b.item_id')
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
        $itemLists = $listsBuilder->select($fields)
            ->from('sysitem_item', 'b')
            ->where($builderWhere)
            ->leftjoin('b', 'sysmall_item', 'a', 'b.item_id = a.item_id')
			->leftjoin('b','sysitem_item_store','c','c.item_id = b.item_id')
			->leftjoin('b','sysitem_item_status','d','d.item_id=b.item_id')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            //->orderBy('a.'.$orderByPhrase[0],$orderByPhrase[1])
            ->execute()->fetchAll();
        //改造为商品id的关联数组
        $itemLists = array_bind_key($itemLists,'item_id');


        $item['list'] = $itemLists;
        $item['count'] = $count;
		//echo "<pre>";print_r($item);die();
        return $item;
    }


}
