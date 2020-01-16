<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客帐号登录相关业务控制器
 */
class topmaker_ctl_passport extends topmaker_controller {

    public $passport;

    public function __construct()
    {
        parent::__construct();
        kernel::single('base_session')->start();
        $this->passport = kernel::single('topwap_passport');
    }

    /**
     * 登录页面
     * @return mixed
     */
    public function signin()
    {
        $inputs = input::get();
		$type=$inputs['type'];
        $thirdData = $this->_getThirdData($inputs);
        if(empty($thirdData['userflag']))
        {
            $thirdData = array();
            //处理微信端访问
            $this->_wechatLogin('topmaker_ctl_trustlogin@callbackSignIn',array('type'=>$type));
        }

        $this->contentHeaderTitle = app::get('topmaker')->_('创客登录');

        if( pamAccount::isEnableVcode('sysmaker') )
        {
            // 开启验证码
            $pagedata['isShowVcode'] = 'true';
        }

        $pagedata['thirdData'] = json_encode($thirdData);
		/*add_2019/8/1_by_wanghaichao_start*/
		$pagedata['type']=$type;						//通过参数判断加载不同的页面
		/*add_2019/8/1_by_wanghaichao_end*/
		if(isset($type) && $type=='ticket'){
			return $this->page('topmaker/passport/ticket_signin.html', $pagedata);
		}else{			
			return $this->page('topmaker/passport/signin.html', $pagedata);
		}
    }

    /**
     * 登录处理
     * @return mixed
     */
    public function login()
    {
        if( pamAccount::isEnableVcode('sysmaker') )
        {
            // 验证图片验证码
            if(!base_vcode::verify(input::get('imagevcodekey'), input::get('imgcode')))
            {
                $msg = app::get('topmaker')->_('图片验证码错误') ;
                $url = url::action('topmaker_ctl_passport@signin');
                return $this->splash('error',$url,$msg,true);
            }
        }

        try
        {
            $validator = validator::make(
                [
                    'loginAccount'=>input::get('login_account'),
                    'password' => input::get('login_password'),
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'required|min:6|max:20',
                ],
                [
                    'loginAccount'=>'请输入你的手机号!|请输入正确的手机号码',
                    'password' => '密码长度不能小于6位!|密码长度不能大于20位!',
                ]
            );
            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    throw new \LogicException($error[0]);
                }
            }

