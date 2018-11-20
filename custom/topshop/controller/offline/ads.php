<?php
/**
 * Created by PhpStorm.
 * @desc: third-party shop withdraw function
 * @author: wudi tvplaza
 * @date: 2017-12-25 13:54
 */
class topshop_ctl_offline_ads extends topshop_controller{
    public $limit = 20;

    /**
     * @name expense
     * @desc 宣传基金使用
     * @return html
     * @author: wudi tvplaza
     * @date:
     */
    public function expense(){
        $this->contentHeaderTitle = app::get('topshop')->_('宣传基金使用记录');

        $filter['shop_id'] = $this->shopId;

        $postSend = utils::_filter_input(input::get());
        $filter['shop_id']=$this->shopId;
        $filter['agent_sign']=1;

        if($postSend['supplier_name'])
        {
            $filter['supplier_name|has']=$postSend['supplier_name'];
            $filter['company_name|has']=$postSend['supplier_name'];
        }
        $filter['is_audit']='PASS';

        $page = $postSend['page'] ? $postSend['page'] : 1;
        $offset=$this->limit*($page-1);
        $count=app::get('sysshop')->model('supplier')->count($filter);
        $result=app::get('sysshop')->model('supplier')->getList('*',$filter,$offset,$this->limit,'supplier_id');
        foreach($result as $key => $value){
            $totalAdsFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as supplier_total_add_fee,sum(payment) as payment,sum(service_charge) as service_charge,sum(supplier_fee) as supplier_fee',array('supplier_id'=>$value['supplier_id'],'settle_status'=>'SETTLE_FINISH'));
            $result[$key]['settle']=$totalAdsFee;
            $tmp=db::connection()->createQueryBuilder()->select('supplier_id,sum(expense_amount) as expense_amount')
                ->from('sysclearing_offline_ads_expense')
                ->where("supplier_id = ".$value['supplier_id'])
                ->andWhere('status = 1')
                ->execute()->fetch();
            $result[$key]['ads_expense']=$tmp;
        }

        $pagedata['data']= $result;
        $total = $count;

        //处理翻页数据
        $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_offline_ads@expense',$postSend);
        $pagedata['pagers'] = $this->__pagers($total,$page,$limit,$link);
        $pagedata['total']=$total;
        return $this->page('topshop/offline/ads/expense.html',$pagedata);
    }

