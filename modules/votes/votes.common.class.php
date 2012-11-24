<?php
class votes_module_common extends x3_module_common
{
   
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new votes_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }

    function votes_module_common()
    {
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();    
        
        $this->set_obj_tree('votes_container');
        $this->obj_tree->setObject('_ROOT',array('LastModified'));       
        $this->obj_tree->setObject('_VOTESGROUP',array('LastModified', 'basic'), '_ROOT');
        $this->obj_tree->setObject('_VOTES',array('LastModified', 'category', 'basic', 'vote_multiple', 'another_variant', 'date1', 'date2', 'question'), '_VOTESGROUP');
        
        $this->define_front_actions();
    }    
             
    function define_front_actions()
    {        
        $l = Common::get_module_lang('votes',$_SESSION['lang'],'define_front_actions');
        
        $this->def_action('show_vote', $l['{show_vote}'], 'ai_show_vote');
        $this->def_action('show_vote_server', $l['{show_vote_server}'], array('showvresult', 'addvote'));
        $this->def_action('show_random_vote', $l['{show_random_vote}'], 'ai_show_random_vote');         
    }
}
?>
