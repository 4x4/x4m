<?php
class catalog_module_cron extends catalog_module_back
    {        
     
        function catalog_module_cron()
        {
            parent::catalog_module_back();
            $this->common_call();   
        }
        
        
        function cron_catalog_import($params)
        {
            global $X3_SESSION;
            
            
            if(!$X3_SESSION->data['sdata']['step'])
            {                
                $params['step']='parse';    
            }
            $params['cron']=true;
            
            
            $this->setSessionHandler($X3_SESSION->data);
            
            $this->importXLS($params);
            
            $X3_CRON     =x3_cron::getInstance(); 
            
            if($this->result['dataWriteEnd'])
            { 
                /*stopping chain*/            
                $X3_CRON->set_chain(false);
            
            }else{
              
                /*starting chain*/                
                $X3_CRON->set_chain(true);
            }
            
        }
        
      
    }
?>