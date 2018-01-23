<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 18:19
 * Desc: 商城基础业务控制器
 */
class topstore_controller extends base_routing_controller
{

    /**
     * 页面不需要menu
     */
    public $nomenu = false;

    public function __construct($app)
    {
        pamAccount::setAuthType('sysstore');//设置权限类型
        $this->app = $app;
        $this->accountId = pamAccount::getAccountId();//帐号id
        $this->accountName = pamAccount::getLoginName();//帐号登录名
        $this->storeId = kernel::single('sysstore_data_account')->getStoreId($this->accountId);//商城id
        if($this->storeId)
        {
            //商城信息
            $this->storeInfo = app::get('sysstore')->model('store')->getRow('*', array('store_id'=>$this->storeId));
			$this->shopId = $this->storeInfo['relate_shop_id'];
			//$this->shopId=explode(',',$shopids);
        }

        $action = route::currentActionName();//当前动作名
        $actionArr = explode('@',$action);

        //如果当前控制器不是topstore_ctl_passport
        if( $actionArr['0'] != 'topstore_ctl_passport' )
        {
            //如果未登陆 && 不在定义数组中
            if( !$this->storeId && !in_array($actionArr[0], []) )
            {
                exit('错误！');
            }
        }

        // debug模式开启情况下使用系统错误处理
        if (! config::get('app.debug', true))
        {
            kernel::single('base_foundation_bootstrap_handleExceptions')
                ->setExceptionHandler(new topstore_exception_handler());
        }
    }

    /**
     * 检查是否登录
     * @return bool
     */
    public function checklogin()
    {
        if($this->accountId) return true;

        return false;
    }

    /**
     * 错误或者成功输出
     * @param string $status
     * @param null $url
     * @param null $msg
     * @param bool $ajax
     * @return mixed
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

    /**
     * 是否有效信息
     * @param $status
     * @return mixed
     */
    public function isValidMsg($status)
    {
        $status = ($status == 'true') ? 'true' : 'false';
        $res['valid'] = $status;
        return response::json($res);
    }

    /**
     * 商家中心页面加载，默认包含商家中心头、尾、导航、和左边栏
     * @param $view  html路径
     * @param array $pagedata  输出数据
     * @return mixed
     */
    public function page($view, $pagedata = array())
    {
        $accountData = storeAuth::getAccountData();

        $topstorePageParams['seo']['title'] = '多店铺商城';
        $topstorePageParams['view'] = $view;
        $topstorePageParams['account'] = $accountData;
        $topstorePageParams['path'] = $this->runtimePath;//设置面包屑

        $pagedata['storeId'] = $this->storeId;

        if( $this->contentHeaderTitle )
        {
            $topstorePageParams['contentTitle'] = $this->contentHeaderTitle;
        }

        //当前页面调用的action
        $topstorePageParams['currentActionName']= route::currentActionName();

        $allMenu = $this->__getMenu();

        if( $allMenu && !$this->nomenu )
        {
            $topstorePageParams['allMenu'] = $allMenu;
        }

        $pagedata['topstore'] = $topstorePageParams;
        unset($topstorePageParams);

        //获取logo
        $logo = app::get('site')->getConf('site.logo');
        $pagedata['logo'] = $logo;

        $pagedata['system_site_name'] = '多店铺商城';//app::get('site')->getConf('site.name');
        $pagedata['system_site_name_mini'] = '商城';
        $pagedata['icon'] =  app::get('topstore')->res_url.'/favicon.ico';

        if( !$this->tmplName )
        {
            $this->tmplName = 'topstore/tmpl/page.html';
        }

        return view::make($this->tmplName, $pagedata);
    }

    /**
     * 设置模版
     * @param $tmpl
     */
    public function set_tmpl($tmpl)
    {
        $tmplName = 'topstore/tmpl/'.$tmpl.'.html';
        $this->tmplName = $tmplName;
    }

    /**
     * 获取到商城的导航菜单和左边栏菜单
     * @return mixed
     */
    private function __getMenu()
    {
        $currentPermission = storeAuth::getAccountPermission();//当前权限
        $defaultActionName = route::currentActionName();//当前动作

        $storeMenu = config::get('store');//商城管理中心菜单定义配置
        $subdomainSetting = config::get('app.subdomain_enabled');//子域名开启配置
        $trafficsetting = config::get('stat.disabled');//是否开启流量统计配置

        $navbar = array();
        foreach( (array)$storeMenu as $menu => $row )
        {
            //不需要显示菜单
            if( $row['display'] === false ) continue;

            if( !$currentPermission || !$navbar[$menu] )
            {
                $row['display'] = false;
                $navbar[$menu] = $row;
                $navbar[$menu]['default'] = ($row['action'] == $defaultActionName && $navbar[$menu]);
                unset($navbar[$menu]['menu']);
            }

            foreach( (array)$row['menu'] as $k => $params )
            {
                //判断是否开启二级域名
                //平台未开启二级菜单功能，二级菜单不显示
                if(!$subdomainSetting && $params['url'] =="subdomain.html")
                {
                    $params['display'] = false;
                }

                //!$currentPermission 店主或者子账号有该菜单权限
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

    /**
     * 设置快捷键菜单
     * @param $data
     * @return mixed
     */
    public function setShortcutMenu($data)
    {
        var_dump('待修改 setShortcutMenu');die;
        return app::get('topshop')->setConf('shortcutMenuAction.'.$this->sellerId, $data);
    }

    /**
     * 获取快捷键菜单
     * @return mixed
     */
    public function getShortcutMenu()
    {
        var_dump('待修改 getShortcutMenu');die;
        return app::get('topshop')->getConf('shortcutMenuAction.'.$this->sellerId);
    }

    /**
     * 用于指示商家操作者的标志
     * @return array 商家登录用户信息
     */
    public function operator()
    {
        var_dump('待修改 operator');die;
        return array(
            'user_type' => 'seller',
            'op_id' => pamAccount::getAccountId(),
            'op_account' => pamAccount::getLoginName(),
        );
    }

    /**
     * 记录商家操作日志
     * @param $memo 日志内容
     * @return bool|void
     * tip: 暂不记录日志 2018-01-06
     */
    protected final function accountlog($memo)
    {
        // 开启了才记录操作日志
        //ACCOUNT_OPERATOR_LOG 需要 /config/production/compatible.php 定义
        if ( ACCOUNT_OPERATOR_LOG !== true ) return;

        if(!$this->storeId)
        {
            $account = app::get('sysstore')->model('account')
                ->getRow('store_id', array('account_id'=>pamAccount::getAccountId()));
            $storeId = $account['store_id'] ? $account['store_id'] : 0;
        }
        else
        {
            $storeId = $this->storeId;
        }
        $queue_params = array(
            'account_id'   => pamAccount::getAccountId(),
            'login_account' => pamAccount::getLoginName(),
            'store_id'         => $storeId,
            'created_time'    => time(),
            'memo'            => $memo,
            'router'          => request::fullurl(),
            'ip'              => request::getClientIp(),
        );
        //暂不记录日志 xinyufeng
        //return system_queue::instance()->publish('system_tasks_accountlog', 'system_tasks_accountlog', $queue_params);
    }

    protected function __removeScript($str)
    {
        var_dump('待修改 __removeScript');die;
        $a ="script";
        $str1 = str_replace($a,' ',$str);
        return $str1;
    }

}