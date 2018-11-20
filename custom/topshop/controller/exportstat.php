<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class topshop_ctl_exportstat extends topshop_controller{

    public function view()
    {
        //导出方式 直接导出还是通过队列导出
        $pagedata['check_policy'] = 'download';

        $filetype = array(
            'csv'=>'.csv',
            'xls'=>'.xls',
        );

        $pagedata['model'] = input::get('model');
        $pagedata['app'] = input::get('app');
        $pagedata['orderBy'] = input::get('orderBy');
        $supportType = input::get('supportType');
        //支持导出类型
        if( $supportType && $filetype[$supportType] )
        {
            $pagedata['export_type'] = array($supportType=>$filetype[$supportType]);
        }
        else
        {
            $pagedata['export_type'] = $filetype;
        }

        return view::make('topshop/exportstat/export.html', $pagedata);
    }

    public function export()
    {
        //导出
        if( input::get('filter') )
        {
            $filter = json_decode(input::get('filter'),true,512,JSON_BIGINT_AS_STRING);

        }

        $params['shop_id']=$this->shopId;

        if(!$filter['createtime'] || !in_array($filter['createtime'],array('yesterday','beforday','week','month'))){
            $filter['createtime']='yesterday';
        }
        if($filter['itemtime']){
            $times = array_filter(explode('-',$filter['itemtime']));
            $params['createtime_start'] = strtotime($times['0']);
            $params['createtime_end'] = strtotime($times['1'])+86400;
        }
        /*else
        {
            $params=$this->_checkParams($filter['createtime']);
        }*/
        //批量处理其他参数，关键词存在时仅仅处理status和tid

        //开始时间点
        if( $params['createtime_start'] )
        {
            $params['createtime|bthan'] = $params['createtime_start'];
            unset($params['creattimed_start']);
        }

        //结束时间点
        if( $params['createtime_end'] )
        {
            $params['createtime|lthan'] = $params['createtime_end'];
            unset($params['createtime_end']);
        }
        $permission = [
            'systrade' =>['trade','order'],
            'sysclearing' =>['settlement','settlement_detail'],
            'sysstat'=>['trade_statics','item_statics','desktop_stat_supplier']
        ];

        $app = input::get('app',false);
        $model = input::get('model',false);
        if( input::get('name') && $app && $model && $permission[$app] && in_array($model,$permission[$app]) )
        {
            $this->sellerlog('导出操作。对应导出model '.$model);
            $model = $app.'_mdl_'.$model;
            $filter['shop_id'] = shopAuth::getShopId();
            $params['shop_id']=$this->shopId;

            try {
                kernel::single('importexport_export')->fileDownload(input::get('filetype'), $model, input::get('name'), $params);
            }
            catch (Exception $e)
            {
                return response::make('导出参数错误', 503);
            }
        }
        else
        {
            return response::make('导出参数错误', 503);
        }
    }

    /**
     * 校验参数
     * @param $filter
     * @return mixed
     */
    private function _checkParams($sendtype)
    {
        $filter['createtime_end']=time();
        if($sendtype=='yesterday'){
            $starttime=strtotime('yesterday');
        }elseif ($sendtype=='beforday'){
            $starttime=strtotime('-2 days');
        }elseif ($sendtype=='week'){
            $starttime=strtotime('last week');
        }elseif ($sendtype=='month'){
            $starttime=strtotime('last month');
        }else{
            $starttime=strtotime('yesterday');
        }
        $filter['createtime_start']=$starttime;
        return $filter;
    }

}

