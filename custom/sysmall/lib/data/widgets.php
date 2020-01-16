<?php
/**
 * User: zhangshu
 * Date: 2018/11/8
 * Desc: 广电优选组件
 */

class sysmall_data_widgets
{
    /**
     * 获取页面组件
     *
     * @param $tmpl
     * @return array
     */
    public function getWidgets($tmpl)
    {
        if (empty($tmpl))
        {
            throw new LogicException('需指定页面');
        }
        $objMdlWidgetsInstance = app::get('sysmall')->model('widgets_instance');
        $orderBy = 'order_sort asc';
        $winfo = $objMdlWidgetsInstance->getList('*', ['tmpl' => $tmpl], 0,-1, $orderBy);

        $restult = [];
        foreach ($winfo as $widget)
        {
            if ($widget['widget'] == 'new_products') {
                $restult[] = $this->getWidgetsInfo($widget['widgets_id']);
            } else if ($widget['widget'] == 'channel_floor') {
                $restult[] = $this->getWidgetsInfo($widget['widgets_id'], 5);
            } else {
                $restult[] = $this->getWidgetsInfo($widget['widgets_id'], 8);
            }
        }

        if (!empty($restult))
        {
            foreach ($restult as $k => $widget)
            {
                if ($widget['widget'] == 'new_products')
                {
                    $temp_item_info = [];
                    $num = 0;
                    foreach ($widget['params']['item_info'] as $key => $item)
                    {
                        $temp_item_info[$num][] = $item;
                        if (($key + 1) % 5 == 0) $num++;
                    }
                    $restult[$k]['params']['item_info'] = $temp_item_info;
                }

                if ($widget['widget'] == 'top_slider')
                {
                    $row_item_info = [];
                    $column_item_info = [];
                    $num = 0;
                    foreach ($widget['params']['item_info'] as $key => $item)
                    {
                        if($num<2){
                            $row_item_info[] = $item;
                        }elseif ($num>=2&&$num<4){
                            $column_item_info[] = $item;
                        }
                        $num++;
                    }
                    //行
                    $restult[$k]['params']['item_info']['row'] = $row_item_info;
                    //列
                    $restult[$k]['params']['item_info']['column'] = $column_item_info;
                }
            }
        }
        return $restult;
    }

    /**
     * 获取组件详细信息
     *
     * @param $widgetId
     * @param $limit
     * @return mixed
     */
    public function getWidgetsInfo($widgetId, $itemLimit = 10)
    {
        $objMdlWidgetsInstance = app::get('sysmall')->model('widgets_instance');
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();

        $winfo = $objMdlWidgetsInstance->getRow('*', ['widgets_id' => $widgetId]);
        $winfo['template_path'] = 'sysmall/widgets/' .$winfo['widget'].'/_default.html';

        if (!empty($winfo['params']))
        {
            $winfo['params']['item_info'] = [];

            if (!empty($winfo['params']['shop_id']))
            {
                $winfo['params']['shop_link'] = url::action('topshop_ctl_mall_shop@index', ['shop_id' => $winfo['params']['shop_id']]);
            }

            $fields = 'm_item.*, item.item_id, item.title, shop.shop_name, item.image_default_id, (store.store-store.freez) AS real_store, FORMAT(price, 2) as price, FORMAT(supply_price, 2) as supply_price, FORMAT((price - supply_price)/price*100,1) AS profit';
            $qb->select($fields)
                ->from('sysmall_item', 'm_item')
                ->leftJoin('m_item', 'sysitem_item', 'item', 'm_item.item_id=item.item_id')
                ->leftJoin('item', 'sysshop_shop', 'shop', 'item.shop_id=shop.shop_id')
                ->leftJoin('item', 'sysitem_item_store', 'store', 'item.item_id=store.item_id');
            if ($winfo['params']['select_item_type'] == 2 && $winfo['params']['item'])
            {
                $mall_item_ids = implode(',', $winfo['params']['item']);
                $qb->where("m_item.mall_item_id in ({$mall_item_ids})");

                if (!empty($winfo['params']['shop_id']))
                {
                    $qb->andWhere("item.shop_id = " . $winfo['params']['shop_id']);
                }
                $winfo['params']['item_info'] = $qb->execute()->fetchAll();
            }
            else if ($winfo['params']['select_item_type'] == 1)
            {
                $qb->where("m_item.deleted = '0'")
                    ->andWhere("m_item.status = 'onsale'")
                    ->andWhere("m_item.sale_type = '0'");
                
                if (!empty($winfo['params']['shop_id']))
                {
                    $qb->andWhere("item.shop_id = " . $winfo['params']['shop_id']);
                }
                $winfo['params']['item_info'] = $qb->setFirstResult(0)
                    ->setMaxResults($itemLimit)
                    ->orderBy('m_item.modified_time', 'desc')
                    ->execute()->fetchAll();
            }
        }
        return $winfo;
    }
}
