<?php
/**
 * User: xinyufeng
 * Time: 2019-01-14 10:32
 * Desc: 广电优选店铺发票数据操作类
 */
class sysmall_data_shopinvoice {
    
    public $shopInvoiceModel = null;
    
    public function __construct()
    {
        $this->shopInvoiceModel = app::get('sysmall')->model('shop_invoice');
    }

    /**
     * 添加数据
     * @param $data
     * @param $msg
     * @return bool
     */
    public function add($data, &$msg)
    {
        if(!$this->_checkSaveData($data, $msg)) return false;

        $id = $this->shopInvoiceModel->insert($data);
        if(!$id)
        {
            $msg = app::get('sysmall')->_('数据添加失败');
            return false;
        }

        $msg = app::get('sysmall')->_('数据添加成功');

        return $id;
    }

    /**
     * 修改数据
     * @param $data
     * @param $filter
     * @param $msg
     * @return bool
     */
    public function update($data, $filter, &$msg)
    {
        if(!$this->_checkSaveData($data, $msg)) return false;
        if(empty($filter))
        {
            $msg = '没有限制字段！';
            return false;
        }

        // 移除过滤条件字段
        foreach ($filter as $k => $f)
        {
            if(!empty($data[$k]))
            {
                unset($data[$k]);
            }
        }

        //更新属性
        if( !$this->shopInvoiceModel->update($data, $filter) )
        {
            $msg = app::get('sysmall')->_('数据更新失败');
            return false;
        }
        $msg = app::get('sysmall')->_('数据更新成功');

        return true;
    }

    /**
     * 验证数据
     * @param $data
     * @param $msg
     * @return bool
     */
    protected function _checkSaveData($data, &$msg)
    {
        $columns = $this->shopInvoiceModel->schema['columns'];

        foreach ($columns as $k => $col)
        {
            if($col['autoincrement']) continue;
            if($col['required'] && empty($data[$k]))
            {
                $msg = "字段{$k}不能为空";
                return false;
            }
        }

        return true;
    }

    public function hasInvoice($filter)
    {
        if(empty($filter['shop_id']) || empty($filter['id']))
        {
            return false;
        }

        $res = $this->shopInvoiceModel->getRow('id', $filter);
        if(!empty($res)) return false;

        return true;
    }
}