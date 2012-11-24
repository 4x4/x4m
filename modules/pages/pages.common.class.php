<?php
class pagesCommon
    extends xCommon implements xCommonInterface
    {
        public $_useTree=true;
        public $nativeLangVersion=false;
       
        function pagesCommon()
        {
            parent::__construct(__CLASS__);
         
         
            $this->_tree->setCacheParams('tree',3600);
          //  $this->_tree->enableCache(true);
    
     
            $this->_tree->setObject('_ROOT', array
                (
                    'StartPage',
                    'Active',
                    'Name',
                    'Gacode'            
                ));
                
                
            $this->_tree->setObject('_DOMAIN', array
                (
                    'StartPage',
                    'Active',
                    'Name'
                    
                ),array('_ROOT'));

            
            $this->_tree->setObject('_LVERSION', array
                (
                    'StartPage',
                    'Active',
                    'Name',
                    'Link'
                    
                ),array('_DOMAIN'));

            $this->_tree->setObject('_LINK', array
                (
                    'Link',
                    'LinkId',
                    'Active',
                    'Icon',
                    'Comment',
                    'DisableMapLink',
                    'Visible',
                    'Name',
                    'ExternalLinkId'
                
                ), array('_GROUP','_LVERSION'));

            $this->_tree->setObject('_GROUP', array
                (
                    'Name',
                    'Active',
                    'StartPage',
                    'Icon',
                    'Comment',
                    'Visible',
                    'Template',
                    'DisableGlobalLink',
                    'DisableAccess',
                    'DisablePath',
                    'DisableMapLink'
                ),array('_ROOT','_GROUP','_LVERSION'));

            $this->_tree->setObject('_PAGE', array
                (
                    'Name',
                    'Active',
                    'AuthRedirId',
                    'NoAuthRedirId',
                    'Visible',
                    'Template',
                    'Keywords',
                    'Title',
                    'Icon',
                    'Comment',            
                    'DisableGlobalLink',
                    'DisableMapLink',
                    'DisablePath',
                    'DisableCache',  
                    'Meta',
                    'Description',
                    'DisableAccess'
                ),array('_ROOT','_GROUP','_LVERSION'));

            
            $this->_tree->setObject('_MODULE', null, array('_PAGE','_LVERSION','_GROUP','_DOMAIN','_ROOT'));
            
            }
    
    

    function getPageSlotz($id) 
    { 
        return $this->_tree->GetChildsParam($page_id,$this->_tree->getObject('_SLOT'),true,array('obj_type' => array('_SLOT'))); 
    }

    
    function get_page_modules($id, $slot=null, $module_type=null)
    {
        if(!$slot)
        {
            if($slotzlist=$this->get_page_slotz($id))
            {
                $slotzlist=array_keys($slotzlist);
            }
        
        }else{
            
            $slotzlist=$this->_tree->FindbyBasic($anc,$slot);
        }
        
        $c=$this->_tree->get_anc_multiply_childs($slotzlist,array('_MODULE'),1);
        
        if($module_type)
        {
           while(list($k,$v)=each($c))
           {
              if($v['params']['type']!=$module_type){unset($c[$k]);} 
           }
        }
        return $c;        
    }                                                                            
                         
                         
    function  get_module_by_action($page_id,$action)
    {
                    if($d=$this->_tree->DeepSearch2($page_id,array('_SLOT'),$stop_level=2,array('Action'=>$action),true))
                    {
                        reset($d);$d=current($d);return $d;
                    }
    }
                                                                                   
   
    function render_module($id,$call_dynamic=false)
    {
                       if(!$call_dynamic)
                       {
                           $module=$this->_tree->getNodeInfo($id);         
                           if(!$module['params']['Active']) return;
                       }else{
                           
                            $module['params']=$id;
                           
                            }
                            
                        if (is_object($module_obj=&Common::module_factory($module['params']['type'] . '.front')))
                            {
                                return $module_obj->execute(
                                    $module['params'],
                                    array('__module_id' => $module['id']
                                ));
                            }       
    }
       



    function get_page_module_servers($action)
        {
        if (is_array($action))
            {

            $serv_modules = array();
            foreach ($action as $act) 
            {
                $t = $this->_tree->Search(array('Action' => $act), true);
                if (is_array($t)) $serv_modules=array_merge($serv_modules, $t);        
            }
            
            }
        else
            {
            $serv_modules=$this->_tree->Search(array('Action' => $action), true);
            }

        if ($serv_modules)
            {
            foreach ($serv_modules as $module)
                {
                $page_node                  = $this->_tree->getNodeInfo(
                $this->_tree->GetAncestor($module['ancestor']));
                $ancestor                   =$this->_tree->getNodeInfo($page_node['ancestor']);
                $page_node['params']['Name']=$ancestor['params']['Name'] . '/' . $page_node['params']['Name'];
                $pages[]                    =$page_node;
                }

            return $pages;
            }
        }
        
        
     function findLinksByExternalId($ExternalLinkId)
     {
       if(  $s=$this->_tree->Search(array('ExternalLinkId'=>$ExternalLinkId),false,array('obj_type'=>'_LINK')))
       {
         return $s[0];
       }

     } 
     
     
       public  function linkCreator($basicPath,$domain=null) 
       {
                if(!$domain)
                        {
                            $domain=xConfig::get('GLOBAL','DOMAIN');
                        }
                        
                $link='http://'.$domain;
                        
                if(!$this->nativeLangVersion)
                            {
                                $link.= '/'.$this->nativeLangVersion;                            
                            }
                        
                if($basicPath)
                        {
                             $bPath=array_slice($basicPath,2);                             
                             $link.='/'.implode('/',$bPath);
                        }
                    
                return $link;      
       }       
       

    public function createPagePath($id, $excludehost = false, $action='',$useNames=false)
        {        
            $bp=$this->_tree->selectStruct(array('id'))->getBasicPath()->where(array('@id','=',$id))->run();
            
            if($action)$action='/~'.$action.'/';      
                      
            if ($bp['basicPathValue'])
                if (!$excludehost)
                    {                      
                        $path=$this->linkCreator($bp['basicPath']).$action;                
                    }
                else
                    {
                        if($useNames)
                          {
                                
                           $namePath=$this->_tree->selectParams(array('Name'))->where(array('@id','=',$bp['path']))->format('paramsparams','id','Name')->run();                            
                           $path=implode('/', $namePath);                    
                                
                            }else{
                              
                                $path=$this->linkCreator($bp['basicPath']).$action;    
                        }
                    }

               return $path;
        
        }


    function defineFrontActions()
        {
            $this->defineAction('showLevelMenu');
            $this->defineAction('showPath');
            $this->defineAction('showMap');
            $this->defineAction('showUserMenu');
        }

    
    } #endclass    
?>