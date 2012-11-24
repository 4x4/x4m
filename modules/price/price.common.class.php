<?php
class price_module_common extends x3_module_common
{
    
        
    static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new price_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        } 
        
        
        function price_module_common()
        {
        
            if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
            
        $this->set_obj_tree('price_container', true);
        $this->obj_tree->UniqueBasics(1);
        $this->obj_tree->setObject('_ROOT',array('LastModified', 'Name'));                   
        $this->obj_tree->setObject('_PRICEGROUP',array('LastModified','basic', 'description', 'hashed_link', 'DisableAccess'),'_ROOT,_PRICEGROUP');       
        $this->obj_tree->setObject('_PRICE',array('LastModified','basic','file_name','category','hidden','text', 'description', 'image', 'DisableAccess', 'counter', 'hashed_link', 'hash'),'_PRICEGROUP');        
        $this->define_front_actions();     
    }
        
    function get_node($page_id) { return $this->obj_tree->getNodeInfo($page_id); }    

    function get_page_slotz($page_id) 
    {
        return $this->obj_tree->GetChildsParam($page_id,$this->obj_tree->getObject('_SLOT'),true,array('obj_type'=>array('_SLOT'))); 
    }

    function get_tpl_slotz($tpl_name)
    {
        //cоединение с деревом шаблонов
        $tpl_tree = new Tree($_DB['DB_NAME'], 'template_container');
        //поиск шаблона
        if($tpl_id = $tpl_tree->FindbyBasic(1, $tpl_name))
        {
            return $tpl_slotz=$tpl_tree->GetChildsParam($tpl_id[0], array('SlotAlias'), true);
        }
    }



    function define_front_actions()
    {
        $l = Common::get_module_lang('price',$_SESSION['lang'],'define_front_actions');
        $this->def_action('show_price_list', $l['{show_price_list}'], array('ai_show_price_list', 'download'));
        $this->def_action('show_folder', $l['{show_folder}'], array('ai_show_folder', 'download'));
    }
}
?>
