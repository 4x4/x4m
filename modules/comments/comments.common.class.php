<?php
class comments_module_common
        extends x3_module_common
    {
        
    static private $instance;
  
    private function __construct() {
    
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        
        parent::x3_module_common();
        $this->set_obj_tree('comments_container',1);
        $this->module_name='comments';
      //  $this->obj_tree->UniqueBasics(1);
        
        $this->obj_tree->setObject('_ROOT',array('LastModified'));       
        $this->obj_tree->setObject('_TREAD',array('LastModified','Alias','Active','Moderation','TreadSort'),'_ROOT');
        //Active - disable to show all comments  //Closed - topic closed
        $this->obj_tree->setObject('_COBJECT',array('LastModified','Module','Marker','Active','Closed','CobjectId'),'_TREAD');
        $this->define_front_actions();
    } 
        
     
     static function getInstance() 
     {
        if(!self::$instance) 
        {
          self::$instance = new comments_module_common();
        } 
        return self::$instance;
     }
      
      function get_cobject_by_module($cobj_id,$module)
      {
            if($results=$this->obj_tree->Search(array('CobjectId'=>$cobj_id,'Module'=>$module),true))
            {
                reset($results);  
                return current($results);
            }
          
      }
      
      
      function get_cobject($tread_id,$c_obj_id,$closed='',$active=1)
        {
            if($results=$this->obj_tree->Search(array('CobjectId'=>$c_obj_id,'Active'=>$active),true,array('ancestor'=>$tread_id)))
            {
                reset($results);  
                return current($results);
            }

         
        }
      
      function get_tread_by_name($name)
        {
            if($name)
            {
                if($this->obj_tree->FindbyBasic(1,$name,true))return $this->obj_tree->LastResult;
            }
        }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }
                 
    
    
    function define_front_actions()
        {   
            $l=Common::get_module_lang('comments',$_SESSION['lang'],'define_front_actions');       
            $this->def_action('show_last_comments',$l['{show_last_comments}'],'ai_show_selected_banner');
            $this->def_action('show_guestbook',$l['{show_guestbook}'],'ai_show_selected_banner');
        }
        
        
        
    private function define_back_actions($static_call = false)
        {
    
        static $back_actions;

        $back_actions=array
            (       
            );

        if (!$static_call)
            {
            $this->def_back_action_array($back_actions);
            }
        else
            {
            return $back_actions;
            }
        }
     
     
  
    }
    
    



?>