            // 登陆验证
            makerAuth::login(input::get('login_account'), input::get('login_password'));
        }
        catch(Exception $e)
        {
            $url = url::action('topmaker_ctl_passport@signin');
            $msg = $e->getMessage();
        }

        if( pamAccount::check() )   // 验证是否已登录
        {
            //登录成功
            if( input::get('remember_me') )
            {
                // 记录用户名
                setcookie('MAKERNAME',trim(input::get('login_account')),time()+31536000,kernel::base_url().'/');
            }

            $third = input::get('third');
            if($third)
            {
                // 添加信任登录数据
                $objHhirdpartyinfo = kernel::single('sysmaker_data_thirdpartyinfo');
                $bool = $objHhirdpartyinfo->saveThirdData($third, $msg);
                if($bool)
                {
                    // 保存信任登录数据
                    $objTrustinfo = kernel::single('sysmaker_data_trustinfo');
                    $trustData = array(
                        'seller_id' => pamAccount::getAccountId(),
                        'user_flag' => $third['userflag'],
                        'flag' => $third['flag'],
                    );
                    $trustId = $objTrustinfo->addTrustInfoData($trustData, $msg);
                    makerAuth::setTrustId($trustId);
                }
            }
            
            /*modify_2019/8/1_by_wanghaichao_start*/
            /*
            $url = url::action('topmaker_ctl_index@index');
			*/
			if(input::get('type')=='ticket'){
				$url = url::action('topmaker_ctl_index@ticketindex');
			}else{
				$url = url::action('topmaker_ctl_index@index');
			}
            /*modify_2019/8/1_by_wanghaichao_end*/
            
            $msg = app::get('topmaker')->_('登录成功');
            $this->accountlog('账号登录。账号名是'.input::get('login_account'));

            if(request::ajax())
                return $this->splash('success',$url,$msg,true);
            else
                return redirect::to($url);
        }
        else
        {
            return $this->splash('error',$url,$msg,true);
        }
    }

    /**
     * 退出
     * @return mixed
     */
    public function logout()
    {
        makerAuth::logout();
        return redirect::action('topmaker_ctl_passport@signin');
    }

    /**
     * 注册页面
     * @return mixed
     */
    public function signup()
    {
        //如果已登录则退出登录
        if( pamAccount::check() ) $this->logout();

        $inputs = input::get();
		if(isset($inputs['type']) && $inputs['type']=='ticket'){
			$type=$inputs['type'];
		}else{
			$type='';
		}
        $thirdData = $this->_getThirdData($inputs);
        if(empty($thirdData['userflag']))
        {
            $thirdData = array();
            //处理微信端访问
            $this->_wechatLogin('topmaker_ctl_trustlogin@callbackSignUp',array('type'=>$type));
        }

        $this->contentHeaderTitle = app::get('topmaker')->_('创客注册');

        // 获取店铺列表
        $objShop = kernel::single('sysshop_data_shop');
        $shopList = $objShop->fetchListShopInfo('shop_id,shop_name');
        $pagedata['shopList'] = $shopList;

        $pagedata['thirdData'] = json_encode($thirdData);
		
		/*add_2019/8/1_by_wanghaichao_start*/
		$type=input::get('type');
		$pagedata['type']=$type;						//通过参数判断加载不同的页面
		/*add_2019/8/1_by_wanghaichao_end*/
	
		if(isset($type) && $type=='ticket'){
			return $this->page('topmaker/passport/ticket_signup.html',$pagedata);
		}else{
			return $this->page('topmaker/passport/signup.html', $pagedata);
		}
    }

    /**
     * 注册处理
     * @return mixed
     */
    public function create()
    {
        $pagedata = utils::_filter_input(input::get());
		//echo "<pre>";print_r($pagedata);die();
        try
        {
            // 无需输入确认密码
            $validator = validator::make(
                [
                    'loginAccount'=>$pagedata['login_name'],
                    'password' => $pagedata['login_password'],
                    'mcode' => $pagedata['mcode'],
                    'name' => $pagedata['name'],
                    'id_card_no' => $pagedata['id_card_no'],
                    //'registered' => $pagedata['registered'],
                    'shop_id' => $pagedata['shop_id'],
                ],
                [
                    'loginAccount'=>'required|mobile',
                    'password' => 'required|min:6|max:20',
                    'mcode' => 'required',
                    'name' => 'required',
                    //'id_card_no' => 'required',
                    //'registered' => 'required',
                    'shop_id' => 'required',
                ],
                [
                    'loginAccount'=>'请输入你的手机号!|请输入正确的手机号码',
                    'password' => '密码长度不能小于6位!|密码长度不能大于20位!',
                    'mcode' => '请输入验证码!',
                    'name' => '请输姓名!',
                    //'id_card_no' => '请输身份证号!',
                    //'registered' => '请选择户口所在地!',
                    'shop_id' => '请选择店铺!',
                ]
            );

            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    throw new \LogicException($error[0]);
                }
            }

            $sellerBool = makerAuth::isExists($pagedata['login_name'], $type='mobile');
            if($sellerBool)
            {
                throw new \LogicException("该手机号已经被使用");
            }

			if($pagedata['type']=='ticket'){
				$res=$this->checkcart($pagedata['cart_number']);
				if($res===false){
                    throw new \LogicException('请输入青岛地区的车牌号!');
				}
				if(empty($pagedata['front_img'])){
                    throw new \LogicException('请上传身份证正面照!');
				}
				if(empty($pagedata['reverse_img'])){
                    throw new \LogicException('请上传身份证反面面照!');
				}

			}

            $vcodeData = userVcode::verify($pagedata['mcode'], $pagedata['login_name'], $pagedata['mcode_type']);
            if(!$vcodeData)
            {
                throw new \LogicException('手机验证码填写错误');
            }

            if(isset($pagedata['id_card_no']) && !empty($pagedata['id_card_no']) && !$this->__check18IDCard($pagedata['id_card_no']))
            {
                throw new \LogicException('请输入正确的身份证号');
            }

            $pid = 0;
            if($pagedata['pmobile'])
            {
                $objSeller = kernel::single('sysmaker_data_seller');
                $pid = $objSeller->getPIdByMobile($pagedata['pmobile']);
                if(!$pid)
                {
                    throw new \LogicException('推荐人不存在!');
                }
            }

            $signUpData = array(
                'mobile' => $pagedata['login_name'],
                'login_password' => $pagedata['login_password'],
                'psw_confirm' => $pagedata['login_password'],
                'name' => $pagedata['name'],
                'id_card_no' => $pagedata['id_card_no'],
                'registered' => $pagedata['registered'],
                'pid' => $pid,
                'shop_id' => $pagedata['shop_id'],
                'third' => $pagedata['third'],
				'front_img'=>$pagedata['front_img'],
				'reverse_img'=>$pagedata['reverse_img'],
				'cart_number'=>strtoupper($pagedata['cart_number']),
				'group_id'=>$pagedata['group_id'],
            );

            $sellerId = makerAuth::signUp($signUpData);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error', null, $msg);
        }
        $url = url::action('topmaker_ctl_passport@makerCheck',array('type'=>$pagedata['type']));
        $msg = '注册成功';
        return $this->splash('success', $url, $msg);
    }

    private function __check18IDCard($IDCard)
    {
        if (strlen($IDCard) != 18)
        {
            return false;
        }

        $IDCardBody = substr($IDCard, 0, 17); //身份证主体
        $IDCardCode = strtoupper(substr($IDCard, 17, 1)); //身份证最后一位的验证码

        if ($this->__calcIDCardCode($IDCardBody) != $IDCardCode) return false;
        return true;
    }
    private function __calcIDCardCode($IDCardBody)
    {
        if (strlen($IDCardBody) != 17) return false;

        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $code = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;

        for ($i = 0; $i < strlen($IDCardBody); $i++)
        {
            $checksum += substr($IDCardBody, $i, 1) * $factor[$i];
        }

        return $code[$checksum % 11];
    }

    /**
     * 验证状态页面
     * @return mixed
     */
    public function makerCheck()
    {
		$type=input::get('type');
        if($this->sellerInfo['account']['status'] == 'success' && $this->bindShop['status'] == 'success')
        {
			if(isset($type) && $type=='ticket'){
				return redirect::action('topmaker_ctl_index@ticketindex');
			}else{
				return redirect::action('topmaker_ctl_index@index');
			}
        }elseif($this->bindShop['status']=='refuse'){			//拒绝直接跳转
			//$this->logout();
		}

        $this->contentHeaderTitle = app::get('topmaker')->_('申请进度');

        $pagedata['account_status'] = $this->sellerInfo['account']['status'];
        $pagedata['shop_status'] = $this->bindShop['status'];
		$pagedata['reason']=$this->bindShop['reason'];
		/*modify_2019/8/5_by_wanghaichao_start*/
		/*return $this->page('topmaker/passport/maker_check.html', $pagedata);*/
		if(isset($type) && $type=='ticket'){
			return $this->page('topmaker/passport/ticket_maker_check.html', $pagedata);
		}else{
			return $this->page('topmaker/passport/maker_check.html', $pagedata);
		}
		/*modify_2019/8/5_by_wanghaichao_end*/
		
    }

    /**
     * 使用此方法的场景 手机验证码登录、找回密码
     * @return mixed
     */
    public function sendVcode()
    {
        $postdata = utils::_filter_input(input::get());

        $validator = validator::make(
            [$postdata['mobile']],['required|mobile'],['您的手机号不能为空!|请输入正确的手机号码']
        );

        $url = url::action('topmaker_ctl_passport@signup');

        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                return $this->splash('error',$url,$error[0]);
            }
        }

        try {
            $this->passport->sendVcode($postdata['mobile'],$postdata['type']);
        } catch(Exception $e) {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }

        return $this->splash('success',null,"验证码发送成功");
    }

    /**
     * 微信信任登录
     * @param $redirectAction
     */
    protected function _wechatLogin($redirectAction,$params)
    {
        // 判断是否来自微信浏览器
        if(kernel::single('sysmaker_wechat')->from_weixin())
        {
            $makerTrust = kernel::single('pam_trust_maker');
            // 如果开启了信任登录
            if($makerTrust->enabled())
            {
                $flag = 'wapweixin';
                // 获取指定的TRUST信息
                $trustInfo = $makerTrust->getTrustInfo('wap', $redirectAction, $flag,$params);
                
                if($trustInfo)
                {
                    echo "<script>location.href = '{$trustInfo['url']}';</script>";
                    exit;
                }
            }
        }
    }

    /**
     * 过滤出来信任登录数据
     * @param $data
     * @return array
     */
    protected function _getThirdData($data)
    {
        $thirdData = array(
            'userflag' => $data['userflag'],
            'flag' => $data['flag'],
            'openid' => $data['openid'],
            'nickname' => $data['nickname'],
            'sex' => $data['sex'],
            'figureurl' => $data['figureurl'],
            'country' => $data['country'],
            'province' => $data['province'],
            'city' => $data['city'],
			/*add_2019/8/1_by_wanghaichao_start*/
			//通过参数判断加载不同的页面
			'type'=>$data['type'],
			/*add_2019/8/1_by_wanghaichao_end*/
        );

        return $thirdData;
    }
	
	/**
	* 匹配车牌号
	* author by wanghaichao
	* date 2019/8/27
	*/
	public function checkcart($cart_number){
		$regular = "/[鲁]{1}[B,U,b,u]{1}[0-9a-zA-Z]{5}$/u";
		preg_match($regular, $cart_number, $match);
		if (isset($match[0])) {
			return true;
		}else{
			return false;
		}

	}

    /**
     * 获取组织列表数据
     * @return string
     * @author xinyufeng
     */
    public function getGroupListData()
    {
        $return = array('status'=>false, 'msg'=>'获取失败', 'data'=>array());
        $shop_id = input::get('shop_id', 0);
        if(!$shop_id)
        {
            return json_encode($return);
        }

        $groupMdl = app::get('sysmaker')->model('group');
        $filter['shop_id'] = $shop_id;
        $data = $groupMdl->getList('group_id, name', $filter);
        if(empty($data))
        {
            return json_encode($return);
        }

        $return['status'] = true;
        $return['msg'] = '获取成功';
        $return['data'] = $data;
        return json_encode($return);
    }
	
	/**
	* 修改个人资料的
	* author by wanghaichao
	* date 2019/10/25
	*/
	public function upuserinfo(){
		$seller_id=pamAccount::getAccountId();
		$pagedata=app::get('sysmaker')->model('seller')->getRow('*',array('seller_id'=>$seller_id));
		return $this->page('topmaker/passport/upuserinfo.html',$pagedata);
	}
	
	/**
	* 师傅信息保存
	* author by wanghaichao
	* date 2019/10/25
	*/
	public function updateuserinfo(){
		$pagedata = utils::_filter_input(input::get());

		$seller_id=pamAccount::getAccountId();

        try
        {
            // 无需输入确认密码
            $validator = validator::make(
                [
                    'name' => $pagedata['name'],
                ],
                [
                    'name' => 'required',
                ],
                [
                    'name' => '请输姓名!',
                ]
            );

            if ($validator->fails())
            {
                $messages = $validator->messagesInfo();
                foreach( $messages as $error )
                {
                    throw new \LogicException($error[0]);
                }
            }

            //$sellerBool = makerAuth::isExists($pagedata['login_name'], $type='mobile');
            //if($sellerBool)
            //{
            //    throw new \LogicException("该手机号已经被使用");
            //}

			$res=$this->checkcart($pagedata['cart_number']);
			if($res===false){
				throw new \LogicException('请输入青岛地区的车牌号!');
			}
			if(empty($pagedata['front_img'])){
				throw new \LogicException('请上传身份证正面照!');
			}
			if(empty($pagedata['reverse_img'])){
				throw new \LogicException('请上传身份证反面面照!');
			}


            $signUpData = array(
                'name' => $pagedata['name'],
				'front_img'=>$pagedata['front_img'],
				'reverse_img'=>$pagedata['reverse_img'],
				'cart_number'=>strtoupper($pagedata['cart_number']),
            );
			
			$db = app::get('sysshop')->database();
			$db->beginTransaction();

			$p=app::get('sysmaker')->model('seller')->update($signUpData,array('seller_id'=>$seller_id));
			$s=app::get('sysmaker')->model('shop_rel_seller')->update(array('status'=>'pending'),array('seller_id'=>$seller_id));
			if ($s && $p)
			{
				$db->commit();
				$return = array('status'=>true, 'message'=>'提交成功,等待审核', 'data'=>array());
				return json_encode($return);
			}else{
				$db->rollback();
				$return = array('status'=>false, 'message'=>'提交失败,稍后再试', 'data'=>array());
				return json_encode($return);
			}
		} catch(Exception $e)
        {
            $msg = $e->getMessage();
			$return = array('status'=>false, 'message'=>$msg, 'data'=>array());
			return json_encode($return);
        }
	}

}
