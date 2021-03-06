<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_controller extends base_routing_controller
{

    /**
     * 页面不需要menu
     */
    public $nomenu = false;
    public $isLm = false;
    public $isHmShop = false;
    public $loginSupplierId = 0;
    
    public function __construct($app)
    {
        pamAccount::setAuthType('sysshop');
        $this->app = $app;
        $this->sellerId = pamAccount::getAccountId();
        $this->sellerName = pamAccount::getLoginName();
        $this->shopId = app::get('topshop')->rpcCall('shop.get.loginId',array('seller_id'=>$this->sellerId),'seller');
        if($this->shopId)
        {
            $this->shopInfo = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shopId));
        }
        $action = route::currentActionName();
        $actionArr = explode('@',$action);
        if( $actionArr['0'] != 'topshop_ctl_passport' )
        {
            if( !$this->shopId &&  !in_array($actionArr[0], ['topshop_ctl_register', 'topshop_ctl_enterapply', 'topshop_ctl_find', 'topshop_ctl_shopexnode', 'topshop_ctl_huiminSupplierRegister']) )
            {
                redirect::action('topshop_ctl_register@enterAgreementPage')->send();exit;
            }
        }
        // debug模式开启情况下使用系统错误处理
        if (! config::get('app.debug', true))
        {
            kernel::single('base_foundation_bootstrap_handleExceptions')->setExceptionHandler(new topshop_exception_handler());
        }
		/*add_2018/6/19_by_wanghaichao_start*/
		//取出登录的卖家的信息,判断是不是主持人
		$this->sellerInfo=app::get('sysshop')->model('seller')->getRow('*',array('seller_id'=>$this->sellerId));
		/*add_2018/6/19_by_wanghaichao_end*/
        $this->topshopNewSetup = app::get('sysconf')->getConf('topshop.firstSetup');
        $this->openNewWapdecorate = redis::scene('shopDecorate')->hget('wapdecorate_status','shop_'.$this->shopId);
        $this->openNewAppdecorate = redis::scene('shopDecorate')->hget('appdecorate_status','shop_'.$this->shopId);
        //判断蓝莓购物权限
        $lm_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
        if($this->shopId == $lm_shop_id)
        {
            $this->isLm = true;
        }
        //判断是否是惠民店铺
        $hm_shop_id = app::get('sysshop')->getConf('sysshop.hmshopping.shop_id');
        if($this->shopId == $hm_shop_id)
        {
            $this->isHmShop = true;
        }
        $this->loginSupplierId = $this->checkHuiminSupplierLogin();
    }

    /**
     * @brief 检查是否登录
     *
     * @return bool
     */
    public function checklogin()
    {
        if($this->sellerId) return true;

        return false;
    }

    /**
     * @brief 错误或者成功输出
     *
     * @param string $status
     * @param stirng $url
     * @param string $msg
     * @param string $method
     * @param array $params
     *
     * @return string
     */
    public function splash($status='success',$url=null,$msg=null,$ajax=true){
        $status = ($status == 'failed') ? 'error' : $status;
        //如果需要返回则ajax
        if($ajax==true||request::ajax()){
            return response::json(array(
                $status => true,
                'message'=>$msg,
                'redirect' => $url,
            ));
        }

        if($url && !$msg){//如果有url地址但是没有信息输出则直接跳转
            return redirect::to($url);
        }
    }

    public function isValidMsg($status)
    {
        $status = ($status == 'true') ? 'true' : 'false';
        $res['valid'] = $status;
        return response::json($res);
    }

    /**
     * @brief 商家中心页面加载，默认包含商家中心头、尾、导航、和左边栏
     *
     * @param string $view  html路径
     * @param stirng $app   html路径所在app
     *
     * @return html
     */
    public function page($view, $pagedata = array())
    {
        $sellerData = shopAuth::getSellerData();
        $sellerData['shop_logo'] = $this->shopInfo['shop_logo'];
        $sellerData['shoptype'] = $this->shopInfo['shoptype'];
        if($this->shopId)
        {
            $roleInfo = app::get('topshop')->rpcCall('account.shop.roles.get',['role_id'=>$sellerData['role_id'],'shop_id'=>$this->shopId]);
            if($roleInfo)
            {
                $sellerData['role_name'] = $roleInfo['role_name'];
            }
        }
        //判断如果为惠民供应商登陆，则将登陆信息替换为惠民供应商的信息
        if($this->checkHuiminSupplierLogin()) {
            $supplier_data = $this->getSupplierInfo();
            $sellerData = array_merge($sellerData, $supplier_data);
            $topshopPageParams['is_hm_supplier'] = true;
        }
        $topshopPageParams['seller'] = $sellerData;
        $pagedata['shopId'] = $this->shopId;
        $topshopPageParams['path'] = $this->runtimePath;//设置面包屑

        if( $this->contentHeaderTitle )
        {
            $topshopPageParams['contentTitle'] = $this->contentHeaderTitle;
        }

        //当前页面调用的action
        $topshopPageParams['currentActionName']= route::currentActionName();

        $allMenu = $this->__getMenu();
		/*add_2018/1/5_by_wanghaichao_start*/
		//判断动力传媒的
		$shop_id=$this->shopId;
		if($shop_id!='32'){
			unset($allMenu['cart']);
		}
		/*add_2018/1/5_by_wanghaichao_end*/

        if( $allMenu && !$this->nomenu )
        {
            $topshopPageParams['allMenu'] = $allMenu;
        }

        //获取logo
        $logo = app::get('site')->getConf('site.logo');
        $pagedata['logo'] = $logo;
        $topshopPageParams['view'] = $view;

        $pagedata['topshop'] = $topshopPageParams;
        $pagedata['system_site_name'] = app::get('site')->getConf('site.name');

        $pagedata['icon'] =  app::get('topshop')->res_url.'/favicon.ico';
        $pagedata['topshopNewSetup'] = $this->topshopNewSetup;
        $pagedata['openNewWapdecorate'] = $this->openNewWapdecorate;
        $pagedata['openNewAppdecorate'] = $this->openNewAppdecorate;

        if( !$this->tmplName )
        {
            /*add_start_gurundong_2017/12/14*/
            //第三方记录session
            kernel::single('base_session')->start();
            kernel::single("base_session")->set_sess_expires(10080);
            //第三方登录特殊凭证
            $cp_login = $_SESSION['cp']['login'];
            /*add_end_gurundong_2017/12/14*/
            if($cp_login){
                //第三方登录
                $this->tmplName = 'topshop/tmpl/pageCp.html';
            }else{
                //广电登录
                $this->tmplName = 'topshop/tmpl/page.html';
            }
        }
        return view::make($this->tmplName, $pagedata);
    }

    /**
     * @return mixed
     * 获取供应商
     *
     */
    public function getSupplierInfo()
    {
        $supplier_id = $_SESSION['huimin_supplier_id'];
        $objMdlSupplier = app::get('sysshop')->model('supplier');
        $supplier_info = $objMdlSupplier->getRow('*', ['supplier_id' => $supplier_id]);

        $seller_data['login_account'] = $supplier_info['login_account'];
        $seller_data['login_password'] = $supplier_info['login_password '];
        $seller_data['mobile'] = $supplier_info['mobile'];
        $seller_data['email'] = $supplier_info['email'];
        $seller_data['name'] = $supplier_info['supplier_name'];
        $seller_data['shoptype'] = '惠民活动商户';

        return $seller_data;
    }

    public function set_tmpl($tmpl)
    {
        $tmplName = 'topshop/tmpl/'.$tmpl.'.html';
        $this->tmplName = $tmplName;
    }

    /**
     * @brief 获取到商家中心的导航菜单和左边栏菜单
     *
     * @return array $res
     */
    private function __getMenu()
    {
        $currentPermission = shopAuth::getSellerPermission();
        $defaultActionName = route::currentActionName();

        $shopMenu = config::get('shop');
        $subdomainSetting = config::get('app.subdomain_enabled');
        $trafficsetting = config::get('stat.disabled');
        /*add_20180516_by_jiangyunhan_start 判断商铺是否有查看'米粒儿线下店菜单'的权限*/
        if($this->shopInfo['supplier_mall'] != 'on'){
            unset($shopMenu['supplier']);
        }
        /*add_20180516_by_jiangyunhan_end*/

        /*add_20180723_by_jiangyunhan_start 判断商铺是否有查看'米粒儿线下店菜单'的权限*/
        if($this->shopInfo['supplier_mini_program'] != 'on'){
            unset($shopMenu['mini_program']);
        }
        /*add_20180723_by_jiangyunhan_end*/
        // 王衍生-2018/08/20-start
        if($this->shopInfo['shop_live_switch'] != 'on'){
            unset($shopMenu['live']);
        }
        // 王衍生-2018/08/20-end
        if(!$this->isLm)
        {
            unset($shopMenu['jinwanda']);
        }

        $is_hm_supplier = $this->checkHuiminSupplierLogin();
        if($is_hm_supplier) {
            $hm_permission = config::get('hmpermission');
            $hm_supplier_permission = $hm_permission['supplier'];
            $allow_main_menu = [];
            $allow_menu_route = [];
            foreach($hm_supplier_permission as $key=>$hsp) {
                array_push($allow_main_menu, $key);
                $allow_menu_route = array_merge($allow_menu_route, $hsp['menu']);
            }
        }

        foreach( (array)$shopMenu as $menu => $row )
        {
            //如果是惠民供应商登陆，则只保留允许的主菜单
            if($is_hm_supplier) {
                if(!in_array($menu, $allow_main_menu)) {
                    unset($shopMenu[$menu]);
                    continue;
                }
            }
            //不需要显示菜单
            if($menu=='offline'){
                if($this->shopInfo['offline']=='on'){
                    $row['display']=true;
                }else{
                    $row['display']=false;
                }
            }
            if( $row['display'] === false ) continue;

            if( !$currentPermission || !$navbar[$menu] )
            {
                $row['display'] = false;
                $navbar[$menu] = $row;
                $navbar[$menu]['default'] = ($row['action'] == $defaultActionName && $navbar[$menu]);
                unset($navbar[$menu]['menu']);
            }
            foreach( (array)$row['menu'] as $k=>$params )
            {
				/*add_2018/12/5_by_wanghaichao_start*/
				//判断是否需要开启黑名单功能
				if($this->shopInfo['blacklist']=='off'){
					if(strpos($params['action'],'topshop_ctl_account_blacklist')!==false){
						$params['display']=false;
					}
				}
				/*add_2018/12/5_by_wanghaichao_end*/

                //判断是否安装财务
                if(!app::get('sysfinance')->is_installed() && strstr($params['action'], 'guaranteeMoney_list'))
                {
                    $params['display'] = false;
                }

                //判断是否开启二级域名
                //平台未开启二级菜单功能，二级菜单不显示
                if(!$subdomainSetting && $params['url'] =="subdomain.html")
                {
                    $params['display'] = false;
                }

                //标记一下，临时解决im的菜单问题
                if(!app::get('sysim')->is_installed() && strstr($params['action'], 'im_webcall'))
                {
                    $params['display'] = false;
                }
                //标记一下，临时解决im的菜单问题--这里结束

                //是否显示wap端店铺装修菜单
                if(!$this->topshopNewSetup && $this->openNewWapdecorate !="open" && $params['url'] == 'new_decorate/edit.html')
                {
                    $params['display'] = false;
                }
                if(!$this->topshopNewSetup && $this->openNewAppdecorate !="open" && $params['url'] == 'app_decorate/edit.html')
                {
                    $params['display'] = false;
                }
                if($this->topshopNewSetup && $params['url'] == 'wapdecorate.html' || ($this->openNewAppdecorate == "open" && $this->openNewWapdecorate == 'open') && $params['url'] == 'wapdecorate.html')
                {
                    $params['display'] = false;
                }
                if($this->shopInfo['withdraw']=='off' && $params['url'] == 'shop/withdraw.html')
                {
                    $params['display'] = false;
                }
                if($this->shopInfo['offline']=='off' && $params['url'] == 'shop/ads_list.html')
                {
                    $params['display'] = false;
                }
                //判断如果不是蓝莓店铺，把归属蓝莓店铺的菜单隐藏
                if(!$this->isLm && $params['shop_belong'] && $params['shop_belong'] == 'LM')
                {
                    $params['display'] = false;
                }
                //判断如果不是惠民供应商登陆，把归属惠民供应商的菜单隐藏
                if(!$is_hm_supplier && $params['shop_belong'] && $params['shop_belong'] == 'HMSUPPLIER')
                {
                    $params['display'] = false;
                }
                //如果是惠民供货商，则将不相关路由隐藏掉
                if($is_hm_supplier) {
                    if(!in_array($params['as'], $allow_menu_route)) {
                        $params['display'] = false;
                    }
                }
				
				/*add_2019/8/8_by_wanghaichao_start*/
				if($this->shopId!=46){
					if($params['action']=='topshop_ctl_shop_setting@bgqr' || $params['action']=='topshop_ctl_clearing_settlement@ticketSellerBill'){
						$params['display']=false;
					}
				}else{
					if($params['action']=='topshop_ctl_clearing_settlement@sellerDetail'){
						$params['display']=false;
					}
				}
				/*add_2019/8/8_by_wanghaichao_end*/
				

                //! $currentPermission 店主 或者子账号有该菜单权限
                if( !$currentPermission || (in_array($params['as'],$currentPermission) ))
                {
                    //如果为当前的路由则高亮
                    if( $params['action'] == $defaultActionName && $navbar[$menu] )
                    {
                        $navbar[$menu]['default'] = true;
                        $params['default'] = true;
                    }
                    $navbar[$menu]['display'] = true;

                    //二级菜单 不需要显示菜单
                    if( !$params['display'] ) continue;

                    $navbar[$menu]['menu'][$k] = $params;
                }
            }
        }

        return $navbar;
    }

    public function setShortcutMenu($data)
    {
        return app::get('topshop')->setConf('shortcutMenuAction.'.$this->sellerId, $data);
    }

    public function getShortcutMenu()
    {
        return app::get('topshop')->getConf('shortcutMenuAction.'.$this->sellerId);
    }

    /**
     * 用于指示商家操作者的标志
     * @return array 商家登录用户信息
     */
    public function operator()
    {
        return array(
            'user_type' => 'seller',
            'op_id' => pamAccount::getAccountId(),
            'op_account' => pamAccount::getLoginName(),
        );
    }

    /**
     * 记录商家操作日志
     *
     * @param $lang 日志内容
     * @param $status 成功失败状态
     */
    protected final function sellerlog($memo)
    {
        // 开启了才记录操作日志
        if ( SELLER_OPERATOR_LOG !== true ) return;

        if(!$this->shopId)
        {
            $shopId = app::get('topshop')->rpcCall('shop.get.loginId',array('seller_id'=>pamAccount::getAccountId()),'seller');
        }
        else
        {
            $shopId = $this->shopId;
        }
        $queue_params = array(
            'seller_userid'   => pamAccount::getAccountId(),
            'seller_username' => pamAccount::getLoginName(),
            'shop_id'         => $shopId,
            'created_time'    => time(),
            'memo'            => $memo,
            'router'          => request::fullurl(),
            'ip'              => request::getClientIp(),
        );
        return system_queue::instance()->publish('system_tasks_sellerlog', 'system_tasks_sellerlog', $queue_params);
    }

    protected function __removeScript($str)
    {
        $a ="script";
        $str1 = str_replace($a,' ',$str);
        return $str1;
    }

    public function checkHuiminSupplierLogin()
    {
        return (pamAccount::check() && $_SESSION['huimin_supplier_id']) ? $_SESSION['huimin_supplier_id'] : 0;
    }
}
