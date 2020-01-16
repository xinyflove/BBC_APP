<?php
/**
 * User: xinyufeng
 * Time: 2019-01-14 10:32
 * Desc: 广电优选店铺发票信息
 */
class topshop_ctl_mall_admin_invoice extends topshop_controller {

    /**
     * 发票配置页
     * @return html
     */
    public function index()
    {
        $shopInvoiceMdl = app::get('sysmall')->model('shop_invoice');
        $item = $shopInvoiceMdl->getRow('*', array('shop_id'=>$this->shopId));
        $pagedata = $item;
        $this->contentHeaderTitle = app::get('topshop')->_('店铺发票配置');
        return $this->page('topshop/mall/admin/invoice.html', $pagedata);
    }

    /**
     * 保存数据
     * @return string
     */
    public function save()
    {
        $msg = '';
        $inputs = input::get();
        $objInvoice = kernel::single('sysmall_data_shopinvoice');
        $datas = $inputs;
        $datas['shop_id'] = $this->shopId;

        $sprint_id = sprintf("%06d", $this->shopId);
        $datas['kingdee_custom_code'] = 'KH089.'.$sprint_id;

        $db = app::get('ectools')->database();
        $db->beginTransaction();

        try
        {
            if(empty($inputs['id']))
            {
                $id = $objInvoice->add($datas, $msg);
                if(!$id)
                {
                    $msg = '保存失败';
                    throw new Exception($msg);
                }
                $primary_id = $id;
            }
            else
            {
                $bool = $objInvoice->update($datas, array('id' => $inputs['id']), $msg);
                if(!$bool)
                {
                    $msg = '保存失败';
                    throw new Exception($msg);
                }
                $primary_id = $inputs['id'];
            }
            //非蓝莓购物的店铺生成金蝶供应商
            $tv_shop_id = app::get('sysshop')->getConf('sysshop.tvshopping.shop_id');
            if($this->shopId != $tv_shop_id)
            {
                $customerModel = app::get('sysmall')->model('shop_invoice');
                $customer_data = $customerModel->getRow('*',['id' => $primary_id]);
                $this->generateKingdeeCustom($customer_data);
            }
        }
        catch(Exception $e)
        {
            $error_message = $e->getMessage();
            $db->rollback();
            return $this->splash('error', null, $error_message, true);
        }
        $db->commit();
        return $this->splash('success', null, '保存成功', true);
    }

    /**
     * @param $customer_data
     * @throws Exception
     * 生成金蝶客户
     */
    public function generateKingdeeCustom($customer_data)
    {
        if(empty($customer_data))
        {
            throw new Exception('没有查询到该店铺的开票信息');
        }
        $customer_data['to_org_id'] = 200;//境界公司的组织编码
        $customer_data['throw'] = true;//表示抛出异常
        $kingdeeCustomerModel = kernel::single('sysclearing_kingdeeCustomer');
        $kingdeeCustomerModel->generate($customer_data);
    }
}