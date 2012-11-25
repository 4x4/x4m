<?php

define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

ini_set('session.gc_maxlifetime', '18000'); 
preg_match('@^(?:www.)?([^/]+)@i', $_SERVER['HTTP_HOST'], $m);
define('REAL_HTTP_HOST', $m[1]);

require_once(DOCUMENT_ROOT.'/inc/core/helpers.php');

ob_start();

require_once(DOCUMENT_ROOT.'/conf/init.php');
require_once(xConfig::get('PATH','CORE').'core.php');
require_once(xConfig::get('PATH','CORE').'helpers.tpl.php');
require_once(xConfig::get('PATH','CLASSES').'cache.class.php');

session_start(); 


//xPDO
xPDO::setSource(DB_HOST,DB_NAME,DB_USER,DB_PASS); 
xRegistry::set('xPDO',xPDO::getInstance());
debugbreak();
$pages=new xTreeEngine('pages_container');
$source=Common::classesFactory('treeJsonSource',array(&$pages));

$source->createView();


        


die();
?>
