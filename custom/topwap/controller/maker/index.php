<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客店铺首页
 */
class topwap_ctl_maker_index extends topwap_controller
{
    private $makerItemObj;
    private $makerSellerObj;
    private $limit;

    public function __construct()
    {
        parent::__construct();
        $this->makerItemObj = kernel::single('sysmaker_data_item');
        $this->makerSellerObj = kernel::single('sysmaker_data_seller');
        $this->limit = 10;
    }

    /**
     * 创客店铺首页
     *
     * @return mixed
     */
    public function home()
    {
        $params = input::get();
        if (!isset($params['seller_id']) || !$this->makerSellerObj->isSellerExist($params['seller_id'])) {
            return kernel::abort(404);
        }

        $seller = $this->makerSellerObj->getSellerInfo($params['seller_id']);
        $pagedata['seller'] = $seller;
        $filter['seller_id'] = $params['seller_id'];
        $pagedata['count'] = $this->makerItemObj->getSellerItemCount($filter);
		/*add_2018/11/22_by_wanghaichao_start*/
		$pagedata['seller_id']=$params['seller_id'];
		//访问记录
		app::get('topmaker')->rpcCall('seller.visit',$params);
		$pagedata['seller_id']=$params['seller_id'];
		/*add_2018/11/22_by_wanghaichao_end*/

        // 微信分享
        $baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $weixin['imgUrl'] = base_storager::modifier($seller['avatar'], 'm');
        $weixin['linelink'] = $baseUrl;
        $weixin['shareTitle'] = empty($seller['shop_name']) ? '创客店铺首页' : $seller['shop_name'];
        $weixin['descContent'] = $seller['shop_description'];
        $pagedata['weixin'] = $weixin;
        $pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);

        return view::make('topwap/maker/home.html',$pagedata);
    }

    /**
     * ajax 获取主持人店铺商品
     */
    public function ajaxGetSellerItemList()
    {
        $params = input::get();

        if (3 == $params['order_by']) {
            $orderBy = 'item_count.paid_quantity desc';
        } else if (2 == $params['order_by']) {
            $orderBy = 'item.price desc';
        } else if (1 == $params['order_by']) {
            $orderBy = 'item.price asc';
        }

        if($params['page_size']) {
            $this->limit = intval($params['page_size']) > 0 ? intval($params['page_size']) : $this->limit;
        }

        $offset = 0;
        if ($params['pages']) {
            $params['pages'] = intval($params['pages']) > 0 ? intval($params['pages']) : 1;
            $offset = ($params['pages'] - 1) * $this->limit;
        }


        $filter['seller_id'] = $params['sellerId'];
        $filter['keywords'] = $params['keywords'];

        $list = $this->makerItemObj->getSellerItemList($filter, $offset, $this->limit, $orderBy);
        echo json_encode($list);
    }
	
	/**
	* 创客店铺首页
	* author by wanghaichao
	* date 2019/8/6
	*/
	public function tickethome(){
		$params=input::get();
		//print_r($params);die();
        /*if (!isset($params['seller_id']) || !$this->makerSellerObj->isSellerExist($params['seller_id'])) {
            return kernel::abort(404);
        }*/
		$pagedata['seller_id']=$params['seller_id'];
        return view::make('topwap/maker/tickethome.html',$pagedata);
	}
	
	/**
	* ajax获取票务系统
	* author by wanghaichao
	* date 2019/8/6
	*/
	public function ajaxGetTicketHomeItem(){
		$seller_id=input::get('seller_id');
		$shop=app::get('sysmaker')->model('shop_rel_seller')->getRow('shop_id',array('seller_id'=>input::get('seller_id')));
		//echo "<pre>";print_r($shop);die();
		$shop_id=$shop['shop_id'];
        $pages =  input::get('pages',1);
		
        $filter = array(
            'shop_id' => $shop_id,
            'approve_status' => 'onsale',
            'page_no' =>intval($pages),
            'page_size' => intval($this->limit),
        );

        $filter['fields'] = 'item_id,list_time,title,image_default_id,price,approve_status,store,dlytmpl_id,nospec,is_virtual,init_shop_id,init_item_id,init_is_change';
        $filter['orderBy'] = 'modified_time desc';
		$itemsList = app::get('topwap')->rpcCall('item.search',$filter);
		if(!empty($itemsList['list'])){
			foreach($itemsList['list'] as $k=>$v){
				$return[$k]['item_id']=$v['item_id'];
				$return[$k]['image_default_id']=base_storager::modifier($v['image_default_id']);
				$return[$k]['title']=$v['title'];
				$return[$k]['price']=number_format(($v['price']),2);
				$return[$k]['seller_id']=$seller_id;
				$return[$k]['url']=url::action('topwap_ctl_item_detail@index', array('item_id'=>$v['item_id'],'seller_id'=>$seller_id));
			}
		}else{
			$return=array();
		}
        echo json_encode($return);
	}

}