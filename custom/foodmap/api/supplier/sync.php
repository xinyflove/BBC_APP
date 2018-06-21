<?php
/**
 * 跟接口同步供应商信息
 * supplier.sync
 */
class foodmap_api_supplier_sync{

    /**
     * 接口作用说明
     */
    public $apiDescription = '跟接口同步供应商信息';

    /**
     * 定义应用级参数，参数的数据类型，参数是否必填，参数的描述
     * 用于在调用接口前，根据定义的参数，过滤必填参数是否已经参入
     */
    public function getParams()
    {
        $return['params'] = array(
            'supplier_id' => ['type'=>'int','valid'=>'required','description'=>'供应商id','example'=>'2','default'=>''],
        );

        return $return;
    }

    public function doSync($params)
    {
        $supplierInfo = app::get('sysshop')->model('supplier')
            ->getRow('*', array('supplier_id'=>$params['supplier_id'],'is_audit'=>'PASS'));

        $shop_base = app::get('topwap')->rpcCall('shop.get',array('shop_id'=>$supplierInfo['shop_id'],'fields'=>'api_shop_id'));
        $roleid = $shop_base['api_shop_id'];
        if(empty($roleid)){
            return false;
        }

        $objSupplier = kernel::single('foodmap_data_supplier');

        $obj_data = array(
            'supplierid' => $supplierInfo['supplier_id'],
            'roleid' => $roleid,
            'shopName' => $supplierInfo['supplier_name'],
            'address' => $supplierInfo['addr'],
            'flag' => 1,
            'longitude' => $supplierInfo['lon'],
            'latitude' => $supplierInfo['lat'],
        );
        if($supplierInfo['api_supplier_id']){
            $obj_data['id'] = $supplierInfo['api_supplier_id'];
        }
        
        $res = $objSupplier->syncApiSupplierInfo($obj_data);
        return $res;
    }
}
