<?php
class forms_module_xfront extends forms_module_front
{
    function executex($action, $acontext)
    {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result = $this->result;
    }
}
?>