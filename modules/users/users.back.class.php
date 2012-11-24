<?php
class users_module_back
    {
    var $last_cached_template;
    var $result;
    var $_moduleName;
    var $_common_obj;
    var $_rolesTree;
    var $_tree;

    function users_module_back() { $this->_module_name='users'; }

    function common_call()
        {
        $this->_common_obj=&users_module_common::getInstance();
        //proxy for tree
        $_module_name='users';
        $this->_tree      =&$this->_common_obj->obj_tree;
        $this->_rolesTree=&$this->_common_obj->rolesTree;
        }

    function execute($action, $parameters = null)
        {        
            $this->common_call();                        
            return   $this->_common_obj->execute(&$this,$action,$parameters);
        }

                           
                   
    function executex($action,$acontext)
    {

        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct = $this->lct;   
        $acontext->result = $this->result;
    }

    function get_groups($selected='') { return  XHTML::arr_select_opt(
                    XARRAY::arr_to_lev(
                     $this->_tree->Search(null,true,array('ancestor'=>1,'obj_type'=>'_USERSGROUP'))
                                               ,'id','params','Name'),$selected,true);
                                 
    }

    function get_tree_inheritance() { $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC; }
    
    function show_new_user()
    {
        $this->result['user_new']['UserGroup']=$this->get_groups();
    }


      function check_uniq($params)
      {          
              if(is_array($this->Search(false,false,array
                (
                "basic"      => $params['username'],
                "obj_type" => '_USERS'
                )))){return false;}else
                {return true;}
                                                                   
          
      }

    function show_new_usergroup() { $this->get_roles_schemes(); }
    
    function show_edit_group($data)
    {  
               
            $node=$this->_tree->getNodeInfo($data['id']); 
            
            $this->result['group_data']['Name']=$node['params']['Name'];
            $roles=explode(':',$node['params']['Roles']);
          
            while(list($k,$v)=each($roles))
            {
                $nroles['_'.$v]=1;        
            }
                    
            $this->result['group_roles']['Roles']=$nroles;
        $this->get_roles_schemes(); 
        

    }
    
        function changeAncestor($parameters)
        {
            //включена проверка на дублирующий basic
            $this->result['dragOK']=    $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],$parameters['relative']);
        }
    

    function load_user_data($params)
    {
              if (is_array($params))
              {
                
                $user=$this->_tree->getNodeInfo($params['userId']);
                unset($user['params']['Password']);
                $user['params']['login']=$user['basic'];
                $this->result['user'] =$user['params'];
              
              }
        
    }
    
    
    function save_new_user($params)
    {             
        if (is_array($params))
        {    
                   $params['data']['Password']=md5(strrev($params['data']['Password']));
                   $basic=$params['data']['UserName'];                   
                   $id=$this->init_user($params['data']['UserGroup'],$basic,$params['data']);
        }
    
    }
    
    
    function save_user($params)
    {              
        if (is_array($params))
        {    
                 if($params['data']['Password']&&$params['data']['PasswordAgain'])
                 {
                   $params['data']['Password']=md5(strrev($params['data']['Password']));                  
                 }else
                 {
                    unset($params['data']['Password']);
                 }
                 $this->reinit_user($params['id'],$params['data']);
        }
    
    }
    

    
    
    function save_usergroup($params)
        {
                    
        if (is_array($params))
            {
            if (is_array($params['rolesdata']))
                    {
                         $params['data']['Roles']=str_replace('_', '', implode(':', array_keys($params['rolesdata'])));
                    }
                
                if($params['id'])
                {
                    $this->reinit_usersgroup($params['id'],$params['data']);
                }else{
                 return   $this->init_usersgroup($params['data']);
                }
            }
        }


    function get_roles_schemes()
        {
       
        if ($s=XARRAY::arr_to_lev($this->_rolesTree->GetChildsParam(1, $this->_rolesTree->getObject('_ROLE'), true),
                                  'id',
                                  'params',
                                  'Name'))
            {
            return $this->result['roles_schemes']=$s;
            }
        }


   function edit_role($data)
        {
            $modules=Common::get_module_list();
            $this->result['roles_editor']['Name']=$this->_rolesTree->ReadNodeParam($data['id'],'Name');            
            if($data['id']&&$maccess=$this->_rolesTree->GetChildsParam($data['id'], array('is_accesible'),true,array('obj_type'=>array('_MODULE'))))
            {      
                $maccess=XARRAY::arr_to_lev($maccess,'basic','params','is_accesible');
                
                foreach($modules as $module)
                {
                        $this->result['maccess'][$module['name']]=$maccess[$module['name']]?1:0;
                }
            }else{   
                        $this->result['maccess']=XARRAY::arr_to_keyarr($modules, 'name','_'); 
            }       
            
        }

        
       function set_permission($params)
       {          
          $attributes=array(2=>'read',3=>'write',4=>'delete',5=>'deep');      
           
           if($perms=$this->_rolesTree->Search(array('obj_id'=>$params['obj_id'],'module'=>$params['module'],false,array('ancestor'=>$params['scheme_id']))))
           {
               
           }else
           {                                  
             $params['state']=(int)$params['state'];              
             $data[$attributes[$params['attribute']]]=$params['state'];
             $data['obj_id']=$params['obj_id'];
             $data['module']=$params['module'];
             $this->init_permission($params['scheme_id'],$data);    
           }
       }
       
       function  show_permissions($params)
       {
            $this->get_roles_schemes();

            if($this->result['roles_schemes'])
            { 
                foreach($this->result['roles_schemes'] as $id=>$scheme)
                {
                   $perms=$this->_rolesTree->Search(array('obj_id'=>$params['id'],'module'=>$params['module'],false,array('ancestor'=>$id)));
                    
                   $rtable[$id]=array('id'=>$id,'sheme'=>$scheme,'read'=>$perms[0]['read'],'write'=>$perms[0]['write'],'delete'=>$perms[0]['delete'],'deep'=>$perms[0]['deep']);
                   
                }
                
                
             $this->result['data_set']=$this->gridformat($rtable);
            }
       }
       
        
   function gridformat($page_array,$idx)
    {
        while(list($k,$v)=each($page_array))
        {
            $result['rows'][$k]=array('data'=>array_values($v));
        }
        return  $result;
    }
        
       


    function roles_table($parameters)
        {

        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];
        $options['shownodesWithObjType']=array('_ROLE');
        $options['columnsAsParameters']=array('name' => 'Name');
        $options['preventDots']=true;
        $options['gridFormat']=1;
        $options['columnsAsStructs']=array('id' => 'id');

        //_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=Array();

        $TD->init_from_source($this->_common_obj->rolesTree);
        $TD->setOptions($options);
        $TD->CreateView(1);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

    function del_role($data)
        {
            if(is_array($data['id']))
            {
                foreach($data['id'] as $id){$this->_rolesTree->DelNode($id);}
                $this->result['deleted']=true;
            }
        }

        
        
    function save_role($data)
        {
        
        if ($data['id'])
            {
                $this->reinit_role($data['id'], $data['formdata']);
                $this->_rolesTree->DelNode($data['id'], true);
                $id=$data['id'];
            }
        else
            {
                $id=$this->init_role($data['formdata']);
            }

        $this->_rolesTree->DelNode($id,true);
        
        if (is_array($data))
        {
            foreach ($data['maccess'] as $module => $enabled)
                    {
                        $this->init_module($id, $module,array('is_accesible'=>$enabled));
                    }
            }
        }

    function delete_obj($data)
        {
        if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                if ($this->_tree->DelNode($id))
                    {
                    $this->result['deleted'][]=$id;
                    }
                }
            }
        else
            {
            if ($this->_tree->DelNode($data['id']))
                {
                $this->result['deleted'][]=$data['id'];
                }
            }
        }

        
        
     function create_super_admin($data)
        {        
         if($childs=$this->_tree->get_nodes_by_obj_type(array('_SUPERADMIN')))
         {
             foreach ($childs as $child){
             $this->_tree->DelNode($child['id']);   
             }
         }
   
        $uniq_param['uniquetype']='unique_in_tree';        
        $data['Password']=md5(strrev($data['Password']));        
        $uniq_param['uniqueOn']  =array('_SUPERADMIN','_USERS');
        $this->_tree->SetUniques($uniq_param);
        return $this->_tree->InitTreeOBJ(1,$data['Name'] , '_SUPERADMIN', $data);
        }

        
    function init_usersgroup($data)
        {
        $id=$this->_tree->InitTreeOBJ(1, '%SAMEASID%', '_USERSGROUP', $data, true);
        return $id;
        }

    function reinit_usersgroup($id,$data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_tree->SetUniques($uniq_param);
        return $this->_tree->ReInitTreeOBJ($id,'%SAME%', $data, $uniq_param);
        }

    function init_role($data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_rolesTree->SetUniques($uniq_param);
        return $this->_rolesTree->InitTreeOBJ(1, '%SAMEASID%', '_ROLE', $data);
        }
        
        
    function init_permission($ancestor,$data)
        {
            $uniq_param['uniquetype']='unique_in_anc';
            $this->_rolesTree->SetUniques($uniq_param);
            return $this->_rolesTree->InitTreeOBJ($ancestor, '%SAMEASID%', '_PERMISSION', $data);
        }
        
        
    function reinit_permission($id, $data)
        {
            $uniq_param['uniquetype']='unique_in_anc';
            $this->_rolesTree->SetUniques($uniq_param);
            return $this->_rolesTree->ReInitTreeOBJ($id, '%SAME%', $data, $uniq_param);
        }

    function init_module($id,$basic,$data)
        {
            $uniq_param['uniquetype']='unique_in_anc';
            $this->_rolesTree->SetUniques($uniq_param);
            return $this->_rolesTree->InitTreeOBJ($id,$basic,'_MODULE', $data);
        }
        
        

    function reinit_role($id, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_rolesTree->SetUniques($uniq_param);
        return $this->_rolesTree->ReInitTreeOBJ($id, '%SAME%', $data, $uniq_param);
        }

    function init_user($id,$basic,$data)
        {
        $uniq_param['uniquetype']='unique_in_tree';
        $uniq_param['uniqueOn']  ='_USERS';
        $this->_tree->SetUniques($uniq_param);
        return $this->_tree->InitTreeOBJ($id, $basic, '_USERS', $data);
        }

    function reinit_user($id, $data)
        {
            $uniq_param['uniquetype']='unique_in_tree';
            $uniq_param['uniqueOn']  ='_USERS';
            $this->_tree->SetUniques($uniq_param);
             return $this->_tree->ReInitTreeOBJ($id,'%SAME%',$data);
        }

   
    }
?>