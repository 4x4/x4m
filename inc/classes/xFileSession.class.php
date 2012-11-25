<?php
class xFileSession 
{

var $sessionid;
var $data= Array();    


    public function get_session_id()
    {
        return $this->sessionid;
    }    

    public function set_shutdown_serialize()
    {
        register_shutdown_function(array($this,"serialize_session"));        
    }
     
     function __construct($id=null)
     {
         
         if(($id)or($_REQUEST['x3sid']))
         {
                $this->sessionid = (empty($_REQUEST['x3sid'])) ? $id : $_REQUEST['x3sid'];                
                $this->data=xCache::serializedRead('session',$this->sessionid,0);               
                
         }else{
                $this->sessionid=md5(time());                 
         }
            
     }
    /*
     * shutdown will serialize all data 
    */
        function serialize_session()
    {        
            xCache::serializedWrite($this->data,'session',$this->sessionid);           
    }


}
?>