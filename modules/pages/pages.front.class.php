<?php
class pagesFront extends xModule
{
    public $menuAncestor;
    public $devideByRows;
    public $langVersion;
    public $bones = array();
    public $moduleOrder = array();
    
    public $additionalBones = array();
    
    function __construct()
    {
        parent::__construct(__CLASS__);
        $this->_tree->enableCache(true);           
    }
    
    public function createTest()
    {
        return;
        //          $this->_tree->delete()->childs(1)->where(array('@obj_type','=','_MODULE'))->run();
        
      //  $this->_tree->delete()->childs(1)->run();
        
        
        
        $root = $this->_tree->initTreeObj(1, 'x4.bi', '_DOMAIN', array(
            'StartPage' => ''
        ));
        $id   = $this->_tree->initTreeObj($root, 'ru', '_LVERSION', array(
            'StartPage' => ''
        ));
        
        $grId = $this->_tree->initTreeObj($id, 'group', '_GROUP', array(
            'Template' => '_index.html'
        ));
        
        $id   = $this->_tree->initTreeObj($grId, 'mypageSecond', '_PAGE', array(
            'Template' => '_index.html',
            'name' => 'second'
        ));
        $id   = $this->_tree->initTreeObj($grId, 'mypageThird', '_PAGE', array(
            'Template' => '_index.html',
            'name' => 'third'
        ));
        $inGR = $this->_tree->initTreeObj($grId, 'groupInner', '_GROUP', array(
            'Template' => '_index.html',
            'name' => 'groupInner'
        ));
        $id   = $this->_tree->initTreeObj($grId, 'mypage', '_PAGE', array(
            'Template' => '_index.html',
            'name' => 'third'
        ));
        
        $this->_tree->initTreeObj($id, '%SAMEASID%', '_MODULE', array(
            'Action' => 'showLevelMenu',
            '_Slot' => 'center',
            'showGroupId' => 1,
            'Type' => 'pages',
            'Template' => 'menu_left.show_level_menu.html',
            'Priority' => 1
        ));
        $this->_tree->initTreeObj($id, '%SAMEASID%', '_MODULE', array(
            'Action' => 'showPath',
            '_Slot' => 'left-side',
            'Type' => 'pages',
            'Priority' => 2,
            'Template' => 'path_tpl.show_path.html'
        ));
        
        
        
        $id = $this->_tree->initTreeObj($root, 'eng', '_LVERSION', array(
            'StartPage' => ''
        ));
        
    }
    
    private function pageFinalPoint()
    {
        $e = false;
        while (!$e)
        {
            if (in_array($this->page['obj_type'], array(
                '_GROUP',
                '_ROOT',
                '_LVERSION',
                '_DOMAIN'
            )))
            {
                if (!empty($this->page['params']['StartPage']))
                {
                    $this->page = $this->_tree->getNodeInfo($this->page['params']['StartPage']);
                    if ($this->page['params']['DisableGlobalLink'])
                        return false;
                }
                else
                {
                    //редиректа нет ->страница не существует  
                    return false;
                }
            }
            else
            {
                $e = true;
            }
        }
        
        return null;
        
    }
    
