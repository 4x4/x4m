<?php
class usersCommon
    extends xCommon implements xCommonInterface
    {
    
    public $_useTree=true;
     
    function usersCommon()
        {
        
        parent::__construct(__CLASS__);  
        
        $this->_tree->setObject('_ROOT', array('LastModified'));
        //'Roles'-serialized array

        $this->_tree->setObject('_USERGROUP', array
            (
            'LastModified',
            'Name',
            'Roles'
            ),array( '_ROOT'));

        $this->_tree->setObject('_SUPERADMIN', array
            (
            'LastModified',
            'Password',
            'Name',
            'Email'
            ),array( '_ROOT'));

        $this->_tree->setObject('_USER', array
            (
                'LastModified',
                'Password',
                'Name',
                'Email'
            
            ), array('_USERGROUP'));
            
            
   /*    $this->_tree->delete()->where(array('@id','>',1))->run();
        

     $id   = $this->_tree->initTreeObj(1, '%SAMEASID%', '_USERGROUP', array(
            'StartPage' => ''
        ));
        
        $this->_tree->initTreeObj(1, 'admin', '_SUPERADMIN', array(
            'Password' => md5(strrev('dexter')),
            'Email'=>'admin@admin.com'
        ));
        
     */   

            
        $this->rolesTree = new xte('usersRoles_container',xRegistry::get('xPDO'));
        
        $this->rolesTree->setObject('_ROOT', array('LastModified'));

        $this->rolesTree->setObject('_ROLE', array('Name'), '_ROOT');

        $this->rolesTree->setObject('_MODULE', array('is_accesible'), '_ROLE');

        $this->rolesTree->setObject('_PERMISSION', array('module','obj_id','read','write','delete','deep'), '_ROLE');
                
        $this->rv=array('update'=>1,'delete'=>2,'add'=>4,'read'=>8);
        
        }
        
    
        
    

    function loadRoles($user_id)
    {   
        $roles_scheme=Array();         
        $node=$this->_tree->GetAncestor($user_id);
        if($node=$this->_tree->getNodeInfo($node))
        {
            
            if(is_array($roles=explode(':',$node['params']['Roles'])))
            {
             $ms=array();                
              foreach($roles as $role_id)
              {
                  if($maccess=XARRAY::arr_to_lev($this->rolesTree->GetChildsParam($role_id, array('is_accesible'),true),'basic','params','is_accesible'))
                  {
                    $ms=array_merge(XARRAY::clear_empty_items($maccess),$ms);
                  }
              
              }
            
              return array($roles,$ms);
            }
        
        }
      
    }
    
    function check_user_rights($action,$module,$obj_id)
    {
        global $TDB;
        $q="SELECT rv FROM `users_rights` WHERE `role_id` in () and `module`='m' and obj_id=''";
    }
    
    function load_users_list($get_superadmin=false)
    {
        if($get_superadmin){$ob=array('_SUPERADMIN','_USERS');}else{$ob=array('_USERS');}
        return $this->_tree->get_nodes_by_obj_type($ob);
    }  
    
      
    function defineFrontActions(){}
        
        
    function checkAndLoadUser($login, $password)
        {
          $login=$this->_tree->selectParams('*')->selectStruct('*')->where(array('@basic','=',$login),array('@obj_type','=',array('_USER','_SUPERADMIN')))->run();
        if ($node=$login[0])
            {
                if ($node['params']['Password'] == md5(strrev($password)))
                    {
                       $_SESSION['user']=array('id'=>$node['id'],'type'=>$node['obj_type'],'email'=>$node['id']['Email']);
                       
                       if($node['obj_type']!='_SUPERADMIN')
                       {
                       /*    $roles=$this->loadRoles($node['id']);
                           list($ro,$ma)=$roles;
                           $_SESSION['user']['roles']=$ro;    
                           $_SESSION['user']['maccess']=$ma;    */
                       
                       }
                           return true;
                       
                    }    
            
            }
        else
            {
            return false;
            }

        }
}

?>