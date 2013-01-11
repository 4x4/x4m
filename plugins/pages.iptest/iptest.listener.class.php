<?php

class iptestListener extends xListener  implements xPluginListener
{
    public function __construct()
    {                    
      
          parent::__construct('pages.iptest');
          $this->_EVM->on('pages.xfront:afterInit','mytest',$this);
          $this->useModuleTplNamespace();
          $this->useModuleXfrontNamespace();
    }  
    
    public function mytest()
    {
        
    }
    
}

?>