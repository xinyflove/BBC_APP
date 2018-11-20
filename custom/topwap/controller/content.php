<?php

/**
 * content.php 
 *
 * @copyright  Copyright (c) 2005-2016 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class topwap_ctl_content extends topwap_controller {

    public function index()
    {
        $nodeList = $this->__getCommonInfo();
        $pagedata['nodeList'] = $nodeList;
        $pagedata ['title'] = app::get('topwap')->_('文章目录');
        
        return $this->page('topwap/content/index.html', $pagedata);
    }
    
    public function childNodeList()
    {
        $nodeId = input::get('node_id');
        if(!is_numeric($nodeId)) kernel::abort(404);
        $childList = $this->__getCommonInfo($nodeId);
        $pagedata['childList'] = $childList;
        $pagedata ['title'] = app::get('topwap')->_('文章目录');
        
        return $this->page('topwap/content/child_index.html', $pagedata);
    }
    
    public function contentList()
    {
        $list = $this->__getConnentList(5);
        $pagedata['title'] = app::get('topwap')->_('文章列表');
        /*add_20171026_by_fanglongji_start*/
        foreach($list['articleList'] as &$item)
        {
            $item['content'] = strip_tags($item['content']);
        }
        /*add_20171026_by_fanglongji_end*/
        $pagedata['contentlist'] = $list['articleList'];
        $pagedata['total'] = $list['total'];
        $pagedata['node_id'] = $list['node_id'];
        
        return $this->page('topwap/content/content_list.html', $pagedata);
    }
    
    public function getContentInfo()
    {
        $articleId = input::get('aid');
        if(!is_numeric($articleId)) kernel::abort(404);
        /*modify_20171026_by_fanglongji_start*/
        /*
        $params = array(
                'article_id' => $articleId,
                'fields' =>'article_id,title,modified,content',
        );
        */
        $params = array(
            'article_id' => $articleId,
            'fields' =>'article_id,title,modified,content,new_label',
        );
        /*modify_20171026_by_fanglongji_end*/
        
        $contentInfo = app::get('topwap')->rpcCall('syscontent.content.get.info',$params);
        $pagedata['info'] = $contentInfo;
        $pagedata['title'] = app::get('topwap')->_('文章详情');
        
        return $this->page('topwap/content/info.html', $pagedata);
    }
    
    public function ajaxContentList()
    {
        $index = input::get('index', false);
        if($index)
        {
            $data = $this->__getConnentList(5);
            
            return response::json($data);exit;
        }
        $contentData = $this->__getConnentList();
        /*add_20171026_by_fanglongji_start*/
        foreach($contentData['articleList'] as &$item)
        {
            $item['content'] = strip_tags($item['content']);
        }
        /*add_20171026_by_fanglongji_end*/
        $pagedata['contentlist'] = $contentData['articleList'];
        $data['html'] = view::make('topwap/content/list.html', $pagedata)->render();
        $data['success'] = true;
        
        return response::json($data);exit;
    }
    
    // 商家文章
    public function shopArticle($shopId, $aid)
    {
        $data = input::get();
        $preview = input::get('preview', 0);
        $params['shop_id'] = $data['shop_id'];
        $params['article_id'] = $data['aid'];
        $params['fields'] = '*';
        $info = app::get('topc')->rpcCall('syscontent.shop.info.article', $params);
        if(!$info || ($info['pubtime'] > time() && !$preview))
        {
            kernel::abort(404);
        }
        $pagedata['info'] = $info;
        $pagedata['title'] = app::get('topwap')->_('文章详情');
        
        return $this->page('topwap/content/info.html', $pagedata);
    }
    
    private function __getConnentList($limit = 20)
    {
        $filter = input::get();
        if(!$filter['pages'])
        {
            $filter['pages'] = 1;
        }
        /*modify_20171024_by_fanglongji_start*/
        /*
            $params = array(
                'node_id'   => $filter['node_id'],
                'page_no'   => $limit*($filter['pages']-1),
                'page_size' => $limit,
                'fields' =>'article_id,title',
                'platform'  =>'wap',
            );
        */
        $params = array(
            'node_id'   => ($filter['node_id'] == '') ? null : $filter['node_id'],
            'page_no'   => $filter['pages'],
            'page_size' => $limit,
            'fields' =>'article_id,title,content,modified,new_label',
            'platform'  =>'wap',
        );
        /*modify_20171024_by_fanglongji_end*/
        $contentData = app::get('topwap')->rpcCall('syscontent.content.get.list',$params);
        $count = $contentData['articlecount'];
        if($count>0) $total = ceil($count/$limit);
        $contentData['total'] = $total;
        $contentData['node_id'] = $filter['node_id'];
        
        return $contentData;
    }
    
    // 获取文章节点树
    private function __getCommonInfo($parentId=0)
    {

        $params ['fields'] = 'node_id,node_name,parent_id,node_depth,node_path';
        $params ['parent_id'] = $parentId;
        $params ['orderBy'] = 'order_sort ASC';
        $nodeList = app::get('topwap')->rpcCall('syscontent.node.get.list', $params);
        
        return $nodeList;
    }

}
 
 