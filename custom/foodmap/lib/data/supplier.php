<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2017/12/21
 * Time: 10:26
 * Desc: 供应商数据
 */
class foodmap_data_supplier {
    private $__useTestData = true;
    //供应商列表接口链接
    private $__supplier_list = 'http://zhibo.tvplaza.cn:28080/mobile/appShop/get915ShopList';
    private $__supplier_save = 'http://zhibo.tvplaza.cn:28080/mobile/appShop/saveShopInfo';
    //接口类型
    private $__apiType = array(
        'supplier_list' => '供应商列表接口',
        'supplier_save' => '供应商保存接口',
    );
    //接口调用信息
    private $__apiLogMsg = array(
        '0' => '调用接口成功,获取数据成功.',
        '10' => '调用接口成功,获取数据失败.',
        '11' => '调用接口失败,获取数据失败.',
    );

    /**
     * 获取处理后的供应商列表数据
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @param null $orderBy
     * @return mixed
     */
    public function getSupplierData($filter=array(), $offset=0, $limit=10, $orderBy=null){
        $data = $this->getCacheSupplierData();
		$data = '';
        $apiPrarm = $filter['apiParam'];
        $where = $filter['where'];

        $curr_page = $offset + 1;
        if($curr_page == 1 || empty($data)){
            if($this->__useTestData){
                //测试数据
                $data = $this->_getApiSupplierTestData();
            }
            else
            {
                $data = $this->getApiSupplierData($apiPrarm);//调用供货商数据接口
            }
            $this->setCacheSupplierData($data);
        }

        //数据进行排序
        if($orderBy){
            $orderByArr = explode(',', $orderBy);
            if(count($orderByArr) == 2){
                $_by = strtolower($orderByArr[1]);
                if(in_array($_by, array('asc','desc'))){
                    $_order = $orderByArr[0];
                    if(array_key_exists($_order, $apiPrarm)){
                        foreach($data as $key=>$val){
                            $dos[$key] = $val[$_order];
                        }
                        if($_by == 'asc'){
                            array_multisort($dos,SORT_ASC,$data);
                        }else{
                            array_multisort($dos,SORT_DESC,$data);
                        }
                    }
                }
            }
        }

        //过滤启用的供应商并且有库存上架的代金券
        $data_page = array();
        $p = 1;$c = 1;$i=0;
        foreach ($data as $d)
        {
            if($c > $limit){
                $c = 1;
                $p++;
            }

            $_data = array();

            //等待接口添加类型字段
            /*if($where['type'] != 'all' && $where['type'] != $d['type']){
                continue;
            }*/
            $d['type'] = 'food';

            if($d['type'] == 'food'){
                $supplierInfo = app::get('sysshop')->model('supplier')
                    ->getRow('*', array('shop_id'=>$where['shop_id'], 'api_supplier_id'=>$d['id'],'is_audit'=>'PASS'));
                if(empty($supplierInfo)) continue;

                $vouchers = $this->_getSupplierInfoVoucher($supplierInfo['supplier_id'],$where['shop_id']);
                if(empty($vouchers)) continue;

                foreach ($vouchers as &$voucher){
                    $voucher['url'] = url::action('topwap_ctl_item_detail@index', array('item_id'=>$voucher['item_id']));
                }
                unset($voucher);

                $_data['supplier_item'] = $vouchers;
            }else{
                $_data['supplier_item'] = array(
                    array('url'=>url::action('topwap_ctl_default@index'))
                );
            }

            $_data['id'] = $i;
            $_data['supplier_id'] = $supplierInfo['supplier_id'];
            $_data['supplier_name'] = $supplierInfo['supplier_name'];
            $_data['supplier_posi'] = implode(',', array($d['longitude'],$d['latitude']));
            $_data['supplier_img'] = '/app/topwap/statics/foodmap/img/tupian.png';
            $_data['supplier_addr'] =  "{$where['name']}{$d['distance']}米";
            $_data['supplier_tel'] = $supplierInfo['sh_phone'];
            $_data['supplier_type'] = $d['type'];
            $_data['is_running'] = 1;

            $data_page[$p][$i] = $_data;

            $c++;
            $i++;
        }
        unset($data);

        $datas = array(
            'list' => $data_page[$curr_page] ? $data_page[$curr_page] : '',
            'page_total' => $p,
            'curr_page' => $curr_page,
        );

        return $datas;
    }

    /**
     * 获取缓存数据
     * @return mixed
     */
    public function getCacheSupplierData()
    {
        kernel::single('base_session')->start();
        $data = unserialize($_SESSION['food_map_supplier_data']);
        return $data;
    }

