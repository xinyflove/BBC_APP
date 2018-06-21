<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/12/11
 * Time: 17:15
 */
class topwap_ctl_foodmap_index extends topwap_controller{

    private $map_key = 'a1dcdbc51efa1e086a14706ffa12a61a';
    private $page_size = 10;
    private $type = array(
        'all' => '全部',
        'food' => '美食',
        'activity' => '活动',
        'live' => '直播',
    );

    /**
     * 地图首页
     * @return mixed
     */
    public function home()
    {
        $shop_id = input::get('shop_id');
        
        $pagedata = array();
        $pagedata['map_key'] = $this->map_key;
        $pagedata['shop_id'] = $shop_id;
        return view::make('topwap/foodmap/home.html', $pagedata);
    }

    /**
     * 获取供应商数据
     */
    public function supplier_data()
    {
        $params = input::get();
        $datas = array('code'=>'400', 'msg'=>'获取地图数据失败', 'url'=>url::action('topwap_ctl_default@index'));
        if(empty($params['posi']) || empty($params['shop_id'])){
            echo json_encode($datas);
            return;
        }

        $shop_base = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$params['shop_id'],'fields'=>'api_shop_id'));
        $roleid = $shop_base['api_shop_id'];
        if(false && empty($roleid)){
            //无接口角色id,无法获取数据
            echo json_encode($datas);
            return;
        }

        $offset = empty($params['page']) ? 0 : $params['page']-1;
        $limit = $this->page_size;
        $posi_arr = explode(',', $params['posi']);
        $filter = array(
            'apiParam' => array(
                'longitude'=>$posi_arr[0],
                'latitude'=>$posi_arr[1],
                'status'=>'shop',
                'distance'=>$params['search_distance'],
                'roleid'=>$roleid,
            ),
            'where' => array(
                'type' => $params['search_type'],
                'shop_id' => $params['shop_id'],
                'name' => $params['name'],
            )
        );
        if(!empty($params['search_word'])){
            $filter['apiParam']['shopName'] = $params['search_word'];
        }
        $orderBy = $params['order'] ? $params['order'] : 'distance,asc';
        $list = kernel::single('foodmap_data_supplier')->getSupplierData($filter,$offset,$limit,$orderBy);

        $datas['code'] = '200';
        $datas['msg'] = '获取地图数据成功';
        $datas['url'] = '';
        $datas['data'] = $list;
        echo json_encode($datas);
    }

    /**
     * 地图导航
     * @return mixed
     */
    public function map_nav()
    {
        $pagedata = input::get();
        $pagedata['map_key'] = $this->map_key;
        return view::make('topwap/foodmap/map_nav.html', $pagedata);
    }

    /**
     * 红包列表数据
     */
    public function red_envelope_list()
    {
        $red_envelope = $this->getRedEnvelope();

        echo json_encode($red_envelope);
    }

    /**
     * 获取红包结果
     */
    public function get_red_envelope()
    {
        $red_envelope = $this->getRedEnvelope();
    }

    /**
     * 生成红包列表数据
     * @return array
     */
    private function getRedEnvelope()
    {
        $red_envelope = array(
            array(
                'id' => 1,
                'center' => array(120.424687,36.100733),
                'img' => '/app/topwap/statics/foodmap/img/red_envelope36x36.png',
                'is_true' => 1,
                'is_exist' => 0,
            ),
            array(
                'id' => 2,
                'center' => array(120.404001,36.093313),
                'img' => '/app/topwap/statics/foodmap/img/red_envelope36x36.png',
                'is_true' => 0,
            ),
            array(
                'id' => 3,
                'center' => array(120.433484,36.099589),
                'img' => '/app/topwap/statics/foodmap/img/red_envelope36x36.png',
                'is_true' => 1,
                'is_exist' => 1,
            ),
            array(
                'id' => 4,
                'center' => array(120.411211,36.097335),
                'img' => '/app/topwap/statics/foodmap/img/red_envelope36x36.png',
                'is_true' => 0,
            ),
            array(
                'id' => 5,
                'center' => array(120.415248,36.087786),
                'img' => '/app/topwap/statics/foodmap/img/red_envelope36x36.png',
                'is_true' => 1,
                'is_exist' => 1,
            ),
        );
        return $red_envelope;
    }
}