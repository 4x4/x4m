<?php
class catalog_module_front
    extends catalog_module_tpl
    {
    var $menu_ancestor;
    var $postoff_buffer;
    var $cross_vars;
    var $bones_path;
    var $pset_link;
    var $cat_destination_page;
    var $ishop;
    var $BasketPage         = null;
    var $onpage;
    var $dynamic_filter_data;
    var $current_search_filter;
    var $use_crumbs_postfix = '';

    function catalog_module_front()
        {
        global $TMS,$REQUEST_ASSOC;
        
        
        
        $this->_module_name='catalog';
        $this->_common_obj =&catalog_module_common::getInstance(true); //true ??? ??? front_call
        $this->_tree       =&$this->_common_obj->obj_tree;
        $this->_tree->enable_cache(true);
        $this->context=null;
        parent::__construct();

        if (Common::is_module_exists('ishop'))
            $this->ishop=true;
        }

    function get_requested_basic_id($point)
        {
        global $REQUEST_VARS, $REQUEST_ASSOC,$TPA;

        
        
        $rcount=count($REQUEST_VARS);
        for ($i=1; $i < $rcount; $i++)
            {
            if (strpos($REQUEST_VARS[$i], '@') === 0)
                {
                if (count($rb) % 2 == 1)
                    {
                        global $TPA;
                        if($vs=array_slice($REQUEST_VARS, $i-1))$REQUEST_ASSOC=$TPA->parse_request_vars($vs);
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
            
            if($r=$this->_tree->IdByBasicPath($rb,  array('_CATOBJ','_CATGROUP'),null, $point))            
            {
                    return $r;    
                    
                }else{
                    
                    $TPA->show_e404_page();
                    die();   
                }
                
            }    
            
        }
        
    //decision function  
    function show($params)
        {
        $this->_tree->recursiveChildCollectFast(1, array('_CATGROUP'), 'ASC', 0, true);
            
        if ($id=$this->get_requested_basic_id($params['showBasicPointId']))
            {
            $node=end($this->_tree->BonesMas);
              $params['id']=$id; 
            }else{
              
                $node=$this->_tree->getNodeStruct($params['showGroupId']);
                $params['id']=$params['showGroupId'];
            }  
            
            if ($node['obj_type'] == '_CATOBJ')
                {
                
                return $this->showobj($params);
                }
            else
                {
                ;
                return $this->showcat($params);
                }
            
        }

        function get_link_by_id($obj_id, $page_link, $node_id=null)
        {
        static $tree_pathes;
                
        if ($tree_path=explode('/', $page_link))
            {

            $this->_tree->recursiveChildCollectFast(1, array('_CATGROUP'), 'ASC', 0, true);
            
            
            if (!$tree_pathes[md5($page_link)])
                {
                $tree_path=XARRAY::clear_empty_items($tree_path);
                $pages    =&Common::module_factory('pages.front');
                if (!$node_id) $node_id=$pages->_tree->IdByBasicPath($tree_path, array
                    (
                    '_PAGE',
                    '_GROUP'
                    ),                                    true);
                    
                if ($node_id) $module=$pages->_common_obj->get_module_by_action($node_id, 'show_catalog_server');

                $module_id=$tree_pathes[md5($page_link)]=$module['params']['showBasicPointId'];
                }
            else
                {
                $module_id=$tree_pathes[md5($page_link)];
                }

            $cat_node=$this->_tree->getNodeStruct($obj_id);

            if ($cat_node['obj_type'] == '_CATOBJ')
                {
                $id   =$this->_tree->getAncestor($obj_id);
                $basic='/' . $cat_node['basic'];
                }
            else
                {
                $basic='';
                $id   =$obj_id;
                }

            return $rpath=$page_link  .'/'. $this->_tree->calculate_r_path($id, $module_id, array('_CATGROUP'),
                                                                                  2) . $basic;
            }
        }

    function cat_objects_render($cat_objects, $section_to_parse = '_catalog_list_item', $anc = null, $prefix = '',
                                    $destinaton = null, $render_by_items = null, $discount_recount = false)
        {
        global $TMS, $TPA, $_PATH;

        if (!$destinaton)
            {
            $destinaton=$TPA->page_link;
            }

        $objs='';

        if ($cat_objects)
            {
            $obj_count=count($cat_objects);
            $discount =$this->discount;


            //get catalog structure for link calculations
            
            $this->_tree->recursiveChildCollectFast(1, array('_CATGROUP'), 'ASC', 0, true);   
            
            foreach ($cat_objects as $key => $obj)
                {
                if (!$obj['params']['Disable'])
                    {
                    $ancestor=$obj['ancestor'];
                    $i++;

                    if ($this->ishop && $discount_recount)
                        {
                        $discount=$this->getDiscountForCategory($key);
                        }

                    $basic               =$obj['basic'];

                    $obj                 =$this->_common_obj->property_set_to_properties($obj['params']['Property_set'],
                                                                                         $obj,
                                                                                         $prefix,
                                                                                         true,
                                                                                         $this,
                                                                                         $discount);

                    //$action              ='show';

                    $rpath               =$this->_tree->calculate_r_path($ancestor, $anc, array('_CATGROUP'), 2);
                    
                    if($rpath)$rpath='/' . $rpath;
                    $obj['props']['Link']=$destinaton . $rpath . '/' . $basic;

                    

                    

                    if ($obj['props'])
                        {
                            $TMS->AddMassReplace($section_to_parse, $obj['props']);
                            
                            $psection = $section_to_parse.'_'.$obj["props"]["Property_set"];
                            
                            if ($TMS->isSectionDefined($psection)) 
                            {
                                $TMS->AddMassReplace($psection,$obj["props"]);                            
                                $TMS->AddReplace($section_to_parse,'_properties', $TMS->parseSection($psection));
                            }
                        }

                        
                        
                    $obj['ancestor']=$ancestor;

                    $TMS->AddReplace($section_to_parse, 'Discount', $discount);

                    $obj['OddEven']=($i + 1) % 2;
                    $obj["_num"]=$i;
                    
                    $TMS->AddMassReplace($section_to_parse, $obj);

                    if ($render_by_items)
                        {
                        $objs.=$TMS->parseSection($section_to_parse);

                        if (($i % abs($render_by_items) == 0) || ($i > ($obj_count - 1)))
                            {
                            $TMS->AddReplace('_catalog_list_divider' . $property_set_anc, 'catalog_items', $objs);
                            $TMS->parseSection('_catalog_list_divider' . $property_set_anc, true);
                            $objs='';
                            }
                        }
                    else
                        {
                        $TMS->parseSection($section_to_parse, true);
                        }

                    $TMS->clear_section_fields($section_to_parse);
                    }
                else
                    {
                    $obj_count--;
                    }
                }
            }
        }

    function show_category($parameters)
        {
        $pages                    =&pages_module_common::getInstance();        
        $parameters['Destination']=$pages->create_page_path($parameters['Destination_page']);
        $parameters['id']         =$parameters['showGroupId'];

        if ($d=$pages->get_module_by_action($parameters['Destination_page'], 'show_catalog_server'))
            {
            $parameters['showBasicPointId']=$d['params']['showBasicPointId'];
            }

        if (!$parameters['Onpage'])$parameters['Onpage']=$parameters['Onpage_category'];

        $parameters['CategoryTemplate']=$parameters['InnerCategoryTemplate'];
        return $this->showcat($parameters);
        }

    function getDiscountForCategory($id)
        {
        static $dscheme;

        if ($this->ishop && $_SESSION['siteuser']['authorized'])
            {
            Common::call_common_instance('ishop');
            $ishop=&ishop_module_common::getInstance();

            if (!$dscheme)
                $dscheme=$ishop->getDiscountScheme($_SESSION['siteuser']['userdata']['DiscountScheme']);

            if ($dscheme)
                {
                $this->_tree->GetFullBonesUp($id);

                if ($fbu=XARRAY::arr_to_keyarr($this->_tree->FullBonesMas, 'id', 'id'))
                    {
                    foreach ($fbu as $bone)
                        {
                        if (($fkey=array_search($bone, array_flip($dscheme))) !== false)
                            return $fkey;
                        }
                    }
                }
            }
        }

 function get_bones2($from, $destination, $obj_types, $end_node=null,$dst_page='')
        {            
            global $TPA;

        if ($bones=$this->_tree->calculate_r_path($from, $destination, $obj_types, 1))
            {
            $ids=XARRAY::askeyval($bones, 'id');

            if(!$dst_page)$dst_page=$TPA->page_link;
            
            $gid=$this->_tree->GetNodesByIdArray2($ids, 'ASC', false, true, false, true);

            foreach ($bones as $bone)
                {

                    $bone['page_link'] = $dst_page .'/'. $this->_tree->calculate_r_path($bone['id'], $destination, $obj_types, 2);
                    $bone['params']=$gid[$bone['id']];
                    $this->bones_path[$bone['id']]=$bone;
                }
            

            }
        
        if($end_node)$this->bones_path[$end_node['id']]=$end_node;
        
        }


    function showcat($parameters)
        {
        global $REQUEST_ASSOC, $TMS, $TPA, $_PATH;
     
        if($REQUEST_ASSOC['@onpage'])$_SESSION['catalog']['Onpage']=$REQUEST_ASSOC['@onpage'];

        $onpage=explode(',',$parameters['Onpage']);
            
            if(count($onpage)>1)
            {
                $this->onpage=$onpage;   
            }
            
            
        if(!$_SESSION['catalog']['Onpage'])
        {
            
            $parameters['Onpage']=$onpage[0];
            
        }else{
            
            $parameters['Onpage']=$_SESSION['catalog']['Onpage'];
        }
        
        

        $cat_id=isset($parameters['id']) ? $parameters['id'] : $this->getIdByBasic($REQUEST_ASSOC['id']);

        if ($cat_id)
            {
            if (!$cat_node=$this->_tree->getNodeInfo($cat_id))
                return;

            $cat_basic=$cat_node['basic'];
            $prop_set =$cat_node['params']['Property_set'];

            if ($cat_node['params']['StartGroup'])
                {
                $parameters['id']=$cat_node['params']['StartGroup'];
                return $this->showcat($parameters);
                }

            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['CategoryTemplate']));

            $startpage     =isset($REQUEST_ASSOC['@sl']) ? $REQUEST_ASSOC['@sl'] : 0;
            $this->discount=$this->getDiscountForCategory($cat_node['id']);

            $this->seoConfirm($cat_node);
            
            $onpage =isset($REQUEST_ASSOC['onpage']) ? $REQUEST_ASSOC['onpage'] : $parameters['Onpage'];
            $endpage=$startpage + $onpage;

            $obj_type=array('_CATOBJ');

            if ($parameters['GetCatObj'])
                {
                $obj_type[]='_CATGROUP';
                }

            if ($REQUEST_ASSOC['onpage'] == 'all')
                {
                $onpage=$obj_count;
                }
            else
                {
                $onpage=isset($REQUEST_ASSOC['onpage']) ? (int)$REQUEST_ASSOC['onpage'] : $parameters['Onpage'];
                }

            $destination=isset($parameters['Destination']) ? $parameters['Destination'] : $TPA->page_link;
            
            $this->_tree->recursiveChildCollectFast(1, array('_CATGROUP'), 'ASC', 0, true);
                     

            $this->get_bones2($cat_id, $parameters['showBasicPointId'], array('_CATGROUP'),null,$destination);
            
            if($path=$this->_tree->calculate_r_path($cat_id,$parameters['showBasicPointId'], array('_CATGROUP'),2))
            {
            $destination=$destination. '/' . $path. $basic;
            } 

            

            if ($this->ishop)
                {
                $pages           =&pages_module_common::getInstance();
                $this->BasketPage=$pages->create_page_path($parameters['BasketPage']);
                }

            
            $cat_node=$this->_common_obj->property_set_to_properties(
                                             $cat_node['params']['Property_set'], $cat_node, 'ancestor.', false, $this);
                                             

            $cat_node['props']['Link']  =$destination;
            $cat_node['props']['basic'] =$cat_basic;
            $cat_node['props']['cat_id']=$cat_node['id'];

            $section                    ='_catalog_category_list';
            
            $TMS->AddMassReplace($section, $cat_node['props']);

            $filter['spack']=array();

            if ($rqtf=$this->request_to_filter())
                {
                $filter['spack']=array_merge_recursive($rqtf, $filter['spack']);
                $getter_line    =$this->filter_to_request($filter['spack']);
                }

            $filter['spack']['ancestor']=array
                (
                'ancestor' => $cat_id,
                'obj_type' => $obj_type
                );

            $filter['startpage']=$startpage;
            $filter['Onpage']   =$onpage;

            if ((!$filter['spack']['ignoredynamicfilter']) && ($this->dynamic_filter_data))
                {
                $filter['spack']=array_merge_recursive($this->dynamic_filter_data, $filter['spack']);
             
                }
                
                
             

             if (!$cat_objects=$this->select_objects($filter))
                {
                    $TMS->parseSection('_catalog_empty', true);
                    return;
                }


            Common::parse_nav_pages($cat_objects['count'], $onpage, $startpage, $destination . $getter_line, '', '@sl');

            $TMS->AddMassReplace($section, array('search_filter' => $getter_line));

           $this->cat_objects_render($cat_objects['objects'],
                                      '_catalog_list_item',
                                      $parameters['showBasicPointId'],
                                      '',
                                      isset($parameters['Destination']) ? $parameters['Destination'] : $TPA->page_link,
                                      $parameters['Render_by_items'],
                                      null,
                                      null);
                                      


            return $TMS->parseSection($section);
            
            }

        return '';
        }

    function show_catalog_server($params, $putt_off_called = false)
        {
        global $REQUEST_VARS;

        if ($putt_off_called && ($this->_common_obj->is_action_called(
                                     'catsearch') || ($this->_common_obj->is_action_called('catfilter'))))
            return;

        if (!((int)$REQUEST_VARS[1]))
            {
            if ($params['Action']=$params['Default_action'])
                {
                unset ($parameters['Default_action']);
                return $this->execute($params);
                }
            }
        }

    function filter_to_request($spack)
        {
  
        if ($spack)
            {
            $f='';
            foreach ($spack as $pk => $pack)
                {
                $criteria = '@' . $pk;

                if (!empty($pack))
                    {
                    if (is_array($pack))
                        {
                        $stack=array();

                        foreach ($pack as $property => $value)
                            {
                            if (!empty($value))
                                {
                                if (is_array($value))
                                    $value=implode('|', $value);

                                $stack[]=$property . '=' . $value;
                                }
                            }

                        if (!empty($stack))
                            $f.='/' . $criteria . '/' . implode("&", $stack);
                        }
                    else
                        {
                        if (!empty($pack))
                            $f.='/' . $criteria . '/' . $pack;
                        }
                    }
                }

            return $f;
            }
        }

    function request_to_filter()
        {
        global $REQUEST_ASSOC;

        static $rqtf;

        if ($rqtf)
            {
            return $rqtf;
            }

        if (is_array($REQUEST_ASSOC))
            {
            foreach ($REQUEST_ASSOC as $criteria => $sp)
                {
                switch ($criteria)
                    {
                    case '@equal':
                        foreach ($sp as $field => $eq)
                            {
                            if (is_array($eq))
                                {
                                $sparams['equalor'][$field]=$eq;
                                }
                            else
                                {
                                $sparams['equal'][$field]=$eq;
                                }
                            }

                        break;

                    case '@ancestors':
                    case '@ancestor':
                    case '@rwords':
                    case '@rlike':
                    case '@lrlike':
                    case '@rwords':
                    case '@from':
                    case '@sort':
                    case '@sortby':
                    case '@to':
                        $criteria=substr($criteria, 1);

                        foreach ($sp as $field => $eq)
                            {
                            $sparams[$criteria][$field]=$eq;
                            }

                        break;

                    case '@endleafs':
                        $sparams['endleafs']=$sp;

                        break;

                    case '@ignoredynamicfilter':
                        $sparams['ignoredynamicfilter']=$sp;

                        break;

                    case '@interval':
                        foreach ($sp as $field => $eq)
                            {
                            if (is_array($eq))
                                {
                                if (trim($eq[0]))
                                    $sparams['from'][$field]=$eq[0];

                                if (trim($eq[1]))
                                    $sparams['to'][$field]=$eq[1];
                                }
                            }

                        break;
                    }
                }

            return $rqtf=$sparams;
            }
        }

    function sortby($id, $parr)
        {
        if ($keys=array_keys($parr))
            {
            if ($nodes=$this->_tree->getNodesByIdArray2($id, 'ASC', false, $keys, false, true))
                {
                return XARRAY::array_msort($nodes, $parr);
                }
            }
        }
    /*
    *  $parr[0] -by param
    *   $parr[1] -asc,desc
    *   $parr[2] -cast as 
    */

    function sorter($id, $parr, $start = 0, $onpage = 20)
        {
        global $TDB;
                          
        if (!$parr[1])
            {
            $parr[1]='asc';
            }

        if ($parr[2])
            {
            $parr[2]='CAST(value AS ' . $parr[2] . ')';
            }
        else
            {
            $parr[2]='value';
            }

        $q='SELECT node_name FROM `_tree_catalog_container_param` WHERE `parameter`="' . $parr[0]
            . '" and node_name in("' . implode('","', $id) . '") order by ' . $parr[2] . ' ' . $parr[1];

        if ($r=$TDB->get_results($q))
            {
            return XARRAY::askeyval($r, 'node_name');
            }
        }

    function catalog_filter($params)
        {
        global $TMS, $TPA, $_PATH;

        
        Common::call_common_instance('pages');
        $pages      =&pages_module_common::getInstance();
        $destination=$pages->create_page_path($params['Destination_page']);

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['FilterTemplate']));
        return $TMS->parseSection('_catalog_show_filter');
        }

    function catalog_filter_results($params)
        {
        global $TMS, $TPA;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['ResultTemplate']));

        if ($this->dynamic_filter_data)
            {
            $params['search']         =$this->dynamic_filter_data;

            $this->dynamic_filter_data=null;
            $pages                    =&pages_module_common::getInstance();

            //                $params['Destination']    =$pages->create_page_path($params['Destination_page']);

            $params['ignoreTemplate'] =1;
            return $this->catsearch($params);
            }
        }

    function show_search_results() { }

    function delcomparse($params)
        {
        global $REQUEST_ASSOC;

        if (is_array($_SESSION['catalog']['comparsedata']))
            {
            if ($REQUEST_ASSOC['id'] == 'all')
                {
                unset ($_SESSION['catalog']['comparsedata']);
                }
            else
                {
                $objid=$REQUEST_ASSOC['id'];

                if (($fkey=array_search($objid, $_SESSION['catalog']['comparsedata'])) !== false)
                    unset ($_SESSION['catalog']['comparsedata'][$fkey]);
                }
            }

        return $this->comparsion($params);
        }

    function remove_comparse($params)
        {
        if (isset($_SESSION['catalog']['comparsedata']))
            unset ($_SESSION['catalog']['comparsedata'][$params['id']]);
        }

    function catalog_comparsion($params) { return $this->comparsion($params); }

    function comparsion($params)
        {
        global $REQUEST_ASSOC, $TMS, $_PATH, $TPA;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['ComparsionTemplate']));

        $comparse_elements=XARRAY::clear_empty_items(explode(',', $_POST['catalog']['comparsedata']));

        if ($this->ishop)
            {
            $pages           =&pages_module_common::getInstance();
            $this->BasketPage=$pages->create_page_path($params['BasketPage']);
            }

        if ((!empty($comparse_elements)) or (!empty($_SESSION['catalog']['comparsedata'])))
            {
            if (!$_SESSION['catalog']['comparsedata'])
                {
                $_SESSION['catalog']['comparsedata']=array();
                }

            $_SESSION['catalog']['comparsedata']=$_SESSION['catalog']['comparsedata'] + $comparse_elements;
            $comparse_elements                  =$_SESSION['catalog']['comparsedata'];

            foreach ($comparse_elements as $el)
                {
                $i++;
                $cat_node                              =$this->_tree->getNodeInfo($el);
                $ps                                    =$cat_node['params']['Property_set'];

                $cat_node                              =$this->_common_obj->property_set_to_properties(
                                                            $cat_node['params']['Property_set'], $cat_node, false,
                                                            $this);
                $cat_node['props']['Link']             =$TPA->page_link . '/~showobj/id/' . $el;
                $cat_node['props']['del_comparse_link']=Common::create_action_link($TPA->page_link, 'delcomparse',
                                                                                   array('id' => $el));
                $catnode_table[$i]                     =$cat_node;
                $TMS->AddMassReplace('_catalog_comparse_head', $cat_node['props']);
                $TMS->AddMassReplace('_catalog_comparse_head', $cat_node);
                $TMS->parseSection('_catalog_comparse_head', true);
                $TMS->clear_section_fields('_catalog_comparse_head');
                }

            $prop_table=$this->_common_obj->get_properties($ps, false, $subgroups, true);

            foreach ($subgroups as $sub_id => $subgroup)
                {
                if ($prop_table[$sub_id])
                    {
                    while (list($prop_t, $property)=each($prop_table[$sub_id]))
                        {
                        if (!$property['params']['isComparse'])
                            {
                            $TMS->AddMassReplace('_catalog_comparse_element', array
                                (
                                'value' => $property['params']['Alias'],
                                'type'  => 'FIRST'
                                ));

                            $TMS->parseSection('_catalog_comparse_element', true);

                            foreach ($catnode_table as $catnode)
                                {
                                $value = ($catnode['props'][$subgroup['SubNameEng']])
                                    ? $catnode['props'][$subgroup['SubNameEng']][$property['params']['Name']]
                                    : $catnode['props'][$property['params']['Name']];

                                if ($value)
                                    {
                                    $type=$property['params']['Type'];
                                    }
                                else
                                    {
                                    $type='EMPTY';
                                    }

                                $TMS->AddMassReplace('_catalog_comparse_element', array
                                    (
                                    'type'  => $type,
                                    'value' => $value
                                    ));

                                $TMS->parseSection('_catalog_comparse_element', true);
                                }

                            $TMS->AddMassReplace('_catalog_comparse_line', array
                                (
                                'key'     => $property['params']['Name'],
                                'colspan' => count($catnode_table) + 1
                                ));

                            $TMS->parseSection('_catalog_comparse_line', true);
                            $TMS->KillMFields('_catalog_comparse_element');
                            }
                        }

                    $TMS->AddMassReplace('_catalog_comparse_group', array
                        (
                        'Name'    => $subgroup['SubName'],
                        'colspan' => count($catnode_table) + 1,
                        'id'      => $sub_id
                        ));

                    $TMS->parseSection('_catalog_comparse_group', true);

                    $TMS->KillMFields('_catalog_comparse_line');
                    }
                }

            $TMS->AddMassReplace('_catalog_comparse',
                                 array('del_comparse_link' => Common::create_action_link($TPA->page_link, 'delcomparse',
                                                                                         array('id' => 'all'))));
            return $TMS->parseSection('_catalog_comparse');
            }
        else
            {
            return $TMS->parseSection('_catalog_comparse_failed');
            }
        }

    function select_objects($params)
        {
            

        if ($params['spack']['endleafs'])
            {
            $params['spack']['ancestors'][$params['spack']['endleafs']][]=$params['spack']['ancestor']['ancestor'];
            unset ($params['spack']['ancestor']['ancestor']);
            }

        if (is_array($params['spack']))
            {
            foreach ($params['spack'] as $stype => $svalues)
                {
                switch ($stype)
                    {
                    case 'to':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        while (list($k, $v)=each($svalues))
                            {
                            $svalues[$k]=XSTRING::date_recognize($v);
                            }

                        if (!empty($svalues))
                            {
                            $OM=$this->_tree->Search($svalues, false, false, $OM, '<=');

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'from':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        while (list($k, $v)=each($svalues))
                            {
                            $svalues[$k]=XSTRING::date_recognize($v);
                            }

                        if (!empty($svalues))
                            {
                            $OM=$this->_tree->Search($svalues, false, false, $OM, '>=');

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'rlike':
                    case 'rwords':
                    case 'lrlike':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        if ($stype == 'rwords')
                            {
                            $or=1;
                            }
                        else
                            {
                            $or=0;
                            }

                        if (!empty($svalues))
                            {
                            $OMR=$this->_tree->Search($svalues, false, false, null, $stype, null, null, $or);

                            if (!$OM)
                                {
                                $OM=$OMR;
                                }
                            else
                                {
                                $OM=array_intersect($OMR, $OM);
                                }

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'equal':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        if (!empty($svalues))
                            {
                            $OM=$this->_tree->Search($svalues, false, false, $OM, '=');

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'equalor':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        if (!empty($svalues))
                            {
                            if (XARRAY::array_depth($svalues) > 1)
                                {
                                foreach ($svalues as $v => $qa)
                                    {
                                    foreach ($qa as $c)
                                        {
                                        $ZM=$this->_tree->Search(array($v => $c), false, false, $ZM, '=', null, null,
                                                                 true);
                                        }
                                    }
                                }
                            else
                                {
                                $ZM=$this->_tree->Search($svalues, false, false, $ZM, '=', null, null, true);
                                }

                            if ($ZM && $OM)
                                {
                                $OM=array_intersect($ZM, $OM);
                                }
                            else
                                {
                                $OM=$ZM;
                                }

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'inchildsall':
                        $svalues=XARRAY::clear_empty_items($svalues);

                        if (!empty($svalues))
                            {
                            foreach ($svalues['id'] as $sv)
                                {
                                if ($childs=$this->_tree->Search(array('id' => $sv), true, false, null, '='))
                                    {
                                    $childs=XARRAY::askeyval($childs, 'ancestor');

                                    if (!$LOM)
                                        {
                                        !$LOM=$childs;
                                        }

                                    $LOM=array_intersect($LOM, $childs);
                                    }
                                else
                                    {
                                    break 1;
                                    }
                                }

                            if ($OM)
                                {
                                $OM=array_intersect($LOM, $OM);
                                }

                            if (empty($OM))
                                break 1;
                            }

                        break;

                    case 'inchilds':
                        $svalues   =XARRAY::clear_empty_items($svalues);

                        $stop_level=array_keys($svalues);

                        if (!empty($svalues))
                            {
                            if ($childs=$this->_tree->Search($svalues, true, false, null, '='))
                                {
                                $a=XARRAY::askeyval($childs, 'ancestor');

                                if ($OM)
                                    {
                                    $OM=array_intersect($a, $OM);
                                    }
                                }
                            else
                                {
                                break 3;
                                }

                            if (empty($OM))
                                break 2;
                            }

                        break;

                    case 'ancestors':
                        $svalues   =XARRAY::clear_empty_items($svalues);

                        $stop_level=array_keys($svalues);

                        $ancestors=array();

                        $this->_tree->EXPcache=null;

                        if (is_array(!$svalues[$stop_level[0]]))
                            {
                            $ancarr[]=trim($svalues[$stop_level[0]]);
                            }
                        else
                            {
                            $ancarr=$svalues[$stop_level[0]];
                            }

                        if (is_array($ancarr))
                            {
                            foreach ($ancarr as $anc)
                                {
                                if ($anc)
                                    {
                                    $a=$this->_tree->recursiveChildCollectFast($anc, array('_CATGROUP'), 'ASC',
                                                                               $stop_level[0]);
                                    }

                                if (is_array($a))
                                    {
                                    $ancestors=array_merge($ancestors, $a);
                                    }
                                }
                            }
                        else
                            {
                            $ancestors=$ancarr;
                            }

                        if (is_array($params['spack']['ancestor']))
                            {
                            $se=array('ancestor' => $ancestors)+ $params['spack']['ancestor'];
                            }
                        else
                            {
                            $se=array('ancestor' => $ancestors);
                            }

                        if ($se['ancestor'])
                            {
                            if ($params['spack']['endleafs'])
                                {
                                $SOM=array();

                                $childs=$this->_tree->get_anc_multiply_childs2($se['ancestor'], $se['obj_type']);

                                if ($childs)
                                    {
                                     $OM=array_intersect($OM, array_keys($childs));
                                    }
                                else
                                    {
                                    $OM=null;
                                    }
                                }
                            else
                                {
                                $OM=$this->_tree->Search(array('Disable' => ''), false, $se, $OM, '=', false,
                                                         ' order by s.rate ASC');
                                }
                            }

                        if (empty($OM))
                            break 2;

                        break;

                    case 'ancestor':
                        $svalues=XARRAY::clear_empty_items($svalues);

                       if (!empty($svalues))
                            {
                            
                            
                            $OM=$this->_tree->Search(null, false, $svalues, $OM, '=', false, ' order by s.rate ASC');

                            if (empty($OM))
                                break 1;
                            }

                        break;

                    case 'sortby':
                        $sortby=XARRAY::clear_empty_items($svalues);

                        break;

                    case 'sort':
                        $sort=XARRAY::clear_empty_items($svalues);

                        break;
                    }
                }

            $endpage=$params['startpage'] + $params['Onpage'];

            
            
            
            if (!empty($OM) && is_array($OM))
                {
                    //убираем отключенные из результатаы
                   
                   if($DOM=$this->_tree->Search(array('Disable' => '1'), false,array('id'=>$OM)))
                    {
                        $OM=array_diff($OM,$DOM);
                    }                    

                    if ($sortby)
                    {
                        $OM=$this->sortby($OM, $sortby);
                    }

                if ($sort)
                    {
                    $OM=$this->sorter($OM, $sort);
                    }

                $obj_count=count($OM);


                if ($OM=array_slice($OM, $params['startpage'], $params['Onpage']))
                    {
                    return array
                        (
                        'objects' => $this->_tree->GetNodesByIdArray($OM, 'ASC', true),
                        'count'   => $obj_count
                        );
                    }
                }
            }
        }

    function catsearch($params)
        {
        global $TMS, $TPA, $REQUEST_ASSOC;

        
        if($REQUEST_ASSOC['@onpage'])$_SESSION['catalog']['Onpage']=$REQUEST_ASSOC['@onpage'];

        $onpage=explode(',',$params['Onpage']);
            
            if(count($onpage)>1)
            {
                $this->onpage=$onpage;   
            }
            
            
        if(!$_SESSION['catalog']['Onpage'])
        {
            
            $params['Onpage']=$onpage[0];
            
        }else{
            
            $params['Onpage']=$_SESSION['catalog']['Onpage'];
        }
        
        if ($_POST['search'])
            {
            $spack=$_POST['search'];
            }
        elseif (is_array($params['search']))
            {
            $spack=$params['search'];
            }
        else
            {
            $spack=$this->request_to_filter();
            }

        $params['spack']    =$spack;
        $params['startpage']=$startpage=isset($REQUEST_ASSOC['@sl']) ? $REQUEST_ASSOC['@sl'] : 0;

        $pages              =&pages_module_common::getInstance();

        if ($this->ishop)
            {
            $this->BasketPage=$pages->create_page_path($params['BasketPage']);
            }

        if ($OM=$this->select_objects($params))
            {
            if (!$params['ignoreTemplate'])
                {
                $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['ResultTemplate']));
                }

            $this->current_search_filter=$getter_line=$this->filter_to_request($params['spack']);
            Common::parse_nav_pages($OM['count'],                                   $params['Onpage'], $startpage,
                                    $TPA->page_link . '/~catsearch' . $getter_line, '',                '@sl');

            $_SESSION['catalog']['search_count']=$OM['count'];

            if ($d=$pages->get_module_by_action($params['Destination_page'], 'show_catalog_server'))
                {
                   $ancid=$d['params']['showBasicPointId'];
                }

            $this->cat_objects_render($OM['objects'], '_catalog_list_item', $ancid, '', $params['Destination'],
                                      $params['Render_by_items']);

            $TMS->AddMassReplace('_catalog_category_list', array
                (
                'Link'          => $TPA->page_link . '/',
                'search_count'  => $OM['count'],
                'search_filter' => $getter_line
                ));

            return $TMS->parseSection('_catalog_category_list');
            }
        else
            {
            if (!$params['ignoreTemplate'])
                {
                $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['ResultTemplate']));
                }

            return $TMS->parseSection('_catalog_search_failed');
            }
        }

    function getIdByBasic($basic)
        {
        if ($ba=$this->_tree->SearhInBasics($basic))
            {
            return $ba[0];
            }
        }
        
        
    function  seoConfirm($cat_node)
    {
            global  $TMS, $TPA;
             
            if ($TMS->isSectionDefined('Title'))
                {
                $TMS->AddMassReplace('Title', $cat_node);
                $title=$TMS->parseSection('Title');
                }
            else
                {
                $title=$cat_node['params']['Title'];
                }

            $TPA->externalMeta['Title']=$title;

            if ($TMS->isSectionDefined('Keywords'))
                {
                $TMS->AddMassReplace('Keywords', $cat_node);
                $keywords=$TMS->parseSection('Keywords');
                }
            else
                {
                $keywords=$cat_node['params']['Keywords'];
                }

            $TPA->externalMeta['Keywords']=$keywords;

            if ($cat_node['params']['Keywords'])
                {
                $TPA->externalMeta['Keywords']=$cat_node['params']['Keywords'];
                }

            if ($cat_node['params']['Description'])
                {
                $TPA->externalMeta['Description']=$cat_node['params']['Description'];
                }
    
    }

    function showobj($parameters)
        {
        global $REQUEST_ASSOC, $TMS, $TPA, $_PATH;

        
        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['ObjectTemplate']));

        if ($objid=isset($parameters['id']) ? $parameters['id'] : $this->getIdByBasic($REQUEST_ASSOC['id']))
            {

                $cat_node=$this->_tree->getNodeInfo($objid);
                
                $this->seoConfirm($cat_node);
                
                $pages                    =&pages_module_common::getInstance();   
                $dst=$pages->create_page_path($parameters['Destination_page']);
           
            //    $this->_tree->recursiveChildCollectFast(1, array('_CATGROUP'), 'ASC', 0, true);
                
                $this->get_bones2($cat_node['ancestor'], $parameters['showBasicPointId'], array('_CATGROUP'),$cat_node,$dst);
                
                
                $discount=$this->getDiscountForCategory($cat_anc['id']);
                $cat_node=$this->_common_obj->property_set_to_properties($cat_node['params']['Property_set'], $cat_node,
                                                                         '',                                  true,
                                                                         $this,                               $discount);

                $section ='_catalog_obj';

                 
                $psection =$section.'_'.$cat_node["props"]["Property_set"];
            if ($TMS->isSectionDefined($psection)) 
            {
                $TMS->AddMassReplace($psection,$cat_node["props"]);
                $buff = $TMS->parseSection($psection);
                $TMS->AddReplace($section,'_properties', $buff);
            }


            if ($this->ishop && $parameters['BasketPage'])
                {
                $pages           =&pages_module_common::getInstance();
                $this->BasketPage=$pages->create_page_path($parameters['BasketPage']);
                }

            if ($cat_node['props'])
                {
                $TMS->AddMassReplace($section, $cat_node['props']);
                unset ($cat_node['props']);
                }

            $TMS->AddReplace($section, 'Discount', $discount);
            $TMS->AddMassReplace($section, $cat_node);
            return $TMS->parseSection($section);
            }
        else
            {
            return $TMS->parseSection('_catalog_object_not_found');
            }
        }