    /**
     * 设置缓存数据
     * @param $data
     */
    public function setCacheSupplierData($data)
    {
        kernel::single('base_session')->start();
        $_SESSION['food_map_supplier_data'] = serialize($data);
    }

    /**
     * 调用接口获取供应商列表
     * @param $post_data
     * @return array
     */
    public function getApiSupplierData($post_data)
    {
        $api_data = array(
            'api_type' => 'supplier_list',
            'api_url' => $this->__supplier_list,
            'api_param' => serialize($post_data),
        );

        $data = array();
        $post_data = http_build_query($post_data);
        $res = $this->requestPost($this->__supplier_list, $post_data);

        if($res['code'] == 0){
            $api_data['api_url_status'] = 0;
            $api_data['api_url_msg'] = '接口链接调用成功';

            if(!empty($res['data'])){
                $res['data'] = json_decode($res['data'], true);

                $api_data['api_res_code'] = $res['data']['code'];
                $api_data['api_res_data'] = serialize($res['data']['result']);

                if($res['data']['code'] == 200){
                    $api_data['api_log_code'] = 0;
                    $api_data['api_log_msg'] = $this->__apiLogMsg[0];

                    if(!empty($res['data']['result']['appShop_list'])){
                        $data = $res['data']['result']['appShop_list'];
                    }
                }else{
                    $api_data['api_log_code'] = 10;
                    $api_data['api_log_msg'] = $this->__apiLogMsg[10];
                }
            }
        }else{
            $api_data['api_url_status'] = 1;
            $api_data['api_url_msg'] = '接口链接调用失败';
            $api_data['api_log_code'] = 11;
            $api_data['api_log_msg'] = $this->__apiLogMsg[11];
        }

        $this->_addApiLog($api_data);

        return $data;
    }

    /**
     * [curl 方式 获取一个post请求]
     * @param  string $url       [post链接]
     * @param  string $post_data [键值数组或者key=val&key2=val2]
     * @return [type]            [code>0请求失败;code=0请求成功]
     */
    public function requestPost($url = '', $post_data = '') {
        if (empty($url) || empty($post_data)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $post_data;
        $ch = curl_init();//初始化curl
        $this_header = array("content-type: application/x-www-form-urlencoded; charset=UTF-8");
        // 设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100')
        curl_setopt($ch, CURLOPT_HTTPHEADER,$this_header);
        // 需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
        curl_setopt($ch, CURLOPT_URL,$postUrl);
        // 启用时会将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
        curl_setopt($ch, CURLOPT_POST, 1);
        // 提交数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        // 设置超时 秒
        curl_setopt($ch, CURLOPT_TIMEOUT,15);
        // 执行一个cURL会话
        $data = curl_exec($ch);
        $curl_errno = curl_errno($ch);  // 返回最后一次的错误号
        $curl_error = curl_error($ch);  // 返回一个保护当前会话最近一次错误的字符串
        curl_close($ch);
        $result = array('code' => $curl_errno);
        if( $curl_errno ){
            // 错误
            $result['msg'] = $curl_error;
            $result['data'] = '';
        }else{
            // 成功
            $result['msg'] = 'success';
            $result['data'] = $data;
        }
        return $result;
    }

    /**
     * [curl 方式 获取一个get请求]
     * @param  [type] $url [get链接]
     * @return [type]      [code>0请求失败;code=0请求成功]
     * @tip 支持https链接请求
     */
    public function requestGet($url) {
        $ch = curl_init();	// 初始化一个cURL会话
        curl_setopt($ch, CURLOPT_URL,$url); 	// 需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候。
        curl_setopt($ch, CURLOPT_HEADER, 0);	// 启用时会将头文件的信息作为数据流输出
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);	// TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	//不验证证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);	//不验证证书
        $data = curl_exec($ch);	// 执行一个cURL会话
        $curl_errno = curl_errno($ch);  // 返回最后一次的错误号
        $curl_error = curl_error($ch);  // 返回一个保护当前会话最近一次错误的字符串
        curl_close($ch);
        $result = array('code' => $curl_errno);
        if( $curl_errno ){
            // 错误
            $result['msg'] = $curl_error;
            $result['data'] = '';
        }else{
            // 成功
            $result['msg'] = 'success';
            $result['data'] = $data;
        }
        return $result;
    }

