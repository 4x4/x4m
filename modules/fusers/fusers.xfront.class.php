<?php
class fusers_module_xfront extends fusers_module_front
{
    function executex($action, $acontext)
    {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result=$this->result;
    }
    
    
    function changecurrency($params)
    {
        
        
        $_SESSION['siteuser']['extuserdata']['currency']=$params['cur'];
        
    }
   
   
   function loginUser($params)
   {
        
       $this->result['loginUser']=$this->check_and_load_user($params['data']['login'],$params['data']['password']);
   }

       
  function send_link($params)  
  {                     
                        global  $_COMMON_SITE_CONF;                        
                        $m=Common::inc_module_factory('Mail');        
                        $m->From($_COMMON_SITE_CONF['admin_email']);
                        $m->To($params['email']);
                        $m->Content_type('text/html');

                        $m->Subject('ссылка с сайта '.HOST.' от '.$_SESSION['siteuser']['userdata']['Name']);  
                        $m->Body(                        
                        '«Здравствуйте, ваш коллега '.$_SESSION['siteuser']['userdata']['Name'].' из компании '.$_SESSION['siteuser']['userdata']['Company'].' просматривая ассортимент бизнес-сувениров на сайте <a href="upress.by">upress.by</a>  решил рекомендовать вам следующий товар: 
                        <a href='.$params['href'].'>'.$params['href'].'</a>','windows-1251');    
                        $m->Priority(1) ;      
                        $m->Send();                               
                        $this->result['ok']=true;

  }
       
   function regUser($params)
   {

       $params['doNotVerifyUser']=1;
       $result=$this->_submit_user($params);  
              
       if(!$result['errors'])
        {
            $this->result['regUser']=1;
        }else{
            $this->result=$result;
        }
   }
    
    
}
?>