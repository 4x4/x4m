<?php
class gallery_module_common extends x3_module_common
{    

    static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new gallery_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        } 
        
    function __construct()
    {
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        
        $this->set_obj_tree('gallery_container', true);
        $this->obj_tree->UniqueBasics(1);

        $this->obj_tree->setObject('_ROOT', array('LastModified', 'Name', 'basic'));
        $this->obj_tree->setObject('_GALLERY', array( 'Name', 'basic', 'LastModified','gallery_short','thumb_width','compress','group','Destination_page'),'_ROOT');
        $this->obj_tree->setObject('_ALBUM', array( 'Name', 'Basic', 'LastModified','gallery_short','thumb_width','Avatar','Destination_page','isOuterLink', 'info', 'counter'),'_GALLERY');
        $this->obj_tree->setObject('_PHOTO', array( 'Name','LastModified','image','category','info','changed' ),'_ALBUM');        

        $this->define_front_actions();     
        }
    
    
    function define_front_actions()
    {
        $l = Common::get_module_lang('gallery', $_SESSION['lang'], 'define_front_actions'); 
        
        $this->def_action('show_gallery_list', $l['{show_gallery_list}'], 'ai_show_gallery_list');
        $this->def_action('show_selected_album',$l['{show_selected_album}'], 'ai_show_selected_album');
        $this->def_action('show_gallery_server',$l['{show_gallery_server}'], array('show_album'));
        $this->def_action('show_from_folder',$l['{show_from_folder}'], array('show_from_folder'));
    }
    
    function get_node($page_id)
    {
        return $this->obj_tree->getNodeInfo($page_id);
    }

    function get_page_slotz($page_id) 
    {
        return $this->obj_tree->GetChildsParam($page_id, $this->obj_tree->getObject('_SLOT'), true, array('obj_type' => array('_SLOT'))); 
    }

    function get_tpl_slotz($tpl_name)
    {
        $tpl_tree = new Tree($_DB['DB_NAME'], 'template_container');

        if($tpl_id = $tpl_tree->FindbyBasic(1, $tpl_name))
        {
            return $tpl_slotz=$tpl_tree->GetChildsParam($tpl_id[0], array('SlotAlias'), true);
        }
    }

}
?>
