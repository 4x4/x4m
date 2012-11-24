<?php
class banners_module_tpl extends x3_module
{
    //aliasSets
    
    public function __construct()
    { 
        global $TMS;
        $TMS->registerHandlerObj($this->_module_name, $this);   
    }


    function skip_stats(){$this->skip_stats=true;}
}
?>