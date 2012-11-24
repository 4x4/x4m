<?php

class expanderListener extends xListener  implements xPluginListener
{
    public function __construct()
    {

        parent::__construct('catalog.expander');
        
        $this->_EVM->on('agregator:start','mytest',$this);
    }  
    
    public function mytest()
    {
        
       
    }
    
}

?>