<?php
/**
 * Created by PhpStorm.
 * User: xinyufeng
 * Date: 2018/10/19
 * Time: 10:25
 * Desc: 定时从易米接口下载应用话单
 */
class syslmgw_tasks_downloadBills extends base_task_abstract implements base_interface_task{

    public function exec($params=null)
    {
        $objCreateBill = kernel::single('syslmgw_emic_createBill');
        $objCreateBill -> downloadBills();
    }
}