    public function getPageIdByPath($path)
    {
        global $REQUEST_VARS;

        $treePath   = XARRAY::clearEmptyItems(explode('/', $path), true);
        $this->root = $this->_tree->getNodeInfo(1);
        
        $this->domain = $this->_tree->selectStruct('*')->selectParams('*')->where(array(
            '@basic',
            '=',
            HTTP_HOST
        ), array(
            '@ancestor',
            '=',
            1
        ))->run();
        $this->domain = $this->domain[0];
        
        
        $langVersions      = $this->getLangVersions($this->domain['id']);
        $langVersionsStack = XARRAY::arr_to_keyarr($langVersions, 'id', 'basic');
        
        
        if ((!$treePath))
        {
            if (!$this->domain['params']['StartPage'])
                return; //здесь заглушка - по умолчанию должен выбирать первую языковую версию если другое не указано
            
            $this->langVersion = $langVersions[$langVersionsStack[$this->domain['params']['StartPage']]];
            
            if (!$this->langVersion['params']['StartPage'])
                return; //здесь заглушка - по умолчанию должен выбирать первую страницу
            
            $this->page = $this->_tree->getNodeInfo($this->langVersion['params']['StartPage']);
            
            if ($this->pageFinalPoint() === false)
            {
                return false;
            }
            else
            {
                return true;
            }
            
        }
        else
        {
            if (($lang = array_search($treePath[0], $langVersionsStack) !== false) && $langVersions[$treePath[0]])
            {
                $this->langVersion = $langVersions[$treePath[0]];
                
            }
            else
            {
                foreach ($langVersions as $lKey => $lVersion)
                {
                    if ($this->domain['params']['StartPage'] == $lVersion['id'])
                    {
                        $this->langVersion = $langVersions[$lKey];
                    }
                }
                
                reset($langVersions);
                if (!$this->langVersion)
                    $this->langVersion = current($langVersions);
                array_unshift($treePath, $this->langVersion['basic']);
                $this->_commonObj->nativeLangVersion = $this->langVersion['basic'];
            }
            
            
            array_unshift($treePath, HTTP_HOST);
            if (!$node = $this->_tree->idByBasicPath($treePath, array(
                '_DOMAIN',
                '_LVERSION',
                '_PAGE',
                '_GROUP'
            ), true))
            {
                return false;
            }
            
            
            $this->page = $this->_tree->getNodeInfo($node['id']);
            
            if ($this->page['obj_type'] == '_PAGE')
            {
                if ($this->_tree->readNodeParam($this->page['ancestor'], 'StartPage') == $this->page['id'])
                {
                    if (xRegistry::get('TPA')->pathParams)
                        $pathParams = '/~' . xRegistry::get('TPA')->pathParams;
                    $this->move_301_permanent(CHOST . '/' . $this->_commonObj->createPagePath($this->page['id'], true) . $pathParams);
                }
            }
            
            if ($this->pageFinalPoint() === false)
            {
                return false;
            }
            else
            {
                $bones   = array_slice($this->page['path'], 3);
                $bones[] = $this->page['id'];
                
                if (!empty($bones))
                {
                    $this->bones = $this->_tree->selectStruct('*')->selectParams('*')->getBasicPath('/', true)->where(array(
                        '@id',
                        '=',
                        $bones
                    ))->format('keyval', 'id')->run();
                }
                
                array_unshift($this->bones, $this->langVersion);
                return true;
            }
        }
        
    }
    
    function getSlotzCrotch($tplSlotz)
    {
        if ($sPath = $this->page['path'])
        {
            $sPath[] = $this->page['id'];
            $result  = $this->_tree->selectStruct('*')->selectParams('*')->childs($sPath, 1)->where(array(
                '@obj_type',
                '=',
                array(
                    '_MODULE'
                )
            ))->asTree()->run();
            
            foreach ($sPath as $pathPoint)
            {
                if ($eModules = $result->fetchArray($pathPoint))
                {
                    foreach ($eModules as $id => $module)
                    {
                        if (in_array($module['params']['_Slot'], $tplSlotz))
                        {
                            $modules[$module['params']['_Slot']][] = $id;
                            $this->execModules[$id]                = $module;
                            $this->modulesOrder[$id]               = $module['params']['Priority'];
                        }
                    }
                }
            }
            
            arsort($this->modulesOrder);
            return $modules;
        }
    }
    
    
    function getLangVersions($domainId = null)
    {
        $where[] = array(
            '@obj_type',
            '=',
            '_LVERSION'
        );
        
        if ($domainId)
        {
            $where[] = array(
                '@ancestor',
                '=',
                $domainId
            );
        }
        
        return $this->_tree->selectStruct('*')->where($where, true)->format('keyval', 'basic')->run();
    }
    
