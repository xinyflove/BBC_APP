<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/9
 * Time: 14:21
 */
class topwap_ctl_store_index extends topwap_controller {
    private $__store_url = '';

    public function __construct()
    {
        parent::__construct();

        $input = input::get();
        if( empty($input['store_id']) )
        {
            throw new \LogicException('商城地址错误');
        }
    }

    public function home()
    {
        $input = input::get();

        $pagedata = array();
        $shop_infos = app::get('topc')->rpcCall('shop.list.all.get');
        if(!empty($input['store_id']))
        {
            kernel::single('base_session')->start();
            $_SESSION['store_id'] = $input['store_id'];
            $fields = 'template_path,widget_type';
            $filter = array(
                'store_id'=>$input['store_id'],
                'page_type'=>'home',
                'disabled'=>'0',
                'deleted'=>'0',
            );
            $order_sort = 'order_sort ASC, widget_id DESC';
            $widget_instance = app::get('sysstore')->model('widget_instance')
                ->getList($fields, $filter, 0, -1, $order_sort);

            foreach ($widget_instance as $k => &$v)
            {
                $v['id'] = $v['widget_type'] .'_'. $k;
            }
            unset($v);
            $pagedata['widget_instance'] = $widget_instance;
            $pagedata['store_id'] = $input['store_id'];
        }

        //店铺分类
        if($input['store_id'])
        {
            $shopids=app::get('sysstore')->model('store')->getRow('relate_shop_id',array('store_id'=>$input['store_id']));
            $filter['shop_id']=$shopids['relate_shop_id'];

            $pagedata['shopcat'] = app::get('topwap')->rpcCall('shop.cat.get',array('shop_id'=>$filter['shop_id']));
            foreach($pagedata['shopcat'] as $shopCatId=>&$row)
            {
                if( $row['children'] )
                {
                    $row['cat_id'] = $row['cat_id'].','.implode(',', array_column($row['children'], 'cat_id'));
                }
            }
            /*add_2018/1/12_by_wanghaichao_start*/
            //兼容多个店铺
            $shopcats=array();
            foreach($pagedata['shopcat'] as $k=>$v){
                $shopcats[$v['shop_id']]['shop_name']=str_replace('（自营店铺）','',$shop_infos[$v['shop_id']]['shop_name']);
                $shopcats[$v['shop_id']]['shop_id']=$v['shop_id'];
                $shopcats[$v['shop_id']]['cat'][]=$v;
            }
            $pagedata['shopcats']=$shopcats;
            //echo "<pre>";print_r($shopcats);die();
        }

		$pagedata['cartCount']=$this->getCartCount();
        return view::make('topwap/store/home.html',$pagedata);
    }

    public function getAjaxHomeData()
    {
        header('Access-Control-Allow-Origin:*');
        $input = input::get();
        $this->__store_url = url::action('topwap_ctl_store_index@home',  array('store_id'=>$input['store_id']));

        $fields = 'params,widget_type';
        $filter = array(
            'store_id'=>$input['store_id'],
            'page_type'=>'home',
            'disabled'=>'0',
            'deleted'=>'0',
        );
        $order_sort = 'order_sort ASC, widget_id DESC';
        $widget_instance = app::get('sysstore')->model('widget_instance')
            ->getList($fields, $filter, 0, -1, $order_sort);

        $data['widget'] = array();
        foreach ($widget_instance as $wd)
        {
            $d = array();
            $d['widget_type'] = $wd['widget_type'];
            $d['data'] = $this->getParams($wd['params'],$wd['widget_type']);
            $data['widget'][] = $d;
        }
        unset($widget_instance);
        //die;
        //$data['widget'] = $this->getTestData();
        echo json_encode($data);
    }

