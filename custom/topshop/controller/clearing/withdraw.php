<?php
/**
 * Created by PhpStorm.
 * @desc: third-party shop withdraw function
 * @author: wudi tvplaza
 * @date: 2017-12-25 13:54
 */
class topshop_ctl_clearing_withdraw extends topshop_controller{
    public $limit = 20;//TODO::testing
    /**
     * @name index
     * @desc 提现申请列表
     * @author: wudi tvplaza
     * @date:2017-12-25 13:55
     */
    public function index(){
        //订单屏显状态
        $withDrawStatusList = array(
            '0'=>'全部',
            '1'=>'待审核',
            '2'=>'审核通过',
            '3'=>'审核未通过',
            '4'=>'成功',
            '5'=>'失败',
            '6'=>'取消',
        );
        $pagedata['status'] = $withDrawStatusList;

        $status = (int)input::get('status');
        $status = in_array($status, array_keys($withDrawStatusList)) ? $status : 0;
        $pagedata['filter']['status'] = $status;

        if(input::get('useSessionFilter'))
        {
            $filter = $this->__getWithdrawSearchFilter();
            $pagedata['filter'] = $filter;
        }
        $pagedata['useSessionFilter'] = input::get('useSessionFilter');

        $this->contentHeaderTitle=app::get('sysclearing')->_('提现');
        return $this->page('topshop/clearing/withdraw/list.html',$pagedata);
    }

    /**
     * @name search
     * @desc ajax获取提现记录
     * @return mixed
     * @author: wudi tvplaza
     * @date: 2017-12-25 16:42
     */
    public function search()
    {
        $pagedata['status'] = array(
            '0'=>'全部',
            '1'=>'待审核',
            '2'=>'审核通过',
            '3'=>'审核未通过',
            '4'=>'成功',
            '5'=>'失败',
            '6'=>'取消',
        );

        $postFilter = input::get();
        if($postFilter['useSessionFilter'])
        {
            $filter = $this->__getWithdrawSearchFilter();
            if($postFilter['status']) $filter['status']=$postFilter['status'];
            if($postFilter['pages']) $filter['pages']=$postFilter['pages'];

        } else {
            $this->__saveWithdrawSearchFilter($postFilter);
            $filter=$postFilter;
        }
        $filter = $this->_checkParams($filter);
        $filter['shop_id']=$this->shopId;
        $limit = $this->limit;
        $page = $filter['pages'] ? $filter['pages'] : 1;
        $mdl=app::get('sysclearing')->model('withdraw');

        $filter['shop_id'] = $this->shopId;
        $withdrawList=$mdl->getList('*',$filter,intval($page-1)*$limit,intval($limit),'create_time desc');
        $count = $mdl->count($filter);

        $pagedata['withdrawList'] =$withdrawList;
        $pagedata['count'] =$count;

        $pagedata['pagers'] = $this->__pager($postFilter,$page,$count);
        $this->contentHeaderTitle = app::get('topshop')->_('订单查询');

        return view::make('topshop/clearing/withdraw/item.html', $pagedata);
    }


    /**
     * @name detail
     * @desc 提现申请详情
     * @return html
     * @author: wudi tvplaza
     * @date: 2017-12-25 15:13
     */
    public function detail(){
        $data=input::get();
        if(!empty($data['id'])){
            $mdlWithdraw=app::get('sysclearing')->model('withdraw');
            $withdrawInfo=$mdlWithdraw->getRow('*',$data);
            $pagedata['withdraw']=$withdrawInfo;
            $timeRange=date('Y/m/d',$withdrawInfo['start_time']).'-'.date('Y/m/d',$withdrawInfo['end_time']);
        }else{
            $pagedata=array();
        }
        return $this->page('topshop/clearing/withdraw/detail.html',$pagedata);
    }

    /**
     * @name editWithdraw
     * @desc 编辑提现申请
     * @return html
     * @author: wudi tvplaza
     * @date: 2017-12-25 15:13
     */
    public function editWithdraw(){
        $pagedata=array();
        return $this->page('topshop/clearing/withdraw/edit.html',$pagedata);
    }

