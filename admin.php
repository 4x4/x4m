<?php
session_start();
preg_match('@^(?:www.)?([^/]+)@i',$_SERVER["HTTP_HOST"],$m);
define("REAL_HTTP_HOST",$m[1]); 

$_SESSION['user']=1;

if (!isset($_SESSION['user']))
    {
        if(!$_REQUEST['xoadCall'])
        {
                 header('location: login.php');
        }else{
                echo 'SESSION_TIME_EXPIRED';
                die();
        }
    }



require_once('inc/core/helpers.php');
require_once('conf/init.php');
require_once(xConfig::get('PATH','CORE').'core.php');
require_once(xConfig::get('PATH','CORE').'helpers.tpl.php');

xRegistry::set('TMS',$TMS = new tMultiSection());
xPDO::setSource(DB_HOST,DB_NAME,DB_USER,DB_PASS); 
xRegistry::set('xPDO',xPDO::getInstance());


xNameSpaceHolder::addObjectToNS('E',new ENHANCE());

require_once(xConfig::get('PATH','XOAD') . 'xoad.php');
require_once(xConfig::get('PATH','ADM') . 'logger.class.php');
require_once(xConfig::get('PATH','ADM') . 'adm.class.php');

xRegistry::set('EVM',xEventMachine::getInstance());
xCore::pluginEventDetector();  


if ($_REQUEST['xoadCall'])
    {
    ob_start();
    
    $adm            =new adminPanel();
    $adm->startMapping();
    
    if (XOAD_Server::runServer())
        {            
            $all=ob_get_contents();            
            ob_end_clean();  
                     
                if($_COMMON_SITE_CONF['output_html_compress'])
                {
                    Common::compress_output($all);
                }
                
            echo $all;
            exit;
        }
    }
    elseif($_REQUEST['action'])
    {
        $adm            =new adminPanel();
        echo $adm->dispatchAction($_GET['action']);    
    
    }else{
        
        $tpl            =xCore::moduleFactory('templates.back'); 
        $adm=new adminPanel();           
        //$data['xObject']=XOAD_Client::register(new adminPanel()); 
        
        $data['xConnector']=XOAD_Client::register(new Connector()); 
        
        echo $adm->buildMainPage($data);
    }
    die();
?>