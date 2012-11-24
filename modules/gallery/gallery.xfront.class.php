<?php
class gallery_module_xfront extends gallery_module_front
{
    function executex($action, $acontext)
    {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result = $this->result;
    }
    
   
}
?>