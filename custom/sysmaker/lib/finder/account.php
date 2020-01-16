<?php
/**
 * Auth: xinyufeng
 * Time: 2018-11-14
 * Desc: 创客finder
 */
class sysmaker_finder_account{

	public $column_edit = '操作';
	public $column_edit_order = 2;
    public $column_edit_width = 200;
	public function column_edit(&$colList, $list)
	{
        foreach($list as $k=>$row)
        {
            $html = $this->_column_editbutton($row);

            if($row['status'] != 'success')
            {
                $bindShopUrl = '?app=sysmaker&ctl=seller&act=checkPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$row['seller_id'];
                $bindShopTar = 'target="dialog::{title:\''.app::get('sysmaker')->_('审核').'\', width:500, height:280}"';
                $html .= '<a href="'.$bindShopUrl.'" '.$bindShopTar.'>'.app::get('sysmaker')->_('审核').'</a>';
            }

            $colList[$k] = $html;
        }
	}

    public function _column_editbutton($row)
    {
        $arr = array(
            'app'=>$_GET['app'],
            'ctl'=>$_GET['ctl'],
            'act'=>$_GET['act'],
            'finder_id'=>$_GET['_finder']['finder_id'],
            'action'=>'detail',
            'finder_name'=>$_GET['_finder']['finder_id'],
        );

        $newu = http_build_query($arr,'','&');
        $arr_link = array(
            'finder'=>array(
                'detail_basic'=>array(
                    'href'=>'javascript:void(0);',
                    'submit'=>'?'.$newu.'&finderview=detail_basic&id='.$row['seller_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],'label'=>$this->detail_basic,
                    'target'=>'tab',
                ),
                'detail_pwd'=>array(
                    'href'=>'javascript:void(0);',
                    'submit'=>'?'.$newu.'&finderview=detail_pwd&id='.$row['seller_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],'label'=>$this->detail_pwd,
                    'target'=>'tab',
                ),
            ),
            'info'=>array(
                'detail_bind_shop'=>array(
                    'href'=>'javascript:void(0);',
                    'submit'=>'?'.$newu.'&finderview=detail_bind_shop&id='.$row['seller_id'].'&_finder[finder_id]='.$_GET['_finder']['finder_id'],'label'=>$this->detail_bind_shop,
                    'target'=>'tab',
                ),
            ),
        );

        //增加编辑菜单权限
        $permObj = kernel::single('desktop_controller');
        if(!$permObj->has_permission('sysMaker_seller_update')){
            unset($arr_link['finder']['detail_basic']);
        }
        if(!$permObj->has_permission('sysMaker_pwd_update')){
            unset($arr_link['finder']['detail_pwd']);
        }

        $pagedata['arr_link'] = $arr_link;
        $pagedata['handle_title'] = app::get('sysmaker')->_('编辑');
        $pagedata['is_active'] = 'true';
        return view::make('sysmaker/seller/detail/actions.html', $pagedata)->render();
    }

    /*查看列-基本信息开始*/
    public $detail_basic = '创客信息';
    public function detail_basic($id)
    {
        $url = '?app=sysmaker&ctl=seller&act=editPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$id;
        $pagedata['url'] = $url;
        $sellerMdl = app::get('sysmaker')->model('seller');
        $info = $sellerMdl->getRow('*', array('seller_id'=>$id));
        if($info)
        {
            $info['id_card_no'] || $info['id_card_no'] = '无';
            if($info['registered'])
            {
                $_addr = explode(':', $info['registered']);
                $info['registered'] = $_addr[0];
            }
            else
            {
                $info['registered'] = '无';
            }
            $info['pid'] || $info['pid'] = 0;
            if($info['pid'])
            {
                $objSeller = kernel::single('sysmaker_data_seller');
                $info['pname'] = $objSeller->getPName($info['pid']);
            }
        }
        $pagedata['data'] = $info;

        return view::make('sysmaker/seller/detail/basic.html',$pagedata)->render();
    }
    public $detail_pwd = '密码修改';
    public function detail_pwd($id)
    {
        $sellerMdl = app::get('sysmaker')->model('seller');
        $info = $sellerMdl->getRow('seller_id,name', array('seller_id'=>$id));
        $pagedata['data'] = $info;

        return view::make('sysmaker/seller/detail/updatepwd.html',$pagedata)->render();
    }
    public $detail_bind_shop = '绑定店铺信息';
    public function detail_bind_shop($id)
    {
        $url = '?app=sysmaker&ctl=seller&act=bindShopPage&finder_id='.$_GET['_finder']['finder_id'].'&p[0]='.$id;
        $pagedata['url'] = $url;
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();
        $shopData = $qb->select('SHOP.shop_id,SHOP.shop_name,SRS.status,SRS.reason')
            ->from('sysmaker_shop_rel_seller', 'SRS')
            ->leftJoin('SRS', 'sysshop_shop', 'SHOP', 'SRS.shop_id=SHOP.shop_id')
            ->where('SRS.seller_id='.$id)
            ->execute()->fetchAll();

        $pagedata['data'] = $shopData;

        return view::make('sysmaker/seller/detail/shop.html',$pagedata)->render();
    }
    /*查看列-基本信息结束*/
}
