<?php
class subscribe_module_common extends x3_module_common
{
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new subscribe_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        } 
        

    function subscribe_module_common()
    {
       

       if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
       parent::x3_module_common();
            
        $this->set_obj_tree('subscribe_container', true);
        
        $this->obj_tree->setObject('_ROOT', array('LastModified'));       
        $this->obj_tree->setObject('_SUBSCRIBEGROUP', array(
            'LastModified',
            'basic',
            'description',
            'news',
            'files',
            'message',
            'news_cats',
            'html',
            'news_number',
            'news_page',
            'Template',
            'pause',
            'from',
            'theme'), '_ROOT');
        $this->define_front_actions();
    }    
             
    function define_front_actions()
    {        

        $l = Common::get_module_lang('subscribe',$_SESSION['lang'],'define_front_actions');
        $this->def_action('show_subscribe_form', $l['{show_subscribe_form}'], '');
        $this->def_action('subscriber_page', $l['{subscriber_page}'], array('subscriber_page','complete_subscribe','complete_unsubscribe'));
    }
}
?>