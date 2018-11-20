<?php
class topwap_ctl_member extends topwap_controller{

    public $limit = 10;
    public function __construct(&$app)
    {
        parent::__construct();
        kernel::single('base_session')->start();
        if(!$this->action) $this->action = 'index';
        $this->action_view = $this->action.".html";
        // 检测是否登录
        if( !userAuth::check() )
        {
            if( request::ajax() )
            {
                $url = url::action('topwap_ctl_passport@goLogin');
                return $this->splash('error', $url, app::get('topwap')->_('请登录'), true);
            }
            redirect::action('topwap_ctl_passport@goLogin')->send();exit;
        }

        $this->passport = kernel::single('topwap_passport');
    }
    public $verifyArray = array('mobile','email');

    public function index()
    {
        if(input::get('shop_id')){
            $_SESSION['shop_id'] = input::get('shop_id');
        }
        $this->setLayoutFlag('member');
        $userId = userAuth::id();
        $pagedata['account'] = userAuth::getLoginName();

        $userInfo = userAuth::getUserInfo();
        $pagedata['userInfo'] = $userInfo;
        $pagedata['nologin'] = userAuth::check() ? "true" : "false";

        //获取订单各种状态的数量
        $pagedata['nupay'] = app::get('topwap')->rpcCall('trade.count',array('user_id'=>$userId,'status'=>'WAIT_BUYER_PAY'));
        $pagedata['nudelivery'] = app::get('topwap')->rpcCall('trade.count',array('user_id'=>$userId,'status'=>'WAIT_SELLER_SEND_GOODS'));
        $pagedata['nuconfirm'] = app::get('topwap')->rpcCall('trade.count',array('user_id'=>$userId,'status'=>'WAIT_BUYER_CONFIRM_GOODS'));
        $cancelData = app::get('topwap')->rpcCall('trade.cancel.list.get',['user_id'=>$userId,'fields'=>'tid']);
        $pagedata['canceled'] = $cancelData['total'];
        $pagedata['nurate'] = app::get('topwap')->rpcCall('trade.notrate.count',array('user_id'=>$userId));

        $pagedata['hongbaoCount'] = app::get('topwap')->rpcCall('user.hongbao.count',['user_id'=>$userId]);

        //优惠劵数量
        $pagedata['coupon'] = app::get('topwap')->rpcCall('user.coupon.list', ['user_id'=>$userId, 'is_valid'=>'1', 'page_size'=>1]);

        //购物券数量
        $pagedata['voucher'] = app::get('topwap')->rpcCall('user.voucher.list.get', ['user_id'=>$userId, 'is_valid'=>'1', 'page_size'=>1]);

        //会员签到
        $pagedata['isCheckin'] = app::get('sysconf')->getConf('open.checkin');
        $pagedata['isPoint'] = app::get('sysconf')->getConf('open.point');
        $pagedata['checkinPointNum'] = app::get('sysconf')->getConf('checkinPoint.num');
        $params =array(
            'user_id' => $userId,
            'checkin_date' => date('Y-m-d'),
			'shop_id'=>$_SESSION['shop_id'],
        );
        $pagedata['checkin_status'] = app::get('topwap')->rpcCall('user.get.checkin.info',$params);

        /*add_2017/9/29_by_xinyufeng_start*/
        $voucher_count=app::get('systrade')->model('voucher')->getList('voucher_id',array('user_id'=>$userId));
        $voucher_count2=app::get('systrade')->model('voucher_history')->getList('voucher_id',array('user_id'=>$userId));
		$voucher_count2=count($voucher_count2);
        $pagedata['voucher_count']=count($voucher_count);
		$pagedata['voucher_count']=$pagedata['voucher_count']+$voucher_count2;
		$bank=app::get('sysbankmember')->model('account')->getList('user_id',array('user_id'=>$userId,'deleted|noequal'=>1));
		$bank_count=count($bank);
        $agent_voucher_count = app::get('systrade')->model('agent_vocher')->count(['user_id'=>$userId]);

        //代金券+购物券数量+优惠劵数+银行卡的数量+线下券的数量
        $pagedata['count_total']=$pagedata['voucher_count']+$pagedata['voucher']['pagers']['total']+$pagedata['coupon']['count']+$bank_count+$agent_voucher_count;
        /*add_2017/9/29_by_xinyufeng_end*/

        /*add_2017/9/29_by_xinyufeng_start*/
        $pagedata['cartNum'] = $this->getCartCount();
        /*add_2017/9/29_by_xinyufeng_end*/

		/*add_2018/1/12_by_wanghaichao_start*/
		$thirdparty_app=$this->from_thirdparty_app();
		if($thirdparty_app){
			$_SESSION['thirdparty_app']='1';
		}
		/*add_2018/1/12_by_wanghaichao_end*/
		$pagedata['isCheckin']=$this->getShopSetting();

        //取出广告相关设置和信息
        $auth_shop_id          = app::get('syspromotion')->getConf('advert.auth.shop.id');
        $pagedata['float_banner']          = app::get('syspromotion')->getConf('advert.float.window.banner');
        $pagedata['float_banner_url']      = app::get('syspromotion')->getConf('advert.float.window.banner.url');
        $advertModel = app::get('sysshop')->model('shop_advert');
        $advert_info = $advertModel->getRow('*',['shop_id' => $auth_shop_id]);
        $pagedata['auth_shop_id'] = $auth_shop_id;
        if($advert_info['is_open'] == 'on')
        {
            $pagedata['float_banner'] = $advert_info['float_banner'];
            $pagedata['float_banner_url'] = $advert_info['float_banner_url'];
        }
        // return $this->page('topwap/member/index_01.html', $pagedata);
        return $this->page('topwap/member/member_index.html', $pagedata);
    }


