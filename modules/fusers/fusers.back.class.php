<?php
class fusers_module_back
{
    var $last_cached_template;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_roles_tree;
    var $_tree;

    function fusers_module_back()
    { 
        $this->_module_name = 'fusers'; 
        $this->common_call();
    }

    function common_call()
    {
        $this->_common_obj =& fusers_module_common::getInstance();
        $_module_name = 'fusers';
        $this->_tree =& $this->_common_obj->obj_tree;
        
        
    }

    

    function executex($action,$acontext)
    {
        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct = $this->lct;   
        $acontext->result = $this->result;
    }
        
        
    function user_active($params)
    {

        $this->_tree->WriteNodeParam($params['id'],'Active',$params['state']);
        
    }    
        
    function get_tree_inheritance()
    {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
    }

    function check_uniq($params)
    {          
        if(is_array($this->Search(false,false,array('basic' => $params['username'], 'obj_type' => '_USERS'))))
            return false;
        
        return true;
    }

    function show_new_fuser()
    {                                                                             
        $this->result['fuser_data']['FUserGroup'] = $this->load_groups();
        $this->result['fuser_data']['DiscountScheme'] = $this->getDiscountsSelector();
    }
    
    function load_fuser_group($data)
    {       
        $this->result['fusergroup']['Name'] = $this->_tree->ReadNodeParam($data['id'],'Name');                  
        $this->result['fusergroup']['DiscountScheme'] = $this->getDiscountsSelector($this->_tree->ReadNodeParam($data['id'],'DiscountScheme'));       
    }
    
    function changeAncestor($parameters)
    {

        $this->result['dragOK'] = $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'], $parameters['relative']);
    }
    
    function load_groups($selected = null)
    {    
        return XHTML::arr_select_opt(XARRAY::arr_to_lev($this->_tree->GetChildsParam(1,array('Name'), true),'id','params','Name'),$selected,true);
    }
    
    function getDiscountsSelector($userDiscount = null)
    {
        if(Common::is_module_exists('ishop'))
        {                
            Common::call_common_instance('ishop');
            $ishop =& ishop_module_common::getInstance();                
            return XHTML::arr_select_opt($ishop->getDiscoutsSchemes(),$userDiscount,true);
        }
    }

    function load_fuser_data($params)
    {
        if(is_array($params))
        {
            $user = $this->_tree->getNodeInfo($params['id']);
            $user['params']['basic'] = $user['basic'];
            //дополнительные данные дл€ регистрации
            if($childs = $this->_tree->GetChildsParam($params['id'], '%'))
            {
                $user['params']['extdata'] = current($childs);
            }
            
            //—хемы скидок         
           $user['params']['DiscountScheme'] = $this->getDiscountsSelector($user['params']['DiscountScheme']);       
           unset($user['params']['Password']);     
           $this->result['fuser'] = $user['params'];        
        }
    }
    
    function save_new_fuser($params)
    {             
        if(is_array($params))
        {           
            $params['data']['Password'] = md5(strrev($params['data']['Password']));
            $basic = $params['data']['UserName'];                   
            $params['data']['Active'] = 1;
            $this->_common_obj->init_fuser($params['data']['FUserGroup'],$basic,$params['data']);           
        }
    }
    
    function save_fuser($params)
    {    
        if(is_array($params))
        {    
            if(trim($params['data']['Password'])&&trim($params['data']['passwordAgain']))
            {
                $params['data']['Password'] = md5(strrev($params['data']['Password']));                  
            }
            else
            {
                unset($params['data']['Password']);
            }
            $this->_common_obj->reinit_fuser($params['id'],$params['data']);
        }    
    }
    
    function save_fusergroup($params)
    {                                        
        if(!$params['id']){
            $this->_common_obj->init_fusersgroup($params['data']);
        }
        else{
            
            $this->_common_obj->reinit_fusersgroup($params['id'],$params['data']);
        }
    }

    function get_rights($id) {}           
              
    function edit_tunes()
    {          
        $data = $this->_tree->GetNodeInfo(1);         
        $this->result['tunes']['DefaultUnregisteredGroup'] = $this->load_groups($data['params']['DefaultUnregisteredGroup']);
        $this->result['tunes']['DefaultRegisteredGroup'] = $this->load_groups($data['params']['DefaultRegisteredGroup']);
    }  
     
    function save_tunes($params)
    {
        $this->_tree->WriteParamPack(1,$params['tunes']);     
    }
    

        
    function fusers_table($params)
    {
                         
        $TD = Common::inc_module_factory('TTreeSource');
        $options['startNode'] = $params['id'];
        $options['shownodesWithObjType'] = array('_FUSER');
        $options['columnsAsParameters'] = array('name' => 'Name','fio'=>'Email','active'=>'Active');
        $options['preventDots'] = true;
        $options['columnsAsStructs'] = array('id' => 'id','basic'=>'basic');
        $options['gridFormat']=1;

        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
            

        $TD->CreateView($params['id']);
        $this->result = $TD->result;
    }

    
    function delete_obj($data)
    {
        if(is_array($data['id']))
        {
            foreach($data['id'] as $id)
            {
                if($this->_tree->DelNode($id))
                {
                    $this->result['deleted'][] = $id;
                }
            }
        }
        else
        {
            if($this->_tree->DelNode($data['id']))
            {
                $this->result['deleted'][] = $data['id'];
            }
        }
    }
        
    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),$parameters['selected'],true);
    }
 
    function get_action_properties($parameters)
    {
        global $TMS,$Adm;

        if(array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
            
            switch($parameters['Action'])
            {
                case 'show_auth_panel':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list('fusers',array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);
                    $this->result['xlist'] = true; 
                    
                    Common::call_common_instance('pages');
                    $pages=&pages_module_common::getInstance();
                    $this->result['action_properties_form']['UserPanelPage'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('user_panel'),'id','params','Name'),false,true);
                    $this->lct['action_properties'] = $TMS->ParseSection($parameters['Action']);
                    break;

                case 'user_panel':
                    $this->result['action_properties'] = true;
                    $this->result['xlist'] = true;   

                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);
                
                    $this->lct['action_properties']=$TMS->ParseSection('user_panel');
                    break;
            }
        }
    }

        
    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj','_tree',));
    }
}
?>