    function showPath($params)
    {
        $this->loadModuleTemplate($params['Template']);
        
        if (is_array($bonesFull = $this->bones))
        {
            $i = 0;
            
            $delimiteSymbol = $this->_TMS->parseSection('_bones_delimiter');
            
            if ($this->additionalBones)
            {
                foreach ($this->additionalBones as $bone)
                {
                    $bonesFull[] = $bone;
                }
            }
            
            $bonesLength = count($bonesFull);
            while (list(, $bone) = each($bonesFull))
            {
                if (!$bone['link'])
                {
                    $link = $this->_commonObj->linkCreator($bone['basicPath']);
                    
                }
                else
                {
                    $link = $bone['link'];
                }
                
                
                if (!$bone['params']['DisablePath'] and ($bonesLength != $i))
                {
                    $this->_TMS->AddMassReplace('_bones_item', array(
                        'name' => $bone['params']['Name'],
                        'link' => $link
                    ));
                    
                    $this->_TMS->parseSection('_bones_item', true);
                }
                
            }
            
            $bone = array_pop($bonesFull);
            $this->_TMS->AddMassReplace('_bones_item_no_link', array(
                'name' => $bone['params']['Name'],
                'link' => $link
            ));
            
            $this->_TMS->parseSection('_bones_item_no_link', true);
        }
        
        $bones = $this->_TMS->parseSection('_bones');
        return $bones;
    }
    
    
    function showLevelMenu($params)
    {
        
         $params['dynamicAdapt']=true;
        
        $this->loadModuleTemplate($params['Template']);
        
        if ($params['objectInRows'])
        {
            $this->devideByRows = (int) $params['objectInRows'];
        }
        else
        {
            $this->devideByRows = 0;
        }
        
        $this->menuAncestor = $this->_tree->getNodeInfo($params['showGroupId']);
        
        if($params['dynamicAdapt'])
        {                
            
            $cPath=array_slice($this->page['path'],2);            
            $cPath=array_reverse($cPath);    
            
            if(count($cPath)>$params['upLevel'])
            {
                $params['showGroupId']=$cPath[$params['upLevel']];
            
            }else{
                
                $params['showGroupId']=array_shift($cPath);
                
            }
                
        }
            
        if ($menuSource = $this->_tree->selectStruct('*')->selectParams('*')->getBasicPath()->childs($params['showGroupId'], $params['levels'])->where(array(
            '@obj_type',
            '=',
            array(
                '_LVERSION',
                '_GROUP',
                '_PAGE',
                '_LINK'
            )
        ))->asTree()->run())
        {
            $this->menuSource = $menuSource->recursiveStep($params['showGroupId'], $this, 'clearDisabled');
            $menu             = $this->renderMultiLevelMenu($menuSource, $params['showGroupId']);
        }
        
        return $menu;
    }
    
    
    public function clearDisabled($node, $ancestor, $tContext, $extdata)
    {
        if ($node['params']['NotVisibleMenu'])
        {
            $tContext->remove($node['id']);
        }
    }
    
    
    private function findLevelSection($level, $section)
    {
        if ($this->_TMS->isSectionDefined($section . $level))
        {
            return $section . $level;
        }
        else
        {
            return $this->findLevelSection($level-1,$section);
        }
    }
    
    function renderMultiLevelMenu($menuSource, $startNode, $level = 0, $anc = null)
    {
        if ($this->mapMode && ($level > 1))$level = 1;

        
        $menuLength = $menuSource->countBranch($startNode);
        
        while (list(, $menuItem) = $menuSource->fetch($startNode))
        {

            $pubMenuItem['_num'] = ++$k;
            
            $pubMenuItem = $menuItem['params'];
            if ($menuItem['obj_type'] == '_LINK')
            {
                if (!$menuItem['params']['linkId'])
                {
                    $pubMenuItem['link'] = $this->_commonObj->createPagePath($menuitem['params']['linkId']);
                }
                
            }
            else
            {
                $pubMenuItem['link'] = $this->_commonObj->linkCreator($menuItem['basicPath']);
            }
            
            
            $pubMenuItem['ancestor'] = $startNode;
            $pubMenuItem['basic']    = $menuItem['basic'];
            $pubMenuItem['id']       = $menuItem['id'];
            
            
            if ($menuSource->hasChilds($menuItem['id']))
            {
                $pubMenuItem['submenu'] = $this->renderMultiLevelMenu($menuSource, $menuItem['id'], $level + 1, $startNode);
            }
            
            
            if ($this->page['id'] == $menuItem['id'])
            {
                $pubMenuItem['selected'] = 1;
            }
            
            if (in_array($menuItem['id'], $this->page['path']))
            {
                $pubMenuItem['branch'] = 1;
            }
            
            if (($menuItem['obj_type'] == '_GROUP'))
            {
                $pubMenuItem['group'] = 1;
            }
            
            if ($k == 1)
            {
                $pubMenuItem['first'] = 1;
                
            }
            elseif ($k == $menuLength)
            {
                $pubMenuItem['last'] = 1;
                
            }

            $itemSection = $this->findLevelSection($level, '_menu_item_level');
            $this->_TMS->AddMassReplace($itemSection, $pubMenuItem);
            $menuBuffer .= $this->_TMS->ParseSection($itemSection);
            
            
            if (($this->devideByRows) && ($k % (int) $this->devideByRows == 0))
            {
                $mainLevelSection=$this->findLevelSection($level, '_menu_main_level');
                $this->_TMS->AddReplace($mainLevelSection, 'menu_items', $menuBuffer);
                $menuBuffer = '';
                
                $menuDbuff .= $this->_TMS->parseSection($mainLevelSection);
                $this->_TMS->KillField($mainLevelSection, 'menu_items');

                $exitSection = $this->findLevelSection($level, '_menu_divide_container');
            }
        }
        
        if (($this->devideByRows) && (($k % (int) $this->devideByRows != 0)))
        {
            
            $mainLevelSection=$this->findLevelSection($level, '_menu_main_level');
            $this->_TMS->AddReplace($mainLevelSection, 'menu_items', $menuBuffer);
            
            $menuDbuff .= $this->_TMS->parseSection($mainLevelSection);
            
            $exitSection = $this->findLevelSection($level, '_menu_divide_container');
            
            $menuBuffer  = $menuDbuff;
        }
        elseif (!$this->devideByRows)
        {
            $exitSection = $this->findLevelSection($level, '_menu_main_level'); 
        }
        else
        {
            $menuBuffer = $menuDbuff;
        }
        
        
        $this->_TMS->AddMassReplace($exitSection, array(
            'menu_items' => $menuBuffer,
            'name' => $this->menuAncestor['params']['Name'],
            'id' => $this->menuAncestor['id'],
            'basic' => $this->menuAncestor['basic']
        ));
        
        
        //    $this->_TMS->AddMassReplace($exitSection, $branch_item);
        $main = $this->_TMS->parseSection($exitSection);
        $this->_TMS->KillField($mainLevelSection, 'menu_items');
        
        return $main;
        
    }
    
    
    
    
    function getRewrites()
    {
        return xPDO::selectIN('*', 'routes');
    }
    
    
    
