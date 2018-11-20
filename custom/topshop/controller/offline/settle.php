<?php
/**
 * Created by PhpStorm.
 * @desc:
 * @author: admin
 * @date: 2018-03-12 19:24
 */

class topshop_ctl_offline_settle extends topshop_controller{
    public $limit = 10;

    /**
     * 结算明细
     * @return
     */
    public function index()
    {
        $this->contentHeaderTitle = app::get('topshop')->_('广告收入');

        $filter['shop_id'] = $this->shopId;

        $postSend = utils::_filter_input(input::get());
        $sqlWhere[]='A.shop_id = '.$this->shopId;
        if($postSend['supplier_name'])
        {
            $sqlWhere[]='(B.supplier_name like "%'.$postSend['supplier_name'].'%" or B.company_name like "%'.$postSend['supplier_name'].'%")';
        }
        $sqlWhereStr=implode(' AND ',$sqlWhere);
        $page = $postSend['page'] ? $postSend['page'] : 1;
        $offset=$this->limit*($page-1);
        //实时数据
        $count=db::connection()->createQueryBuilder()
            ->select('count(distinct A.supplier_id) as total')
            ->from('sysclearing_offline_payment_detail','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->execute()->fetch();
        //实时数据
        $qb=db::connection()->createQueryBuilder();
        $result=$qb->select('A.supplier_id,B.supplier_name,B.company_name,sum(A.payment) as payment,sum(A.service_charge) as service_charge,sum(A.supplier_fee) as supplier_fee,sum(A.shop_fee) as shop_fee')
            ->from('sysclearing_offline_payment_detail','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->setFirstResult($offset)
            ->setMaxResults($this->limit)
            ->groupBy("A.supplier_id")
            ->execute()->fetchAll();
        $pagedata['data']= $result;

        $total = $count['total'];

        //处理翻页数据
        $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_offline_settle@index',$postSend);
        $pagedata['pagers'] = $this->__pagers($total,$page,$limit,$link);
        $pagedata['total']=$total;
        return $this->page('topshop/offline/settle/list.html', $pagedata);
    }

    /**
     * @name __pagers
     * @desc 分页
     * @param $count
     * @param $page
     * @param $limit
     * @param $link
     * @return array
     * @author: wudi tvplaza
     * @date: 2018-03-12 21:51
     */
    private function __pagers($count,$page,$limit,$link)
    {
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

    public function detail(){
        $this->contentHeaderTitle = app::get('topshop')->_('详情');
        $pagedata=array();
        return $this->page('topshop/offline/settle/detail.html',$pagedata);
    }

    public function dailysupplier(){
        $this->contentHeaderTitle = app::get('topshop')->_('供应商日报');
        $pagedata=array();
        return $this->page('topshop/offline/settle/daily_supplier.html',$pagedata);
    }

    public function dailystore(){
        $this->contentHeaderTitle = app::get('topshop')->_('线下店日报');
        $pagedata=array();
        return $this->page('topshop/offline/settle/daily_store.html',$pagedata);
    }

    public function offlinedetail(){

        $this->contentHeaderTitle = app::get('topshop')->_('广告收入明细');

        $filter['shop_id'] = $this->shopId;

        $postSend = utils::_filter_input(input::get());

        if(empty($postSend['supplier_id'])){
            return redirect::action('topshop_ctl_offline_settle@index');
        }


        $sqlWhere[]='A.shop_id = '.$this->shopId;
        if($postSend['supplier_name'])
        {
            $sqlWhere[]='(B.supplier_name like "%'.$postSend['supplier_name'].'%" or B.company_name like "%'.$postSend['supplier_name'].'%")';
        }
        $sqlWhereStr=implode(' AND ',$sqlWhere);
        $page = $postSend['page'] ? $postSend['page'] : 1;
        $offset=$this->limit*($page-1);
        //实时数据
        $count=db::connection()->createQueryBuilder()
            ->select('count(distinct A.supplier_id) as total')
            ->from('sysclearing_offline_ads_expense','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->execute()->fetch();
        //实时数据
        $qb=db::connection()->createQueryBuilder();
        $result=$qb->select('A.supplier_id,B.supplier_name,B.company_name,sum(A.expense_amount) as expense_amount')
            ->from('sysclearing_offline_ads_expense','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->setFirstResult($offset)
            ->setMaxResults($this->limit)
            ->groupBy("A.supplier_id")
            ->execute()->fetchAll();
        foreach($result as $key => $value){
            $totalAdsFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as supplier_total_add_fee',array('supplier_id'=>$value['supplier_id']));
            $result[$key]['total_ads_fee']=$totalAdsFee['supplier_total_add_fee'];

        }
        $pagedata['data']= $result;

        $total = $count['total'];

        //处理翻页数据
        $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_offline_settle@offlinedetail',$postSend);
        $pagedata['pagers'] = $this->__pagers($total,$page,$limit,$link);
        $pagedata['total']=$total;
        return $this->page('topshop/offline/settle/detail_offline.html',$pagedata);
    }
}