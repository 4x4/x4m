<?php

class forms_module_tpl extends x3_module
{
    
    public function __construct()
    { 
        global $TMS;
        $TMS->registerHandlerObj($this->_module_name, $this);
    }

   public function values_count($params)
   {
       if(is_string($params[0]))
       {
           $delimiter = ($params[1]) ? $params[0] : "\n";
           $size = sizeof(explode($delimiter, $params[0]));
       }
       else if(is_array($params[0]))
       {
           $size = sizeof($params[0]);
       }

       return ($size > 4) ? 4 : $size;
   }
   
   
   public function get_request_param($params)
   {
       return $_REQUEST[$params[0]];
   } 

}

?>