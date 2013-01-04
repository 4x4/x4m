<?php       
preg_match('@^(?:www.)?([^/]+)@i',$_SERVER["HTTP_HOST"],$m);
define("REAL_HTTP_HOST",$m[1]); 

require_once('inc/core/helpers.php');
require_once('conf/init.php');
require_once(xConfig::get('PATH','CORE').'core.php');
require_once(xConfig::get('PATH','ADM').'adm.class.php');

xRegistry::set('TMS',$TMS = new tMultiSection());
xPDO::setSource(DB_HOST,DB_NAME,DB_USER,DB_PASS); 
xRegistry::set('xPDO',xPDO::getInstance());

session_start();              
@session_destroy();   
@session_start();   

$adm =new adminPanel();   

$_SESSION['lang']=$_COMMON_SITE_CONF['default_language']; 

if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if($_POST['login']&&$_POST['password'])
        {   
                    $users=xCore::loadCommonClass('users');                                         
                    if( $users->checkAndLoadUser($_POST['login'],$_POST['password']))
                    {                    
                        if($_POST['lang']){$_SESSION['lang']=$_POST['lang'];}
                        if($_POST['clearGSTORAGE'])$_SESSION['clearGSTORAGE']=1;
                        header('location: admin.php?');     
                        exit;
                    }
        }
        
        echo   $adm->showLogin(true);
        
    }else{
        
        echo   $adm->showLogin();    
    }

?>
