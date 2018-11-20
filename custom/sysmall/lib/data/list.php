<?php
/**
 * Auth: xinyufeng
 * Date: 2018-11-02
 * Desc: 处理广电优选商品列表数据
 */
class sysmall_data_list {

    public $orderBy = array(
        'created_time_asc' => '.created_time ASC',    // 创建时间升序
        'created_time_desc' => 'created_time DESC',  // 创建时间降序
        'modified_time_asc' => 'modified_time ASC',  // 修改时间升序
        'modified_time_desc' => 'modified_time DESC',    // 修改时间降序
        'price_asc' => 'supply_price ASC',  // 销售价升序
        'price_desc' => 'supply_price DESC',  // 销售价降序
        'profit_asc' => 'profit ASC', // 毛利率升序
        'profit_desc' => 'profit DESC', // 毛利率降序
        'paid_quantity_asc' => 'all_paid_quantity ASC', // 销量升序
        'paid_quantity_desc' => 'all_paid_quantity DESC', // 销量降序
    );

    /**
     * 获取广电优选商品数量
     * @param $filter
     * @return mixed
     */
    public function getMallItemCount($params)
    {
        $filter = $params['filter'];

        $where = $this->__filterWhere($filter);
        
        $sql = 'SELECT COUNT(m_item.item_id) AS count';
        $sql .= ' FROM sysmall_item m_item';
        $sql .= ' LEFT JOIN sysitem_item AS item ON item.item_id = m_item.item_id';
        $sql .= ' WHERE m_item.status = "onsale" AND m_item.deleted = "0"';
        $sql .= $where;
        
        $db = app::get('base')->database();
        $count = $db->fetchColumn($sql);

        return $count;
    }

    /**
     * 获取广电优选商品列表
     * @param $params
     * @return mixed
     */
    public function getMallItemList($params)
    {
        $filter = $params['filter'];

        //$orderBy = $this->orderBy['profit_desc'] . ', ' . $this->orderBy['paid_quantity_desc'];
		$orderBy=$this->orderBy['modified_time_asc'];
        if($this->orderBy[$params['orderBy']])
        {
            $orderBy = $this->orderBy[$params['orderBy']];
        }

        $offset = $params['offset'] ? $params['offset'] : 0;
        $limit = $params['limit'] ? $params['limit'] : 10;

        $where = $this->__filterWhere($filter);

        $baseFields = ' SELECT CASE WHEN item.init_item_id > 0 THEN item.init_item_id ELSE item.item_id END item_id';
        $fields = $baseFields . ', item.title, item.image_default_id, item.price, item.supply_price,item.created_time,item.shop_id,m_item.modified_time, count.paid_quantity, shop.shop_name, ROUND((item.price-item.supply_price)/item.price*100,1) AS profit, (store.store-store.freez) AS sell_out';
        $sql = 'SELECT sub.*, sum(sub.paid_quantity) AS all_paid_quantity';
        $sql .= ' FROM ( ' . $fields;
        $sql .= ' FROM sysitem_item item';
        $sql .= ' LEFT JOIN sysitem_item_count AS count ON count.item_id = item.item_id';
        $sql .= ' LEFT JOIN sysshop_shop AS shop ON shop.shop_id = item.shop_id';
        $sql .= ' LEFT JOIN sysitem_item_store AS store ON store.item_id = item.item_id';
        $sql .= ' LEFT JOIN sysmall_item AS m_item ON m_item.item_id = item.item_id';
        $sql .= ' WHERE (item.item_id IN ( SELECT item_id FROM sysmall_item WHERE status = "onsale" AND deleted = "0" )';
        $sql .= ' OR item.init_item_id IN ( SELECT item_id FROM sysmall_item WHERE status = "onsale" AND deleted = "0" ))';
        $sql .= $where;
        $sql .= ' ORDER BY item.item_id ASC';
        $sql .= ' ) AS sub';
        $sql .= ' GROUP BY sub.item_id';
        $sql .= ' ORDER BY '.$orderBy;
        $sql .= " LIMIT $offset, $limit";

        $db = app::get('base')->database();
        $list = $db->fetchAll($sql);

        return $list;
    }

    /**
     * 获取广电优选商品列表 备份
     * @param $fields
     * @param $filter
     * @param $offset
     * @param $limit
     * @param $orderBy
     * @return mixed
     */
    public function getMallItemListBak($fields, $filter, $offset, $limit, $orderBy)
    {
        $orderByArr = $this->orderBy['created_time_desc'];
        if($orderBy && $this->orderBy[$orderBy])
        {
            $orderByArr = $this->orderBy[$orderBy];
        }

        $fields = $fields . ', FORMAT((item.price-item.supply_price)/item.price*100,1) AS profit';
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();
        $qb->select($fields)
            ->from('sysmall_item', 'm_item')
            ->leftJoin('m_item', 'sysitem_item', 'item', 'm_item.item_id=item.item_id')
            ->leftJoin('item', 'sysitem_item_count', 'count', 'item.item_id=count.item_id')
            ->leftJoin('item', 'sysshop_shop', 'shop', 'item.shop_id=shop.shop_id')
            ->leftJoin('item', 'sysitem_item_store', 'store', 'item.item_id=store.item_id')
            ->where("m_item.deleted = '0'")
            ->andWhere("m_item.status = 'onsale'");
        if($filter['shop_id'])
        {
            $qb->andWhere("m_item.shop_id = '{$filter['shop_id']}'");
        }
        if($filter['login_shop_id'])
        {
            // 过滤当前登录店铺商品
            $qb->andWhere("m_item.shop_id <> '{$filter['login_shop_id']}'");
        }
        if($filter['title'])
        {
            $qb->andWhere("item.title LIKE '%{$filter['title']}%'");
        }
        if($filter['cat_id'])
        {
            $qb->andWhere("item.cat_id = '{$filter['cat_id']}'");
        }
        if($filter['brand_id'])
        {
            $qb->andWhere("item.brand_id = '{$filter['brand_id']}'");
        }
        $list = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($orderByArr[0], $orderByArr[1])->execute()->fetchAll();

        return $list;
    }

