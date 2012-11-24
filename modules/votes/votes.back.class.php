<?php
class votes_module_back
{
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function votes_module_back()
    {
        $this->_module_name = 'votes';
    }

    function common_call($front_call = null)
    {
        $this->_module_name = 'votes';
        $this->_common_obj = votes_module_common::getInstance();
        $this->_tree = $this->_common_obj->obj_tree;
    }
        function executex($action,$acontext)
    {
        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct = $this->lct;   
        $acontext->result = $this->result;
    }

    function execute($action, $parameters = null)
    {        
        $this->common_call();                        
        return $this->_common_obj->execute(&$this,$action,$parameters);
    }

    function get_categories($flows, $category_selected, $ext = true, $sec_flow = 'ctg_id')
    {
        $this->result[$flows][$sec_flow] = XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected, $ext);
    }

    function add_votes($params) 
    { 
        $this->get_categories('add_votes', $params['parent']); 
    }

    function save_category($params)
    {
        if($id = $this->init_votesgroup($params['data']['basic']))
        {
            $this->result['is_saved'] = true;
        }
    }

    function save_edited_category($parameters)
    {
        if($this->reinit_formsgroup($parameters['id'], $parameters['data']['basic'], $parameters['data']))
        {
            $this->result['is_saved'] = true;
        }
    }
              

    function delete_votes($id)
    {
        global $TDB;
        $query = 'DEELETE FROM votes_params WHERE vote_id = ' . $id;
        $query .= ';DELETE FROM votes_server WHERE vote_id = ' . $id;
        $TDB->get_results($query);    
        $this->result['isDel'] = $TDB->result;
    }

    function delete_obj($data)
    {
        if(is_array($data['id']))
        {
            foreach ($data['id'] as $id)
            {
                if($this->_tree->DelNode($id))
                {
                    $this->result['deleted'][] = $id;
                    $this->delete_votes($id);
                }
            }
        }
        else
        {
            if($this->_tree->DelNode($data['id']))
            {
                $this->result['deleted'][] = $data['id'];
                $this->delete_votes($data['id']);
            }
        }
    }

    function show_edit_form($params)
    {
        $this->result['forms_data'] = $this->_common_obj->select_forms($params['id']);
        $this->get_categories('forms_data', $this->result['news_data']['ctg_id'], false);
    }

    function load_category($params)
    {
        $this->result['category_data']['category'] = $this->_tree->GetNodeInfo($params['id']);
    }
      
    function load_vote_info($params)
    {
        global $TMS, $_PATH;

        $node=$this->_tree->getNodeInfo($params['id']);
        $this->result['votes_data']['basic'] = $node['basic'];
        $this->result['votes_data']['question'] = $node['params']['question'];
        $this->result['votes_data']['category'] = $node['params']['category'];
        $this->result['votes_data']['date1'] = $node['params']['date1'];
        $this->result['votes_data']['date2'] = $node['params']['date2'];
        $this->result['votes_data']['another_variant'] = $node['params']['another_variant'];
        $this->result['votes_data']['vote_multiple'] = $node['params']['vote_multiple'];
    }              

    function load_initial_votes_data($params)
    { 
        $this->load_categories($params['group_id']);
    }

    function load_categories($category_selected)
    {
        $this->result['votes_data']['category'] = XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,true);
    }

    function load_votes($params)
    {
        global $TMS,$TDB;
        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'fields.html'));
        $vars = $TDB->get_results('SELECT * FROM votes_params WHERE vote_id = '  .$params['id'] . ' ORDER BY ord');
        foreach($vars as $v)
        {
            $this->result['variants'][] = array('ord' => $v['ord'], 'id' => $v['var_id'], 'value' => $v['text']);    
        }
    }        
    
    function save_votes($parameters)
    {
        global $TDB;
        $parameters['main']['LastModified'] = time();
        $id = $this->init_votes($parameters['main']['category'], $parameters['main']['basic'], $parameters['main']);                      
        $this->push_vars($parameters['variants'],$id); 
    }
    
    function push_vars($arr,$vote_id)
    {
        global $TDB;

        $TDB->get_results('DELETE FROM votes_params WHERE vote_id = ' . $vote_id);
        foreach($arr as $v)
        {
            $q[] = "(NULL, {$vote_id},'{$v[ord]}', '{$v[var_id]}', '{$v[value]}')";
        }
        $query = 'INSERT INTO votes_params VALUES ' . implode(',', $q);
        $TDB->get_results($query);
        return $TDB->result;
    }
        
    function save_edited_votes($parameters)
    {   
        $c = count($parameters["variants"]);
        for ($i = 0; $i < $c; $i++) {
            $parameters["variants"][$i]["value"] = htmlspecialchars($parameters["variants"][$i]["value"]);
        }
        if($id = $this->reinit_form($parameters['id'], $parameters['main']['basic'], $parameters['main'])&&$this->push_vars($parameters['variants'],$parameters['id']))
        {
            $this->result['is_saved']=true;
        }
    }        

    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj', '_tree', '_module_name'));
    }

    function init_votes($anc, $basic, $data)
    {
        $data['LastModified'] = time();
        $id = $this->_tree->InitTreeOBJ($anc, $basic, '_VOTES', $data, true);
        return $id;
    }
        
    function reinit_form($id, $basic, $data)
    {
        $data['LastModified'] = time();
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->_tree->SetUniques($uniq_param);
        $id = $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
    }       

    function init_votesgroup($basic)
    {
        $data['LastModified'] = time();
        $id = $this->_tree->InitTreeOBJ(1, $basic, '_VOTESGROUP', $data, true);
        return $id;
    }

    function reinit_formsgroup($id, $basic, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
    }

    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }   
    
  
    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'), $parameters['selected'], true);
    }

    function load_xlist_data($parameters)
    {
        $TD = Common::inc_module_factory('TTreeSource');

        $options['startNode'] = $parameters['anc_id'];
        $options['shownodesWithObjType'] = array('_VOTESGROUP', '_VOTES');
        $options['columnsAsParameters'] = array('LastModified' => 'LastModified');
        $options['columnsAsStructs'] = array('name'  => 'basic', 'image' => 'obj_type');
        $options['transformResults']['image'] = array('_VOTESGROUP' => 'group','_VOTES' => 'page');
        $options['selectable'] = array('image' => array('_VOTES'));

        $this->result['data_set'] = null;

        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result = array_merge_recursive($TD->result, $this->result);
    }        

    function get_action_properties($parameters)
    {
        global $TMS,$Adm;
              
        if(array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {                                
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
            
            switch($parameters['Action'])
            {  
                case 'show_vote':
                    $this->result['action_properties'] = true;
                    $this->result['xlist'] = true;        
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                    $this->result['action_properties_form']['page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_vote_server'), 'id', 'params', 'Name'), false, true);             
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    break;
                case 'show_random_vote':
                    $this->result['action_properties'] = true;
                    $this->result['xlist'] = false;        
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                    $this->result['action_properties_form']['page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_vote_server'), 'id', 'params', 'Name'), false, true);             
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    break;                    
                case 'show_vote_server':
                    $this->result['xlist'] = false;
                    $this->result['action_properties'] = true;        
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true); 
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
            }
        }
    }
}
?>