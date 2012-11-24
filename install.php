<?php
/*С€Р°Р±Р»РѕРЅРЅР°СЏ СЃРёСЃС‚РµРјР°*/

require_once ('conf/init.php');  
require_once ('inc/core.php');


//global tpl system
$TMS=new TMutiSection();
//global DB layer
$TDB=null;
require_once ('_adm/adm.class.php');
require_once ('inc/xoad/xoad.php');

class x3minstall
    {
    var $result = null;

    function execute($action, $parameters = null)
        {  
        $this->result='';
        if (is_array($action))
            {
            foreach ($action as $act_name => $act_param)
                {
                {
                $this->$act_name($act_param);
                    }
                }
            if($this->result['error'])
            {
                $this->result['errortext']=implode('  ',$this->result['errortext']);
            }
            
            $this->result=XCODE::win2utf($this->result);
            }
        else
            {
            return $this->actions($action, $parameters);
            }
        }

    function start_install()
        {
        global $TMS;
        $TMS->parseSection('first_page', true);
        return $TMS->parseSection('main');
        }

    function x3minstall()
        {
        global $TMS;
        $TMS->AddFileSection('install/install.html');
        }

    function initiate_db_connection($data)
        { 
        global $TDB;        
        $link=mysql_connect($data['db_host'], $data['db_user'], $data['db_password']);
        $db_selected = mysql_select_db($data['db_name'], $link);
        
        
        
        if (!$link||!$db_selected)
            {
                    
                $this->result['error']=true;
                $this->result['errortext'][]='Подключение невозможно,ответ сервера:' . mysql_error();
            
            return false;
            }
        else
            {
            $TDB=new Tmysqllayer($data['db_user'], $data['db_password'], $data['db_name'], $data['db_host']); 
            return true;                  
            }
        }

     function error($text)
     {
                $this->result['error']=true;
                $this->result['errortext'][]=$text;
     }   
        
     function check_admin($data)
     {   

         if($this->initiate_db_connection($_SESSION['db']))
        {   
            
                if((!trim($data['user']['Name']))or (!trim($data['user']['Password']))or(!trim($data['user']['Email'])))
                {
                    $this->error('Заполните все поля');
                    return  false;
                
                }
                
                if($data['user']['Password']!=$data['user']['passwordAgain'])
                {
                    $this->error('Пароли не совпадают');
                    return  false;
                }
                           
                $users = &Common::module_factory('users.back'); 
                $users->common_call();
            
            if($users->create_super_admin($data['user']))
            {

                $this->create_conf(); 
                $this->intialize_trees_roots(); 
                $this->result['admin']=1;
                $this->process(array('page'=>'end'));
                 
            
            }else{            
               $this->error('РћС€РёР±РєР°Ошибка при создании записи администратора, возможно такой администратор');
            }        
        
        }
         
     }
     
     
     function create_conf()
     {   
         global $TMS;
      /*   if (is_writable('conf')) 
         {                 
                   $fp=fopen('conf/init.php','w');
                   $TMS->AddMassReplace('conf_template',$_SESSION['db']);                   
                   if (fwrite($fp, $TMS->parseSection('conf_template')) === FALSE) 
                            {
                             $this->error('РќРµРІРѕР·РјРѕР¶РЅРѕ Р·Р°РїРёСЃР°С‚СЊ РєРѕС„РёРіСѓСЂР°С†РёРѕРЅРЅС‹Р№ С„Р°Р№Р»');
                            }

                   fclose($fp);   
                   
                   
         }else{
         
               $this->error('Р—Р°РїРёСЃСЊ РІ РїР°РїРєСѓ conf РЅРµРІРѕР·РјРѕР¶РЅР°, СѓСЃС‚Р°РЅРѕРІРёС‚Рµ РїСЂР°РІР° 0777');
         }     */
     }
     
     
     
    function check_database($data) 
    {      
        if(!trim($data['data']['db_host']))
        {      
                 $this->error('Неверно указан хост mysql подключения:' . mysql_error());
            
        }else
        {
        
        if($this->initiate_db_connection($data['data']))
        {          
            $this->result['db']=true;
            $_SESSION['db']=$data['data'];            
            $this->process(array('page'=>'datageneration'));
        }
        }
    }

    
    function process($params)
        {
        global $TMS;
        $this->result['steppage']=$TMS->parseSection($params['page']);
        }

        
        
    function intialize_trees_roots()
        {
                   
        $mod_list = Common::get_module_list();
        if ($mod_list)
            {
            foreach ($mod_list as $module)
                {
                $_module = &Common::module_factory($module['name'] . '.back');
                if($_module){
                if (method_exists($_module, 'common_call'))
                    {
                    $_module->common_call();

                       if($_module->_tree)
                       {
                        
                          $_module->_tree->WriteNodeParam(1, 'Name', $module['alias'] . '(' . $_SERVER["HTTP_HOST"] . ')');

                       }
                                       $this->result['modules'][]='Модуль ' . $module['name'] . " инициализирован\r\n";    
                    }
                }    }
                
            }
        }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        }
    }

$TMS->AddFileSection('install/install.html');

session_start();
$inst=new x3minstall();

if ($_REQUEST['xoadCall'])
    {
    ob_start();

    if (XOAD_Server::runServer())
        {
        
        $all=ob_get_contents();
        ob_end_clean();
        echo $all;
        exit;
        }
    }
else
    {
    $TMS->AddReplace('main', 'xoadHeader', XOAD_Utilities::header("http://".$_SERVER["HTTP_HOST"].'/inc/xoad/'));
    $TMS->AddReplace('main', 'xObject', XOAD_Client::register($inst));
    echo $inst->start_install($data);
    }
?>