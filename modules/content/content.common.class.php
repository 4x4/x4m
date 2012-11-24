<?php

class content_module_common extends x3_module_common
{
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new content_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }                            
        
    function content_module_common($front_call)
        {
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        $this->set_obj_tree('content_container',true);

        if (!$front_call)
            {
            $this->obj_tree->UniqueBasics(1);
            //объявляем объекты
            $this->obj_tree->setObject('_ROOT', array('LastModified'));
            $this->obj_tree->setObject('_CONTENTGROUP', array
                (
                'Name',
                'Title',
                'Keywords',
                'Description',
                'Disable',
                'LastModified',
                'Template',
                'description',
                'Destination_page',
                'isOuterLink',
                'view_group'
                ),                     '_ROOT');

            $this->obj_tree->setObject('_CONTENT', array
                (
                'Name',
                'Title',
                'Keywords',
                'Description',
                'Disable',
                'LastModified',
                'Template',
                'Cache'
                ),                     '_CONTENTGROUP');

            $this->obj_tree->setObject('_FIELD', array('field_value'), '_CONTENT');
            }

        $this->define_front_actions();
        }

    function  show_content_extra($params)
    {
        $this->obj_tree->GetFullBonesUp($params['contentId']);
            if ($this->obj_tree->FullBonesMas)
            {
                $params['_Extra']=$params['showContentName'] = implode('/', XARRAY::arr_to_lev($this->obj_tree->FullBonesMas, 'id', 'params', 'Name'));
            }
        $this->obj_tree->FullBonesMas = null;
        
        return $params;
    }
        
    function get_fields_data($id)
    {
        return XARRAY::arr_to_lev($this->obj_tree->GetChildsParam($id, array('field_value'), true),'basic','params','field_value');
    }

    function define_front_actions()
    {
        $l = Common::get_module_lang('content',$_SESSION['lang'],'define_front_actions');
        $this->def_action('show_content', $l['{show_content}'], '');
        $this->def_action('show_content_announce', $l['{show_content_announce}'],'');
        $this->def_action('show_contents_list', $l['{show_contents_list}'],'');
        $this->def_action('show_contentgroups_list', $l['{show_contentgroups_list}'],'');
        $this->def_action('content_server', $l['{content_server}'], array('showcontent','show_content_announce','show_contents_list','show_articles'));
    }
        
        
}

?>