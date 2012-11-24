<?php
class faq_module_common
    extends x3_module_common
    {
        
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new faq_module_common($front_call);
        } 
        return self::$instance;
     }
     
     public $bones_path;
     public $cache_cats;
     
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }                            

    function faq_module_common()
        {
            if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
            parent::x3_module_common();
            $this->module_name='faq';
            $this->set_obj_tree('faq_container',true);
            
            $this->obj_tree->setObject('_ROOT',array('LastModified','Name'));       
            $this->obj_tree->setObject('_FAQFOLDER',array('LastModified', 'Name', 'description'),'_ROOT');
            $this->obj_tree->setObject('_FAQGROUP',array('LastModified','basic','email','hidden','description', 'Name','count', 'moderation', 'answer_template'),'_FAQFOLDER');
            
            $this->define_front_actions();
        
        }    
             
        function define_front_actions()
        {        
            $l=Common::get_module_lang('faq',$_SESSION['lang'],'define_front_actions');
            $this->def_action('show_categories',$l['{show_categories}'],'');
            $this->def_action('show_selected_category',$l['{show_selected_category}'],'');
            $this->def_action('show_folder_questions',$l['{show_folder_questions}'],'');
            $this->def_action('show_faq_server',$l['{show_faq_server}'],array('show_question'));
            $this->def_action('show_faq_search',$l['{show_faq_search}'],'');
            $this->def_action('show_faq_search_server',$l['{show_faq_search_server}'],array('faqsearch'));
            
        }
     
     
  
    }
    
    



?>
