<?php
/**
 * User: zhangshu
 * Date: 2018/11/15
 * Desc: 创客商品相关
 */
class sysmaker_data_item
{
    private $sellerItemMdl;
    private $db;

    public function __construct()
    {
        $this->sellerItemMdl = app::get('sysmaker')->model('seller_item');
        $this->db = app::get('base')->database();
    }

    /**
     * 获取主持人店铺商品总数
     *
     * @param $filter
     * @return mixed
     */
    public function getSellerItemCount($filter)
    {
        if (empty($filter['seller_id'])) {
            throw new LogicException('参数seller_id不能为空');
        }
        $qb = $this->db->createQueryBuilder();
        $qb->select('count(*) as count')
            ->from('sysmaker_seller_item', 's_item')
            ->leftJoin('s_item', 'sysmall_item', 'm_item', 's_item.item_id = m_item.item_id')
            ->where('s_item.deleted = 0')
            ->andWhere('m_item.status = "onsale"')
            ->andWhere('s_item.seller_id = ' . $filter['seller_id']);
        $count = $qb->execute()->fetch();
        return $count['count'];
    }

    /**
     * 获取主持人店铺商品
     *
     * @param array $filter
     * @param int $offset
     * @param int $limit
     * @param string $orderBy
     * @return mixed
     */
    public function getSellerItemList($filter, $offset = 0, $limit = 10, $orderBy = 'item.price asc')
    {
        if (empty($filter['seller_id'])) {
            throw new \LogicException('参数seller_id不能为空');
        }

        $fields = 'item.item_id, item.price, item.title, item.image_default_id, item_count.paid_quantity';
        $qb = $this->db->createQueryBuilder();
        $qb->select($fields)
            ->from('sysmaker_seller_item', 's_item')
            ->leftJoin('s_item', 'sysitem_item', 'item', 's_item.item_id = item.item_id')
            ->leftJoin('item', 'sysitem_item_count', 'item_count', 'item.item_id = item_count.item_id')
            ->leftJoin('s_item', 'sysmall_item', 'm_item', 's_item.item_id = m_item.item_id')
            ->where('s_item.deleted = 0')
            ->andWhere('m_item.status = "onsale"');
        if ($filter['seller_id']) {
            $qb->andWhere('s_item.seller_id = ' . $filter['seller_id']);
        }
        if ($filter['keywords']) {
            $qb->andWhere('item.title like "%'. $filter['keywords']. '%"');
        }

        list($sort, $order) = explode(' ', $orderBy);

        $sellerItems = $qb->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy($sort, $order)
            ->execute()
            ->fetchAll();
        if ($sellerItems) {
            foreach ($sellerItems as $key => $item) {
                $sellerItems[$key]['price'] = number_format($item['price'], 2);
                $sellerItems[$key]['image_default_id'] = base_storager::modifier($item['image_default_id'], 'm');
            }
        }
        return $sellerItems;
    }
}
