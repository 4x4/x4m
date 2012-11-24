<?php
class search_module_common extends x3_module_common
{
    function &getInstance($front_call=null)
    {
        static $instance = array();
                   
        if(!$instance)
        {
            $token = '___search_singleton___';
            $GLOBALS[$token] = true;
            $instance[0] = new search_module_common($front_call);
            unset($GLOBALS[$token]);
        }
        return $instance[0];
    }
        
    function search_module_common($front_call)
    {
        $token = '___search_singleton___';

        if(!array_key_exists($token, $GLOBALS))
        {
            trigger_error('singleton can\'t be created initally');
        }
           
        $parent = 'x3_module_common';
        $this->$parent();    
        $this->define_front_actions();
    }
       
    function define_front_actions()
    {
        $l = Common::get_module_lang('search',$_SESSION['lang'],'define_front_actions');
        
        $this->def_action('show_search_form', $l['{show_search_form}'], 'show_search_form');
        $this->def_action('search_server', $l['{search_server}'], array('find'));
    }
}
?>