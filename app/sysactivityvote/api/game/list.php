<?php
/*
* 获取参赛列表
* author by wanghaichao
* date 2017/10/16
*/
class sysactivityvote_api_game_list{
    public $apiDescription = "获取参赛活动列表";
    public function getParams()
    {
        $return['params'] = array(
            'cat_id' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'分类id'],
            'keywords' => ['type'=>'string', 'valid'=>'', 'default'=>'', 'example'=>'','description'=>'关键字查询'],
            'page_no' => ['type'=>'int','valid'=>'int','description'=>'分页当前页码,1<=no<=499','example'=>'','default'=>'1'],
            'page_size' =>['type'=>'int','valid'=>'int','description'=>'分页每页条数(1<=size<=200)','example'=>'','default'=>'40'],
            'order_by' => ['type'=>'int','valid'=>'','description'=>'排序方式','example'=>'','default'=>'created_time desc'],
            'fields' => ['type'=>'field_list','valid'=>'','description'=>'获取的交易字段集','example'=>'','default'=>''],
        );
        return $return;
    }
    public function gameList($params)
    {

        $gameRow = $params['fields'];
        if(!$params['order_by'])
        {
            $orderBy = "order_sort desc";
        }elseif($params['order_by']=='vote desc'){
			$orderBy="total_poll desc";
		}elseif($params['order_by']=='vote asc'){
			$orderBy="total_poll asc";
		}else{
			$orderBy=$params['order_by'];
		}
        $pageNo = $params['page_no'];
        $pageSize = $params['page_size'];
        unset($params['fields'],$params['page_no'],$params['page_size'],$params['order_by'],$params['oauth']);
		
		if(isset($params['keywords']) && !empty($params['keywords'])){
			if(is_numeric($params['keywords'])){
				$params['game_number']=$params['keywords'];
			}else{
				$params['game_name|has']=$params['keywords'];
			}
			unset($params['keywords']);
		}
		$params['is_game']='1';
        $objMdlGame = app::get('sysactivityvote')->model('game');
        $count = $objMdlGame->count($params);
        //分页使用
        $page =  $pageNo ? $pageNo : 1;
        $limit = $pageSize ? $pageSize : 40;
        $pageTotal = ceil($count/$limit);
        $currentPage = $pageTotal < $page ? $pageTotal : $page;
        $offset = ($currentPage-1) * $limit;

        $gameLists = $objMdlGame->getList($gameRow,$params,$offset,$limit,$orderBy);
        $game['list'] = $gameLists;
        $game['count'] = $count;
        return $game;
    }
}



