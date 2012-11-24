<?php

class forms_module_back
{
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function forms_module_back()
    {
        $this->_module_name = 'forms';
    }
        
    function common_call($front_call = null)
    {
        $this->_module_name = 'forms';
        $this->_common_obj = &forms_module_common::getInstance();
        $this->_tree = &$this->_common_obj->obj_tree;
    }

    function execute($action, $parameters = null)
    {        
            $this->common_call();                        
            return $this->_common_obj->execute($this,$action,$parameters);
    }

    function executex($action,$acontext)
    {
            $this->common_call();
            $this->_common_obj->execute($this, $action);
            $acontext->lct = $this->lct;
            $acontext->result = $this->result;
    }
    
    
    function load_xlist_data($parameters)
    {                        
        $TD = Common::inc_module_factory('TTreeSource');
        
        $options['startNode'] = $parameters['anc_id'];
        $options['shownodesWithObjType'] = array('_ROOT', '_FORMSGROUP', '_FORMS');
        $options['columnsAsParameters'] = array('name' => 'Name');
        $options['columnsAsStructs'] = array('image' => 'obj_type');
        $options['transformResults']['image'] = array('_ROOT' => 'group', '_FORMSGROUP' => 'group', '_FORMS' => 'page');
        $options['selectable'] = array('image' => array('_ROOT', '_FORMSGROUP', '_FORMS'));
                                                                          
        $this->result['data_set'] = null;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result = array_merge_recursive($TD->result, $this->result);
    }
    