    protected function getParams($params, $widget_type)
    {
        $params = unserialize($params);

        switch ($widget_type)
        {
            case 'four_photo_display':
                if(!empty($params['left_posi']))
                {
                    if(!empty($params['left_posi']['valid_time']))
                    {
                        $valid_time = explode('-', $params['left_posi']['valid_time']);
                        unset($params['left_posi']['valid_time']);

                        $params['left_posi']['s_time'] = strtotime($valid_time[0]);
                        $params['left_posi']['e_time'] = strtotime($valid_time[1]);
                    }
                }

                if(empty($params['left_posi_url'])) $params['left_posi_url'] = $this->__store_url;

                if(empty($params['top_posi_url'])) $params['top_posi_url'] = $this->__store_url;

                if(empty($params['bottom_left_posi_url'])) $params['bottom_left_posi_url'] = $this->__store_url;

                if(empty($params['bottom_right_posi_url'])) $params['bottom_right_posi_url'] = $this->__store_url;

                if(!empty($params['left_posi_pic'])) $params['left_posi_pic'] = base_storager::modifier($params['left_posi_pic'], 't');
                if(!empty($params['top_posi_pic'])) $params['top_posi_pic'] = base_storager::modifier($params['top_posi_pic'], 't');
                if(!empty($params['bottom_left_posi_pic'])) $params['bottom_left_posi_pic'] = base_storager::modifier($params['bottom_left_posi_pic'], 't');
                if(!empty($params['bottom_right_posi_pic'])) $params['bottom_right_posi_pic'] = base_storager::modifier($params['bottom_right_posi_pic'], 't');

                break;
            case 'cate_nav':
                foreach ($params as &$p)
                {
                    if(empty($p['url'])) {
                        $p['url'] = $this->__store_url;
                    }
                    if(!empty($p['pic'])) {
                        $p['pic'] = base_storager::modifier($p['pic'], 't');
                    }
                }
                unset($p);

                break;
            case 'slider_ad':
                foreach ($params as &$p)
                {
                    if(empty($p['url'])) {
                        $p['url'] = $this->__store_url;
                    }
                    if(!empty($p['pic'])) {
                        $p['pic'] = base_storager::modifier($p['pic'], 't');
                    }
                }
                unset($p);

                break;
            case 'live_hot_sell':

                $items = app::get('sysitem')->model('item')
                    ->getList('item_id,price,title,image_default_id',array('item_id'=>$params['item_id']));

                $doc = array();
                foreach($items as $k=>&$v){
                    $v['sort'] = $params['sort'][$v['item_id']];
                    $v['url'] = url::action('topwap_ctl_item_detail@index',  array('item_id'=>$v['item_id']));
                    $v['price'] = number_format($v['price'], 2, '.', '');
                    $v['image_default_id'] = base_storager::modifier($v['image_default_id'], 't');
                    $doc[$k] = $v['sort'];
                }
                array_multisort($doc, SORT_ASC, $items);
                $params['items'] = $items;
                unset($params['item_id'],$params['sort']);

                if(empty($params['ad_url'])) $params['ad_url'] = $this->__store_url;
                if(!empty($params['ad_pic'])) $params['ad_pic'] = base_storager::modifier($params['ad_pic'], 't');

                break;
            case 'cate_goods_recommend_floor':
                $items = app::get('sysitem')->model('item')
                    ->getList('item_id,price,title,image_default_id,shop_id',array('item_id'=>$params['item_id']));

                $shop_arr = app::get('sysshop')->model('shop')->getList('shop_id,shop_name,shop_mold');
                $shop_arr = array_column($shop_arr, NULL, 'shop_id');

                foreach($items as $k=>&$v){
                    $v['url'] = url::action('topwap_ctl_item_detail@index', array('item_id'=>$v['item_id']));
                    $v['price'] = number_format($v['price'], 2, '.', '');
                    $v['shop_name'] = $shop_arr[$v['shop_id']]['shop_name'];
                    $v['shop_type'] = $shop_arr[$v['shop_id']]['shop_mold'];
                    $v['image_default_id'] = base_storager::modifier($v['image_default_id'], 't');
                }

                $params['items'] = $items;
                unset($params['item_id']);

                if(empty($params['ad_url'])) $params['ad_url'] = $this->__store_url;
                if(!empty($params['ad_pic'])) $params['ad_pic'] = base_storager::modifier($params['ad_pic'], 't');
                
                break;
        }

        //var_dump($params);
        return $params;
    }

