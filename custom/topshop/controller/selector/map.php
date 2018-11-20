<?php

class topshop_ctl_selector_map extends topshop_controller {

    public function loadMapModal()
    {
        $area_json = file_get_contents(PUBLIC_DIR.'/app/ectools/statics/scripts/bank_region.json');
        $pagedata['area_json'] = $area_json;
        return view::make('topshop/selector/map/index.html',$pagedata)->render();
    }
}
