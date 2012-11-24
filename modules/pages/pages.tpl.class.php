<?php
class pagesTpl extends xTpl implements xModuleTpl 
    {
    //aliasSets

    public function __construct($module)
        {
              parent::__construct($module); 
        }
    
    
    
         function echos()
         {

         }
         
         
        function getMenuTreeElement($id)
        {
            return $this->menuSource[$id];    
        }
    
    
        
         function render_module($id)
        {
            if($id[0]['call_dynamic'])$call_dynamic=true;
            
           return $this->_common_obj->render_module($id[0],$call_dynamic);
        }

             
      
}
?>