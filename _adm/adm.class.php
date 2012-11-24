<?php
class adminPanel
    {
    
    var $_module_name = 'adminPanel';

    function __construct() 
    {
        $this->_TMS=xRegistry::get('TMS');
        xNameSpaceHolder::addObjectToNS('module.adminPanel.back',$this);
    }

    public function ping()
        {
            $this->result['ping']='pong';
        }

    public  function startMapping()
        {
            XOAD_Server::allowClasses('Connector');
        }

    public  function enable_fronted($params)
        {
        if ($params['enable'])
            {
            $_SESSION['fronted']['enabled']=true;
            }
        else
            {
            $_SESSION['fronted']['enabled']=false;
            }
        }

    
    public function loadModuleTplsBack($params) { $this->loadModuleTpls($params['module'], $params['tpls']); }

    private function tplKeyTransform($keys)
    {
          if($keys) {foreach($keys as $key){$keyr[]='{'.$key.'}';}return  $keyr;}
    }
    
    private function tplLangConvert($lang,$file)
    {
        return str_replace($this->tplKeyTransform(array_keys($lang)), $lang, file_get_contents($file));
    }
    
    
    function loadModuleTpls($moduleName, $tplNames, $_return = false)
        {        
        if (is_array($tplNames))
            {

                
            foreach ($tplNames as $tpl)
                {
                if((strpos($tpl['tplName'],'@'))!==false)
                {
                    $tplExploded=explode('@',$tpl['tplName']);
                    $tplLoadName=$tplExploded[0];$modifier=$tplExploded[1];
                }else{
                    $tplLoadName=$tpl['tplName'];
                }
                    
                if ($moduleName == 'adminPanel')
                    {
                        $file=Common::getAdminTpl($tplLoadName . '.html');
                    }
                else
                    {
                        $file=Common::getModuleTpl($moduleName, $tpl['tplName'] . '.html');
                    }

                clearstatcache();

                $fstats=stat($file);

                if ($tpl['time'])
                    {
                    if ($tpl['time'] != $fstats['mtime'])
                        {
                        if (is_array($lang=Common::getModuleLang($moduleName, $_SESSION['lang'], $tpl)))
                            {
                                $this->lct['templates'][$tpl['tplName']]=$this->tplLangConvert($lang,$file);
                            }
                        else
                            {
                                $this->lct['templates'][$tpl['tplName']]=file_get_contents($file);
                            }
                        }
                    }
                else
                    {
                    if (is_array($lang=Common::getModuleLang($moduleName, $_SESSION['lang'], $tpl['tplName'])))
                        {
                        if (!$_return)
                            {
                                $this->lct['templates'][$tpl['tplName']]=$this->tplLangConvert($lang,$file);
                            }
                        else
                            {
                                return $this->tplLangConvert($lang,$file);
                            }
                        }
                    else
                        {
                            $this->lct['templates'][$tpl['tplName']]=file_get_contents($file);
                        }
                    }


                $this->_TMS->AddFileSection($this->lct[$tpl['tplName']], true);

                if ($this->_TMS->isSectionDefined($tplLoadName))
                    {
                        $this->_TMS->AddMassReplace($tplLoadName,'action',$modifier);
                        $this->lct['templates'][$tpl['tplName']]=$this->_TMS->parseSection($tplLoadName);
                    }

                $this->lct['timers'][$tpl['tplName']]=$fstats['mtime'];
                
                return true;
                
                }
            }
        }

    function get_module_list($params)
        {
            $this->result['modules']=Common::get_module_list($params['actionable']);
        }

        
    function  getAdminPanelData()
    {   
        static $apd=array();
        
        if(!$apd)
        {
            $apd['version']=xCore::getVersion();
            $apd['charset']=xConfig::get('GLOBAL','site_encoding');            
            $apd['lisence']=xCore::getLicense();            
            if(class_exists('XOAD_Utilities'))$apd['xoadHeader']= XOAD_Utilities::header(xConfig::get('WEBPATH','XOAD'));
            $apd['lang']=$_SESSION['lang'];            
            $apd['siteByDefault']=HOST;   
            $apd['XJS']=xConfig::get('WEBPATH','XJS');
    
        }
        
        return $apd;
    }
        
    function showLogin($e=null)    
        {   
            $this->_TMS->AddFileSection($this->loadModuleTpls($this->_module_name, array(array('tplName'=>'login')),true),true);                     
            if($e)
                {
                    $this->_TMS->parseSection('error',true);                          
                }
            
            $this->_TMS->AddMassReplace('main',$this->getAdminPanelData());
            return  $this->_TMS->parseSection('main');    
        }

    function buildMainPage($page_fields = null)
        {
        
            $this->_TMS->AddFileSection($this->loadModuleTpls($this->_module_name, array(array('tplName' => 'run')), true),
                             true);

       
        if ($moduleList=xCore::getModuleList())
            {
            
            $users=xCore::loadCommonClass('users');
            
            //$maccess=$users->roles_tree->GetChildsParam($_SESSION['user']['id'], array('is_accesible'), true);

            foreach ($moduleList as $module)
                {
                if ($_SESSION['user']['type'] == '_SUPERADMIN' or ($_SESSION['user']['maccess'][$module['name']]))
                    {
                        $i++;
                        $this->_TMS->AddMassReplace('js_modules', $module);
                        $this->_TMS->parseSection('js_modules', true);

                        $lang=Common::getModuleLang($this->_module_name, $_SESSION['lang'], 'modules');

                        $m=array
                            (
                            'name'  => $module['name'],
                            'alias' => $lang[$module['name']],
                            'info'  => $lang[$module['name'] . '_info']
                            );

                        $this->_TMS->AddMassReplace('fw_module', $m);

                        $column1.=$this->_TMS->parseSection('fw_module');

                        if ($i % 3 == 0)
                            {
                            $this->_TMS->AddReplace('fw_module_all', 'fw_module', $column1);
                            $this->_TMS->parseSection('fw_module_all', true);
                            $column1='';
                            }

                        $this->_TMS->AddMassReplace('module_menu', $m);
                        $menu_html=$this->_TMS->parseSection('module_menu');

                        switch ($module['moduleCategory'])
                            {
                            case 0:
                                $mm['menu_content'].=$menu_html;

                                break;

                            case 1:
                                $mm['menu_control'].=$menu_html;

                                break;

                            case 2:
                                $mm['menu_special'].=$menu_html;

                                break;
                            }
                        }
              
                }

            $this->_TMS->AddReplace('fw_module_all', 'fw_module', $column1);
            $this->_TMS->parseSection('fw_module_all', true);
            }

        $this->_TMS->AddMassReplace('main', $mm);
        $this->_TMS->AddMassReplace('main', $this->getAdminPanelData());

        $this->_TMS->AddMassReplace('main', $page_fields);

        return $this->_TMS->parseSection('main');
        }



    function del_media_file($params)
        {
        global $_PATH;

        if (file_exists($path=$_PATH['PATH_MEDIA'] . $params['sender'] . '/' . $params['file']))
            {
            if (unlink($path))
                {
                $this->result['isDel']=true;
                }
            }
        else
            {
            $this->result['fileNotExists']=true;
            }
        }

    function dispatchAction($action)
        {
        global $_COMMON_SITE_CONF;

        switch ($action)
            {
            case 'wcss':
                if ($cssfile=file(PATH_ . 'css/' . $_COMMON_SITE_CONF['global_css_file_name']))
                    {
                    $wcss='';

                    foreach ($cssfile as $line)
                        {
                        if (strstr($line, '/*global*/'))
                            {
                            $sp=true;
                            }
                        elseif (strstr($line, '/*/global*/'))
                            {
                            $sp=false;
                            }

                        if ($sp)
                            {
                            $wcss.=$line;
                            }
                        }

                    header ('Content-type: text/css');
                    echo $wcss;
                    }

                break;
            }
        }


   

    function getSessionId()
        {
        $this->result['clearGSTORAGE']=($_SESSION['clearGSTORAGE']) ? 1 : 0;
        $this->result['sessionid']    =session_id();

        //$_SESSION['clearGSTORAGE'] = 0;
        }


    }
?>