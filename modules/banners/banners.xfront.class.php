<?php

class banners_module_xfront extends banners_module_front
{
    function executex($action, $acontext)
        {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result=$this->result;
        }

        
    function   get_banner($params)    
    {
        $this->result['banner']=$this->show_random_banner($params);
    }      
    
}
?>