<?php
class xChainCall
{ 
    var $scriptcall;
    var $stop;
    
     public function stop()
     {
         $this->stop=true;
     }
     
     function __construct($scriptcall)
     {
        $this->scriptcall=$scriptcall;       
     } 
     
     
     function _get($type,$host,$port='80',$path='/',$data='') 
     {
        if(!empty($data)) foreach($data AS $k => $v) $str .= urlencode($k).'='.urlencode($v).'&'; $str = substr($str,0,-1);
        $fp = fsockopen($host,$port,$errno,$errstr,$timeout=30);
        if(!$fp) die($_err.$errstr.$errno); else {
            fputs($fp, "POST $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ".strlen($str)."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $str."\r\n\r\n");
           
            fgets($fp,1);
            fclose($fp);
        } return $d;
    } 
     
     public function chain($gparams)
     {
         if(!$this->stop)
         {
             if($gparams)
             {         
                 foreach($gparams as $key=>$param)
                 {
                     $gp[]=$key.'='.$param;   
                 }
                 
                 $gp='&'.implode('&',$gp);
             }

             
             $callto='/'.$this->scriptcall.'?chaincall=1'.$gp;             

             $this->_get('http',$_SERVER['HTTP_HOST'],'80',$callto);
         }
         
         die();
         
     }
     
} 
     
?>     
