<?php

class forms_module_common extends x3_module_common
{
    static private $instance;
    
    static function getInstance($front_call = null) 
    {
        if(!self::$instance) 
        {
          self::$instance = new forms_module_common($front_call);
        } 
        return self::$instance;
    }
      
     public final function __clone()
     {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
     }
     
     
     function show_forms_extra($params)
     {
         $this->obj_tree->GetFullBonesUp($params['formsId']);
            if($this->obj_tree->FullBonesMas)
            {
                $params['_Extra']=$params['showformsName'] = implode('/', XARRAY::arr_to_lev($this->obj_tree->FullBonesMas, 'id', 'params', 'Name'));
            }
         $this->obj_tree->FullBonesMas=null;
         return $params;
    }
     
     
     function forms_module_common()
     {
         global $_DB;
         
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        
        $this->set_obj_tree('forms_container', true);
        $this->obj_tree->UniqueBasics(1);
        
        $this->obj_tree->setObject('_ROOT', array('LastModified'));       
        $this->obj_tree->setObject('_FORMSGROUP', array('LastModified', 'Name'), '_ROOT');
        $this->obj_tree->setObject('_FORMS', array('LastModified','category','Name','Disable','subject','email','charset','save_to_server','use_captcha','captcha_settings','async','timeout','comment', 'message_after'), '_FORMSGROUP');
        $this->obj_tree->setObject('_FIELDS', null, '_FORMS');
        
        $this->define_front_actions();
        
     }    
             
        function define_front_actions()
        {        
            $l=Common::get_module_lang('forms', $_SESSION['lang'], 'define_front_actions');
            $this->def_action('show_forms',$l['{show_forms}'],'');    
        }

}

?>