	/* action_name (par1, par2, par3)
	* 获取店铺的签到配置
	* author by wanghaichao
	* date 2018/8/27
	*/
	public function getShopSetting(){
		$shop_id=$_SESSION['shop_id'];
		if(empty($shop_id)){
			return false;
		}
		$shopPointSetting=app::get('sysuser')->rpcCall('shop.point.setting.get',array('shop_id'=>$shop_id));
		if($shopPointSetting['open']=='on'){
			return true;
		}else{
			return false;
		}

	}



    public function security()
    {
        $pagedata['title'] = app::get('topwap')->_('安全中心');
        // 查看当前会员是否设置了手机
        $userInfo = userAuth::getUserInfo();
        $pagedata['user'] = $userInfo;
        return $this->page('topwap/member/safe_center.html', $pagedata);
    }
    public function setting()
    {
        $pagedata['thirdparty_app'] = $this->from_thirdparty_app();
        return $this->page('topwap/member/setting.html', $pagedata);
    }

    public function detail()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['userInfo'] = $userInfo;
        ob_flush();
        flush();
        return $this->page('topwap/member/detail.html',$pagedata);
    }

    public function goSetName()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['name'] = $userInfo['name'];
        return $this->page('topwap/member/set/name.html',$pagedata);
    }

    public function goSetUsername()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['username'] = $userInfo['username'];
        return $this->page('topwap/member/set/username.html',$pagedata);
    }

    public function goSetSex()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['sex'] = $userInfo['sex'];
        return $this->page('topwap/member/set/sex.html',$pagedata);
    }

    public function goSetLoginAccount()
    {
        $userInfo = userAuth::getUserInfo();
        return $this->page('topwap/member/set/login_account.html',$pagedata);
    }
    /*add_20180309_by_fanglongji_start*/
    public function goSetIdentityCardNumber()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['identity_card_number'] = $userInfo['identity_card_number'];
        return $this->page('topwap/member/set/identity.html',$pagedata);
    }

    public function saveIdentityCardNumber()
    {
        $identity_card_number = input::get('identity_card_number');

        $userId = userAuth::id();

        $url = url::action('topwap_ctl_member@detail');
        try
        {
            if(empty($identity_card_number))
            {
                $identity_card_number = 0;
            }
            else
            {
                $this->__checkIdentityCardNumber($identity_card_number);
            }
            app::get('sysuser')->model('user')->update(array('identity_card_number'=>$identity_card_number),array('user_id'=>$userId));
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        return $this->splash('success',$url,app::get('topwap')->_('修改成功'),true);
    }
    /*add_20180309_by_fanglongji_end*/

    public function goSetBirthday()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['name'] = $userInfo['name'];
        return $this->page('topwap/member/set/birthday.html',$pagedata);
    }

    /*add_start_gurundong_2017/11/2*/
    public function goSetHeadImg()
    {
        $userInfo = userAuth::getUserInfo();
        $pagedata['headimg_url'] = $userInfo['headimg_url'];
        return $this->page('topwap/member/set/headimg.html',$pagedata);
    }

    public function saveHeadImg(){
        $userInfo = userAuth::getUserInfo();

        //设置文件保存目录
        $uploaddir = "upfiles/";
        //设置允许上传文件的类型
        $type=array("jpg","gif","bmp","jpeg","png");
        //获取文件类型
        $file_type = $_FILES["myfile"]["name"]['extension'];
        //最大文件宽度
        $pic_width_max = 400;
        //最大文件高度
        $pic_height_max = 400;
        //文件保存路径
        $uploadDir = 'images'.DIRECTORY_SEPARATOR.'headimg'.DIRECTORY_SEPARATOR;
        $dir = $uploadDir;
        file_exists($dir) || (mkdir($dir,0777,true) && chmod($dir,0777));
        $fileName = microtime().uniqid().'.'.pathinfo($_FILES["myfile"]["name"])['extension'];
        $uploaddir = $dir.$fileName;


        if($_FILES["myfile"]['size'])
        {
            if($file_type == "image/pjpeg"||$file_type == "image/jpg"|$file_type == "image/jpeg")
            {
                $im = imagecreatefromjpeg($_FILES['myfile']['tmp_name']);
            }
            elseif($file_type == "image/x-png")
            {
                $im = imagecreatefrompng($_FILES['myfile']['tmp_name']);
            }
            elseif($file_type == "image/gif")
            {
                $im = imagecreatefromgif($_FILES['myfile']['tmp_name']);
            }
            else//默认jpg
            {
                $im = imagecreatefromjpeg($_FILES['myfile']['tmp_name']);
            }
            if($im)
            {
                $this->ResizeImage($im,$pic_width_max,$pic_height_max,$uploaddir);
                ImageDestroy ($im);
            }
        }

        $ret['file'] = DIRECTORY_SEPARATOR.$uploadDir.$fileName;
        $params = [
                'user_id'=>$userInfo['userId'],
                'headimg_url'=>$ret['file']
            ];
        try{
            app::get('topwap')->rpccall('user.setuser.headimg',$params);
        }catch (LogicException $e){
            $msg = $e->getMessage();
            exit($msg);
        }
        $ret['msg'] = '保存成功';
        $ret['url'] = url('topwap_ctl_member@goSetHeadImg');
        exit(json_encode($ret));

    }

    private function ResizeImage($uploadfile,$maxwidth,$maxheight,$name)
    {
     //取得当前图片大小
     $width = imagesx($uploadfile);
     $height = imagesy($uploadfile);

     //生成缩略图的大小
     if(($width > $maxwidth) || ($height > $maxheight))
     {
      $widthratio = $maxwidth/$width;
      $heightratio = $maxheight/$height;

      if($widthratio < $heightratio)
      {
       $ratio = $widthratio;
      }
      else
      {
        $ratio = $heightratio;
      }

      $newwidth = $width * $ratio;
      $newheight = $height * $ratio;
     /* $i=0.5;
      $newwidth = $width * $i;
      $newheight = $height * $i;*/
      if(function_exists("imagecopyresampled"))
      {
       $uploaddir_resize = imagecreatetruecolor($newwidth, $newheight);
       imagecopyresampled($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
      }
      else
      {
        $uploaddir_resize = imagecreate($newwidth, $newheight);
        imagecopyresized($uploaddir_resize, $uploadfile, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
      }

        ImageJpeg ($uploaddir_resize,$name);
        ImageDestroy ($uploaddir_resize);
    }
    else
    {
        ImageJpeg ($uploadfile,$name);
    }
    }
    /*add_end_gurundong_2017/11/2*/

    public function saveUserInfo()
    {
        $userId = userAuth::id();
        $postdata = utils::_filter_input(input::get('user'));
        if(!$this->_validator($postdata,$msg))
        {
            return $this->splash('error',null,$msg);
        }

        try
        {
            $data = array('user_id'=>$userId,'data'=>json_encode($postdata));
            app::get('topwap')->rpcCall('user.basics.update',$data);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg);
        }

        $url = url::action('topwap_ctl_member@detail');
        $msg = app::get('topwap')->_('修改成功');
        return $this->splash('success',$url,$msg,true);
    }

    private function _validator($postdata,&$msg)
    {
        try
        {
            switch(key($postdata)) {
            case "username":
                $rule = ['username'=>'required|max:20'];
                $message = ['username' => '用户姓名不能为空!|用户姓名过长,请输入20个英文或10个汉字!'];
                break;
            case "name":
                $rule = ['name'=>'required|min:4|max:20'];
                $message = ['name' =>'用户昵称不能为空!|用户昵称最少4个字符!|用户昵最多20个字符!'];
                break;
            }
            $validator = validator::make($postdata,$rule,$message);
            $validator->newFails();
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return false;
        }
        return ture;
    }

    /**
     * 信任登陆用户名密码设置
     */
    public function saveLoginAccount()
    {
        $username = input::get('username');

        $userId = userAuth::id();
        //会员信息
        $userInfo = userAuth::getUserInfo();
        if($userInfo['login_account']){
            $msg = app::get('topwap')->_('您已有用户名，不能再设置');
            return $this->splash('error',null,$msg,true);
        }



        $url = url::action('topwap_ctl_member@detail');
        try
        {
            $this->__checkAccount($username);
            $data = array(
                'user_name'   => $username,
                'user_id' => $userId,
            );
            app::get('topwap')->rpcCall('user.account.update',$data,'buyer');
        }
        catch(\Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }


        return $this->splash('success',$url,app::get('topwap')->_('修改成功'),true);
    }

    /**
     *  会员签到
     */
    public function checkin(){

        $url = url::action('topwap_ctl_member@index');
        try
        {
            $params = array(
				'shop_id'=>$_SESSION['shop_id'],
                'user_id' => userAuth::id(),
            );
            $point=app::get('topwap')->rpcCall('user.add.checkin.log',$params);
        }
        catch(Exception $e)
        {
            $msg = $e->getMessage();
            return $this->splash('error',null,$msg,true);
        }

        return $this->splash('success',$url,"签到成功，成功领取".$point."积分",true);
    }

    private function __checkAccount($username)
    {

        $validator = validator::make(
            ['username' => $username],
            ['username' => 'loginaccount|required|min:4|max:20'],
            ['username' => '用户名不能为纯数字或邮箱地址!|用户名不能为空!|用户名最少4个字符！|用户名最长为20个字符!']
        );
        if ($validator->fails())
        {
            $messages = $validator->messagesInfo();
            foreach( $messages as $error )
            {
                throw new LogicException( $error[0] );
            }
        }
        return true;
    }

    private function __checkIdentityCardNumber($identity_card_number)
    {

        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $identity_card_number))
        {
            throw new LogicException(app::get('systrade')->_('身份证号格式不正确，请重新输入'));
        }
        return true;
    }
	/*add_2017/9/20_by_wanghaichao_start*/
	public function ticket(){
        $userId = userAuth::id();
		$voucher_count=app::get('systrade')->model('voucher')->getList('voucher_id',array('user_id'=>$userId,'status'=>'WAIT_WRITE_OFF','end_time|bthan'=>time()));
        $voucher_count2=app::get('systrade')->model('voucher_history')->getList('voucher_id',array('user_id'=>$userId,'status'=>'WAIT_WRITE_OFF','end_time|bthan'=>time()));
		$voucher_count2=count($voucher_count2);
        $pagedata['voucher_count']=count($voucher_count);
		$pagedata['voucher_count']=$pagedata['voucher_count']+$voucher_count2;
        $pagedata['coupon'] = app::get('topm')->rpcCall('user.coupon.list',['user_id'=>$userId,'page_size'=>1]);
		$bank=app::get('sysbankmember')->model('account')->getList('user_id',array('user_id'=>$userId,'deleted|noequal'=>1));
		$pagedata['bank_count']=count($bank);
		/*add_start_gurundong_20171023*/
        $filter = [
            'start_time|lthan'=>time(),
            'end_time|than'=>time(),
            'status' => '0',
            'user_id' => (string)userAuth::id(),
            'deleted' => '0',
            'order_by'=>'create_time desc'
        ];
        $giftList = app::get("topwap")->rpccall("activity.vote.gift.gain.list",$filter);
        $pagedata['vote_count'] = $giftList['count'];
		/*add_end_gurundong_20171023*/
        $pagedata['agent_voucher_count'] = app::get('systrade')->model('agent_vocher')->count(['user_id'=>$userId]);
		return $this->viewPage('topwap/member/ticket.html', $pagedata);
	}
	/*add_2017/9/20_by_wanghaichao_end*/

}
