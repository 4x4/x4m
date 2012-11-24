<?php

define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

ini_set('session.gc_maxlifetime', '18000'); 
preg_match('@^(?:www.)?([^/]+)@i', $_SERVER['HTTP_HOST'], $m);
define('REAL_HTTP_HOST', $m[1]);

require_once(DOCUMENT_ROOT.'/inc/core/helpers.php');

ob_start();


     
$time=Common::getmicrotime();

$_SERVER['REQUEST_URI']='/group/mypage';

require_once(DOCUMENT_ROOT.'/conf/init.php');
require_once(xConfig::get('PATH','CORE').'core.php');
require_once(xConfig::get('PATH','CORE').'helpers.tpl.php');
require_once(xConfig::get('PATH','CLASSES').'cache.class.php');

session_start(); 
  
xConfig::set('GLOBAL','currentMode','front');
xRegistry::set('EVM',xEventMachine::getInstance());
xCore::pluginEventDetector();  


xConfig::set('PATH','fullBaseUrl', CHOST . $_SERVER['REQUEST_URI']);
xConfig::set('PATH','baseUrl',trim($_SERVER['REQUEST_URI']));    

$position=strpos(xConfig::get('PATH','baseUrl'),'?');


if($position!==false)
{
    xConfig::set('PATH','baseUrl',substr(xConfig::get('PATH','baseUrl'),0,$position,'?'));        
}


require_once(xConfig::get('PATH','XOAD') . 'xoad.php');
xCache::initialize('MemCache');
xRegistry::set('TMS',$TMS = new tMultiSection());

/*
$TMS->AddFileSection(PATH_.'templates/robots.htm');
$TMS->AddReplace('robots','paramse','hello');
$TMS->AddReplace('robots','arr',array('hell'=>'d'));
echo $TMS->ParseSection('robots');*/

//xPDO
xPDO::setSource(DB_HOST,DB_NAME,DB_USER,DB_PASS); 
xRegistry::set('xPDO',xPDO::getInstance());

//Enhance
xNameSpaceHolder::addObjectToNS('E',new ENHANCE());
xNameSpaceHolder::addObjectToNS('D',new DEBUG());


        global $time;
        $y=Common::getmicrotime();
        echo $y-$time.' instansces ready ';
        


if(isset($_REQUEST['xoadCall']))
{
    $connector = new connector();

    @ob_end_clean();            
    ob_start();
    
    if(XOAD_Server::runServer())
    {
        if($all = ob_get_contents())
        {
            @ob_end_clean();            
        
            if(xConfig::get('GLOBAL','output_html_compress'))
            {
                echo Common::compress_output($all);
                
                }else{
                    
                echo $all;
            }
        }
        
        exit();
    }
}
else
{
    xRegistry::set('TPA',$TPA = new tPageAgregator());
    
    if(isset($_GET['action']))
    {
        $TPA->dispatch_action($_GET['action']);
    }
    elseif($page = $TPA->execute_page(xConfig::get('PATH','baseUrl')))
    {
            if(($TPA->FED_MODE))
            {
                $xConnector = new Connector();      
                $xConnector_obj = XOAD_Client::register($xConnector,array('url' => '/admin.php'));                 
                $TMS->AddFileSection($_PATH['ADM'] . 'tpl/FED.html');
                $TMS->AddMassReplace('FED',array('front_obj' => XOAD_Client::register(new front_api()), 'xConnector' => $xConnector_obj, 'xoad_header' => XOAD_Utilities::header($_WEBPATH['XOAD'])));
                $page = preg_replace(array('#<script[^>]*>.*?</script>#is'), array(''), $page);
                $page .= $TMS->parseSection('FED');
            }
     
        $all = $page;

        if(xConfig::get('GLOBAL','enable_page_caching')&&!$TPA->DisablePageCaching && !$TPA->FED_MODE)
        {
             $cache->toCache($all);
        }
            
        if(xConfig::get('GLOBAL','show_debug_info'))
        {
            $y = Common::getmicrotime() - $x;            
            $all.='<!-- ' . $y . ' -->';
        }

        if(xConfig::get('GLOBAL','output_html_compress'))
        {
            $all=Common::compress_output($all);
        }
        
        echo $all;
        
    }
}
die();
?>
