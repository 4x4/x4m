<?php
class banners_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function price_module_back()
        {
        $this->_module_name='banners';
        }

    function common_call($front_call = null)
        {    
        $this->_module_name='banners'; 
        $this->_common_obj=&banners_module_common::getInstance();
        $this->_tree      =&$this->_common_obj->obj_tree;
        }

    function executex($action,$acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }



    function delete_obj($data)
        {              
        if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                $this->delete_item($id);
                }
            }
            
        else
            {
            $this->delete_item($data['id']);
            }
        }

        
        
    function delete_item($id){
            $item = $this->_tree->getNodeInfo($id);
            if ($item['obj_type']=="_BANNERS"){
                $this->delete_banner($id);
            }
            if ($item['obj_type']=="_BANNERSGROUP"){
                $this->delete_bannersgroup($id);    
            }
        
        }
        
        
        
        function delete_banner($id)
        {
            global $TDB;       
                $items = $this->_tree->getNodeInfo($id);                                   
                $adress = Common::media_path($this->_module_name);
                
                if ($this->_tree->DelNode($id)){
                    if ($items['params']['banner_type']!='html'){
                        $img = $adress.$items['params']['file_name'];
                        $b1=@unlink($img);
                    }
                    $TDB->get_results('delete from banners_server where banner_id='.$id);
                    
                    $this->result['deleted'][]=$id;
                }                
        }
        
        
        
        function delete_bannersgroup($id)
        {
            if($banners = $this->_tree->getChilds($id))
            {
                foreach($photos as $items){
                    $this->delete_banner($items['id']);           
                }
                
            }
            if ($this->_tree->DelNode($id)){
                $this->result['deleted'][]=$id;
            };
            
        }
        
    function changeAncestor($parameters)
        {                                            
            $this->result['dragOK'] =    $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],$parameters['relative']);
        }
        
        
        
    function load_tpl($parameters)
        {
        $this->result['default_tpl']=$this->_tree->ReadNodeParam($parameters['id'], 'Template');
        }



    function get_initial_category_data($tpl_selected = null)
        {
        if ($files=Common::get_module_template_list($this->_module_name))
            {
            $this->result['category_data']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$tpl_selected);
            }
        }

    function add_category() { $this->get_initial_category_data(); }

    function add_price($parameters)
        {
        $this->load_initial_price_data($parameters['group_id']);
        }

    function load_initial_banners_data($params)
        {         
        $this->load_categories($params['category']);
        }

    function load_categories($category_selected)
        {
        $this->result['banners_data']['category']
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'),
                                   $category_selected,true);
        }

    function load_banner($params)
    {
        global $TMS, $_PATH, $TDB;  
        $node=$this->_tree->getNodeInfo($params['banner_id']);
        $this->result['banner_data']['basic']=$node['basic'];
        $this->result['banner_data'] = array_merge($this->result['banner_data'],$node['params']);
        $r = $TDB->get_results("SELECT count(*) as `xcount` FROM `banners_server` WHERE `banner_id` = ".$node["id"].";");
        $this->result['banner_data']["shows"]=$r[1]["xcount"];
    }

    function load_category($parameters)
        {                   
        if ($node=$this->_tree->getNodeInfo($parameters['id']))
            {           
            $this->get_initial_category_data($node['params']['Template']);
            $this->result['category_data']['basic']=$node['basic'];
            $this->result['category_data'] = array_merge($this->result['category_data'],$node['params']);
            }
        }

    function save_category($parameters)
        {                               
        $this->result['is_saved']=$this->init_bannergroup($parameters['data']['basic'], $parameters['data']);
        }

    function save_edited_category($parameters)
        {
        $this->result['is_saved']=$this->reinit_bannergroup($parameters['id'],$parameters['data']['basic'], $parameters['data']);
        }

    function save_edited_banner($parameters)
        {        
        
        if ($parameters['main']['banner_type']!='html'){
            $p['fname'] = $parameters['main']['file_name'];
            $p['type'] =  $parameters['main']['banner_type'];
            $p['alt'] =  $parameters['main']['alt'];
            $info = $this->get_banner_info($p);
            
            if(is_array($info))
		    {
                $parameters['main'] = array_merge($parameters['main'],$info);
            }
        }                                         
        if ($id=$this->reinit_banner($parameters['id'], $parameters['main']['basic'], $parameters['main']))
            { 

            $this->_tree->DelNode($parameters['id'], true);
                                                 
            foreach ($parameters['inner_fields'] as $field_name => $field_value)
                {
                if (in_array($field_name, array_keys($tms->Extended['xtr_price'])))
                    {
                    $this->init_field($id, $field_name, $field_value, true);
                    }
                }

            $this->result['is_saved']=true;
            }
        }

    function save_banner($parameters)
        {
        global $TDB;
        $parameters['main']['LastModified']=time();
      
        if ($parameters['main']['banner_type']!='html')
        {
            $p['fname'] = $parameters['main']['file_name'];
            $p['type'] =  $parameters['main']['banner_type'];
            $p['alt'] =  $parameters['main']['alt'];
            $info = $this->get_banner_info($p);
            if(is_array($info)) $parameters['main'] = array_merge($parameters['main'],$info);
        }
        $this->result['is_saved']=$this->init_banner($parameters['main']['category'], $parameters['main']['basic'], $parameters['main']);
        
        }
        
    /*banner_info--------------------------------------------------------------------------------------------*/

    function get_banner_info($params){
        global $_PATH;
        
        $fname = DOCUMENT_ROOT.$params['fname'];
        switch ($params['type']){
            
            case 'gif':
                $size = getimagesize($fname);
                $this->result['banner_info'] = Array('width'=>$size[0],'height'=>$size[1]);
                break;
            case 'flash':  
                $SWF = Common::inc_module_factory('swfheader');
                $SWF->loadswf($fname);
                $this->result['banner_info'] = Array('width'=>$SWF->width,'height'=>$SWF->height);                
                break;        
        }
        return $this->result['banner_info'];
   
    }
    
    function get_category_info($params){
      
        $p = $this->_tree->GetNodeInfo($params['id']);
        $p = $p['params'];
        array_shift($p);
        $this->result['category_info'] = $p;
    }
    
    function get_banner_stats($params){
        global $TDB;
        /*$q = "select shown, clicked from banners_server where banner_id=".$params['banner_id'];
            $stats = $TDB->get_results($q);
            $this->result['stats'] = $stats[1];
        */
    }
    
    function clear_statictics($params)
    {
        global $TDB;
        $q = 'delete from banners_server id='.$params['id'];
        $TDB->get_results($q);   
    }

    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_ainterface()
        {
        global $TMS;

        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
        $this->lct['ainterface']=$TMS->parseSection('a_interface');
        }

    function load_actions($parameters)
        {
        $this->result['tune_actions']['Action']
            =XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),
                                   $parameters['selected'],
                                   true);
        }

        
    function get_template_list($module_name, $actions) {
        $files = Common::get_module_template_list($module_name,$actions);
        $files_aliases = array();
        foreach ($files as $file) {
            for ($i=0; $i<count($actions);$i++) { $file = str_replace( $actions[$i], '', $file ); }
            $files_aliases[] = $file;
        }
        return XHTML::arr_select_opt(XARRAY::combine($files_aliases, $files),$se, true);
    }        
        
    function get_action_properties($parameters)
        {
      
        global $TMS,$Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
                
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
            switch ($parameters['Action'])
                {
                case 'show_selected_banner':
               
                    $this->result['action_properties'] =true;
                    
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html', '.show_random_banner.html'));                    
                    $this->result['xlist'] =true;
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;
                    
                case 'show_random_banner':
               
                    $this->result['action_properties'] =true;                    
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html', '.show_selected_banner.html'));                    
                    $this->result['action_properties']['category']=XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,true);
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;
                    
                    
                case 'show_banners_from_group':
               
                    $this->result['action_properties'] =true;                    
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties']['category']=XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,true);
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;

                    
                }
            }
        }


    function load_xlist_data($parameters)
        {

        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_BANNERSGROUP',
            '_BANNERS'
            );

        $options['columnsAsParameters']=array('LastModified' => 'LastModified');

        $options['columnsAsStructs']=array
            (
            'name'  => 'basic',
            'image' => 'obj_type'
            );

        $options['transformResults']['image']=array
            (
            '_BANNERSGROUP' => 'group',
            '_BANNERS'      => 'page'
            );

        $options['selectable']=array('image' => array('_BANNERS'));

                                                                                     
        $this->result['data_set']=null;

        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }   
    
    /*--------------------------------------------------------------------------------------------*/

    
    function init_field($anc, $basic, $field_value)
        {
        $data=Array('field_value' => $field_value);

        $id=$this->_tree->InitTreeOBJ($anc, $basic, '_FIELD', $data, true);
        return $id;
        }

    function init_banner($anc, $basic, $data)
        {
        $data['LastModified']=time();
        $id                  =$this->_tree->InitTreeOBJ($anc, $basic, '_BANNERS', $data, true);
        return $id;
        }

    function reinit_banner($id, $basic, $data)
        {
        $data['LastModified']    =time();
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_tree->SetUniques($uniq_param);
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }

    function init_bannergroup($basic, $data)
        {   
        $data['LastModified']=time();
        $id                  =$this->_tree->InitTreeOBJ(1, $basic, '_BANNERSGROUP', $data, true);
        return $id;
        }

    function reinit_bannergroup($id, $basic, $data)
        {
        $data['LastModified']=time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }
    


    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree'            
            ));
        }
        
    function load_xlist_link_pages($parameters)
    {
        Common::call_common_instance('pages');
        $pages = &pages_module_common::getInstance(true);
        $TD=Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];
        $options['shownodesWithObjType']=array('_PAGE','_LINK','_GROUP','_ROOT');
        $options['columnsAsParameters']=array('name' => 'Name');
        $options['columnsAsStructs']=array('image' => 'obj_type');
        $options['transformResults']['image']=array('_GROUP' => 'group', '_PAGE'  => 'page', '_LINK' => 'page', '_ROOT'  => 'group' );
        $options['selectable']=array('image' => array('_PAGE' ,'_LINK', '_GROUP', '_ROOT' ));
        $this->result['data_set']=null;
        $TD->init_from_source($pages->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
    }    
        
        
        
        
    }


?>