    /**
     * 获取店铺代售商品id数组
     * @param $shop_id
     * @return array
     */
    public function getInitItemsId($shop_id)
    {
        $arr = array();
        if($shop_id)
        {
            $itemMdl = app::get('sysitem')->model('item');
            $res = $itemMdl->getList('init_item_id', array('shop_id'=>$shop_id, 'init_shop_id|than'=>0));
            if($res)
            {
                $arr = array_column($res, 'init_item_id');
            }
        }
        return $arr;
    }

    /**
     * 获取店铺代售商品数量
     * @param $filter
     * @return mixed
     * @throws Exception
     */
    public function getMallAgentCount($filter)
    {
        if(!$filter['shop_id'])
        {
            throw new Exception('参数shop_id不为空');
        }

        $fields = 'COUNT(m_item.mall_item_id) AS count';
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();
        $qb->select($fields)
            ->from('sysmall_item', 'm_item')
            ->leftJoin('m_item', 'sysitem_item', 'item', 'm_item.item_id=item.init_item_id')
            ->leftJoin('item', 'sysitem_item_status', 'status', 'item.item_id=status.item_id')
            //->where("m_item.deleted = '0'")
            ->andWhere("item.init_item_id > '0'")
            ->andWhere("item.shop_id = '{$filter['shop_id']}'");
        if($filter['status'])
        {
            $qb->andWhere("status.approve_status = '{$filter['status']}'");
        }
        if($filter['title'])
        {
            $qb->andWhere("item.title LIKE '%{$filter['title']}%'");
        }
        $res = $qb->execute()->fetch();

        return $res['count'];
    }

    /**
     * 获取店铺代售商品列表
     * @param $fields
     * @param $filter
     * @param $offset
     * @param $limit
     * @param $orderBy
     * @return mixed
     * @throws Exception
     */
    public function getMallAgentList($fields, $filter, $offset, $limit, $orderBy)
    {
        if(!$filter['shop_id'])
        {
            throw new Exception('参数shop_id不为空');
        }

        $_orderBy = $this->orderBy['created_time_desc'];
        if($orderBy && $this->orderBy[$orderBy])
        {
            $_orderBy = $this->orderBy[$orderBy];
        }
        $orderByArr = explode(' ', $_orderBy);

        $fields = $fields . ', FORMAT((item.price-item.cost_price)/item.price*100,1) AS profit';
        $db = app::get('base')->database();
        $qb = $db->createQueryBuilder();
        $qb->select($fields)
            ->from('sysmall_item', 'm_item')
            ->leftJoin('m_item', 'sysitem_item', 'item', 'm_item.item_id=item.init_item_id')
            ->leftJoin('item', 'sysitem_item_count', 'count', 'item.init_item_id=count.item_id')
            ->leftJoin('item', 'sysshop_shop', 'shop', 'item.init_shop_id=shop.shop_id')
            ->leftJoin('item', 'sysitem_item_store', 'store', 'item.init_item_id=store.item_id')
            ->leftJoin('item', 'sysitem_item_status', 'status', 'item.item_id=status.item_id')
            //->where("m_item.deleted = '0'")
            ->andWhere("item.init_item_id > '0'")
            ->andWhere("item.shop_id = '{$filter['shop_id']}'");
        if($filter['status'])
        {
            $qb->andWhere("status.approve_status = '{$filter['status']}'");
        }
        if($filter['title'])
        {
            $qb->andWhere("item.title LIKE '%{$filter['title']}%'");
        }
        $list = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($orderByArr[0], $orderByArr[1])->execute()->fetchAll();

        return $list;
    }

    private function __filterWhere($filter)
    {
        $where = '';
        if($filter['shop_id'])
        {
            $where .= " AND item.shop_id = '{$filter['shop_id']}'";
        }
        if(isset($filter['title']))
        {
            $where .= " AND item.title LIKE '%{$filter['title']}%'";
        }
        if($filter['cat_id'])
        {
            $where .= " AND item.cat_id = '{$filter['cat_id']}'";
        }
        if($filter['brand_id'])
        {
            $where .= " AND item.brand_id = '{$filter['brand_id']}'";
        }

        return $where;
    }
}