    /**
     * @name saveWithdraw
     * @desc 保存提现申请
     * @return bool
     * @author: wudi tvplaza
     * @date: 2017-12-25 15:13
     */
    public function saveWithdraw(){
        $data=input::get();
        $chk=$this->_checkSaveData($data);
        if($chk['status']=='error'){
            return $this->splash('error','','数据不合法');
        }else{
            $date=explode('-',$data['withdraw']['time_range']);
            $data['withdraw']['start_time']=strtotime($date[0]);
            $data['withdraw']['end_time']=strtotime($date[1]);
            $data['withdraw']['create_time']=time();
            $data['withdraw']['modified_time']=time();
            $data['withdraw']['shop_id']=$this->shopId;
            $data['withdraw']['status']=1;
            unset($data['withdraw']['time_range']);
        }

        try{
            $db = app::get('sysclearing')->database();
            $transaction_status = $db->beginTransaction();
            $mdlWithdraw=app::get('sysclearing')->model('withdraw');
            $result=$mdlWithdraw->save($data['withdraw']);
            if($result!==false){
                $dailyFitler['shop_id']=$this->shopId;
                $dailyFitler['account_time|bthan']=$data['start_time'];
                $dailyFitler['account_time|sthan']=$data['end_time'];
                $mdlDaily=app::get('sysclearing')->model('settlement_supplier_daily');
                $reDaily=$mdlDaily->update(array('withdraw_status'=>1),$dailyFitler);
                if($reDaily!==false){
                    $db->commit($transaction_status);
                    $url=url::action('topshop_ctl_clearing_withdraw@index');
                    return $this->splash('success',$url,'保存成功');
                }else{
                    $db->rollback();
                    return $this->splash('error','','保存结算关联表失败：');
                }
            }else{
                $db->rollback();
            }
        }catch(Exception $e){
            $db->rollback();
            return $this->splash('error','','保存失败：'.$e->getMessage());
        }
    }

    /**
     * @name _checkSaveData
     * @desc 校验申请金额
     * @param $data
     * @return array
     * @author: wudi tvplaza
     * @date: 2018-01-07 14:19
     */
    private function _checkSaveData($data){
        $return=array(
            'status'=>'succ',
            'msg'=>'数据校验无误',
        );
        if($data['money']==0){
            $return['status']='failed';
            $return['msg']='提现金额为0';

        }
        return $return;
    }

    /**
     * @name delWithdraw
     * @desc 取消提现申请
     * @return bool
     * @author: wudi tvplaza
     * @date: 2017-12-25 15:14
     */
    public function delWithdraw(){
        $pagedata['id'] = input::get('id');
        return view::make('topshop/clearing/withdraw/del.html', $pagedata);
    }

    /**
     * @name dodel
     * @desc 取消提现申请
     * @return string
     * @author: wudi tvplaza
     * @date: 2018-01-07 14:19
     */
    public function dodel(){
        $input=input::get();
        if(empty($input['id'])){
            $this->splash('error','','取消失败：申请记录编号丢失');
        }else{

            $filter['id']=$input['id'];
            $data['status']='6';
            $data['cancel_reason']=$input['cancel_reason'];
            try{
                $db = app::get('sysclearing')->database();
                $transaction_status = $db->beginTransaction();
                $mdlWithdraw=app::get('sysclearing')->model('withdraw');
                $result=$mdlWithdraw->update($data,$filter);
                if($result!==false){
                    $withdrawInfo=$mdlWithdraw->getRow('*',$filter);
                    $dailyFitler['account_time|bthan']=$withdrawInfo['start_time'];
                    $dailyFitler['account_time|sthan']=$withdrawInfo['end_time'];
                    $mdlDaily=app::get('sysclearing')->model('settlement_supplier_daily');
                    $reDaily=$mdlDaily->update(array('withdraw_status'=>6),$dailyFitler);
                    if($reDaily!==false){
                        $db->commit($transaction_status);
                        $url=url::action('topshop_ctl_clearing_withdraw@index');
                        return $this->splash('success',$url,'取消成功');
                    }else{
                        $db->rollback();
                        return $this->splash('error','','取消失败：保存结算关联表失败.');
                    }
                }else{
                    $db->rollback();
                }
            }catch(Exception $e){
                $db->rollback();
                return $this->splash('error','','取消失败：'.$e->getMessage());
            }
        }
    }

    /**
     * @name __saveWithdrawSearchFilter
     * @desc 保存搜索条件
     * @param $filter
     * @author: wudi tvplaza
     * @date: 2018-01-07 14:20
     */
    private function __saveWithdrawSearchFilter($filter)
    {
        $_SESSION['topshop_withdraw_search_filter'] = $filter;
    }

    /**
     * @name __getWithdrawSearchFilter
     * @desc 获取保存的搜索条件
     * @param $filter
     * @return mixed
     * @author: wudi tvplaza
     * @date: 2018-01-07 14:20
     */
    private function __getWithdrawSearchFilter($filter)
    {
        return $_SESSION['topshop_withdraw_search_filter'];
    }

    /**
     * @name _checkParams
     * @desc 格式化传参
     * @param $filter
     * @return mixed
     * @author: wudi tvplaza
     * @date: 2018-01-07 14:20
     */
    private function _checkParams($filter)
    {
        foreach($filter as $key=>$value)
        {
            if(empty($value)){
                unset($filter[$key]);
            }
            if($key == 'create_time')
            {
                if(empty($value)) unset($filter[$key]);
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $filter['create_time_start'] = strtotime($times['0']);
                    $filter['create_time_end'] = strtotime($times['1'])+86400;
                    unset($filter['create_time']);
                }
            }

            if($key == 'start_time'){
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $filter['start_time_start'] = strtotime($times['0']);
                    $filter['start_time_end'] = strtotime($times['1'])+86400;
                    unset($filter['start_time']);
                }
            }

