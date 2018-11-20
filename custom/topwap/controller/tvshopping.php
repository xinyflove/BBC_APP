<?php
/**
 * 电视购物页面wap端页面
 * @auth: xinyufeng
 */
class topwap_ctl_tvshopping extends topwap_controller{

    public $shop_id;
    public $objWidgets = array();
    public $shop_setting;

    public function __construct()
    {
        $this->shop_id = input::get('shop_id');

        if(!$this->shop_id)
        {
            //蓝莓电视购物id
            $this->shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
        }

        if(!$this->shop_id)
        {
            exit("没有店铺信息.");
        }

        $this->shop_setting = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shop_id));
        if($this->shop_setting['shop_live_switch'] != 'on')
        {
            exit("没有配置电视购物店铺信息，请联系超管.");
        }

        kernel::single('base_session')->start();
    }

    public function index()
    {
        $platform = 'wap';
        $page_type = input::get('page_type','home');
        $obj_pages = kernel::single('topshop_tvshopping_pages');
        $pages_type = $obj_pages->pages_type[$platform];
        if(!array_key_exists($page_type, $pages_type))
        {
            throw new \LogicException("页面类型[{$page_type}]不存在.");
        }

        $_SESSION['shop_id'] = $this->shop_id;
        $pagedata['widget_html'] = '';

        $obj_widgets = kernel::single('topshop_tvshopping_widgets');
        $widgets = $obj_widgets->getUsedWidgets($this->shop_id, $page_type, $platform);

        foreach ($widgets as $key => $widget)
        {
            if(!$this->objWidgets[$widget['widgets_type']])
            {
                $this->objWidgets[$widget['widgets_type']] = kernel::single('topshop_tvshopping_widgets_'.$widget['widgets_type']);
            }
            $params = unserialize($widget['params']);
            // $params = $widget['params'];
            unset($widget['params']);
			$params['shop_id']=$this->shop_id;
            $datas = $this->objWidgets[$widget['widgets_type']]->makeParams($params);
            $output['setting'] = array_merge($widget, $params);
            $output['datas'] = $datas;
            $pagedata['widget_html'] .= view::make($widget['template_path'], $output)->render();
        }
        $pagedata['shopinfo'] = $this->shop_setting;
		$pagedata['title']=$pagedata['shopinfo']['shop_name'];
        // 店铺分类
		
		$extsetting=$this->__getExtSetting($this->shop_id);

		$weixin['imgUrl']= base_storager::modifier($pagedata['shopinfo']['shop_logo']);
		$weixin['linelink']= url::action("topwap_ctl_tvshopping@index",array('shop_id'=>$this->shop_id));
		$weixin['shareTitle']=$extsetting['params']['share']['shopcenter_title'];
		$weixin['descContent']=$extsetting['params']['share']['shopcenter_describe'];
		$pagedata['weixin']=$weixin;
		/*add_2017/9/27_by_wanghaichao_end*/
		/*add_2018/6/28_by_wanghaichao_start*/
		$baseUrl = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$pagedata['signPackage'] = kernel::single('topwap_jssdk')->index($baseUrl);
		/*add_2018/6/28_by_wanghaichao_end*/

        $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$this->shop_id));
		foreach($pagedata['shopcat'] as $shopCatId=>&$row)
        {
            if( $row['children'] )
            {
                $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
            }
        }
		$pagedata['shopId']=$this->shop_id;
        return view::make('topshop/tvshopping/pages/'. $page_type .'.html', $pagedata);
    }

    /**
     * 直播列表页
     * @return mixed
     * @auth: xinyufeng
     */
    public function liveList()
    {
        $weeks=array('星期日','星期一','星期二','星期三','星期四','星期五','星期六');
        $date_list = array();
        for($i=-2; $i < 3; $i++)
        {
            $time = strtotime("{$i} day");
            $date_list[] = array(
                'date' =>date('m-d', $time),
                'select_date' =>date('Y-m-d', $time),
                'today' => $i == 0 ? true : false,
                'week' => $i == 0 ? '今日直播' : $weeks[date('w', $time)],
            );
        }

        $pagedata['date_list'] = $date_list;
        $pagedata['size'] = 4;
        $pagedata['shop_id'] = $this->shop_id;

        return view::make('topshop/tvshopping/live_list.html', $pagedata);
    }

    /**
     * 直播列表数据
     * @return mixed
     * @auth: xinyufeng
     */
    public function ajaxLiveListData()
    {
        header('Access-Control-Allow-Origin:*');
        $input = input::get();                  // 获取参数
        $select_date = $input['select_date'];   // 选择日期

        if(empty($select_date))
        {
            // 如果没有选择日期参数，提示错误
            return $this->_output('error', '参数错误');
        }

        $shop_id = $this->shop_id;                              // 店铺id
        $op = $input['op'] ? $input['op'] : 'up';               // up↑ down↓
        $pages = $input['pages'] ? $input['pages'] - 1 : 0;     // 0为第一页
        $size = $input['size'] ? $input['size'] : 10;           // 每页显示的数据条数
        $time = strtotime('now');                               // 今天时间戳
        $date = date('Y-m-d', $time);                           // 今天日期
        $liveMdl = app::get('sysshop')->model('live');          // 商品modle
        $orderBy = 'live_start_time ASC';                       // 直播列表排序
        if($op == 'down') $orderBy = 'live_start_time DESC';
        $time_start = strtotime($select_date . ' 00:00:00');    // 选择日期开始时间戳
        $live_start_time = $time_start;                         // 直播开始时间戳
        $time_end = strtotime($select_date . ' 23:59:59');      // 选择日期结束时间戳
        $fields = 'live_id, title AS live_title, live_start_time, live_end_time, item_id';
        $datas['list'] = array();

        // live直播中 lived直播结束 unlive未直播
        if($op == 'down') $status_flag = 'lived';
        else $status_flag = 'unlive';

        if($date == $select_date)
        {
            // 如果选择日期等于今天日期
            $curr_fields = 'live_id, live_start_time';
            $curr_filter = array(
                'live_start_time|sthan' => $time,   // 直播开始时间小于等于今天时间
                'live_end_time|than' => $time,      // 直播结束时间大于今天时间
                'status' => 1,                      // 直播状态开启
            );
            // 当前直播信息
            $curr_live = $liveMdl->getRow($curr_fields, $curr_filter);
            if($curr_live)
            {
                $live_start_time = $curr_live['live_start_time'];
            }

        }
        elseif ($date < $select_date)
        {
            // 如果选择日期大于今天日期
            $status_flag = 'unlive';
        }
        else
        {
            // 如果选择日期小于今天日期
            $status_flag = 'lived';
        }

        $filter = array(
            'status' => 1,                               // 开启的直播
            'shop_id' => $shop_id,                       // 店铺id
        );
        if($op == 'up')
        {
            $filter['live_start_time|bthan'] = $live_start_time; // 直播开始时间大于等于选择日期的00:00:00
            $filter['live_end_time|sthan'] = $time_end;          // 直播结束时间小于等于选择日期的23:59:59
        }
        else
        {
            $filter['live_start_time|bthan'] = $time_start;     // 直播开始时间大于等于选择日期的00:00:00
            $filter['live_start_time|lthan'] = $live_start_time;// 直播结束时间小于当前直播开始时间戳
        }

        // 数据列表
        $list = $liveMdl->getList($fields, $filter, $pages*$size, $size, $orderBy);
        $count = $liveMdl->count($filter);  // 总条数
        $total = ceil($count/$size);    //总页数
        $datas['total'] = $total;

        // 遍历直播列表
        if($list)
        {
            $itemMdl = app::get('sysitem')->model('item');
            foreach ($list as &$live)
            {
                $live['live_time'] = date('H:i', $live['live_start_time']).'~'.date('H:i', $live['live_end_time']);
                $live['item_url'] = url::action('topwap_ctl_item_detail@index', array('item_id'=>$live['item_id']));

                $item_fields = 'title, image_default_id, price';
                $item_filter = array(
                    'item_id' => $live['item_id'],
                    'shop_id' => $shop_id,
                );
                $item = $itemMdl->getRow($item_fields, $item_filter);

                $live['title'] = $item['title'];
                $live['image_default_id'] = base_storager::modifier($item['image_default_id'], 't');
                $live['price'] = number_format($item['price'], 2, '.', '');
                $price_arr = explode('.', $live['price']);
                $live['price_0'] = $price_arr[0];
                $live['price_1'] = $price_arr[1];
                $live['live_status'] = $status_flag;

                if($curr_live['live_id'] == $live['live_id'])
                {
                    $live['live_status'] = 'live';
                    $live['live_time'] = '直播中';
                }

                if($op == 'up') $datas['list'][] = $live;
                else array_unshift($datas['list'], $live);
            }
            unset($live);
        }

        return $this->_output('success', '请求数据成功', $datas);
    }

	/* action_name (par1, par2, par3)
	* ajax获取店铺商品列表
	* author by wanghaichao
	* date 2018/8/6
	*/
	public function ajaxGetGoodsList($filter){
		$pages=input::get('pages');
		$itemids=input::get('item_ids');
        $params['shop_id'] = $this->shop_id;
        $params['item_id_no'] = $itemids;
        $params['page_size'] = 10;
		$offset=$params['page_size']*($pages-1);
		//echo "<pre>";print_r($params);die();
        //$params['approve_status'] = 'onsale'; //搜索必须为上架的商品
		$params['fields']='SI.item_id,SI.shop_id,SI.title,SI.image_default_id,SI.price,SIC.sold_quantity,SI.sell_time,SIC.paid_quantity,SISS.store,SI.right_logo,SISS.freez';
		$objMdlItem = app::get('sysitem')->model('item');
        $pagedata['itemsList']['list'] =$objMdlItem->getItemListOrderByStore($params['fields'],$params,$offset,$params['page_size']);
        //$pagedata['itemsList'] = app::get('topwap')->rpcCall('item.search',$params);
		$now=time();
		foreach($pagedata['itemsList']['list'] as $k=>&$v){
			$activityItem = app::get('topc')->rpcCall('promotion.activity.item.info',array('item_id'=>$v['item_id'],'valid'=>1),'buyer');
			if($activityItem && $activityItem['start_time']<$now && $activityItem['end_time']>$now){
				$v['price']=$activityItem['activity_price'];
				//$v['activity_tag']=$activityItem['activity_tag'];
			}
			$v['image_default_id']=base_storager::modifier($v['image_default_id'], 't');
			$v['url']=url::action('topwap_ctl_item_detail@index',array('item_id'=>$v['item_id']));
			$v['price'] = "￥".number_format($v['price'], 2, '.', '');
			//$price_arr = explode('.', $v['price']);
			//$v['price_0'] = $price_arr[0];
			//$v['price_1'] = $price_arr[1];
		}
        $pagedata['sell_out_img'] = app::get('sysconf')->getConf('item.sell.out.default.img');

        /*add_20171013_by_xinyufeng_start*/
        $pagedata['shop_setting'] = $this->shop_setting;
        /*add_20171013_by_xinyufeng_end*/
		//echo "<pre>";print_r($pagedata['itemsList']);die();
		return json_encode($pagedata['itemsList']);
        //return view::make('topshop/tvshopping/common/goods_list.html',$pagedata);
	}
	/* action_name (par1, par2, par3)
	* 获取主持人推荐商品
	* author by wanghaichao
	* date 2018/8/6
	*/
	//废弃
	public function ajaxGetCompere(){
		$filter['shop_id']=$this->shop_id;
		$filter['page_size']=1;
		$filter['page_no']=input::get('page');
		$filter['order']='sort asc';
		$compereList = app::get('topshop')->rpcCall('compere.list', $filter);
		$data=array();
		foreach($compereList['data'] as $k=>$v){
			$data[$k]['compere']=$v;
			$filter['compere_id']=$v['id'];
			$filter['page_size']=$params['itemcount'];
			$itemlist = app::get('topshop')->rpcCall('compere.item.list', $filter);
			$data[$k]['item']=$itemlist['data'];
		}
		$pagedata['datas']['title_pic']=$params['title_pic'];
		$pagedata['datas']['data']=$data[0];
		$pagedata['datas']['page']=input::get('page');
		return view::make('topshop/tvshopping/common/compere.html',$pagedata);
	}

    protected function _output($status='success', $msg=null, $datas=array())
    {
        $status = !in_array($status, array('error', 'success')) ? 'error' : $status;
        return response::json(array(
            $status => true,
            'message'=>$msg,
            'datas' => $datas,
        ));
    }
}