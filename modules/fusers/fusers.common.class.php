<?php
class fusers_module_common extends x3_module_common
{
    static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new fusers_module_common($front_call);
        } 
        return self::$instance;
     }
     
     
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }                            
        
     function fusers_module_common()
    {
        
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        $this->module_name='fusers';
        $this->set_obj_tree('fusers_container');
        //$this->obj_tree->UniqueBasics(1);
        $this->obj_tree->setObject('_ROOT', array('LastModified','DefaultUnregisteredGroup','DefaultRegisteredGroup'));
        $this->obj_tree->setObject('_FUSERSGROUP', array('Name','DiscountScheme','Comments'),'_ROOT');
        
        //!имя это basic
        $this->obj_tree->setObject('_FUSER', array('ancuser', 'LastVisit', 'Password', 'Name', 'Email', 'Company','Address','DiscountScheme', 'Site', 'Phone', 'ShopCode', 'Active',  'VerificationCode', 'Sex', 'Avatar', 'BlogPage', 'Comment'), '_FUSERSGROUP');        
        $this->obj_tree->setObject('_FUSEREXTDATA',null,'_FUSER');
        $this->obj_tree->setObject('_SCHEMEITEM',array('Module','Node','Rights'),'_FUSERSGROUP');    
        $this->define_front_actions();            
    }
    
    
    function load_users_list()
    {
        return $this->obj_tree->get_nodes_by_obj_type(array('_FUSERS'));
    }
       
     /*$user_group уточняет права для определенной группы пользователей
       $rights уточняет права на определнную группу
     */
    function get_node_rights($node_id,$module,$user_group=null,$rights=1)
    {
        //DebugBreak();
        if($user_group)
        {
            $user_group = array('ancestor'=>$user_group);
        }
        return  $this->obj_tree->Search(array('Module'=>$module,'Node'=>$node_id,'Rights'=>$rights), true,$user_group);
        //return $this->obj_tree->JoinSearch(array(array('Module',$module),array('Node',$node_id),array('Rights',$rights)),$user_group);       
    }
        
    function init_fusersgroup($data)
    {
        return $this->obj_tree->InitTreeOBJ(1, '%SAMEASID%', '_FUSERSGROUP', $data, true);
    }

    function reinit_fusersgroup($id,$data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->ReInitTreeOBJ($id,'%SAME%', $data, $uniq_param);
    }
              
    function init_scheme_item($id,$data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->InitTreeOBJ($id, '%SAMEASID%', '_SCHEMEITEM', $data);
    }

    function init_fuser($id, $basic, $data)
    {
        $uniq_param['uniquetype'] = 'unique_in_tree';
        $uniq_param['uniqueOn'] = '_FUSER';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->InitTreeOBJ($id, $basic, '_FUSER', $data);
    }
            
    function init_fuserextdata($id,$data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->InitTreeOBJ($id, '%SAMEASID%','_FUSEREXTDATA',$data);
    }
    
    function reinit_fuserextdata($id, $data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->ReInitTreeOBJ($id,'%SAME%',$data,$uniq_param,'_FUSEREXTDATA');        
    }
        
    function reinit_fuser($id, $data)
    {
        $uniq_param['uniquetype'] = 'unique_in_tree';
        $uniq_param['uniqueOn'] ='_FUSER';
        $this->obj_tree->SetUniques($uniq_param);
        return $this->obj_tree->ReInitTreeOBJ($id,'%SAME%',$data);
    }

   
            
    function define_front_actions()
    {
        
        $l = Common::get_module_lang('fusers', $_SESSION['lang'], 'define_front_action'); 
        $this->def_action('show_auth_panel', $l['{show_auth_panel}'],'show_auth_panel');
        $this->def_action('show_users_from_group', $l['{show_users_from_group}'],'show_users_from_group');
        $this->def_action('user_panel',$l['{user_panel}'],array('needauth','submitsubuser','registration','forgotpassword','logout','edituser','auth','save_profile','userpanel','submituser','editsubusers','addsubuser','deletesubuser','editsubuser','verifyuser'));               
        $this->set_action_priority('show_auth_panel', array('auth' => 0,'logout'=>0));
    }
    
}
?>