    function showMap($params)
    {
        $this->mapMode         = true;
        $params['showGroupId'] = 1;
        $map                   = $this->showLevelMenu($params);
        $this->mapMode         = false;
        return $map;
    }
    
    
    
    function showUserMenu($params)
    {
        //global $this->_TMS, $TDB, $TPA;
        
        $menu   = $TDB->get_results('select pages, header from user_menu where id=' . $params['menu']);
        $pages  = $menu[1]['pages'];
        $pages1 = explode(',', $pages);
        $w      = Array();
        $x      = array_keys($pages1);
        
        $items = $this->_tree->GetNodesByIdArray($pages1);
        
        $cur_page_id    = $TPA->page_node['id'];
        $cur_page_basic = $TPA->page_node['basic'];
        foreach ($x as $x1)
        {
            $w[$pages1[$x1]] = $x1;
        }
        
        foreach ($items as $i)
        {
            $key       = $w[$i['id']];
            $arr[$key] = array(
                'id' => $i['id'],
                'text' => $i['params']['Name'],
                'obj_type' => $i['obj_type'],
                'Icon' => $i["params"]["Icon"],
                'basic' => $i["basic"],
                'Comment' => $i["params"]["Comment"],
                'StartPage' => $i["params"]["StartPage"]
                
            );
            
        }
        
        ksort($arr);
        
        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();
        
        $this->_TMS->AddFileSection(Common::get_fmodule_tpl_path('menu', $params['Template']));
        
        $i = 0;
        
        $umenu = '';
        
        foreach ($arr as $p)
        {
            if ($p['obj_type'] != '_LINK')
            {
                $alink = $pages->create_page_path($p['id']);
            }
            else
            {
                $alink = $items[$p['id']]['params']['Link'];
            }
            $selected = '';
            if ($p["id"] == $TPA->page_node["id"] || ($p['obj_type'] == '_GROUP' && ($TPA->page_node['id'] == $p['StartPage'])))
            {
                $selected = 'selected_';
            }
            
            $r = array(
                'link' => $alink,
                'basic' => $p["basic"],
                'caption' => $p['text'],
                '_num' => $i,
                'oddeven' => ($i % 2),
                'Icon' => $p['Icon'],
                'Comment' => $p['Comment']
            );
            
            if ($i == 0 && $this->_TMS->isSectionDefined('_menu_item_first_' . $selected . 'level0'))
            {
                $this->_TMS->AddMassReplace('_menu_item_first_' . $selected . 'level0', $r);
                $umenu .= $this->_TMS->parseSection('_menu_item_first_' . $selected . 'level0');
            }
            elseif (($i == sizeof($arr) - 1) && $this->_TMS->isSectionDefined('_menu_item_last_' . $selected . 'level0'))
            {
                $this->_TMS->AddMassReplace('_menu_item_last_' . $selected . 'level0', $r);
                $umenu .= $this->_TMS->parseSection('_menu_item_last_' . $selected . 'level0');
            }
            else
            {
                $this->_TMS->AddMassReplace('_menu_item_middle_' . $selected . 'level0', $r);
                $umenu .= $this->_TMS->parseSection('_menu_item_middle_' . $selected . 'level0');
            }
            $i++;
        }
        
        $this->_TMS->AddReplace('_menu_main_level0', 'menu_buff', $umenu);
        $this->_TMS->AddReplace('_menu_main_level0', 'header', $menu[1]['header']);
        $menu = $this->_TMS->parseSection('_menu_main_level0');
        return $menu;
        
    }
    
    
}

?>
