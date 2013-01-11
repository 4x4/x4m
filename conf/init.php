<?php
define('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);

define('HTTP_HOST', $_SERVER['HTTP_HOST']);
include($_SERVER['DOCUMENT_ROOT'].'/conf/db.init.php');
                                                   
###############################################
#GLOBAL PATH INIT
###############################################

define('HOST', 'http://' . HTTP_HOST . '/');
define('CHOST', 'http://' . HTTP_HOST);
define('PATH_', DOCUMENT_ROOT. '/');
define('X4_VERSION', '0.1 empyrean');  
define('DEFAULT_CHUNK_SIZE', 100); 

###############################################
#PATH'S
###############################################
  
$_PATH['INC'] =  PATH_. 'inc/';

$_PATH['CORE'] =$_PATH['INC'].'core/';
$_PATH['CLASSES'] =$_PATH['INC'] . 'classes/';
$_PATH['DRIVERS'] =$_PATH['INC'] . 'drivers/';
$_PATH['MEDIA'] = PATH_. 'media/';
$_PATH['WEB_MEDIA'] = HOST. 'media/';
//resource folder for javascript css and images
$_PATH['WEB_ARES'] = HOST. 'templates/_ares/';

$_PATH['EXT']=$_PATH['INC'].'ext/';

$_PATH['MODULES'] =  PATH_. 'modules/';
$_PATH['PLUGINS'] =  PATH_. 'plugins/';
$_PATH['ADM'] =  PATH_. '_adm/';
$_PATH['PAGES'] = PATH_. 'pages/';
$_PATH['XJS'] =  $_PATH['ADM']. 'xjs/';

$_PATH['XOAD'] =  $_PATH['EXT'].'xoad/';
$_PATH['CACHE'] =  PATH_. 'cache/';
$_PATH['EXPORT'] = $_PATH['MEDIA'].'export/'; 
$_PATH['WEB_EXPORT'] = $_PATH['WEB_MEDIA'].'export/';  
$_PATH['BACKUP'] = $_PATH['MEDIA'].'backup/'; 
$_PATH['TPL_CACHE'] =  PATH_. 'cache/tpl/';
$_PATH['TEMPLATES'] =  PATH_. 'templates/';
$_PATH['COMMON_TEMPLATES'] = $_PATH['TEMPLATES']. '_common/';
$_PATH['SITEMAP'] = $_PATH['MEDIA'] . 'sitemap.xml';


xConfig::setBranch('PATH',$_PATH);
xConfig::set('PATH','MODULES_TEMPLATES',$_PATH['TEMPLATES']. '_modules/');      



$_WEBPATH['XJS'] = HOST . '_adm/xjs/';
$_WEBPATH['XOAD'] = HOST . 'inc/ext/xoad';

xConfig::setBranch('WEBPATH',$_WEBPATH);

$_DB['NAME'] = DB_NAME;
$_DB['HOST'] = DB_HOST;
$_DB['USER'] = DB_USER;
$_DB['HOST'] = DB_PASS;

xConfig::setBranch('DB',$_DB);

$_COMMON_SITE_CONF['admin_email']='abiatop@gmail.com';
$_COMMON_SITE_CONF['site_encoding']='utf-8';
$_COMMON_SITE_CONF['encodings']=array('utf-8'=>'utf8');   
$_COMMON_SITE_CONF['show_debug_info']=1;
$_COMMON_SITE_CONF['disable_m_caching']=1;
$_COMMON_SITE_CONF['enable_front_api']=1;

$_COMMON_SITE_CONF['output_js_compress']=1;

$_COMMON_SITE_CONF['output_html_compress']=0;       
$_COMMON_SITE_CONF['enable_page_caching']=0;
$_COMMON_SITE_CONF['global_css_file_name']='global.css';
$_COMMON_SITE_CONF['enable_tree_auto_creation']=0;
$_COMMON_SITE_CONF['default_language']='rus';
$_COMMON_SITE_CONF['site_language']='rus';
$_COMMON_SITE_CONF['deny_files_upload_extensions']=array('php','phtml','php3','php4','php5','htaccess');    

$_COMMON_SITE_CONF['do_not_translit_in_file_manager']=false;   

xConfig::setBranch('GLOBAL',$_COMMON_SITE_CONF);

xConfig::set('GLOBAL','cacheTimeout',3600);
xConfig::set('GLOBAL','DOMAIN',HTTP_HOST);      
xConfig::set('GLOBAL','HOST','http://' . HTTP_HOST . '/');      
xConfig::set('GLOBAL','CHOST','http://' . HTTP_HOST);     

xConfig::set('GLOBAL','output_js_back_compress',1);      



$_CONFIG['content']['show_content']['m_caching']=1;
$_CONFIG['pages']['show_level_menu']['m_caching']=1;
$_CONFIG['pages']['show_path']['m_caching']=1;
$_CONFIG['catalog']['show_level_catmenu']['m_caching']=1;
$_CONFIG['catalog']['show_category']['m_caching']=1;
$_CONFIG['catalog']['show_catalog_server']['m_caching']=1;
$_CONFIG['catalog']['catalog_filter']['m_caching']=1;      
$_CONFIG['catalog']['showcat']['m_caching']=1;
$_CONFIG['catalog']['showobj']['m_caching']=1;
$_CONFIG['catalog']['show']['m_caching']=1;
$_CONFIG['news']['show_news_interval']['m_caching']=1;


$_CONFIG['ishop']['admin_rows_per_page']=20;
$_CONFIG['news']['admin_rows_per_page']=20;

                                           
$_CONFIG['news']['show_news_per_page']=15;       
$_CONFIG['news']['date_format']='%d.%m.%Y %H:%i:%s';       
$_CONFIG['news']['show_similar_news_per_news']=15;    

$_CONFIG['faq']['question_words_count'] = 15;
$_CONFIG['faq']['admin_rows_per_page'] = 15;


$_CONFIG['search']['index_time_limit']=10;  

$_CONFIG['catalog']['excelparser']='ExcelExplorer'; //РІРѕР·РјРѕР¶РµРЅ 'ExcelReader'

xConfig::setBranch('MODULES',$_CONFIG);

?>