function show_react_menu($params, $putt_off_called)
        {
        global $TMS, $TPA, $REQUEST_ASSOC;

        $this->prev_start_node=null;

        if (!$putt_off_called)
            return;

        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));

        Common::call_common_instance('pages');
        $pages=pages_module_common::getInstance();

        if ($d=$pages->get_module_by_action($params['CatObjDestination_page'], 'show_catalog_server'))
            {
            $ance=$d['params']['showBasicPointId'];
            }

        if ($REQUEST_ASSOC['id'])
            {
            $id=$this->getIdByBasic($REQUEST_ASSOC['id']);
            }
        else
            {
            $id=$this->get_requested_basic_id($ance);
            }

        if (!$id && $REQUEST_ASSOC['@ancestor']['ancestor'])
            {
            $id=$REQUEST_ASSOC['@ancestor']['ancestor'];
            }

        $anc=$this->_tree->GetAncestor($id);

        if ($params['ObjectInRows'])
            {
            $this->devide_by_rows=(int)$params['ObjectInRows'];
            }

        if ($params['showGroupId'] == $anc)
            {
            $category=(int)$id;
            }
        else
            {
            $this->_tree->GetFullBonesUp($id);
            $fbu=(array)XARRAY::arr_to_keyarr($this->_tree->FullBonesMas, 'id', 'id');

            array_unshift($fbu, 1);
            $key=array_search($params['showGroupId'], $fbu);

            if ($key !== false)
                $category=$fbu[$key + 1];
            }

        if (!$category)
            $category=$this->menu_builds_from['id'];

        $this->_tree->FullBonesMas=null;

        $obj_type=array('_CATGROUP');

        if ($params['ShowObj'])
            $obj_type[]='_CATOBJ';

        $this->_sel_arr=$select_obj_type['obj_type']=$obj_type;

        if (!is_array($this->_tree->EXPcache[$category]))
            {
            $this->_tree->EXPcache=null;

            $this->_tree->recursiveChildCollectFast(1, $obj_type, 'ASC', 0, true);

            $this->_tree->recursiveChildCollect($category, '%', array('obj_type' => $obj_type), 'ASC', '_CATGROUP',
                                                $params['Levels']);
            }

        $this->menu_ancestor             =$this->_tree->getNodeInfo($category);
        $pages                           =&pages_module_common::getInstance();
        $params['CatObjDestination_page']=$pages->create_page_path($params['CatObjDestination_page']);

        $menu                            =&$this->render_multi_level_menu($this->_tree->EXPcache, $category,
                                                                          $TPA->page_link,        0,
                                                                          $ance);

        return $menu;
        }

    function show_level_catmenu($params)
        {
        global $TMS;
        $this->_tree->EXPcache    =null;
        $this->_tree->FullBonesMas=null;
        $this->prev_start_node    =null;

        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));

        Common::call_common_instance('pages');
        $pages=&pages_module_common::getInstance();
        $link =$pages->create_page_path($params['Destination_page']);

        if ($d=$pages->get_module_by_action($params['Destination_page'], 'show_catalog_server'))
            {
            $anc=$d['params']['showBasicPointId'];
            }

        if ($params['Cat_destination_page'])
            {
            $this->cat_destination_page=$pages->create_page_path($params['Cat_destination_page']);
            }

        if ($params['ObjectInRows'])
            {
            $this->devide_by_rows=(int)$params['ObjectInRows'];
            }

        $this->menu_ancestor=$this->_tree->getNodeInfo($params['showGroupId']);

        $obj_sel_arr[]      =('_CATGROUP');

        if ($params['GetCatObj'])
            $obj_sel_arr[]='_CATOBJ';

        $this->_sel_arr=$select_obj_type['obj_type']=$obj_sel_arr;

        $mark=Common::createMark(array($params['showGroupId'], 0, $select_obj_type, 'ASC', $obj_sel_arr,
                                             $params['Levels']));
      
        $this->_tree->recursiveChildCollectFast($params['showGroupId'],$select_obj_type['obj_type'],'ASC',$stop_level); 
       
        if(!$this->_tree->EXPcache=Common::cacheSerializeRead('pages',$mark))
        {
        
        $this->_tree->recursiveChildCollect2($params['showGroupId'], 0, $select_obj_type, 'ASC', $obj_sel_arr,
                                             $params['Levels']);
        
        Common::cacheSerialize($this->_tree->EXPcache,'pages',$mark);
        }

        $pages               =&pages_module_common::getInstance();

        $menu                =&$this->render_multi_level_menu($this->_tree->EXPcache, $params['showGroupId'], $link, 0,
                                                              $anc);

        $this->devide_by_rows=0;

        if ($this->_tree->EXPcache)
            {
            $this->menu_builds_from=array_shift(array_shift($this->_tree->EXPcache));
            }

        return $menu;
        }

    //$menu_source ?????? ?? ??????? ????????
