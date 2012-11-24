<?php

class content_module_front
{
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function content_module_front()
    {
        $this->_module_name='content';
        //true так как front_call
        $this->_common_obj =&content_module_common::getInstance(true);
        $this->_tree       =&$this->_common_obj->obj_tree;
    }

    function execute($action_data)
    { 
        global $TMS;

        if(is_array($action_data))
        {
            if ($action=$this->_common_obj->is_action($action_data['Action']))
            {
                $TMS->startLogging($action_data['Action']);
                $q = &$this->$action($action_data);
                $TMS->clearLogged($action_data['Action']);
                
                return $q;
            }
        }
    }

    function request_action_set($action) { $this->_common_obj->request_action_set($action); }

    function content_server($params)
    {
        global $TMS, $REQUEST_ASSOC;

        if (!isset($REQUEST_ASSOC['cid']))
        {
            if ($defaultAction=$params['Default_action'])
            {
                return $this->$defaultAction($params);
            }
        }
        else
        {
            return $this->show_content(array_merge($params, array('contentId' => $REQUEST_ASSOC['cid'])));
        }
    }

    function show_articles($params)
    {
        return $this->show_contents_list($params);
    }
    
    function show_article($params)
    {
        return $this->show_content($params);
    }
    

    function show_contentgroups_list($params)
    {
        global $TMS, $TPA;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
        
        Common::call_common_instance('pages');
        $pages = &pages_module_common::getInstance();  
        $content_page = $pages->create_page_path($params['page']) . '/~show_articles/cid/';
        $link = $pages->create_page_path($params['page']) . '/~show_article/cid/';

            if($groups = $this->_tree->GetChildsParam(1, '%'))
            {
                $articles = array();
                
                    while(list($k,$v) = each($groups))
                    {
                        if($v['view_group'])
                        {
                            //GetChildsParam($anc, $param, $get_ancestor_params = null, $ancestor_params_equalto = null, $order = 'ASC',$limit='')
                            $groups[$k] = $this->_tree->getNodeInfo($k);
                            //$articles[$k] = $this->_tree->GetChildsParam($k, '%', true, null, 'ASC');
                            $groups[$k]['articles'] = $this->_tree->GetChildsParam($k, '%', true, null, 'ASC');
                        }
                        else
                        {
                            unset($groups[$k]);
                        }
                    }

                $TMS->AddReplace('content_groups', 'groups', $groups);
                //$TMS->AddReplace('content_groups', 'articles', $articles);
                return $TMS->parseSection('content_groups', true);
            }
    }
    
        
    function show_contents_list($params)
    {
        global $TMS, $TPA, $REQUEST_ASSOC;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['xTemplate']));
        $startpage = isset($REQUEST_ASSOC['sl']) ? (int) $REQUEST_ASSOC['sl'] : 0;
        $count = (int) $params['OnPage'];

        Common::call_common_instance('pages');
        $pages = &pages_module_common::getInstance();  
        $content_page = $pages->create_page_path($params['page']) . '/~show_articles/cid/';
        $link = $pages->create_page_path($params['page']) . '/~show_article/cid/';

            if($articles_count = $this->_tree->GetChilds($params['Category'], 'ASC', 0, 0, null, true))
            {
                if(!$count) $count = $articles_count;
                if($articles_count > 0 && $count > 0)
                {
                    if($articles_count / $count > 1)
                    {
                        Common::parse_nav_pages($articles_count, $count, $startpage, $content_page . $params['Category']);
                        $articles = $this->_tree->GetChilds($params['Category'], 'ASC', $startpage, $count);
                    }
                    else
                    {
                        $articles = $this->_tree->GetChilds($params['Category']);
                    }
                }
                
                foreach($articles as $article)
                {
                    $fd = $this->_common_obj->get_fields_data($article['id']);
                    $fd['link'] = $link . $article['id'];
                    $TMS->AddMassReplace('article', array_merge($fd, $article));
                    $TMS->parseSection('article', true);
                }
            }

        $Category          =$this->_tree->getNodeInfo($params['Category']);
        $cat['header']     =$Category['basic'];
        $cat['description']=$Category['params']['description'];
        
        $this->seoConfirm($Category);
        
        $TMS->AddMassReplace('articles_list', $cat);
        return $TMS->parseSection('articles_list', true);
    }
    

    function show_content_announce($params)
    {
        Common::call_common_instance('pages');
        $pages             =&pages_module_common::getInstance();
        $params['Template']=$params['aTemplate'];
        $params['link']    =$pages->create_page_path($params['page']) . '/~show_article/cid/' . $params['contentId'];

        return $this->show_content($params);
    }

    function show_content($params, $cycl = null)
    {
        global $TMS;
        static $cycles;

        if ($node = $this->_tree->getNodeInfo($params['contentId']))
        {
            $this->seoConfirm($node);
            
            $fd=$this->_common_obj->get_fields_data($params['contentId']);

            if($params['link']) $fd['link']=$params['link'];

            $cycles[] = $params['contentId'];

            //Выбор шаблона нестандартного - для анонса или для сервера
            $Template = ($params['Template']) ? $params['Template'] : $node['params']['Template'];

            $TMS->delSection('xtr_content');
            $TMS->enableExtendFields(true);
            if(trim($Template))
            {
                $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $Template));
                $TMS->AddReplace('xtr_content', 'content_name', $node['basic']);

                if($TMS->Extended['xtr_content'])
                {
                    foreach ($TMS->Extended['xtr_content'] as $field_name => $ext)
                    {
                        //здесь рекурсивная сборка статей
                        if(($ext['type'] == 'ARTICLE') && ($fd[$field_name]))
                        {
                            //возможно зацикливание статей на вложенности, предотвращаем это
                            if(!in_array($fd[$field_name], $cycles))
                            {
                                $fd[$field_name]=$this->show_content(array('contentId' => $fd[$field_name]), true);

                                $TMS->delSection('xtr_content');
                                $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $node['params']['Template']));
                            }
                        }
                    }
                }
           }

            if (is_array($fd))
            {
                $TMS->AddMassReplace('xtr_content', $fd + array('basic' => $node['basic']));
            }

            //обнуляем буфер цикла 
            //отключаем обработку длинных полей
            if(!$cycl)
            {
                $TMS->enableExtendFields(false);
                $cycles=null;
            }
	            
	        $TMS->enableExtendFields(false);
            
            return $TMS->parseSection('xtr_content');
         
         }
    }
    
    
    function get_requested_basic_id($point)
        {
        global $REQUEST_VARS, $REQUEST_ASSOC,$TPA;

        
        
        $rcount = count($REQUEST_VARS);
        
        for ($i=1; $i < $rcount; $i++)
            {
            if (strpos($REQUEST_VARS[$i], '@') === 0)
                {
                if (count($rb) % 2 == 1)
                    {
                        global $TPA;
                        
                        if($vs=array_slice($REQUEST_VARS, $i-1))$REQUEST_ASSOC = $TPA->parse_request_vars($vs);
                    }
                break;
                }

            if ($REQUEST_VARS[$i])
                {
                $rb[]=$REQUEST_VARS[$i];
                }
            }
            
            
        if ($rb)
            {
            
            //if($r=$this->_tree->IdByBasicPath($rb,  array('_CONTENT','_CONTENTGROUP'),null, $point))            
            if($r=$this->_tree->IdByBasicPath($rb,  array('_CONTENT','_CONTENTGROUP'),null, 1))            
            {
                    return $r;    
                    
                }else{
                    
                    $TPA->show_e404_page();
                    die();   
                }
                
            }    
            
        }
    
    
    function showcontent($params)
    {
        $this->_tree->recursiveChildCollectFast(1, array('_CONTENTGROUP'), 'ASC', 0, true);

            if ($id = $this->get_requested_basic_id($params['Category']))
            {
                $node = end($this->_tree->BonesMas);
                $params['id'] = $id; 
                $params['contentId'] = $id; 
            }
            else
            {
                $node = $this->_tree->getNodeStruct($params['Category']);
                $params['id'] = (int) $params['Category'];
            }
            
            if($node['obj_type'] == '_CONTENT')
            {
                return $this->show_article($params);
            }
            else
            {
                return $this->show_articles($params);
            }
    }
    
    
    function seoConfirm($node)
    {
            global  $TMS, $TPA;
             
            if ($TMS->isSectionDefined('Title'))
                {
                $TMS->AddMassReplace('Title', $node);
                $title=$TMS->parseSection('Title');
                }
            else
                {
                $title=$node['params']['Title'];
                }

            $TPA->externalMeta['Title']=$title;

            if ($TMS->isSectionDefined('Keywords'))
                {
                $TMS->AddMassReplace('Keywords', $node);
                $keywords=$TMS->parseSection('Keywords');
                }
            else
                {
                $keywords=$node['params']['Keywords'];
                }

            $TPA->externalMeta['Keywords']=$keywords;

            if ($node['params']['Keywords'])
                {
                $TPA->externalMeta['Keywords']=$node['params']['Keywords'];
                }

            if ($node['params']['Description'])
                {
                $TPA->externalMeta['Description']=$node['params']['Description'];
                }
    
    }
    

    function build_content($content_id)
    {
        global $TMS;
        
        $fields=XARRAY::arr_to_lev($this->_tree->GetChildsParam($id, array('field_value'), true), 'basic', 'params', 'field_value');
    }
    } #endclass
?>