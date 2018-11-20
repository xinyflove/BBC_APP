<?php

class topshop_ctl_account_roles extends topshop_controller {

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('角色管理');
        $params['shop_id'] = $this->shopId;
        $data = app::get('topshop')->rpcCall('account.shop.roles.list',$params);
        $pagedata['data'] = $data;
        return $this->page('topshop/account/roles/list.html', $pagedata);
    }

    public function edit()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('添加角色');

        //面包屑
        $this->runtimePath = array(
            ['url'=> url::action('topshop_ctl_index@index'),'title' => app::get('topshop')->_('首页')],
            ['url'=> url::action('topshop_ctl_account_roles@index'),'title' => app::get('topshop')->_('角色管理')],
            ['title' => app::get('topshop')->_('添加角色')],
        );

        if( input::get('role_id') )
        {
            $params['shop_id'] = $this->shopId;
            $params['role_id'] = input::get('role_id');
            $data = app::get('topshop')->rpcCall('account.shop.roles.get',$params);
            if( $data )
            {
                $pagedata['role_id'] = $data['role_id'];
                $pagedata['role_name'] = $data['role_name'];
                $pagedata['workground'] = explode(',',$data['workground']);
				/*add_2018/6/15_by_wanghaichao_start*/
				$pagedata['is_compere']=$data['is_compere'];
				$pagedata['commission_rate']=$data['commission_rate'];
				$pagedata['commission_type']=$data['commission_type'];
				/*add_2018/6/15_by_wanghaichao_end*/
            }
        }
        //新移动端店铺装修
        $topshopNewSetup = app::get('sysconf')->getConf('topshop.firstSetup');
        $openNewdecorate = redis::scene('shopDecorate')->hget('wapdecorate_status','shop_'.$this->shopId);
        $openNewAppdecorate = redis::scene('shopDecorate')->hget('appdecorate_status','shop_'.$this->shopId);

        $permission = config::get('permission');
        $trafficsetting = config::get('stat.disabled');
        unset($permission['common']);
        foreach( $permission as $k=>$row)
        {
            foreach( $row['group'] as $key=>$value )
            {
                $permissionKey[$key] = $k.'.group.'.$key;

                if($trafficsetting && $key=='trafficstat'){
                    unset($permission[$k]['group'][$key]);
                }

                if(!app::get('sysfinance')->is_installed() && $key =='guaranteeMoney'){
                    unset($permission[$k]['group'][$key]);
                }
                if($topshopNewSetup && $key=='mobile_decorate' || ($openNewdecorate == 'open' && $openNewAppdecorate =='open') && $key=='mobile_decorate' ){
                    unset($permission[$k]['group'][$key]);
                }

                if(!$topshopNewSetup && $openNewdecorate !='open' && $key=='wap_decorate'){
                    unset($permission[$k]['group'][$key]);
                }
                if(!$topshopNewSetup && $openNewAppdecorate !='open'&& $key=='app_decorate'){
                    unset($permission[$k]['group'][$key]);
                }
            }
        }

        $pagedata['permissionKey'] = $permissionKey;
        $pagedata['permission'] = $permission;
		/*add_2018/6/15_by_wanghaichao_start*/
		//判断是否有添加主持人权限
		$pagedata['has_compere']=$this->shopInfo['has_compere'];
		/*add_2018/6/15_by_wanghaichao_end*/
        return $this->page('topshop/account/roles/edit.html', $pagedata);
    }

    public function save()
    {
        $roleId = input::get('role_id',false);
		$is_compere=input::get('is_compere');
		/*add_2018/6/15_by_wanghaichao_start*/
		if($is_compere==1){
			$workground=array(
				0 => 'item.group.showItem',
				1 => 'item.group.setStatusItem',
				2 => 'trade.group.showTrade',
				3 => 'aftersales.group.aftersales',
				4 => 'select.group.selectitem',
				5=>'shopinfo.group.sellerDetail',
			);
		}else{
			$workground = input::get('workground', false);
		}
		/*add_2018/6/15_by_wanghaichao_end*/
        if( !$workground )
        {
            $msg = '请选择角色权限';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['shop_id'] = $this->shopId;
            $params['role_name'] = input::get('role_name');
            $params['workground'] = implode(',',$workground);
			/*add_2018/6/15_by_wanghaichao_start*/
			//判断是不是主持人
			$params['is_compere']=$is_compere;
			if($params['is_compere']==1){
				$params['commission_rate']=input::get('commission_rate');
				$params['commission_type']=input::get('commission_type');
			}
			/*add_2018/6/15_by_wanghaichao_end*/
            if( $roleId )
            {
                $params['role_id'] = $roleId;
                $flag = app::get('topshop')->rpcCall('account.shop.roles.update',$params);
                $msg = '角色修改成功';
            }
            else
            {
                $flag = app::get('topshop')->rpcCall('account.shop.roles.add',$params);
                $msg = '角色添加成功';
            }
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $this->sellerlog('添加/修改角色。角色名是 '.$params['role_name']);
        $url = url::action('topshop_ctl_account_roles@index');
        return $this->splash('success',$url,$msg,true);
    }

    public function delete()
    {
        $roleId = input::get('role_id', false);
        if( !$roleId )
        {
            $msg = '删除失败';
            return $this->splash('error','',$msg,true);
        }

        try
        {
            $params['role_id'] = $roleId;
            $params['shop_id'] = $this->shopId;
            app::get('topshop')->rpcCall('account.shop.roles.delete',$params);
        }
        catch( \LogicException $e )
        {
            $msg = $e->getMessage();
            return $this->splash('error','',$msg,true);
        }

        $this->sellerlog('删除角色。角色ID是 '.$roleId);
        $msg = '删除成功';
        $url = url::action('topshop_ctl_account_roles@index');
        return $this->splash('success',$url,$msg,true);
    }
}

