<?php
/**
 * 王衍生
 * 
 */

class ljstore_app_controller extends base_routing_controller
{

    /**
     * 页面不需要menu
     */
    public $account_type_arr = array('sysshop','syssupplier');
    public $account_type = '';
    public $bguser;

    public function __construct($app)
    {
        $config = config::get('session');
        $sess_key = $config['cookie'] ? $config['cookie']:'s';
        $_COOKIE[$sess_key] = input::get('sess_id',false);
        kernel::single('base_session')->start();

        // 签名验证
        // $this->isValidate();
        $this->setAuthType();
        // 调置sesson有效期 分钟
        kernel::single('base_session')->set_sess_expires(7*24*60);
        $this->app = $app;
        if(pamAccount::check())
        {
            // 商家或供应商id
            $this->supplierId = pamAccount::getAccountId();
            // 商家或供应商登录名
            $this->supplierName = pamAccount::getLoginName();

            if($this->account_type == 'sysshop')
            {
                // 取得店铺id
                $this->shopId = app::get('topshop')->rpcCall('shop.get.loginId',array('seller_id'=>$this->supplierId),'seller');
            }
            elseif($this->account_type == 'syssupplier')
            {
                // 取得店铺id
                $supplier = app::get('sysshop')->model('supplier')->getRow('*',array('supplier_id'=>$this->supplierId));
                $this->shopId = $supplier['shop_id'];
            }
            
            if($this->shopId)
            {
                $this->shopInfo = app::get('topshop')->rpcCall('shop.get',array('shop_id'=>$this->shopId));
            }
        }
    }
    /**
     * 取得供应商或商家会员信息
     * @Author   王衍生
     * @DateTime 2017-08-07T17:19:25+0800
     * @return   [type]                   [description]
     */
    public function getbguserData()
    {
        if($this->bguser) return $this->bguser;
        if(pamAccount::check())
        {
            if($this->account_type == 'sysshop')
            {
                $this->bguser = shopAuth::getSellerData();
            }
            elseif($this->account_type == 'syssupplier')
            {
                $this->bguser = supplierAuth::getSupplierData();
            }
        }
        return $this->bguser;
    }
    /**
     * 登录账户类型
     * @Author   王衍生
     * @DateTime 2017-08-07T16:12:32+0800
     */
    private function setAuthType()
    {
        $this->account_type = $_SESSION['account_type'];
        if(in_array($this->account_type,$this->account_type_arr))
        {
            pamAccount::setAuthType($this->account_type);
        }
    }
    /**
     * 签名验证
     * @Author   王衍生
     * @DateTime 2017-08-07T16:00:52+0800
     * @return   boolean                  [description]
     */
    private function isValidate()
    {
        $params = base_prism_request::getParams();

        $validate['timestamp'] = $params['timestamp'];
        $validate['format']    = $params['format'];
        $validate['sign_type'] = $params['sign_type'];
        $validate['sign']      = $params['sign'];
        // echo json_encode($params);
        // exit;
        // return $next($request);
        try{
           if( !base_rpc_validate::isValidate($validate) )
            {
                throw new LogicException(app::get('base')->_('签名错误'));
            }

            if( !( $validate['format'] == 'json' || $validate['format'] == 'xml' ) )
            {
                throw new LogicException(app::get('base')->_('返回格式设定必须是json或者xml'));
            }

            if( !is_numeric($validate['timestamp']) )
            {
                throw new LogicException(app::get('base')->_('时间格式错误（包含非数字的字符）'));
            }

            if( time() - intval($validate['timestamp']) > 300 )
            {
                throw new LogicException(app::get('base')->_('请求已超时'));
            }
        }        
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            $this->splash('failed',$msg);
        }
    }
    /**
     * 取得session_id
     * @Author   王衍生
     * @DateTime 2017-08-05T17:03:33+0800
     * @return   [type]                   [description]
     */
    public function get_sess_id()
    {
        return kernel::single('base_session')->sess_id();
    }
    /**
     * @brief 检查是否登录
     *
     * @return bool
     */
    public function checklogin()
    {
        if($this->supplierId) return true;

        return false;
    }

    /**
     * 王衍生
     * @brief 检查是否登录
     *
     * @return bool
     */
    public function not_login_out()
    {
        if($this->checklogin()){
            return true;
        }
        $this->splash(4001,'未登录！');
    }    
    /**
     * 王衍生
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
    public function splash($status=0,$msg=null,$expires=null){
        echo json_encode(array(
            'error'   => $status,
            'message' => $msg,
            'sess_id' => $this->get_sess_id(),
            'expires' => is_null($expires) ? (7*24*60*60 + time()) : (time()+$expires),
            // 'redirect' => $url,
        ));
        exit;

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
        $topshopPageParams['seller'] = $sellerData;
        $pagedata['shopId'] = $this->shopId;
        $topshopPageParams['path'] = $this->runtimePath;//设置面包屑

        if( $this->contentHeaderTitle )
        {
            $topshopPageParams['contentTitle'] = $this->contentHeaderTitle;
        }

        //当前页面调用的action
        $topshopPageParams['currentActionName']= route::current()->getActionName();

        $menuArr = $this->__getMenu();
		$shop_id=$this->shopId;

		if($shop_id!='19'){
			unset($menuArr['navbar']['cart']);
			unset($menuArr['all']['cart']);
		}
        if( $menuArr && !$this->nomenu )
        {
            $topshopPageParams['navbar'] = $menuArr['navbar'];
            $topshopPageParams['sidebar'] = $menuArr['sidebar'];
            $topshopPageParams['allMenu'] = $menuArr['all'];
        }
        //获取logo
        $logo = app::get('site')->getConf('site.logo');
        $pagedata['logo'] = $logo;
        $topshopPageParams['view'] = $view;

        $pagedata['topshop'] = $topshopPageParams;

        //$pagedata['icon'] =  kernel::base_url(1).'/statics/shop/favicon.ico';
        $pagedata['icon'] =  app::get('topshop')->res_url.'/favicon.ico';

        if( !$this->tmplName )
        {
            $this->tmplName = 'topshop/tmpl/page.html';
        }

        return view::make($this->tmplName, $pagedata);
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

        $defaultActionName = route::current()->getActionName();
        $shopMenu = config::get('shop');

        $shortcutMenuAction = $this->getShortcutMenu();
        $sidebar['commonUser']['label'] = '常用菜单';
        $sidebar['commonUser']['shortcutMenu'] = true;
        $sidebar['commonUser']['active'] = true; //是否展开
        $sidebar['commonUser']['icon'] = 'glyphicon glyphicon-heart';
        //$sidebar['commonUser']['menu'] = $commonUserMenu;

        foreach( (array)$shopMenu as $menu => $row )
        {
            if( $row['display'] === false ) continue;

            foreach( (array)$row['menu'] as $k=>$params )
            {
                //编辑常用菜单使用
                if( $params['display'] !== false && ( !$currentPermission || in_array($params['as'],$currentPermission )) )
                {
                    $allMenu[$menu]['label'] = $row['label'];
                    if( in_array($params['action'], $shortcutMenuAction) )
                    {
                        $sidebar['commonUser']['menu'][] =  $params;
                        $params['isShortcutMenu'] = true;
                    }

                    $allMenu[$menu]['menu'][] =  $params;
                }

                if($row['shopIndex'] || !$currentPermission || ($params['display'] && in_array($params['as'],$currentPermission) ))
                {
                    if( !$navbar[$menu] )
                    {
                        $navbar[$menu]['label'] = $row['label'];
                        $navbar[$menu]['icon'] = $row['icon'];
                        $navbar[$menu]['action'] = $navbar[$menu]['action'] ?  $navbar[$menu]['action'] : $params['action'];
                        $navbar[$menu]['default'] = false;
                    }
                }

                //如果为当前的路由则高亮
                if( !$navbar[$menu]['default'] && $params['action'] == $defaultActionName && $navbar[$menu] )
                {
                    $navbar[$menu]['default'] = true;
                    $selectMenu = $menu;
                }
            }

            if( !$row['shopIndex'] && $selectMenu ==  $menu)
            {
                foreach( (array)$row['menu'] as $k=>$params )
                {
                    $sidebar[$menu]['active'] = true;
                    $sidebar[$menu]['label'] = $row['label'];
                    $sidebar[$menu]['icon'] = $row['icon'];
                    if( !$currentPermission || in_array($params['as'],$currentPermission) )
                    {
                        $params['default'] = ($params['action'] == $defaultActionName) ? true : false;
                        $sidebar[$menu]['menu'][] =  $params;
                    }
                }
            }
        }

        $res['all'] = $allMenu;
        $res['navbar'] = $navbar;
        $res['sidebar'] = $sidebar;
        return $res;
    }

    public function setShortcutMenu($data)
    {
        return app::get('topshop')->setConf('shortcutMenuAction.'.$this->supplierId, $data);
    }

    public function getShortcutMenu()
    {
        return app::get('topshop')->getConf('shortcutMenuAction.'.$this->supplierId);
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
		    if ( SELLER_OPERATOR_LOG != true ) return;
	
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
       return app::get('system')->model('seller_log')->insert($queue_params);
      //  return system_queue::instance()->publish('system_tasks_sellerlog', 'system_tasks_sellerlog', $queue_params);
    }

	//增加分页功能
	public function __pagers($count,$page,$limit,$link){
        if($count>0)
        {
            $total = ceil($count/$limit);
        }
        $pagers = array(
            'link'=>$link,
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
        );
        return $pagers;
    }
	
	
}