    private function getTestData()
    {
        $data = array(
            'widget'=>array(
                array(//search
                    'placeholder' => '搜索商品'
                ),
                /*array(//four_photo_display
                    'left_posi' => array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                        's_time' => '1515220727',
                        'e_time' => '1515867216',
                    ),
                    'top_posi' => array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    'bottom_left_posi' => array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    'bottom_right_posi' => array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                ),
                array(//cate_nav
                    array(
                        'title' => '个护美妆',
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    array(
                        'title' => '母婴用品',
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    array(
                        'title' => '食品保健',
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    array(
                        'title' => '家居用品',
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                ),
                array(//slider_ad
                    array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                    array(
                        'pic' => '',
                        'url' => 'qtv.plaza.cn',
                    ),
                ),
                array(//live_hot_sell
                    'ad_pic' => '',
                    'ad_url' => '',
                    'items' => array(
                        array(
                            'sort' => 1,
                            'item_id' => 11,
                            'title' => '智能感体按摩椅',
                            'price' => '8299.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                        ),
                        array(
                            'sort' => 2,
                            'item_id' => 12,
                            'title' => '拉杆箱',
                            'price' => '399.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                        ),
                        array(
                            'sort' => 3,
                            'item_id' => 13,
                            'title' => 'N520除螨吸尘器',
                            'price' => '299.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                        ),
                        array(
                            'sort' => 4,
                            'item_id' => 14,
                            'title' => '尿不湿',
                            'price' => '199.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                        ),
                    ),
                ),
                array(//cate_goods_recommend_floor
                    'ad_pic' => '',
                    'ad_url' => '',
                    'items' => array(
                        array(
                            'item_id' => 11,
                            'title' => '不锈钢汤锅 还原食品本味',
                            'price' => '998.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                        array(
                            'item_id' => 12,
                            'title' => '可控粮油刷',
                            'price' => '998.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'broadcast',
                            'shop_name' => '915吃在青岛',
                        ),
                        array(
                            'item_id' => 13,
                            'title' => '厨房用剪刀',
                            'price' => '559.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'other',
                            'shop_name' => '官方自营',
                        ),
                        array(
                            'item_id' => 14,
                            'title' => '不粘锅',
                            'price' => '199.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                    ),
                ),
                array(//cate_goods_recommend_floor
                    'ad_pic' => '',
                    'ad_url' => '',
                    'items' => array(
                        array(
                            'item_id' => 11,
                            'title' => '升级救护颈加翼记忆枕',
                            'price' => '524.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                        array(
                            'item_id' => 12,
                            'title' => '保暖嫩肤蚕丝羽绒字母被',
                            'price' => '331.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'broadcast',
                            'shop_name' => '915吃在青岛',
                        ),
                        array(
                            'item_id' => 13,
                            'title' => '艾米 真皮沙发',
                            'price' => '559.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'other',
                            'shop_name' => '官方自营',
                        ),
                        array(
                            'item_id' => 14,
                            'title' => '四件套',
                            'price' => '998.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                    ),
                ),
                array(//cate_goods_recommend_floor
                    'ad_pic' => '',
                    'ad_url' => '',
                    'items' => array(
                        array(
                            'item_id' => 11,
                            'title' => '柔风黄油曲奇 480克',
                            'price' => '124.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                        array(
                            'item_id' => 12,
                            'title' => '东北稻花香大米',
                            'price' => '188.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'broadcast',
                            'shop_name' => '915吃在青岛',
                        ),
                        array(
                            'item_id' => 13,
                            'title' => '坚果夹心海苔 35克',
                            'price' => '59.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'other',
                            'shop_name' => '官方自营',
                        ),
                        array(
                            'item_id' => 14,
                            'title' => '龙卫辣条',
                            'price' => '4.00',
                            'image_default_id' => '',
                            'url' => 'qtv.plaza.cn',
                            'shop_type' => 'tv',
                            'shop_name' => '秀粉福利社',
                        ),
                    ),
                ),*/
            )
        );

        return $data;
    }
}