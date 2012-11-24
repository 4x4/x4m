<?php
class  front_api
    { 
    var $result;
       
    function front_api() { }

    function start_mapping()
        {
            XOAD_Server::allowClasses('front_api');       
        }

    
    function xroute($data)
      {          
   
        if(is_array($data))
            {
               foreach ($data as $modulename=>$route_function)
                {      //wake up module

                     if ($module=Common::module_factory($modulename.'.xfront'))
                     {                     
                         //execute + result context
                         $module->executex($route_function,$this);
                     
                     }
                
                }
            }
      }                   
         


   function incroute($data)
      {                    
        if(is_array($data))
            {
               foreach ($data as $modulename=>$route_function)
                {      //wake up module

                     if ($module=Common::inc_module_factory($modulename))
                     {                     
                         //execute + result context
                         $module->executex($route_function,$this);
                     
                     }
                
                }
            }
      }

   
    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array
            (
            'xroute','incroute'
            ));

        XOAD_Client::publicMethods($this, array
            (
            'xroute','incroute'
            ));
        }
    } #endclass
?>
