<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topwap_controller extends base_routing_controller
{
    /**
     * 定义当前平台
     */
    public $platform = 'wap';

    /**
     * 控制器指定的布局(layout), 具体到文件
     *
     * @var \Illuminate\View\View
     */
    private $layout = null;

    /**
     * 控制器指定的布局标识, 会调取用户配置, 以决定最终应用的布局.
     *
     * @var \Illuminate\View\View
     */
    private $layoutFlag = 'default';


    public function __construct()
    {
        $this->thirdparty_app_login();

        theme::setIcon(app::get('topwap')->res_url.'/favicon.ico');
    }

    protected function setLayout($layout)
    {
        $this->layout = $layout;
    }

    protected function setLayoutFlag($layoutFlag)
    {
        $this->layoutFlag = $layoutFlag;
    }

    public function set_cookie($name,$value,$expire=false,$path=null){
        if(!$this->cookie_path){
            $this->cookie_path = kernel::base_url().'/';
        }
        $this->cookie_life = $this->cookie_life > 0 ? $this->cookie_life : 315360000;
        $expire = $expire === false ? time()+$this->cookie_life : $expire;
        setcookie($name,$value,$expire,$this->cookie_path);
        $_COOKIE[$name] = $value;
    }
    /**
     * page
     *
     * @param  boolean $realpath
     * @return base_view_object_interface | string
     */
    public function page($view = null, $data = array())
    {
        // 王衍生-2018/07/06-end
        // 第三方app标识 在注册第三方app内打开则有效,否则为空
        $data['thirdparty_app_name'] = $this->from_thirdparty_app();
        // 王衍生-2018/07/06-end
        // 模板名称
        $themeName = ($params['theme'])?$params['theme']:kernel::single('site_theme_base')->get_default('mobile');
        $theme = theme::uses($themeName);
        $layout = $this->layout;
        if (!$layout)
        {
            $layoutFlag = !is_null($this->layoutFlag) ? $this->layoutFlag : 'defalut';
            $tmplObj = kernel::single('site_theme_tmpl');
            $layout = $tmplObj->get_default($this->layoutFlag, $themeName);
            $layout = $layout ? $layout : (($tmpl_default = $tmplObj->get_default('default', $themeName)) ? $tmpl_default : 'default.html');
        }
        $theme->layout($layout);

        if (! is_null($view))
        {
            $theme->of($view, $data);
        }
        return $theme->render();
    }

    /*
     * 结果处理
     * @var string $status
     * @var string $url
     * @var string $msg
     * @var boolean $ajax
     * @var array $data
     * @access public
     * @return void
     */

    public function splash($status='success', $url=null , $msg=null,$ajax=false){
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

        $this->setLayoutFlag('splash');
        $pagedata['msg'] = $msg;
        return $this->page('topwap/splash/error.html', $pagedata);
    }

    /**
     * 用于指示卖家操作者的标志
     * @return array 买家登录用户信息
     */
    public function operator()
    {
        return array(
            'account_type' => 'buyer',
            'op_id' => userAuth::id(),
            'op_account' => userAuth::getLoginName(),
        );
    }
	/*add_2017/9/20_by_wanghaichao_start*/
   public function viewPage($view = null, $pagedata = array())
    {
        $pagedata['pathtopm'] = app::get('topwap')->res_full_url;
        return view::make($view, $pagedata);
    }
	/*add_2017/9/20_by_wanghaichao_end*/

	/* function_name:__getExtSetting (shop_id)
	*  函数说明:
	*  获取店铺其他配置信息的
	* 参数:shop_id 商铺id
	* author by wanghaichao
	* date 2017/9/27
	*/

	public function __getExtSetting($shop_id){
		if(empty($shop_id)) return;
		$extsetting= app::get('topshop')->rpcCall('shop.extsetting.get',array('shop_id'=>$shop_id,'use_platform'=>'wap'));

        $shopdata = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$shop_id));
		if(empty($extsetting['params']['share']['shopcenter_title'])){
			$extsetting['params']['share']['shopcenter_title']=$shopdata['shop_name'];
		}
		if(empty($extsetting['params']['share']['shopcenter_describe'])){
			$extsetting['params']['share']['shopcenter_describe']=$shopdata['shop_name']."直营店，青岛广电生活圈，媒体认证，正品保证。";
		}
		return $extsetting;
	}

	/* function_name:getCartCount (shop_id)
	*  函数说明:
	*  获取购物车数量
	* author by wanghaichao
	* date 2017/9/27
	*/

	public function getCartCount(){
		$cartCount='';
		if(userAuth::check()){
			$params['user_id'] = userAuth::id();
			$cartData = app::get()->rpcCall('trade.cart.getCount',$params);
			$cartCount=$cartData['number'];
			return  $cartCount;
		}else{
			$result = kernel::single('topwap_cart')->getCartInfo();
			$data=$result['resultCartData'];
			foreach($data as $key=>$val){
				foreach($val['object'] as $k=>$v){
					$cartCount+=$v['quantity'];
				}
			}
			return $cartCount;
		}
	}

	/* getXzCount (sku_id)
	* 根据商品sku_id获取商品活动限制数量的
	* author by wanghaichao
	* date 2017/9/28
	*/
	public function getXzCount($sku_id){
		$item=app::get('sysitem')->model('sku')->getRow('item_id',array('sku_id'=>$sku_id));
		$item_id=$item['item_id'];
		/*add_2017/11/1_by_wanghaichao_start*/
		//在商品表里面也有数量限制在这里进行判断
		$item_info=app::get('sysitem')->model('item')->getRow('limit_quantity',array('item_id'=>$item_id));
		if($item_info['limit_quantity']>0){
			return $item_info['limit_quantity'];
		}
		/*add_2017/11/1_by_wanghaichao_end*/

        // 活动促销(如名字叫团购)
        $activityDetail = app::get('topm')->rpcCall('promotion.activity.item.info',array('item_id'=>$item_id,'valid'=>1),'buyer');
		$time=time();
		if($activityDetail['activity_info']['start_time']>$time){
			$limit=0;
		}else{
			$limit=$activityDetail['activity_info']['buy_limit'];   //用户的限制数量
		}
		return $limit;
	}

	/* action_name (par1, par2, par3)
	* 二维数组排序,par1:数组,par2:根据的字段,par3 排序规则,默认正序
	* author by wanghaichao
	* date 2017/11/9
	*/
    public function array_sort($arr,$keys,$type='asc')
    {
        $keysvalue = $new_array = array();
        foreach ($arr as $k=>$v)
        {
            $keysvalue[$k] = $v[$keys];
        }
        if($type == 'asc')
        {
            asort($keysvalue);
        }
        else
        {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k=>$v)
        {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

	/* action_name (par1, par2, par3)
	* 验证手机号有没有注册用户
	* author by wanghaichao
	* date 2017/11/27
	*/
	public function checkUser($mobile){
		$user_info=app::get('sysuser')->model('account')->getRow('user_id',array('mobile'=>$mobile));
		if($user_info){
            userAuth::login($user_info['user_id'], $mobile);
			return true;
		}else{
			return false;
		}
    }

	/* action_name (par1, par2, par3)
	* 用户获取open_id的逻辑
	* author by wanghaichao
	* date 2017/11/27
	*/
	public function getOpenID($url = ''){
		if(kernel::single('topwap_wechat_wechat')->from_weixin()){
			// $payInfo = app::get('topwap')->rpcCall('payment.get.conf', ['app_id' => 'wxpayjsapi']);
			// $wxAppId = $payInfo['setting']['appId'];
			// $wxAppsecret = $payInfo['setting']['Appsecret'];
			$wxAppId = app::get('site')->getConf('site.appId');
			$wxAppsecret = app::get('site')->getConf('site.appSecret');
			if(!input::get('code'))
			{
			    /*edit_start_gunrundong_2017/12/22*/
			    if(empty($url))
                {
                    $url = url::action('topwap_ctl_activityvote_vote@voteList',$postdata);
                }
				/*edit_start_gurundong_2017/12_22*/
				kernel::single('topwap_wechat_wechat')->get_code($wxAppId, $url);
			}
			else
			{
				$code = input::get('code');
				$openid = kernel::single('topwap_wechat_wechat')->get_openid_by_code($wxAppId, $wxAppsecret, $code);
				if($openid == null){
					$this->splash('failed', 'back',  app::get('topwap')->_('获取openid失败'));
				}
				return $openid;
			}
		}else{
			return "NO_OPENID";
		}
	}

    public function thirdparty_app_login()
    {
        // 如果未登录并且来自第三方app
        if(!userAuth::check() && $this->from_thirdparty_app()){
            // 则进行注册登录
            $this->thirdparty_app_register();
        }
    }

    /**
     * 为兼容早期蓝睛app
     * 判断是否来自蓝睛APP
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function from_lanjing() {
        // $_SERVER['HTTP_USER_AGENT'] = 'abcdlanjing{"uid":"10"}';
        $p = strpos($_SERVER['HTTP_USER_AGENT'], 'lanjing');
        if ( $p !== false ) {
            $json_info = json_decode(substr($_SERVER['HTTP_USER_AGENT'], $p + 7), true);
            if($json_info['from'] == 'lanjing') {
                return 'lanjing';
            }
        }
        return false;
    }

    /**
     * 为兼容早期蓝睛app
     * 取得蓝睛会员信息
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function get_lanjing_userinfo()
    {
        $p = strpos($_SERVER['HTTP_USER_AGENT'], 'lanjing');
        if ( $p !== false ) {
            $json_info = json_decode(substr($_SERVER['HTTP_USER_AGENT'], $p + 7), true);
            if($json_info['from'] == 'lanjing') {
                return $json_info;
            }
        }

        return [];

    }
    /**
     * 返回第三方app标识
     * 未在第三方app 则返回false
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    public function from_thirdparty_app() {
        return kernel::single('topwap_thirdpartyapp_thirdpartyapp')->from_thirdparty_app();
    }

    /**
     * 第三方APP内登录/注册
     * {"from":"lanjing","user_info":{"uid":12,"mobile":"13188998899"}}
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    protected function thirdparty_app_register()
    {
        $json_info = $this->get_thirdparty_app_userinfo();
        if(empty($json_info)) {
            $this->thirdparty_app_throw_error('用户信息不对！');
        }

        $app_user_info = $json_info['user_info'];
        $app_user_info['flag'] = $this->from_thirdparty_app() ? : '';
        if(empty($app_user_info['flag'])){
            $this->thirdparty_app_throw_error('APP标识不能为空！');
        }

        $type = kernel::single('pam_tools')->checkLoginNameType($app_user_info['mobile']);
        if($type != 'mobile') {
            $this->thirdparty_app_throw_error('用户名类型必须为手机号！');
        }
        // 是否有注册锁
        $lock_status = redis::scene('sysuser')->get("signup_lock:{$app_user_info['flag']}:{$app_user_info['mobile']}");
        if($lock_status){
            $this->thirdparty_app_throw_error('系统繁忙，请稍后再试！');
        }
        // 加上注册锁
        redis::scene('sysuser')->set("signup_lock:{$app_user_info['flag']}:{$app_user_info['mobile']}", '1');

        $objMdlaccount = app::get('sysuser')->model('account');
        $trustModel = app::get('sysuser')->model('trustinfo');

        // 已注册
        if ($row = $trustModel->getRow('user_id', ['user_flag' => $app_user_info['uid'], 'flag' => $app_user_info['flag']]))
        {
            $userId = $row['user_id'];
            // return ['err_code' => '404002', 'err_code_des' => ''];
        } else {
            $db = app::get('sysuser')->database();
            $db->beginTransaction();
            try {
                $filter[$type] = $app_user_info['mobile'];
                $user = $objMdlaccount->getRow('user_id',$filter);
                // 绑定老用户
                if($user) {
                    $userId = $user['user_id'];
                // 创建新用户
                } else {
                    $logo = request::fullUrl();
                    $logo .= json_encode($app_user_info);
                    logger::info('thirdparty_app_throw_info' . $logo . time());
                    // 手机验证码为初始密码
                    $password = $confirmedPassword = $app_user_info['flag'] . $app_user_info['flag'];
                    //  -1代表来自第三方app
                    $userId = userAuth::signUp($app_user_info['mobile'], $password, $confirmedPassword, -1);
                }
                kernel::single('sysuser_passport_trust_trust')->bind($userId, $app_user_info['uid'], $app_user_info['flag']);
                $db->commit();
            } catch (Exception $e) {
                $db->rollback();
                // 删除注册锁
                redis::scene('sysuser')->setex("signup_lock:{$app_user_info['flag']}:{$app_user_info['mobile']}", 5, 1);
                $message = $e->getMessage();
                $this->thirdparty_app_throw_error($message);
            }
        }
        userAuth::login($userId, null, 'wap', $app_user_info['flag']);
        // 删除注册锁
        redis::scene('sysuser')->setex("signup_lock:{$app_user_info['flag']}:{$app_user_info['mobile']}", 5, 1);
    }
    /**
     * 取得第三方app用户信息
     *
     * @return void
     * @Author 王衍生 50634235@qq.com
     */
    protected function get_thirdparty_app_userinfo()
    {
        if(!($app_name = $this->from_thirdparty_app())) {
            // $this->thirdparty_app_throw_error('非注册第三方app！');
            return false;
        }

        $app_flag = "tvplaza_thirdparty_app_{$app_name}_app";
        $p = strpos($_SERVER['HTTP_USER_AGENT'], $app_flag);

        if ($p === false) {

            // 兼容蓝睛早期app
            return $this->get_lanjing_userinfo();
            // return '';
        }
        preg_match("/{$app_flag}(.+?)$/i", $_SERVER['HTTP_USER_AGENT'], $user_agent);

        $json_info = json_decode($user_agent[1], true);
        return $json_info;
    }

    protected function thirdparty_app_throw_error($error_msg)
    {
        // echo 'thirdparty_app_throw_error' . $error_msg;
        logger::info('thirdparty_app_throw_error' . $error_msg);
        // $this->splash('error', '', $error_msg);
        $pagedata['msg'] = $error_msg;
        echo view::make('topwap/splash/error.html', $pagedata);
        exit;
    }

}


