<?php
/**
 * Created by PhpStorm.
 * User: XinYufeng
 * Date: 2018/1/3
 * Time: 17:24
 */
class sysstore_ctl_widgets extends desktop_controller{

    public function lists()
    {
        $actions = array(
            array(
                'label'=>app::get('sysstore')->_('更新挂件'),
                'href'=>'?app=sysstore&ctl=widgets&act=updateWidgets',
            ),
        );

        $params = array(
            'title' => app::get('sysstore')->_('挂件列表'),
            'actions'=> $actions,
            'use_buildin_delete' => false,
        );

        return $this->finder('sysstore_mdl_widgets', $params);
    }

    public function updateWidgets()
    {
        $this->widgets = $this->getDiffPlatformWidgets();

        $url = '?app=sysstore&ctl=widgets&act=lists';
        //$path = $this->app->widgets_dir;
        $path = CUSTOM_CORE_DIR.'/sysstore/view/widgets';
        //判断传入的变量是否是目录 || 是否可读
        if(!is_dir($path) || !is_readable($path)) {
            return $this->splash('success',$url,'更新失败');
        }

        //处理pc端挂件
        $path_pc = $path . '/pc';
        $this->updateWidgetInfo($path_pc, 'pc');

        //处理wap端挂件
        $path_wap = $path . '/wap';
        $this->updateWidgetInfo($path_wap, 'wap');

        foreach ($this->widgets as $fk => $fw)
        {
            foreach ($fw as $w)
            {
                $widgetsMdl = app::get('sysstore')->model('widgets');
                $widgetsMdl->delete(array('platform'=>$fk, 'widgets_name'=>$w['widgets_name']));
            }
        }

        return $this->splash('success',$url,'更新成功');
    }

    public function getDiffPlatformWidgets($platform = '')
    {
        $widgetsMdl = app::get('sysstore')->model('widgets');
        $widgets = $widgetsMdl->getList('*');

        $data = array();
        foreach ($widgets as $w)
        {
            if($w['platform'] == 'pc')
            {
                $data['pc'][$w['widgets_name']] = $w;
            }
            elseif($w['platform'] == 'wap')
            {
                $data['wap'][$w['widgets_name']] = $w;
            }
        }
        unset($widgets);

        if(empty($platform))
        {
            return $data;
        }

        return $data[$platform] ? $data[$platform] : '';
    }

    protected function updateWidgetInfo($path, $platform)
    {
        if(!in_array($platform, array('pc', 'wap')))
        {
            return false;
        }

        //取出目录中的文件和子目录名,使用scandir函数
        $allFiles = scandir($path);

        foreach($allFiles as $fileName) {

            if(in_array($fileName, array('.', '..', '.svn'))) {
                continue;
            }

            //路径加文件名
            $path_1 = $path.'/'.$fileName;
            $data = array();
            $data['widgets_name'] = $fileName;
            if(is_dir($path_1))
            {
                $widgets_info_path = $path_1 . '/widgets.php';
                $setting = array();
                if(file_exists($widgets_info_path) && require($widgets_info_path))
                {
                    $data['widgets_title'] = $setting['title'];
                    $data['widgets_desc'] = $setting['desc'];
                    $data['widgets_author'] = $setting['author'];
                    $data['platform'] = 'wap';
                    $data['created_time'] = strtotime($setting['created_time']);
                }

                $data['modified_time'] = time();
                if(empty($data['created_time'])) $data['created_time'] = $data['modified_time'];

                $widgetsMdl = app::get('sysstore')->model('widgets');
                if(array_key_exists($data['widgets_name'], $this->widgets[$platform]))
                {
                    $widgetsMdl->update($data, array('widgets_name'=>$data['widgets_name']));
                    unset($this->widgets[$platform][$data['widgets_name']]);
                }
                else
                {
                    $widgetsMdl->insert($data);
                }
            }
        }
    }
}