    /**
     * @name expenseDetail
     * @desc supplier expense detail
     * @return html
     * @author: wudi tvplaza
     * @date: 2018-03-13 10:34
     */
    public function expenseDetail(){
        $postSend=input::get();
        if(empty($postSend['supplier_id'])){
            return redirect::action('topshop_ctl_offline_ads@expense');
        }
        //supplier summary info
        $supplierInfo=app::get('sysshop')->model('supplier')->getRow('*',array('supplier_id'=>$postSend['supplier_id'],'is_audit'=>'PASS'));
        $totalAdsFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as supplier_total_add_fee',array('supplier_id'=>$postSend['supplier_id'],'settle_status'=>'SETTLE_FINISH'));
        //$curDate=strtotime(date('Y-m-d',time()));
        $expenses=app::get('sysclearing')->model('offline_ads_expense')->getRow('sum(expense_amount) as total_expense_amount',array('supplier_id'=>$postSend['supplier_id'],'status'=>1));
        $pagedata=array(
            'supplier'=>$supplierInfo,
            'total_adds_fee'=>$totalAdsFee,
            'expense'=>$expenses
        );
        $sqlWhere[]="A.shop_id=".$this->shopId;
        $sqlWhere[]="A.status = 1";
        $page = $postSend['page'] ? $postSend['page'] : 1;
        $offset=$this->limit*($page-1);
        $sqlWhere[]="A.supplier_id = ".$postSend['supplier_id'];
        $sqlWhere[]="B.is_audit = 'PASS'";

        $sqlWhereStr=implode(' AND ',$sqlWhere);
        //实时数据
        $count=db::connection()->createQueryBuilder()
            ->select('count(*) as total')
            ->from('sysclearing_offline_ads_expense','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->execute()->fetch();
        //实时数据
        $qb=db::connection()->createQueryBuilder();
        $result=$qb->select('A.*,B.supplier_name,B.company_name')
            ->from('sysclearing_offline_ads_expense','A')
            ->leftJoin('A','sysshop_supplier','B','A.supplier_id=B.supplier_id')
            ->where($sqlWhereStr)
            ->setFirstResult($offset)
            ->setMaxResults($this->limit)
            ->execute()->fetchAll();
        $pagedata['data']= $result;

        $total = $count['total'];

        //处理翻页数据
        $limit = $this->limit;
        $postSend['page'] = time();
        $link = url::action('topshop_ctl_offline_ads@expenseDetail',$postSend);
        $pagedata['pagers'] = $this->__pagers($total,$page,$limit,$link);
        $pagedata['total']=$total;

        $this->contentHeaderTitle = app::get('topshop')->_('宣传基金使用明细-'.$supplierInfo['supplier_name']."(".$supplierInfo['company_name'].")");

        return $this->page('topshop/offline/ads/expense_detail.html',$pagedata);
    }

    /**
     * @name editExpense
     * @desc
     * @return html
     * @author: wudi tvplaza
     * @date:
     */
    public function editExpense(){
        $postSend=input::get();
        $supplierInfo=app::get('sysshop')->model('supplier')->getList('*',array('shop_id'=>$this->shopId,'agent_sign'=>1,'is_audit'=>'PASS'));
        $pagedata['supplier']=$supplierInfo;

        if(!empty($postSend['ads_expense_id'])){
            $expenseRecord=app::get('sysclearing')->model('offline_ads_expense')->getRow('*',array('ads_expense_id'=>$postSend['ads_expense_id']));
            $expenseRecord['expense_amount']=number_format(round($expenseRecord['expense_amount'],2),2);
            $pagedata['expense']=$expenseRecord;
        }

        $pagedata['supplier_id']=$postSend['supplier_id'];
        return $this->page('topshop/offline/ads/expense_edit.html',$pagedata);
    }

    /**
     * @name saveExpense
     * @desc
     * @return string
     * @author: wudi tvplaza
     * @date:
     */
    public function saveExpense(){
        $data=input::get();
        $chk=$this->_checkSaveData($data['expense']);
        if($chk['status']=='error'){
            return $this->splash('error','',$chk['msg']);
        }else{
            unset($data['expense']['aviliable_fee']);
            $data['expense']['shop_id']=$this->shopId;
            $data['expense']['account_time']=time();
            $data['expense']['status']=1;

            $expenseMdl=app::get('sysclearing')->model('offline_ads_expense');
            try{
                $result=$expenseMdl->save($data['expense']);
                $url=url::action('topshop_ctl_offline_ads@expenseDetail',array('supplier_id'=>$data['expense']['supplier_id']));
                return $this->splash('success',$url,'保存成功');
            }catch(Exception $e){
                return $this->splash('error','','保存失败');
            }
        }

    }

    /**
     * @name _checkSaveData
     * @desc 提现申请数据校验
     * @param $data
     * @return array
     * @author: wudi tvplaza
     * @date: 2018-03-06 17:59
     */
    private function _checkSaveData($data){
        $return=array(
            'status'=>'succ',
            'msg'=>'数据校验无误',
        );
        if(empty($data['ads_expense_id'])){
            $expenses=app::get('sysclearing')->model('offline_ads_expense')->getRow('sum(expense_amount) as total_expense_amount',array('supplier_id'=>$data['supplier_id'],'status'=>1));
            $totalAdsFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as supplier_total_ads_fee',array('supplier_id'=>$data['supplier_id'],'settle_status'=>'SETTLE_FINISH'));
            if($data['expense_amount'] > ($totalAdsFee['supplier_total_ads_fee']-$expenses['total_expense_amount'])){
                $return['status']='error';
                $return['msg']='超出可用余额';
                return $return;
            }
        }else{
            $expenses=app::get('sysclearing')->model('offline_ads_expense')->getRow('sum(expense_amount) as total_expense_amount',array('supplier_id'=>$data['supplier_id'],'status'=>1));
            $totalAdsFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as supplier_total_add_fee',array('supplier_id'=>$data['supplier_id'],'settle_status'=>'SETTLE_FINISH'));
            $expenseRecord=app::get('sysclearing')->model('offline_ads_expense')->getRow('*',array('ads_expense_id'=>$data['ads_expense_id'],'status'=>1));
            if($data['expense_amount'] > ($totalAdsFee['supplier_total_add_fee']-$expenses['total_expense_amount']+$expenseRecord['expense_amount'])){
                $return['status']='error';
                $return['msg']='超出可用余额';
                return $return;
            }

        }
        if($data['expense_amount'] <= 0){
            $return['status']='error';
            $return['msg']='金额必须大于0';
            return $return;
        }

        if(empty($data['supplier_id']) && empty($data['ads_expense_id'])){
            $return['status']='error';
            $return['msg']='供应商和线下店信息丢失，请刷新后台重试';
        }

        return $return;
    }

    public function getStoreListBySupplierId(){
        $supplierId=input::get('supplier_id');
        $storeList=app::get('syssupplier')->model('agent_shop')->getList('*',array('supplier_id'=>$supplierId));
        return $this->splash('succ','',$storeList);

    }

    public function getAvailableAdsFee(){
        $supplier_id = input::get('supplier_id');
        $totalFee=app::get('sysclearing')->model('offline_payment_detail')->getRow('sum(shop_fee) as shop_fee',array('supplier_id'=>$supplier_id,'settle_status'=>'SETTLE_FINISH'));
        $usedAdsFee=app::get('sysclearing')->model('offline_ads_expense')->getRow('sum(expense_amount) as expense_fee',array('supplier_id'=>$supplier_id,'status'=>1));
        $availableFee=(float)$totalFee['shop_fee']-(float)$usedAdsFee['expense_fee'];
        $data['total']=number_format(round((float)$totalFee['shop_fee'],2),2);
        $data['use']=number_format(round((float)$usedAdsFee['expense_fee'],2),2);
        $data['ava']=number_format(round((float)$availableFee,2),2);
        return $this->splash('succ','',$data);
    }

    /**
     * @name delWithdraw
     * @desc 提现记录删除提示页面
     * @return mixed
     * @author: wudi tvplaza
     * @date:2018-03-02 10:48
     */
    public function delExpense(){

        $pagedata = input::get();
        return view::make('topshop/offline/ads/del.html', $pagedata);
    }

    /**
     * @name dodel
     * @desc 删除提现记录逻辑
     * @return string
     * @author: wudi tvplaza
     * @date:2018-03-02 10:49
     */
    public function dodel(){
        $input=input::get();
        if(empty($input['ads_expense_id'])){
            $this->splash('error','','取消失败：投放记录编号丢失');
        }else{
            $data['ads_expense_id']=$input['ads_expense_id'];
            $data['status']=0;
            try{
                $result=app::get('sysclearing')->model('offline_ads_expense')->save($data);
                return $this->splash('succ',url::action('topshop_ctl_offline_ads@expenseDetail',array('supplier_id'=>$input['supplier_id'])),'删除成功.');
            }catch(Exception $e){
                return $this->splash('error','','删除失败：'.$e->getMessage());
            }
        }
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


    /**
     * @name __pager
     * @desc 宣传基金分页
     * @param $postFilter
     * @param $page
     * @param $count
     * @return array
     * @author: wudi tvplaza
     * @date:
     */
    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_offline_ads@expense',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
            'useSessionFilter'=>1,
        );
        return $pagers;

    }


    /**
     * @name export
     * @desc 导出
     * @return string
     * @author: wudi tvplaza
     * @date: 2018-01-02 9:34
     */
    public function export(){
        $params=input::get();
        //filter process
        if($params['type']=='detail'){
            $mdl=app::get('sysclearing')->model('settlement_supplier_detail');
        }elseif($params['type']=='daily'){
            $mdl=app::get('sysclearing')->model('settlement_supplier_daily');
        }else{
            echo "<script>alert('导出失败');window.close()</script>";
        }
        $date=explode('-',$params['time_range']);
        $filter['account_time|bthan']=strtotime($date[0]);
        $filter['account_time|sthan']=strtotime($date[1]);
        $data=$mdl->getList('*',$filter);
        $filePath=kernel::single('sysclearing_data_export')->exec($params);
        if(file_exists(PUBLIC_DIR.'/'.$filePath)){
            return $filePath;
        }else{
            echo "<script>alert('导出失败1');window.close()</script>";
        }
    }

}