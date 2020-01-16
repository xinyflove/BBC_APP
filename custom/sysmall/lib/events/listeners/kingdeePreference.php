<?php
/**
 * Class sysmall_events_listeners_kingdeePreference
 * 优选商城拉取商品后的处理
 */

class sysmall_events_listeners_kingdeePreference {

    public function handle($item_id)
    {
        return true;
        try
        {
            //$item_id为新生成的商品id
            $itemModel = app::get('sysitem')->model('item');
            $item_info = $itemModel->getRow('*',['item_id' => $item_id]);
            $init_item_id = $item_info['init_item_id'];

            $customerModel = app::get('sysmall')->model('shop_invoice');
            //是否是蓝莓购物
            $tv_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');

            $kingdeeSupplierModel = kernel::single('sysclearing_kingdeeSupplier');

            //判断如果是蓝莓购物拉取其他店铺的商品,则需要将该频道转化为蓝莓购物下的供应商
            //todo 1号位置
            if($item_info['shop_id'] == $tv_shop_id)
            {
                $pullSupplierModel = app::get('sysshop')->model('kingdee_supplier');
                $pull_supplier_info = $pullSupplierModel->getRow('*', ['shop_id'=>$item_info['shop_id'],'init_shop_id' =>$item_info['init_shop_id']]);
                if(empty($pull_supplier_info))
                {
                    $init_customer_data = $customerModel->getRow('*',['shop_id'=>$item_info['init_shop_id']]);
                    if(empty($init_customer_data))
                    {
                        throw new Exception('被拉取店铺的发票信息不能为空');
                    }

                    $supplier_insert_data = [
                        'login_account' =>  'B2B'.$item_info['init_shop_id'],
                        'login_password'=>  'asdf1234',
                        'psw_confirm'   =>  'asdf1234',
                        'supplier_name' =>  $init_customer_data['invoice_name'],
                        'company_name'  =>  $init_customer_data['invoice_name'],
                        'deposit_bank'  =>  $init_customer_data['deposit_bank'],
                        'card_number'   =>  $init_customer_data['card_number'],
                        'registration_number'=> $init_customer_data['registration_number'],
                        'mobile'        =>  $init_customer_data['contact_way'],
                        'lat'           =>  1,
                        'lon'           =>  1,
                        'modified_time' => time(),
                        'addr'          => $init_customer_data['addr'],
                        'sh_phone'      => $init_customer_data['contact_way'],
                        'sh_address'    => $init_customer_data['addr'],
                        'shop_id'       => $item_info['shop_id'],
                        'is_audit'      => 'PASS',
                    ];
                    $supplierId = app::get('topshop')->rpcCall('supplier.shop.add', $supplier_insert_data);
                    //创建金蝶供应商
                    $sprint_id = sprintf("%06d", $supplierId);
                    $kingdee_supplier_id = $sup_update['kingdee_supplier_id'] = 'GYS089.'.$sprint_id;

                    $sup_gen_data = [
                        'to_org_id' => 400,
                        'kingdee_supplier_id' =>  $kingdee_supplier_id,
                        'company_name'        =>  $init_customer_data['invoice_name'],
                        'company_addr'        =>  $init_customer_data['addr'],
                        'registration_number' =>  $init_customer_data['registration_number'],
                        'deposit_bank'        =>  $init_customer_data['deposit_bank'],
                        'card_number'         =>  $init_customer_data['card_number'],
                        'supplier_id'         =>  $supplierId,
                        'throw'               =>  true,//表示接口抛出异常
                    ];
                    $sup_gen_res = $kingdeeSupplierModel->generate($sup_gen_data);
                    if(!$sup_gen_res)
                    {
                        throw new Exception('金蝶B2B供应商创建失败');
                    }

                    $supplierModel = app::get('sysshop')->model('supplier');
                    $supplierModel->update(['kingdee_supplier_id' => $kingdee_supplier_id],['supplier_id' => $supplierId]);

                    $pull_supplier_insert_data = [
                        'shop_id' => $item_info['shop_id'],
                        'init_shop_id' => $item_info['init_shop_id'],
                        'supplier_id' => $supplierId,
                    ];
                    $pullSupplierModel->insert($pull_supplier_insert_data);
                }
            }
            //此逻辑一定要放在1号位置之后，如果蓝莓拉取别人的商品只是后续不执行 但是1号要执行，不管金蝶的开关打没打开
            $filter['shop_id'] = $item_info['init_shop_id'];
            $shop_info = app::get('systrade')->rpcCall('shop.get', $filter);
            if($shop_info['push_documents'] === 'off')
            {
                return true;
            }

            //todo 第二部分，分配供应商
            //todo 分配
            $supplierModel = app::get('sysshop')->model('supplier');
            $supplier_info = $supplierModel->getRow('*',['supplier_id'=>$item_info['supplier_id']]);

            $org_ids = 1;//境界公司在金蝶中对应的组织主键

            //todo 查询分配结果(因无法保证整体流程一次性顺利执行，如果第一次在分配之后出现问题，当问题处理完毕后，再次出发操作后会导致提示供应商重复分配，导致流程终止，金蝶中供应商重复分配也属于错误，但在本系统不能认定为错误）
            $supplier_params['FUseOrgId'] = $org_ids;
            $supplier_params['FNumber'] = $supplier_info['kingdee_supplier_id'];
            $supplier_query_res = $kingdeeSupplierModel->queryInfo($supplier_params);
            if(empty($supplier_query_res))
            {
                $sup_allot_data['pk_ids']  = $supplier_info['kingdee_supplier_isn'];//原始供应商对应的金蝶主键
                $sup_allot_data['org_ids']  = $org_ids;//境界公司在金蝶中对应的组织主键
                $sup_allot_data['throw']  = true;
                $kingdeeSupplierModel->allot($sup_allot_data);
            }

            //todo 第三部分，分配物料
            $skuModel = app::get('sysitem')->model('sku');
            $sku_list = $skuModel->getList('*',['item_id'=>$item_id]);
            $init_sku_list = $skuModel->getList('*',['item_id'=>$init_item_id]);
            $pk_id_array = array_column($init_sku_list,'fmaterial_id');
            $pk_ids = implode(',',$pk_id_array);

            $need_allot_sku = array_bind_key($sku_list,'material_code');

            $kingdeeMaterielModel = kernel::single('sysclearing_kingdeemateriel');

            foreach($need_allot_sku as $sku)
            {
                if(!$sku['material_code'])
                {
                   throw new Exception('缺少物料编码信息');
                }
            }
            //todo 第四部分 查询是否分配成功（条件：使用组织和物料编码）
            //因为金蝶中分配后的信息不包括新物料的信息，如果重复分配也没有单独的错误码区分，
            //因此只能通过查询分配的目标组织下有没有这个编码的商品，得到相应的主键信息
            $material_code_array = array_column($need_allot_sku, 'material_code');
            //金蝶中物料编码的查询条件必须是单引号
            foreach($material_code_array as &$code)
            {
                $code = "'".$code."'";
            }
            $params['FUseOrgId'] = $org_ids;
            $params['FNumber'] = $material_code_array;
            $query_res = $kingdeeMaterielModel->queryInfo($params);
            if(empty($query_res))
            {
                $data['pk_ids']  = $pk_ids;//原始物料对应的金蝶主键
                $data['org_ids']  = $org_ids;//境界公司在金蝶中对应的组织主键
                $data['throw']  = true;
                $kingdeeMaterielModel->allot($data);
            }
            $return_params = [];
            foreach($query_res as $res)
            {
                $sku_id = $need_allot_sku[$res[1]]['sku_id'];
                $skuModel->update(['fmaterial_id' => $res[0]],['sku_id' => $sku_id]);
                $return_params[] = ['fmaterial_id' => $res[0]];
            }
            //todo 第五部分 修改新分配的商品的税率
            if(!empty($return_params))
            {
                foreach($return_params as $param)
                {
                    $edit_data['fmaterial_id'] = $param['fmaterial_id'];
                    $edit_data['edit_info']    = ['incoming_type' => $item_info['incoming_type']];
                    kernel::single('sysclearing_kingdeemateriel')->editInfo($edit_data);
                }
            }
        }
        catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

}