function render_multi_level_menu($menu_source, $start_node, $start_link, $level = 0, $anc_destination)
        {
            
//        if($level==0)DebugBreak();
        
        global $TMS, $_PATH, $REQUEST_ASSOC, $REQUEST_VARS;
        static $branches;

        if ($REQUEST_VARS[0] == 'show' && ($level === 0))
            {
            $objid                     =$this->get_requested_basic_id($anc_destination);
            $this->menu_selected_obj_id=$objid;
            $this->menu_selected_id    =$this->_tree->GetAncestor($objid);
            $this->menu_selected_id3   =$this->_tree->GetAncestor($this->menu_selected_id);
            }
        elseif ($REQUEST_VARS[0] == 'showobj' && $objid && ($level === 0))
            {
            $this->menu_selected_obj_id=$objid;
            $this->menu_selected_id    =$this->_tree->GetAncestor($objid);
            }
        elseif ($level === 0&&$objid)
            {
            $this->menu_selected_id=$objid;
            }

            
            if (!$branches && $objid)
            {
                $this->_tree->BonesMas=null;
                $this->_tree->GetBones($objid, 1);
                $branches= $this->_tree->BonesMas;
          
            }
            
        if (($menu_source) && ($menu_source[$start_node]))
            {
            foreach ($menu_source[$start_node] as $key => $menuitem)
                {
                if ($menuitem['params']['Disable'])
                    unset ($menu_source[$start_node][$key]);
                }

            $xtr_count=count($menu_source[$start_node]);

            $i        =0;

            foreach ($menu_source[$start_node] as $menuitem)
                {
                $itembasic = $menuitem['basic'];

                $menuitem  =$this->_common_obj->property_set_to_properties($menuitem['params']['Property_set'],$menuitem,'',true,$this);

                if (is_array($menuitem['props']))$menuitem=array_merge($menuitem, $menuitem['props']);

                unset ($menuitem['props']);

                $menuitem['_num']    =$i++;
                $menuitem['Ancestor']=$start_node;

                if ($menuitem['obj_type'] == '_CATGROUP')
                    {

                    $menuitem['subcount']=count($menu_source[$menuitem['id']]);
                    }

                $rpath              =$this->_tree->calculate_r_path($menuitem['id'], $anc_destination, $this->_sel_arr,
                                                                    2);
                $menuitem['Link']   =$start_link . '/' . $rpath;

                $menuitem['submenu']='';

                
                if (is_array($menu_source[$menuitem['id']]))
                    {

                    $menuitem['submenu']=$this->render_multi_level_menu($menu_source, $menuitem['id'], $start_link,
                                                                        $level + 1,   $anc_destination);
                    }

                $selected=false;

                if (in_array($menuitem['id'], array
                    (
                    $this->menu_selected_id,
                    $this->menu_selected_obj_id
                    )))
                    {
                    $selected     =true;
                    $selected_item=$menuitem;
                    }

                $branch_detected=false;


                if (!$selected && $branches && in_array($menuitem['id'], $branches))
                    {
                        
                    $branch_detected=true;
                    }

                if (($menuitem['obj_type'] == '_CATGROUP') && is_array($menu_source[$menuitem['id']])
                    && ($TMS->isSectionDefined('_menu_item_nested_level' . $level)))
                    {
                    $TMS->AddMassReplace('_menu_item_nested_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_nested_level' . $level);
                    $TMS->clear_section_fields('_menu_item_nested_level' . $level);
                    }
                elseif ($branch_detected && ($TMS->isSectionDefined('_menu_item_branch_level' . $level)))
                    {
                    $TMS->AddMassReplace('_menu_item_branch_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_branch_level' . $level);
                    }
                elseif (($i == 1) && ($selected) && ($TMS->isSectionDefined(
                                                         '_menu_item_first_selected_level' . $level)))
                    {
                    $TMS->AddMassReplace('_menu_item_first_selected_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_first_selected_level' . $level);
                    $TMS->clear_section_fields('_menu_item_first_selected_level' . $level);
                    }
                elseif (($i == 1) && ($TMS->isSectionDefined('_menu_item_first_level' . $level)))
                    {
                    $TMS->AddMassReplace('_menu_item_first_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_first_level' . $level);
                    $TMS->clear_section_fields('_menu_item_first_level' . $level);
                    }
                elseif (($i == $xtr_count) and ($selected) && ($TMS->isSectionDefined(
                                                                   '_menu_item_last_selected_level' . $level)))
                    {
                    $TMS->AddMassReplace('_menu_item_last_selected_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_last_selected_level' . $level);
                    $TMS->clear_section_fields('_menu_item_last_selected_level' . $level);
                    }
                elseif ($i == $xtr_count && $TMS->isSectionDefined('_menu_item_last_level' . $level))
                    {
                    $TMS->AddMassReplace('_menu_item_last_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_last_level' . $level);
                    $TMS->clear_section_fields('_menu_item_last_level' . $level);
                    }
                elseif ($selected && $TMS->isSectionDefined('_menu_item_middle_selected_level' . $level))
                    {
                    $TMS->AddMassReplace('_menu_item_middle_selected_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_middle_selected_level' . $level);
                    $TMS->clear_section_fields('_menu_item_middle_selected_level' . $level);
                    }
                elseif ($TMS->isSectionDefined('_menu_item_middle_level' . $level))
                    {
                    $TMS->AddMassReplace('_menu_item_middle_level' . $level, $menuitem);
                    $menu_buff.=$TMS->parseSection('_menu_item_middle_level' . $level);
                    $TMS->clear_section_fields('_menu_item_middle_level' . $level);
                    }

                if ($level == 0)
                    {
                    $anc_node=$this->menu_ancestor;
                    $TMS->AddMassReplace('_menu_main_level' . $level, $anc_node['params']);
                    }

                if (($this->devide_by_rows) && ($i % (int)$this->devide_by_rows == 0))
                    {
                    $levnum++;
                    $TMS->AddReplace('_menu_main_level' . $level, 'id', $start_node);
                    $TMS->AddReplace('_menu_main_level' . $level, 'menu_buff', $menu_buff);
                    $TMS->AddReplace('_menu_main_level' . $level, '__divnum', $levnum);

                    $menu_buff='';
                    $menu_dbuff.=$TMS->parseSection('_menu_main_level' . $level);
                    $exit_section='_menu_divide_container' . $level;
                    $TMS->KillField('_menu_main_level' . $level, 'menu_buff');
                    }
                }

            if (($this->devide_by_rows) && (($i % (int)$this->devide_by_rows != 0)))
                {
                $levnum++;
                $TMS->AddReplace('_menu_main_level' . $level, 'menu_buff', $menu_buff);
                $TMS->AddReplace('_menu_main_level' . $level, '__divnum', $levnum);

                $menu_dbuff.=$TMS->parseSection('_menu_main_level' . $level);
                $exit_section='_menu_divide_container' . $level;
                $menu_buff   =$menu_dbuff;
                }
            elseif (!$this->devide_by_rows)
                {
                $exit_section='_menu_main_level' . $level;
                }
            else
                {
                $menu_buff=$menu_dbuff;
                }

            $TMS->AddReplace($exit_section, 'menu_buff', $menu_buff);

            $TMS->AddReplace('_menu_main_level' . $level, 'id', $start_node);
            $TMS->AddReplace($exit_section, 'ancestor', $this->menu_ancestor['params']['Name']);
            $TMS->AddReplace($exit_section, 'id', $start_node);
            $TMS->AddMassReplace($exit_section, $branch_item);

            $main=$TMS->parseSection($exit_section);

            $TMS->KillField($exit_section, 'menu_buff');

            return $main;
            }
        }
        
        
        function collect_params_in_branch($params_names, $obj_types=array('_CATGROUP'), $return_first=false) {
        global $TPA; 
        static $branch;
            $pages=&pages_module_common::getInstance();       
            if($d=$pages->get_module_by_action($TPA->page_node["id"],'show_catalog_server')) 
            $anc=$d['params']['showBasicPointId'];
            if(!$anc) return;
            $basic_id = $this->get_requested_basic_id($anc);
            
            $this->show_crumbs_from=$anc;
            if (!$branch) {
               $old_branch = $this->bones_path;
               $this->_tree->recursiveChildCollectFast(1, $obj_types, 'ASC', 0, true); 
               
               $this->get_bones2($basic_id, $anc, $obj_types, false, true);
               $branch = $this->bones_path;
               $this->bones_path=$old_branch;
               $last_bone=array_pop($branch);
               array_push($branch,$last_bone);
               if ($last_bone['params']['StartGroup']) {
                   if ($bone=$this->_tree->getNodeInfo($last_bone['params']['StartGroup'])) { 
                       $branch[$last_bone['params']['StartGroup']]=$bone;
                   }
               }
            }
            $result = array();
            if (!$branch) return;
            foreach ($branch as $key => $nod) {
                if (!$key || !in_array($nod["obj_type"], $obj_types)) continue;
                foreach ($params_names as $param) {
                    $flag = false;
                    if ($nod["params"][$param]) {
                        $result[$param]=$nod["params"][$param];
                        $flag=true;
                    }
                    if ($nod[$param]) {
                        $result[$param]=$nod[$param];
                        $flag=true;
                    }                    
                    if ($flag && !($return_first && $result["catobject"])) $result["catobject"]=$nod;
                }
            }
            
            return $result;
        }
    
        function get_existing_values($sform_id, $ancestor, $pset = null){
            global $TDB; 
            $psets = &$this->_common_obj->property_sets;
            $search_fields = $TDB->get_results('SELECT P.value FROM `_tree_catalog_search_forms_struct` S LEFT JOIN _tree_catalog_search_forms_param P ON P.node_name = S.id WHERE S.ancestor = '. $sform_id .' AND P.parameter = "property" ORDER BY rate', ARRAY_N);
               
            if(is_array($search_fields)) {
                
                $parameters = array();
                foreach($search_fields as $value) {
                    if($value[0] != 'price') $parameters[] = $value[0];
                }
                
                // РѕС‚СЃРµРІ РїРѕ СЃРІРѕР№СЃС‚РІР°Рј СЃ С‚РёРїРѕРј = FIELD
                if ($pset) {
                    $psets[$pset]=$this->_common_obj->get_properties($pset);
                    $tmp=array();
                    foreach ($parameters as $param) {
                        foreach ($psets[$pset] as $prop) {
                            if( $prop["params"]["Name"] == $param && $prop["params"]["Type"] == 'FIELD') {
                                $tmp[]=$param;
                                break;
                            }
                        }
                    }
                    $parameters=$tmp;
                }
                if (!$parameters) return;
                $parameters = '"'. implode('","', $parameters) .'"';
                
                ($pset) ? $params=array('Property_set' => $pset) : $params=array();
                
                if ($children = $this->_tree->DeepSearch2($ancestor, array('_CATGROUP', '_CATOBJ'), 0, $params, false)) {
                    $parents = '"'. implode('","', $children) .'"';
                } 
                else return;
                    
                $query = 'SELECT * FROM `_tree_catalog_container_struct` S LEFT JOIN `_tree_catalog_container_param` P ON S.id IN ('. $parents .') WHERE P.node_name = S.id AND P.parameter IN ('. $parameters .') GROUP BY value ASC';
                if($objects = $TDB->get_results($query)){
                    $result = array();
                    while(list($k,$v) = each($objects)) {
                        if(!empty($v['value'])) $result[$v['parameter']][] = $v['value'];
                    }
                    return $result;
                }
            }            
        }
    
    function show_branch_info($params){
        global $TMS;
        
        $sfields=$this->_common_obj->search_forms_tree->GetChildsParam($params['SearchForm'], '%');
        $fields = array();
        foreach ($sfields as $field)  $fields[]=$field["property"];
        
        if (!$obj = $this->collect_params_in_branch($fields)) return;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
        foreach($fields as $field) {
            $TMS->AddReplace('_show_branch_info', $field, $obj["catobject"]["params"][$field]);
        }
        return $TMS->parseSection('_show_branch_info');
    }
    
        
    function show_smart_search_form($params)
    {
        global $REQUEST_VARS; 
        if($REQUEST_VARS[0] == 'catsearch') {
            if ( ($params['SearchForm'] = $_SESSION['current_ancestor']['SearchForm']) &&  ($params["search_from"] = $_SESSION['current_ancestor']['anc'])) {
                $params["search_objects"] = $_SESSION['catalog']['search_objects'];
                if ($params["FilterPset"]) $params['SearchProperty_set'] = $_SESSION['current_ancestor']['SearchProperty_set'];
                return $this->show_search_form($params);               
            }
        } else { 
            $obj = $this->collect_params_in_branch(array('search_form'));
            if (($form_id = $obj["search_form"]) && ($node = $obj["catobject"])) {
               $pset=$node["params"]["Property_set_default"];
               $search_from = $node["id"];
               $params["search_objects"]=$this->get_existing_values($form_id, $search_from, $pset);
               $params['SearchForm'] = $form_id;
               $params['ancestor'] = $node["id"];
               if ($params["FilterPset"]) {
                    $params['SearchProperty_set'] = $pset;
                    $_SESSION['current_ancestor']['SearchProperty_set'] = $pset;
               }
               $_SESSION['current_ancestor']['anc'] = $search_from;                            
               $_SESSION['current_ancestor']['SearchForm'] = $form_id;
               
               $_SESSION['catalog']['search_objects']= $params["search_objects"];
               return $this->show_search_form($params);
            }
        }
    }            
    
    
    function show_search_form($params)
        {
        global $TMS, $TPA;
        static $property_sets;
        $psets = &$this->_common_obj->property_sets; 
        
        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['SearchTemplate']));
        if ($params["SearchProperty_set"]) $TMS->AddReplace('_catalog_show_search', 'SearchProperty_set', $params["SearchProperty_set"]);
        if ($params["ancestor"]) $TMS->AddReplace('_catalog_show_search', 'ancestor', $params["ancestor"]);  // ??
        Common::call_common_instance('pages');
        $pages     =&pages_module_common::getInstance();
        $cat_action=$pages->create_page_path($params['Destination_page']) . '/~catsearch/';
        $TMS->AddReplace('_catalog_show_search', 'action', $cat_action);

        if (!empty($_POST['search']))
            {
            $spack=$_POST['search'];
            }
        elseif ($_SESSION['catalog']['last_search_form'])
            {
            $spack=$_SESSION['catalog']['last_search_form'];
            }

        if (is_array($spack))
            {
            foreach ($spack as $group => $value)
                {
                if (is_array($value))
                    foreach ($value as $k => $v)
                        {
                        $fields_values[$group . '_' . $k]=$v;
                        }
                }
            }

        $_SESSION['catalog']['last_search_form']=$spack;
        $sform = $this->_common_obj->search_forms_tree->GetNodeParam($params["SearchForm"]);
        $TMS->AddReplace('_catalog_show_search', 'SFromName', $sform["Name"]);
        $i=0;
        
        if ($sfields=$this->_common_obj->search_forms_tree->GetChildsParam($params['SearchForm'], '%'))
            {
            
            foreach ($sfields as $sfid => $sfield)
                {
                 //DebugBreak();
                if (!$property_sets[$sfield['property_set']])
                    {
                    if (!$psets[$sfield['property_set']]) {
                        $psets[$sfield['property_set']]=$this->_common_obj->get_properties($sfield['property_set']);
                    }
                    $prp=$psets[$sfield['property_set']];
                    if ($prp) {
                        $property_sets[$sfield['property_set']]=array
                            (
                            array_flip(XARRAY::arr_to_lev($prp, 'id', 'params', 'Name')),
                            $prp
                            );
                    } else {
                        $property_sets[$sfield['property_set']]=array();
                    }
                    }

                $section='_catalog_search_' . $sfield['criteria'];
                $psets =
                    $property_sets[$sfield['property_set']][1][$property_sets[$sfield['property_set']][0][$sfield['property']]]['params'];

                if ($psets['catselector'])
                    {
                        
                    $sfield=$sfield + array('catselector' => $psets['catselector']);
                    }

                $sfield=$sfield + array('type' => $psets['Type']);

                switch ($sfield['criteria'])
                    {
                    case 'interval':
                        $sfield=$sfield + array
                            (
                            'from'       => 'search[from][' . $sfield['property'] . ']',
                            'to'         => 'search[to][' . $sfield['property'] . ']',
                            'from_value' => $fields_values['from_' . $sfield['property']],
                            'to_value'   => $fields_values['to_' . $sfield['property']]
                            );

                        break;

                    case 'larger':
                        $sfield=$sfield + array
                            (
                            'input_name' => 'search[from][' . $sfield['property'] . ']',
                            'value'      => $fields_values['from_' . $sfield['property']]
                            );

                        break;

                    case 'less':
                        $sfield=$sfield + array
                            (
                            'input_name' => 'search[to][' . $sfield['property'] . ']',
                            'value'      => $fields_values['to_' . $sfield['property']]
                            );

                        break;

                    case 'equal':
                        $evalues = $params["search_objects"][$sfield['property']];
                        if ($evalues) $sfield['E_values'] = $evalues;
                        $sfield=$sfield + array
                            (
                            'input_name' => 'search[' . $sfield['criteria'] . '][' . $sfield['property'] . ']',
                            'value'      => $fields_values[$sfield['criteria'] . '_' . $sfield['property']]
                            );
                    
                    
                        break;
                    case 'rlike':
                    case 'lrlike':
                    case 'rwords':
                        $sfield=$sfield + array
                            (
                            'input_name' => 'search[' . $sfield['criteria'] . '][' . $sfield['property'] . ']',
                            'value'      => $fields_values[$sfield['criteria'] . '_' . $sfield['property']]
                            );

                        break;
                    }
                $sfield["_num"]=$i++;
                //DebugBreak();
                $TMS->AddMassReplace($section, $sfield);
                $TMS->parseSection($section, true);
                $TMS->clear_section_fields($section);
                }
            }
        
        //if ($params[""])$TMS->AddMassReplace('_catalog_show_search', $fields_values);
        $TMS->AddMassReplace('_catalog_show_search', $fields_values);
        return $TMS->parseSection('_catalog_show_search');
        }
    
    }
    
?>