            if($key == 'end_time'){
                $times = array_filter(explode('-',$value));
                if($times)
                {
                    $filter['end_time_start'] = strtotime($times['0']);
                    $filter['end_time_end'] = strtotime($times['1'])+86400;
                    unset($filter['end_time']);
                }
            }

        }
        return $filter;
    }

    /**
     * @name __pager
     * @desc
     * @param $postFilter
     * @param $page
     * @param $count
     * @return array
     * @author: wudi tvplaza
     * @date:9/23/2017 4:26 PM
     */
    private function __pager($postFilter,$page,$count)
    {
        $postFilter['pages'] = time();
        $total = ceil($count/$this->limit);
        $pagers = array(
            'link'=>url::action('topshop_ctl_clearing_withdraw@search',$postFilter),
            'current'=>$page,
            'use_app' => 'topshop',
            'total'=>$total,
            'token'=>time(),
            'useSessionFilter'=>1,
        );
        return $pagers;

    }

    /**
     * @name calcWithdraw
     * @desc 根据选择的时间周期计算提现数据
     * @return string
     * @author: wudi tvplaza
     * @date: 2017-12-27 15:55
     */
    public function calcWithdraw(){
        $time_range=input::get('time_range');
        $date=explode('-',$time_range);
        $filter['start_time']=strtotime($date[0]);
        $filter['end_time']=strtotime($date[1]);
        $filter['shop_id']=$this->shopId;
        $withdrawAuth=app::get('sysshop')->model('shop')->getRow('withdraw',array('shop_id'=>$this->shopId));
        if($withdrawAuth['withdraw']!=='on') return $this->splash('error','','未开通提现功能');
        $re=$this->_checkTimeRange($filter);
        if($re['status']=='failed'){
            return $this->splash('error','',$re['msg']);
        }
        $mdl=app::get('sysclearing')->model('settlement_supplier_daily');
        $params=array(
            'shop_id'=>$this->shopId,
            'account_time|bthan'=>$filter['start_time'],
            'account_time|sthan'=>$filter['end_time'],
            'settlement_status|nequal'=>1,
            'withdraw_status|noequal'=>4,
        );
        $fields='shop_id,account_type,accounting_type,SUM(payment) as payment,SUM(service_charge) as service_charge,SUM(incoming) as incoming,SUM(shop_fee) as shop_fee,SUM(platform_service_fee) as platform_service_fee';
        $info=$mdl->getRow($fields,$params);

        //if($info['account_type']=='off' && $info['accounting_type']=='thirdparty'){
        if(!empty($info['shop_fee'])){
            $info['time_range']=$time_range;
            return $this->splash('success','',$info);
        }else{
            return $this->splash('error','','获取提现信息失败：您申请的周期内可提现金额为0');
        }
    }

    /**
     * @name _checkTimeRange
     * @desc 校验提交的时间周日
     * @param $filter
     * @author: wudi tvplaza
     * @date: 2017-12-27 15:54
     */
    private function _checkTimeRange($filter){
        //TODO::
        $params['shop_id']=$filter['shop_id'];
        $params['status']=array(1,2,4);
        $mdl=app::get('sysclearing')->model('withdraw');
        $pre=$mdl->getList('*',$params);

        $res=array('status'=>'succ');
        if(!empty($pre)){
            foreach($pre as $k => $v){
                if($filter['start_time'] >= $v['start_time'] && $filter['start_time'] <= $v['end_time']){
                    $res=array('status'=>'failed','msg'=>'所选的提现开始时间已经被申请。');
                }elseif($filter['end_time'] >= $v['start_time'] && $filter['end_time'] <= $v['end_time']){
                    $res=array('status'=>'failed','msg'=>'所选的提现结束时间已经被申请。');
                }
            }
        }

        return $res;
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

    /**
     * @name saveComment
     * @desc 保存提现备注
     * @return array
     * @author: wudi tvplaza
     * @date: 2017-12-28 15:29
     */
    public function saveComment(){
        $input=input::get();
        if($input['id']){
            $filter['id']=$input['id'];
            $data['comment']=$input['comment'];
            $mdl=app::get('sysclearing')->model('withdraw');
            $result=$mdl->update($data,$filter);
            if($result!==false){
                return $this->splash('success','','备注保存成功');
            }else{
                return $this->splash('error','','保存备注失败');
            }
        }else{
            return $this->splash('error','','提现编号丢失');
        }
    }
}