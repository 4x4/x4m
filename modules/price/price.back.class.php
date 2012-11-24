<?php
class price_module_back
    {
    var $lct;
    var $result;

    var $_module_name;
    var $_common_obj;
    var $_tree;

    function price_module_back()
        {
        $this->_module_name='price';
        }

    function common_call($front_call = null)
        {
        //вы 
        $this->_common_obj=&price_module_common::getInstance();
        $this->_tree      =&$this->_common_obj->obj_tree;
        }

/*
        function execute($action, $parameters = null)
        {        
            $this->common_call();                        
            return   $this->_common_obj->execute(&$this,$action,$parameters);
        }
*/
        
        function executex($action,$acontext)
        {
            //DebugBreak();
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }


    function actions($action, $parameters)
        {
        switch ($action)
            {
            case 'parse_price_tpl':
                $this->parse_price_tpl($parameters['tpl'], true);

                break;

            default: return false;
            }
        }

    function delete_obj($data)
        {
        $del = $data['files'];
        if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                $this->delete_item($id,$del);
                }
            }
            
        else
            {
            $this->delete_item($data['id'],$del);
            }
        }

        
        
    function delete_item($id,$del){
            $item = $this->_tree->getNodeInfo($id);
            if ($item['obj_type']=="_PRICE"){
                $this->delete_price($id,$del);
            }
            if ($item['obj_type']=="_PRICEGROUP"){
                $this->delete_pricegroup($id,$del);    
            }
        }
        
        
        
        function delete_price($id,$delete=false){
            $items = $this->_tree->getNodeInfo($id);                                                   
                $file = $_SERVER['DOCUMENT_ROOT'].$items['params']['file_name'];
                if (file_exists($file)&&$delete){
                    $r = @unlink($file);
                }
                if (($this->_tree->DelNode($id))){
                    $this->result['deleted'][]=$id;
                }                
        }
        
        
        
        function delete_pricegroup($id,$del){
            $files = $this->_tree->getChilds($id,$del);
            foreach($files as $items){
                $this->delete_price($items['id']);           
            }
            if ($this->_tree->DelNode($id)){
                $this->result['deleted'][]=$id;
            };
        }
        
    function changeAncestor($parameters)
        {
            $this->result['dragOK']=    $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],$parameters['relative']);
        }
        
        
        
    function load_tpl($parameters)
        {
            
        $this->result['default_tpl']=$this->_tree->ReadNodeParam($parameters['id'], 'Template');
        }

    function tpl_price_edit()
        {
        global $TMS;
        //DebugBreak();
        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'edit_price.html'));
        $this->lct['edit_price']=$TMS->noparse('edit_price');
        }

    function get_initial_category_data($tpl_selected = null)
        {
        global $_PATH;
        
        $this->result['category_data'] = array();
        if ($files=Common::get_module_template_list($this->_module_name))
            {
            $this->result['category_data']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),
                                                                             $tpl_selected);
            }
        }

    function load_root_data()
        {
        $root                                  =$this->_common_obj->get_node(1);
        $this->result['root_data']             =$root['params'];
        }
    
    
    function save_edited_root($parameters) {
        if ($id=$this->reinit_root($parameters['root_data'])) $this->result['saved']=true;
    }    
        
    function reinit_root($data)
    {
        $id=$this->_tree->ReInitTreeOBJ(1, '%SAME%', $data);
        return $id;
    }        
        
        
        
    function add_category($params) { 
        $this->get_initial_category_data(); 
    }

    function add_price($parameters)
        {
        global $_PATH;
        $this->load_initial_price_data($parameters['group_id']);
        }

    function load_initial_price_data($params)
        {
        global $_PATH;
        $this->load_categories($params['category']);
        }

    function load_categories($category_selected)
        {    
        $this->result['price_data']['category']
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'),
                                   $category_selected,true);
        }

    function load_price($params)
        {
        global $TMS, $_PATH;
        $node=$this->_tree->getNodeInfo($params['price_id']);
        $this->result['price_data'] = $node['params'];
        }

    function load_category($parameters)
        {
         //DebugBreak();
        if ($node=$this->_tree->getNodeInfo($parameters['id']))
            {
            $this->get_initial_category_data($node['params']['Template']);
            $this->result['category_data']=$node['params'];
            }
        }

    function save_category($parameters)
        {
            //DebugBreak();
        if ($this->init_pricegroup($parameters['data']['basic'], $parameters['data'], $parameters["data"]["ParentCategoryId"]))
            {
            $this->result['is_saved']=true;
            }
        }

    function save_edited_category($parameters)
    {
        //DebugBreak(); 
        if($this->reinit_pricegroup($parameters['id'], $parameters['data']['basic'], $parameters['data']))
        {
            $this->result['is_saved'] = true;
        }
    }

    function save_edited_price($parameters)
        {
        /*  if ($parameters["main"]["ParentCategoryId"]) $this->changeAncestor(array("id" => $parameters['id'], "ancestor" => $parameters["main"]["ParentCategoryId"])); */
        if (!$parameters["main"]["counter"]) $parameters["main"]["counter"]=0;
        else $parameters["main"]["counter"] = (int) $parameters["main"]["counter"];
        $parameters['main']['LastModified']=time();
        $parameters["main"]["hash"] = md5(strrev($parameters["main"]["file_name"].$parameters['main']['LastModified']));
        
        if ($id=$this->reinit_price($parameters['id'], $parameters['main']['basic'], $parameters['main']))
            {        
              $this->result['is_saved']=true;
            }
        }

    function save_price($parameters)
        {
        if (!$parameters["main"]["ParentCategoryId"]) $parameters["main"]["ParentCategoryId"] = 1;
        $parameters["main"]["counter"] = 0;
        $parameters['main']['LastModified']=time();        
        $parameters["main"]["hash"] = md5(strrev($parameters["main"]["file_name"].$parameters['main']['LastModified']));
        

        //инициализация объекта в дереве
       
       if( $id=$this->init_price($parameters['main']['ParentCategoryId'], $parameters['main']['basic'], $parameters['main']))
            {
           
            $this->result['is_saved']=true;
           }
    
        }

    function parse_price_tpl($tpl_file, $is_runtime = false)
        {
        global $TMS;

        $tms=new TMutiSection(true);

        if ($is_runtime)
            {
            $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'edit_price.html'));
            }
    
        $tms->AddFileSection(Common::get_site_tpl($this->_module_name, $tpl_file));

        if ($tms->Extended)
            {
            foreach ($tms->Extended['xtr_price'] as $field_name => $ext)
                {
                switch ($ext['type'])
                    {
                    case 'IMAGE':
                    case 'INPUT':
                        $TMS->AddReplace($ext['type'], '_field_name', $field_name);

                        $TMS->AddMassReplace($ext['type'], $ext);
                        $TMS->ParseSection($ext['type'], true);
                        break;

                    case 'TEXT':
                        $TMS->AddReplace($ext['type'], '_field_name', $field_name);

                        $TMS->AddMassReplace($ext['type'], $ext);
                        $TMS->ParseSection($ext['type'], true);
                    }
                }

            $this->result['fields']=$TMS->ParseSection('fields');
            }
        }


    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_ainterface()
        {
        global $TMS;

        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
        $this->lct['ainterface']=$TMS->ParseSection('a_interface');
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
                case 'show_price_list':
                    $this->result['action_properties'] =true;
                    $this->result['xlist'] =true;                 
                    $this->lct['action_properties']=$TMS->ParseSection($parameters['Action']);
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    break;                    
                case 'show_folder':
                    $this->result['action_properties'] =true;
                    $this->result['xlist'] =false;                 
                    $this->lct['action_properties']=$TMS->ParseSection($parameters['Action']);
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    break;                    
                    
                }
            }
        }

    //специальная функция сервер данных для xlist
    function load_xlist_data($parameters)
        {
        $this->module_name = 'price';
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_PRICEGROUP',
            '_PRICE'
            );

        $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name' => 'Name');

        $options['columnsAsStructs']=array
            (
            'name'  => 'basic',
            'image' => 'obj_type'
            );

        $options['transformResults']['image']=array
            (
            '_PRICEGROUP' => 'group',
            '_PRICE'      => 'page'
            );

        $options['selectable']=array('image' => array('_PRICE', '_PRICEGROUP'));


        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;

        //$TD->init_from_source($this->_tree);
        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);

        $this->result=array_merge_recursive($TD->result, $this->result);
        }     
        
    //специальная функция сервер данных для xlist
    function load_xlist_data_folders($parameters)
        {
        $this->module_name = 'price';
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array('_PRICEGROUP');
        $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name' => 'Name');
        $options['columnsAsStructs']=array('name'  => 'basic', 'image' => 'obj_type');
        $options['transformResults']['image']=array
            (
            '_PRICEGROUP' => 'group',
            '_PRICE'      => 'page'
            );

        $options['selectable']=array('image' => array('_PRICEGROUP'));

        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;

        //$TD->init_from_source($this->_tree);
        $TD->init_from_source($this->_common_obj->obj_tree);
        
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }        

    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }


    function init_field($anc, $basic, $field_value)
        {
        $data=Array('field_value' => $field_value);

        $id=$this->_tree->InitTreeOBJ($anc, $basic, '_FIELD', $data, true);
        return $id;
        }

    function init_price($anc, $basic, $data)
        {
        $data['LastModified']=time();
        $data['counter']=0;
        $id                  =$this->_tree->InitTreeOBJ($anc, $basic, '_PRICE', $data, true);
        return $id;
        }

    function reinit_price($id, $basic, $data)
        {
        $data['LastModified']    =time();
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_tree->SetUniques($uniq_param);
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }

    function init_pricegroup($basic, $data, $anc = 1)
        {
        if (!$anc) $anc = 1;
        $data['LastModified']=time();
        $id=$this->_tree->InitTreeOBJ($anc, $basic, '_PRICEGROUP', $data, true);
        return $id;
        }

    function reinit_pricegroup($id, $basic, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
    }
    


    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree',            
            ));
        }
        
        
    function get_access($params)
    {    

      //DebugBreak();
        Common::call_common_instance('fusers');  
                $fusers=fusers_module_common::getInstance();         
              
             $this->result['access']['DisableAccess']=$this->_tree->ReadNodeParam($params['id'],'DisableAccess');
            
             if($groups=$fusers->obj_tree->GetChildsParam(1,array('Name')))
               {                   
                    if( $results=$fusers->get_node_rights($params['id'],'price'))
                    {                    
                        foreach($results as $r)
                        {
                            //read
                            if($r['params']['Rights']==1)
                             {                    
                                 $groups[$r['ancestor']]['r']=1;
                             }                        
                        }                    
                    }                     
                    $this->result['access_groups']=$groups;                    
                   
                    
               }
    } 
    
    function set_access($params)
    {
                Common::call_common_instance('fusers');  
                $fusers=fusers_module_common::getInstance();       
                if ($params["access"]["DisableAccess"]) $params["access"]["hashed_link"] = 1;
                if($params['access'])
                {
                    unset($params['access']['Name']);
                    //$params['access']['AuthRedirId']=$params['access']['LinkId'];                    
                    $this->reinit_price($params['id'], '%SAME%', $params['access']);
                
                }
                
                if($params['groups'])
                {   
            
                    if( $results=$fusers->get_node_rights($params['id'],$this->_module_name)) 
                    {
                       foreach($results as $k=>$v)
                       {
                         $fusers->obj_tree->DelNode($k);
                       }
                    }
                    
                                  
                    foreach($params['groups'] as $gr_key=>$gr_)
                    {
                        if($gr_)$fusers->init_scheme_item(str_replace('_','',$gr_key),array('Module'=>'price','Node'=>$params['id'],'Rights'=>1));                    
                    }
                }    
    }            
        



}   


?>
