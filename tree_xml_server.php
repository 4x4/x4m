<?php
preg_match('@^(?:www.)?([^/]+)@i',$_SERVER["HTTP_HOST"],$m);
define("REAL_HTTP_HOST",$m[1]); 

require_once('inc/core.php');   
require_once("conf/init.php");
require_once ($_PATH['PATH_INC'] . 'TxmlTree.class.php');


global $TMS, $_PATH;
//global tpl system
$TDB =new Tmysqllayer(DB_USER, DB_PASS, DB_NAME, DB_HOST);


$tree=$_GET['tree'];

if ($tree)
    {
    switch ($tree)
        {

            /*s
            case 'news_container':
            $XT  =&new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('lm' => 'LastModified'),null);            
            $XT->build_xml_tree($_GET['id'], 'Name');
            $XT->displayXML();
            break;
           */
            
            case 'news_container':
            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('lm' => 'LastModified'),null,array('_NEWS'));   
            $XT->build_json_tree($_GET['id'], 'Name');
            $XT->displayXML();
            break;
           
            
                        
            
            case 'comments_container':
            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);            
            $XT->add_treexml_params(array('Alias' => 'Alias'),null,array('_TREAD'));
            $XT->set_params_redirect(array('basic' => 'Alias'));
            $XT->build_json_tree($_GET['id'], 'Alias');
            $XT->displayXML();
            break;
    
    
    
            case 'fusers_container':
            $XT  =new XMLTree(DB_NAME, $tree,true,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('nm' => 'Name'),null,array('_FUSERSGROUP'));
            $XT->set_params_redirect(array('basic' => 'Name'));
            $XT->build_json_tree($_GET['id'], 'Name'); 
            $XT->displayXML();
            break;
                    
            

            

		  case 'banners_container':

            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);            
            $XT->add_treexml_params(array('lm' => 'LastModified'),null);            
            $XT->build_json_tree($_GET['id'], 'Name'); 
            $XT->displayXML();
            break;
            
            
             case 'users_container':

            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);            
            $XT->add_treexml_params(array('nm' => 'Name'),null,array('_USERS'));            
            $XT->set_params_redirect(array('basic' => 'Name'));  
            $XT->build_json_tree($_GET['id'], 'Name'); 
            $XT->displayXML();
            break;

            
         
 		    case 'price_container':
            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('sbasic' => 'LastModified'),null,array('_PRICE'));
            $XT->build_json_tree($_GET['id'], 'Name');
            $XT->displayXML();
            break;
            
            
            case 'forms_container':
            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);            
            $XT->add_treexml_params(array('nm' => 'Name','lm'=>'LastModified'),null,array('_FORMS'));            
            $XT->set_params_redirect(array('basic' => 'Name'));              
            $XT->build_json_tree($_GET['id'], 'Name');
            $XT->displayXML();
            break;           
            
            case 'subscribe_container':
            $XT  =new XMLTree(DB_NAME, $tree,true,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('sbasic' => 'LastModified'),null,array('_SUBSCRIBE'));
            $XT->build_json_tree($_GET['id'], 'Name'); 
            $XT->displayXML();
            break;

            case 'votes_container':
            $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('lm' => 'LastModified'),null);            
            $XT->build_json_tree($_GET['id'], 'Name');                
            $XT->displayXML();
            break;

            case 'faq_container':
            $XT  =new XMLTree(DB_NAME, $tree,true,$_COMMON_SITE_CONF['site_encoding']);
            $XT->add_treexml_params(array('sbasic' => 'LastModified','aname'=>'Name'),null,array('_FAQ'));
            $XT->set_params_redirect(array('basic' => 'Name'));
            $XT->build_json_tree($_GET['id'], 'Name');
            $XT->displayXML();
            break;

        case 'template_container':
             $XT  =new XMLTree(DB_NAME, $tree,false,$_COMMON_SITE_CONF['site_encoding']);            
             $fs_walk_params['path']         =$path;
             $fs_walk_params['files_ot']      ='_TEMPLATE';
             $fs_walk_params['image']          =$image;
             $fs_walk_params['allow_files_types']=array('.html','.htm','.js','.css');
             $fs_walk_params['exclude_path_prefix']=$_PATH['PATH_TEMPLATES'];
             $fs_walk_params['dir_ot']='_DIRGROUP';
             $fs_walk_params['dir_image']=$images['_DIRGROUP'];                                       
             $fs_walk_params['capture_type']='all';   

            //если корневые папки
            if (($_GET['id'] == 1))
                {
                                        
                $XT->add_treexml_params(array('Name' => 'Name'));
                $XT->set_params_redirect(array('basic' => 'Name'));
                $XT->build_json_tree($_GET['id'], 'Name');
               
                }
            elseif (($_GET['id'] > 1 ))
                {

                $dir_name=$XT->GetNodeStruct($_GET['id']);
                $fs_walk_params['path']         =$_PATH['PATH_TEMPLATES'] . $dir_name['basic'];
                //!? $image                                   
                $XT->add_fs_walk_params($fs_walk_params);
                $XT->build_json_tree($_GET['id'],'root','_ROOT' ,$source_mode='files');
                
                }elseif(is_dir($fullpath=$_PATH['PATH_TEMPLATES'].$_GET['id']))
                {
                   
                    $fs_walk_params['path']         =$fullpath;
                    $XT->add_fs_walk_params($fs_walk_params);
                    $XT->build_json_tree($fullpath,'root','_ROOT' ,$source_mode='files');
                
                }else
                
                {
                
                 $XT->build_json_tree($_GET['id'], 'Name');
                }
            $XT->displayXML();
            break;
        }
    }



?>
