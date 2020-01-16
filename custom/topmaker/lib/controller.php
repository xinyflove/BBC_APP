<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客基础业务控制器
 */
class topmaker_controller extends base_routing_controller {
    
    public $app;
    public $sellerId;
    public $accountName;
    public $sellerInfo;
    public $bindShop;

    public function __construct($app)
    {
        $this->app = $app;
        $this->sellerId = pamAccount::getAccountId();//帐号id
        $this->accountName = pamAccount::getLoginName();//帐号登录名

        if($this->sellerId)
        {
            $this->sellerInfo = makerAuth::getSellerData($this->sellerId);
            $_bindShop = app::get('sysmaker')->model('shop_rel_seller')->getList('*', array('seller_id'=>$this->sellerId));
            if($_bindShop)
            {
                $this->bindShop = $_bindShop[0];
                $_shopInfo = app::get('sysshop')->model('shop')->getRow('shop_name', array('shop_id'=>$this->bindShop['shop_id']));
                $this->bindShop['shop_name'] = $_shopInfo['shop_name'];
            }
        }

        $action = route::currentActionName();//当前动作名
        $actionArr = explode('@',$action);
        //如果当前控制器不是topmaker_ctl_passport
        if(!in_array($actionArr[0], ['topmaker_ctl_passport','topmaker_ctl_trustlogin']))
        {
			/*add_增加判断_by_wanghaichao_start*/
			if(!($actionArr[0]=='topmaker_ctl_index' && $actionArr[1]=='share')){
				
				//如果未登陆 && 非登陆控制器
				if(!$this->sellerId)
				{
					exit('错误！');
				}

				$this->_checkSellerStatus();
			}
			/*add_增加判断_by_wanghaichao_end*/
			
        }

        // debug模式开启情况下使用系统错误处理
        if (! config::get('app.debug', true))
        {
            kernel::single('base_foundation_bootstrap_handleExceptions')
                ->setExceptionHandler(new topmaker_exception_handler());
        }
    }

    /**
     * 加载静态页面
     * @param $view
     * @param array $pagedata
     * @return mixed
     */
    public function page($view, $pagedata = array())
    {
        if( $this->contentHeaderTitle )
        {
            $topmakerPageParams['contentTitle'] = $this->contentHeaderTitle;
        }
        
        $pagedata['topmaker'] = $topmakerPageParams;
        $pagedata['topmaker']['accountName'] = $this->accountName;
        $pagedata['topmaker']['sellerInfo'] = $this->sellerInfo;
        unset($topmakerPageParams);
        
        return view::make($view, $pagedata);
    }

    /**
     * 错误或者成功输出
     * @param string $status
     * @param null $url
     * @param null $msg
     * @param bool $ajax
     * @return mixed
     */
    public function splash($status='success',$url=null,$msg=null,$ajax=true)
    {
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
     * 检查创客状态
     */
    protected function _checkSellerStatus()
    {
        $action = route::currentActionName();//当前动作名
		if($action=='topmaker_ctl_index@ticketindex'){
			$type='ticket';
		}else{
			$type='';
		}
        $url = url::action('topmaker_ctl_passport@makerCheck',array('type'=>$type));

        if($this->sellerInfo['account']['deleted'])
        {
            echo '<p>店铺已删除</p>';
            exit;
        }
        if($this->sellerInfo['account']['disabled'])
        {
            echo '<p>店铺被禁用</p>';
            exit;
        }
        if($this->sellerInfo['account']['status'] != 'success')
        {
            Header('Location: ' . $url);
        }
        if($this->bindShop['deleted'])
        {
            echo '<p>创客被绑定店铺禁用</p>';
            exit;
        }
        if($this->bindShop['status'] != 'success')
        {
            Header('Location: ' . $url);
        }
    }

    /**
     * 记录创客操作日志
     * @param $memo 日志内容
     * @return bool|void
     * tip: 暂不记录日志 2018-11-14
     */
    protected final function accountlog($memo)
    {
        // 开启了才记录操作日志
        //ACCOUNT_OPERATOR_LOG 需要 /config/production/compatible.php 定义
        if ( ACCOUNT_OPERATOR_LOG !== true ) return;
        
        $queue_params = array(
            'seller_id'   => pamAccount::getAccountId(),
            'login_account' => pamAccount::getLoginName(),
            'created_time'    => time(),
            'memo'            => $memo,
            'router'          => request::fullurl(),
            'ip'              => request::getClientIp(),
        );
        //暂不记录日志 xinyufeng
        //return system_queue::instance()->publish('system_tasks_accountlog', 'system_tasks_accountlog', $queue_params);
    }
}