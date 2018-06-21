<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2017/12/27
 * Time: 17:56
 * Desc: 批量跟接口同步供应商信息定时任务文件
 */
class foodmap_tasks_supplierSync extends base_task_abstract implements base_interface_task{
    
    function exec($params = null)
    {
        // TODO: Implement exec() method.
        logger::info('执行跟接口同步供应商信息任务开始！');

        $supplierMdl = app::get('sysshop')->model('supplier');
        $supplierList = $supplierMdl->getList('*',array('api_sync'=>0,'is_audit'=>'PASS'), 0, 100);
        foreach ($supplierList as $s){
            $shop_base = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$s['shop_id'],'fields'=>'api_shop_id'));
            $roleid = $shop_base['api_shop_id'];
            if($roleid){
                $obj_data = array(
                    'supplierid' => $s['supplier_id'],
                    'roleid' => $roleid,
                    'shopName' => $s['supplier_name'],
                    'address' => $s['addr'],
                    'flag' => 1,
                    'longitude' => $s['lon'],
                    'latitude' => $s['lat'],
                );

                $objSupplier = kernel::single('foodmap_data_supplier');
                //$res = $objSupplier->syncApiSupplierInfo($obj_data);
            }
        }
        
        logger::info('执行跟接口同步供应商信息任务结束！');
    }
}