    /**
     * 获取代金券信息
     * @param $supplier_id
     * @param $shop_id
     * @return bool
     */
    protected function _getSupplierInfoVoucher($supplier_id,$shop_id)
    {
        //供应商信息
        $supplierInfo = app::get('sysshop')->model('supplier')
            ->getRow('disabled', array('supplier_id'=>$supplier_id,'shop_id'=>$shop_id,'is_audit'=>'PASS'));
        if($supplierInfo['disabled']){//如果被禁用
            return false;
        }

        //供应商代金券
        $builder = app::get('base')->database()->createQueryBuilder();
        $row = 'item.item_id,item.title,item.price,item.mkt_price,item.image_default_id';
        $where = "item.shop_id='{$shop_id}' AND item.supplier_id='{$supplier_id}' AND item.is_virtual='1' AND (item_store.store-item_store.freez)>0 AND item_status.approve_status='onsale'";
        $itemsList = $builder->select($row)
            ->from('sysitem_item', 'item')
            ->leftJoin('item', 'sysitem_item_store', 'item_store', 'item.item_id=item_store.item_id')
            ->leftJoin('item', 'sysitem_item_status', 'item_status', 'item.item_id=item_status.item_id')
            ->where($where)
            ->execute()->fetchAll();

        return $itemsList;
    }

    /**
     * 添加日志信息
     * @param $api_data
     */
    protected function _addApiLog($api_data)
    {
        kernel::single('foodmap_data_apilog')->add($api_data,$msg);
    }

    /**
     * 测试接口数据
     * @return array
     */
    protected function _getApiSupplierTestData()
    {
        $data = array(
            array(
                'id' => 1,// 对应 sysshop_supplier 表中的 api_supplier_id,此供货商的必须有在售的虚拟商品
                'longitude' => '120.410863',
                'latitude' => '36.08637',
                'distance' => '423',
                'supplier_id' => 2,
                'roleid' => 1,
                'type' => 'food',
            ),
            array(
                'id' => 2,
                'longitude' => '120.410777',
                'latitude' => '36.08363',
                'distance' => '613',
                'supplier_id' => 2,
                'roleid' => 2,
                'type' => 'food',
            ),
            array(
                'id' => 3,
                'longitude' => '120.410606',
                'latitude' => '36.087271',
                'distance' => '419',
                'supplier_id' => 3,
                'roleid' => 3,
                'type' => 'food',
            ),
            array(
                'id' => 4,
                'longitude' => '120.41494',
                'latitude' => '36.087965',
                'distance' => '29',
                'supplier_id' => 4,
                'roleid' => 4,
                'type' => 'food',
            ),
            array(
                'id' => 5,
                'longitude' => '120.41494',
                'latitude' => '36.087965',
                'distance' => '100',
                'supplier_id' => 5,
                'roleid' => 5,
                'type' => 'food',
            ),
            array(
                'shopName' => '青实樱花郡直播',
                'longitude' => '120.403921',
                'latitude' => '36.09336',
                'distance' => '300',
                'type' => 'live',
            ),
        );

        return $data;
    }

    /**
     * 与接口同步供应商信息
     * @param $post_data
     * @return bool
     */
    public function syncApiSupplierInfo($post_data)
    {
        if(!$post_data){
            return false;
        }

        $supplierid = $post_data['supplierid'];
        $api_shop_id = 0;

        $api_data = array();
        $api_data['api_type'] = 'supplier_save';
        $api_data['api_url'] = $this->__supplier_save;
        $api_data['api_param'] = serialize($post_data);

        $post_url = $api_data['api_url'];
        $post_data = http_build_query($post_data);
        $res = $this->requestPost($post_url, $post_data);
        if($res['code'] == 0){
            $api_data['api_url_status'] = 0;
            $api_data['api_url_msg'] = '接口链接调用成功';

            if(!empty($res['data'])){
                $res['data'] = json_decode($res['data'], true);

                $api_data['api_res_code'] = $res['data']['code'];
                $api_data['api_res_data'] = serialize($res['data']['result']);

                if($res['data']['code'] == 200){
                    $api_data['api_log_code'] = 0;
                    $api_data['api_log_msg'] = $this->__apiLogMsg[0];

                    if(!empty($res['data']['result']['shop'])){
                        $shop = $res['data']['result']['shop'];
                        $api_shop_id = $shop['id'];
                    }
                }else{
                    $api_data['api_log_code'] = 10;
                    $api_data['api_log_msg'] = $this->__apiLogMsg[10];
                }
            }
        }else{
            $api_data['api_url_status'] = 1;
            $api_data['api_url_msg'] = '接口链接调用失败';
            $api_data['api_log_code'] = 11;
            $api_data['api_log_msg'] = $this->__apiLogMsg[11];
        }
        $this->_addApiLog($api_data);

        if($api_shop_id){
            app::get('sysshop')->model('supplier')
                ->update(array('api_supplier_id'=>$api_shop_id,'api_sync'=>1), array('supplier_id'=>$supplierid));
            return true;
        }
        return false;
    }
}