    function get_tree_inheritance()
    {
        //$this->_tree->Cleartree();
        $this->result['tree_inheritance'] = $this->_tree->LOCK_OBJ_ANC;
    }
    
    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj','_tree'));
    }
    
    function _copy($params)
    {
        $this->_common_obj->_copy($this,$params,array('_FORMS'));
    }
    
        
    function delete_obj($data)
    {
     
        return $this->_common_obj->delete_obj($this, $data);
    }
    
    
    function changeAncestor($parameters)
    {
        $this->result['dragOK'] = $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],$parameters['relative']);
    }
    
    
    
    function init_formsgroup($name)
    {
        $data['Name'] = $name;
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->InitTreeOBJ(1, '%SAMEASID%', '_FORMSGROUP', $data, true);
        
        return $id;
    }

    function reinit_formsgroup($id, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->ReInitTreeOBJ($id, '%SAME%', $data, true);

        return $id;
    }

    function init_form($anc, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->InitTreeOBJ($anc, '%SAMEASID%', '_FORMS', $data, true);

        return $id;
    }
        
    function reinit_form($id, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->ReInitTreeOBJ($id, '%SAME%', $data, true);
        
        return $id;
    }
    
    function init_fields($anc, $fields_data)
    {
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        foreach($fields_data as $data)
        {
            unset($data['flid']);
            $id = $this->_tree->InitTreeOBJ($anc, '%SAMEASID%', '_FIELDS', $data, true);
        }
        return $id;
    }
    
    function reinit_fields($anc, $fields_data)
    {
       $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
            foreach($fields_data as $data)
            {
                    if(empty($data['flid']))
                    {
                        $fid = $this->_tree->InitTreeOBJ($anc, '%SAMEASID%', '_FIELDS', $data, true);
                    }
                    else
                    {
                        $fid = $data['flid']; unset($data['flid']);
                        $this->_tree->ReInitTreeOBJ($fid, '%SAME%', $data, true);
                    }
                    
                $this->_tree->move_rate($fid, null, 'down');
            }
    }
    
    
    
    function get_obj_type($params)
    {
        $obj = $this->_tree->getNodeInfo($params['formsId']);
            if($obj)
            {
                $this->result['obj_type'] = $obj['obj_type'];
            }
            else
            {
                $this->result['obj_type'] = false;
            }
    }
    
    
    
    
    function get_categories($flows, $category_selected, $ext = true, $sec_flow = 'ctg_id')
    {
        $this->result[$flows][$sec_flow] = XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'Name'), $category_selected, $ext);
    }
    
    function add_category($params)
    {
        $this->get_categories('add_category', $params['parent']);
    }
    
    function save_category($params)
    {
        if ($id = $this->init_formsgroup($params['data']['Name']))
        {
            $this->result['is_saved'] = true;
        }
    }
    
    function edit_category($params)
    {
        $this->result['category_data']['Name'] = $this->_tree->ReadNodeParam($params['id'], 'Name');
    }
    
    function save_edited_category($params)
    {
        if ($this->reinit_formsgroup($params['id'], $params['data']))
        {
            $this->result['is_saved'] = true;
        }
    }
    
    function add_form($params)
    {
        $this->result['categories']['category'] = XHTML::arr_select_opt(
            XARRAY::arr_to_lev(
                $this->_tree->GetChildsParam(1, '%', true, array('obj_type' => array('_FORMSGROUP'))),
                'id',
                'params',
                'Name'
            ),
            $params['group_id'],
            true
        );
    }
    
    
    function save_form($params)
    {
        global $TDB;

        $params['main']['LastModified'] = time();

               if($id = $this->init_form($params['main']['category'], $params['main']))
               {
                   $this->init_fields($id, $params['fields']);
                   $this->result['is_saved'] = $id;
               }
               else
               {
                   $this->result['is_saved'] = false;
               }
    }
    
    
    function tpl_form_edit($params)
    {
        global $TMS;
        
        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'edit_form.html'));
        $this->lct['edit_form'] = $TMS->noparse('edit_form');
    }
    
    
    function tpl_fields_edit($params)
    {            
        global $TMS,$Adm;

        $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'fields')),true),true);
        $TMS->AddMassReplace($params['type'], $params);
        $this->lct[] = array('field'=>$params['type'],'text'=>$TMS->noparse($params['type']));
    }
    
    
    function edit_form($params)
    {
        global $TMS, $_PATH;

        $node = $this->_tree->getNodeInfo($params['form_id']);
        $fields = $this->_tree->GetChildsParam($params['form_id'], '%', true, array('obj_type' => array('_FIELDS')), 'ASC');               
        $this->result['form_data'] = $node['params'];
        $this->result['fields'] = $this->parse_fields($fields);
    }
    
    
    function save_edited_form($params)
    {
        if ($id = $this->reinit_form($params['id'], $params['main']))
        {
            $this->reinit_fields($id, $params['fields']);
            $this->result['is_saved'] = true;
        }
    }
    
    
    function parse_fields($fields)
    {
        global $TMS, $Adm;

        $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'fields')),true),true);
        
            foreach($fields as $key => $field)
            {
                $field['params']['flid'] = $field['params']['num'] = $key;
                $field['params']['compulsory_to_fill'] = ($field['params']['compulsory_to_fill']) ? 'checked' : '';
                $field['params']['readonly'] = ($field['params']['readonly']) ? 'checked' : '';
                    switch ($field['params']['field_type'])
                    {
                        case 'textfield':
                            $field['params']['email'] = ('email' == $field['params']['type']) ? 'selected' : '';
                            $field['params']['url'] = ('url' == $field['params']['type']) ? 'selected' : '';
                            $field['params']['text'] = ('text' == $field['params']['type']) ? 'selected' : '';
                            $field['params']['hidden'] = ('hidden' == $field['params']['type']) ? 'selected' : '';
                            $field['params']['numerical'] = ('numerical' == $field['params']['type']) ? 'selected' : '';    
                            $TMS->AddMassReplace('textfield',$field['params']);
                            $TMS->parseSection('textfield', true);
                            break;
                        default:
                            $TMS->AddMassReplace($field['params']['field_type'],$field['params']);
                            $TMS->parseSection($field['params']['field_type'], true);                   
                            break;
                    }
            }
        return $TMS->parseSection('fields', true);
    }

    
        
    function open_selected_message($params)
    {
        global $TDB;  

        $msg = $TDB->get_results('SELECT message FROM messages WHERE id=' . $params['id']);
            if($msg)
            {
                $msg = $msg[1]['message'];
                $this->result['message'] = $msg;
            }
    }    

    
    
    function in_archive_msg($params)
    {
        global $TDB;
        
        if (is_array($params['id']))
        {
                $id=implode($params['id'],"','");
                $where='id in (\''. $id . '\')';
            }
        else
            {
            $where='id="' . $params['id'] . '"';
            }

        $query = 'UPDATE messages SET messages.archive=1 WHERE ' . $where;

        if ($TDB->query($query))
        {
            $this->result['isArch']=true;
        }
    }
    
    
    function delete_msg($params)
    {
        global $TDB;
        
        if (is_array($params['id']))
        {
            $id = implode($params['id'],"','");
            $where = 'id in (\''. $id . '\')';
        }
        else
        {
            $where='id="' . $params['id'] . '"';
        }

        $query='DELETE FROM messages WHERE ' . $where;

        if ($TDB->query($query))
        {
            $this->result['isDel']=true;
        }
    }
    
    
    function read_msg($params)
    {
        global $TDB;

        if($TDB->UpdateIN('messages', (int)$params['id'], array('status' => (int)$params['state'])))
        {
            $this->result['read'] = true;
        }
    }
    
    
    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),$parameters['selected'],true);
    }
    
    
        
    function load_messages($parameters)
    {
        global $_CONFIG;

        $TTS = Common::inc_module_factory('TTableSource');
        
        $options['startRow'] = 0;
        $options['table'] = 'messages';
        $options['where'] = 'archive=' . $parameters['archive'].' order by id DESC';
        $options['rows_per_page'] = $_CONFIG['news']['admin_rows_per_page'];
        $options['columns'] = array('id','form_id','Name','date','status','archive');
        $options['gridFormat'] = 1;                                                                                 
        $options['sequence'] = array('id','date','Name','status');
        
        $this->result['data_set']=null;

        $TTS->setOptions($options);
        $this->result['data_set'] = $TTS->CreateView();
    }


        

    function get_action_properties($parameters)
    {
        global $TMS,$Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);
                          
            switch ($parameters['Action'])
            {   
                case 'show_forms':
                    $this->result['action_properties'] = true;
                    Common::call_common_instance('pages');
                    $pages = &pages_module_common::getInstance();
                    $files = Common::get_module_template_list('forms',array('.'.$parameters['Action'].'.html'));
                    $this->result['xlist'] = true;
                    $this->result['action_properties_form']['Template_group'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);         
                    $this->result['action_properties_form']['Template1'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    break;
                default:
                    break;
                }
                
            }
        }
}
      
?>