<?php

class banners_module_common
    extends x3_module_common
    {
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new banners_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }                            


    function banners_module_common()
        {

        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        $this->set_obj_tree('banners_container',true,true);
        //$this->obj_tree->UniqueBasics(1);
                $this->obj_tree->setObject('_ROOT',array('LastModified'));                   
                $this->obj_tree->setObject('_BANNERSGROUP',array('LastModified','maxsize','height','width'),'_ROOT');       
                $this->obj_tree->setObject('_BANNERS',array('LastModified','basic','file_name','category','banner_type','date1','date2','times_to_show','flash_text','link','width','height', 'alt', 'page', 'pageId','use_page'),'_BANNERSGROUP');        

        $this->define_front_actions();     
        }
    
    
        function define_front_actions()
        {
            $l=Common::get_module_lang('banners',$_SESSION['lang'],'define_front_actions');       
            $this->def_action('show_selected_banner',$l['{show_selected_banner}'],'ai_show_selected_banner');
            $this->def_action('show_random_banner',$l['{show_random_banner}'],'ai_show_random_banner');
            $this->def_action('show_banners_from_group',$l['{show_banners_from_group}'],'ai_show_banners_from_group');
        }
    } #endclass    
?>