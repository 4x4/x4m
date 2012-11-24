<?php
class catalog_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_tree;
    var $_common_obj;
    var $_property_tree;
    var $ishop;

    function catalog_module_back() { $this->_module_name='catalog'; }

    function common_call()
        {
        $this->_module_name  ='catalog';
        $this->_common_obj   =&catalog_module_common::getInstance();
        //proxy for tree
        $this->_tree         =&$this->_common_obj->obj_tree;
        $this->_property_tree=&$this->_common_obj->property_tree;
        $this->_common_obj->set_context(&$this);

        if (Common::is_module_exists('ishop'))
            $this->ishop=true;
        }

    
    function execute($action, $parameters = null)
        {
        $this->common_call();
        return $this->_common_obj->execute(&$this, $action, $parameters);
        }

    function executex($action, $acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
        }

    function delete_obj($data) { return $this->_common_obj->delete_obj(&$this, $data); }

    function get_tree_inheritance() { $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC; }

    function get_property_sets($flows, $fields = 'Property_set',
                                   $selected = null) { 
                                       $add_empty=true;
                                       if($selected)$add_empty=false;
                                       $this->result[$flows][$fields]=XHTML::arr_select_opt(
                                                                                          XARRAY::arr_to_lev(
                                                                                              $this->_property_tree->GetChildsParam(
                                                                                                  1,
                                                                                                  array('Name'),
                                                                                                  true, array('obj_type'
                                                                                                            =>
                                                                                                            array
                                                                                                                ('_PROPERTYSET'))),
                                                                                              'id',
                                                                                              'params',
                                                                                              'Name'),
                                                                                          $selected,
                                                                                          $add_empty); }

    function get_property_types($pset) { 
        return XARRAY::arr_to_lev2($this->_common_obj->get_properties($pset, true), 'params', 'Name', 'params', 'Type'); 
    }

    function date_convert_timestamp($pset, &$properties)
        {
        $types=$this->get_property_types($pset);

        foreach ($types as $field => $type)
            {
            if (($type == 'DATE') && ($properties[$field]))
                {
                if (preg_match('/^\s*(\d\d?)[^\w](\d\d?)[^\w](\d{1,4}\s*$)/', $properties[$field], $match))
                    {
                    $y=$match[2] . '/' . $match[1] . '/' . $match[3];
                    }

                $properties[$field]=strtotime($y);
                }
            }
        }
        

        
       function property_set_to_properties($pset, $properties, $flows)
        {
        $props_types=$this->get_property_types($pset);

        foreach ($props_types as $name => $pt)
            {
            if ($properties[$name] !== '')
                {
                if ($pt == 'CATOBJ')
                    {
                    //добавляем путь            
                    $this->_tree->FullBonesMas=null;
                    $this->_tree->GetFullBonesUp($properties[$name]);
                    $this->result[$flows]['props'][$name]=$properties[$name];

                    if ($this->_tree->FullBonesMas)
                        $path=implode('/', XARRAY::arr_to_lev($this->_tree->FullBonesMas, 'id', 'params', 'Name'));

                    $this->result[$flows]['props'][$name . 'Alias']=$path;
                    }
                elseif ($pt == 'DATE')
                    {
                    $this->result[$flows]['props'][$name]=date('d-m-Y G:i', $properties[$name]);
                    }
                elseif ($pt == 'FUSER' || $pt == 'ALBUM' || $pt == 'DOCS'){
                    $this->result[$flows]['props'][$name . '_name']=$properties[$name.'_name'];
                    $this->result[$flows]['props'][$name]=$properties[$name];
                    
                }
                else
                    {
                    $this->result[$flows]['props'][$name]=$properties[$name];
                    }
                }
            }
        }

    function get_property_set_sfields() { $this->get_property_sets('add_sfield', 'property_set'); }

    function get_properties_sfields($params) { 
        $this->result['add_sfield']['property']=XHTML::arr_select_opt(XARRAY::arr_to_lev(
                                                                                               $this->_common_obj->get_properties(
                                                                                                   $params['id']),
                                                                                               'basic',
                                                                                               'params',
                                                                                               'Alias'),
                                                                                           null,
                                                                                           true); }

    function load_catobj_data($params)
        {
        $node                       =$this->_tree->getNodeInfo($params['id']);
        $this->result['catobj_data']=$node['params'];

        $this->currentId=$this->result['catobj_data']['Property_set'];
        
        
        if ($this->result['catobj_data']['pset']=$this->result['catobj_data']['Property_set'])
            {
            $this->property_set_to_properties($this->result['catobj_data']['pset'], $node['params'], 'properties');
            }

        if ((is_array($this->result['catobj_data'])) && (is_array($this->result['properties']['props'])))
            {
            $this->result['catobj_data']=array_diff($this->result['catobj_data'], $this->result['properties']['props']);
            }

        $this->result['catobj_data']['Name'] =$node['params']['Name'];
        $this->result['catobj_data']['Basic']=$node['basic'];

        
        
        if ($obj_conn_params=XARRAY::arr_to_keyarr($this->_common_obj->getConnectedObjs($params['id'], true), 'id',
                                                   'params'))
            {
                
        
            $names=XARRAY::arr_to_lev($this->_common_obj->getConnectedObjs($params['id']), 'id', 'props', 'Name');

            while (list($k, $v)=each($obj_conn_params))
                {
                $obj_conn_params[$k]['ConName']=$names[$v['id']];
                }
            }

        $this->result['connobjs']=$obj_conn_params;
        $this->get_property_sets('catobj_data', 'Property_set', $this->result['catobj_data']['Property_set']);
        }

    function changeAncestor($parameters) {
    
    $this->result['dragOK']=$this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],
                                                              $parameters['relative']); }

    function edit_property_set($params)
        {
        $this->result['properties']
                                  =$this->_common_obj->get_properties($params['id'], false, $this->result['subgroups']);

        $this->result['edit_form']=$this->_property_tree->GetNodeParam($params['id']);
        }

    function show_new_catgroup($parameters)
        {
        $this->get_property_sets('catgroup_data');
        $this->get_property_sets('catgroup_data', 'Property_set_default');
        }

    function change_property_set($params)
        {
        
        if($this->currentId&&$params===true){$id=$this->currentId;}else{$id=$params['id'];}
        
        $prps=$this->_common_obj->get_properties($id, true);

        foreach ($prps as $id => $prpty)
            {
            $this->result['prp'][$id]=$prpty['params'];
            }
        }

    function save_edited_catgroup($parameters)
        {
        $parameters['properties']['Property_set']        =$parameters['data']['Property_set'];
        $parameters['properties']['StartGroup']          =$parameters['data']['StartGroup'];
        $parameters['properties']['Property_set_default']=$parameters['data']['Property_set_default'];
        $parameters['properties']['Name']                =$parameters['data']['Name'];
        $parameters['properties']['Disable']             =$parameters['data']['Disable'];
        $parameters['properties']['Title']               =$parameters['tunes']['Title'];
        $parameters['properties']['Keywords']            =$parameters['tunes']['Keywords'];
        $parameters['properties']['Description']         =$parameters['tunes']['Description'];

        $this->date_convert_timestamp($parameters['properties']['Property_set'], $parameters['properties']);
        $this->_tree->clearnodeparam($parameters['id']);
        
        
        $this->reinit_group($parameters['id'], $parameters['properties'],$parameters['tunes']['Basic']);

        $this->clear_connected($parameters['id']);
        $this->save_connobjs($parameters['id'], $parameters['connobjs']);
        }

    function clear_connected($id)
        {
        if ($connobj=$this->_tree->Search(null, false, array
            (
            'obj_type' => '_CATCONNOBJ',
            'ancestor' => $id
            )))
            {
            $connobj=array_unique($connobj);

            foreach ($connobj as $c)
                {
                $this->_tree->DelNode($c);
                }
            }
        }

    function show_new_catobj($parameters)
        {
        if ($parameters['parent_id'] != -1)
            {
            $pnode                                     =$this->_tree->GetNodeParam($parameters['parent_id']);
            $this->result['catobj_data']['showGroupId']=$parameters['parent_id'];
            $this->result['catobj_data']['showGroup']  =$pnode['Name'];
            }

        $this->result['pset']=$pnode['Property_set_default'];
        $this->get_property_sets('catobj_data', 'Property_set', $pnode['Property_set_default']);
        }

    function load_catgroup_data($parameters)
        {
        $node                         =$this->_tree->getNodeInfo($parameters['group_id']);
        $this->result['catgroup_data']=$node['params'];

        if ($groups=$this->_tree->GetChildsParam($parameters['group_id'], array('Name'), false,
                                                 array('obj_type' => array('_CATGROUP'))))
            {
            $this->result['catgroup_data']['StartGroup']=XHTML::arr_select_opt(XARRAY::askeyval($groups, 'Name'),
                                                                               $node['params']['StartGroup'],
                                                                               true);
            }
            
        $this->result['catgroup_data']['Basic']=$node['basic'];
        $this->currentId=$this->result['catgroup_data']['Property_set'];            
            
        if ($this->result['catgroup_data']['pset']=$this->result['catgroup_data']['Property_set'])
            {
            $this->property_set_to_properties($this->result['catgroup_data']['pset'], $node['params'], 'properties');
            $this->get_property_sets('catgroup_data', 'Property_set', $this->result['catgroup_data']['Property_set']);
            }

        if (is_array($this->result['properties']))
            {
            $this->result['catgroup_data']=array_diff_assoc($this->result['catgroup_data'],
                                                            $this->result['properties']['props']);
            }

            
            
        if ($obj_conn_params=XARRAY::arr_to_keyarr($this->_common_obj->getConnectedObjs($parameters['group_id'], true),
                                                   'id',
                                                   'params'))
            {
     
            $names=XARRAY::arr_to_lev($this->_common_obj->getConnectedObjs($parameters['group_id']), 'id', 'props',
                                      'Name');

            while (list($k, $v)=each($obj_conn_params))
                {
                $obj_conn_params[$k]['ConName']=$names[$v['id']];
                }
            }

        $this->result['connobjs']=$obj_conn_params;

        $this->get_property_sets('catgroup_data', 'Property_set_default',
                                 $this->result['catgroup_data']['Property_set_default']);
        }

    function delete_property_set($params)
        {
        global $TDB;
                $this->_common_obj->delete_obj($this,$params,$this->_property_tree);
        }

    function save_new_properyset($parameters)
        {
        $id=$this->init_property_set($parameters['propertySet']);

        if ($parameters['properties'])
            {
            foreach ($parameters['properties'] as $property)
                {
                $rid = $this->init_property($id, strtolower($property['Name']), $property);

                switch ($property['Type'])
                    {
                    case 'SELECTOR':
                    case 'CURRENCY':
                    case 'SFORM' :
                        $this->init_propertyvals($rid, $property['catselector']);

                        break;
                    }
                }
            }
        }


    function copy_sform($params)
        {
        if ($this->_common_obj->search_forms_tree->CopyNodes(1, $params['id'], array
            (
            '_SFORM',
            '_SFIELD'
            )))
            {
            $this->result['isCopy']=1;
            }
        }

    function copy_property_set($params)
        {
        if ($this->_property_tree->CopyNodes(1, $params['id'], array
            (
            '_PROPERTYSET',
            '_PROPERTY'
            )))
            {
            $this->result['isCopy']=1;
            }
        }

    function save_propertyset($parameters)
        {
        $this->_property_tree->DelNode($parameters['id'], true);
        $rid=$this->reinit_property_set($parameters['id'], $parameters['propertySet']);

        if ($parameters['subgroups'])
            {
            foreach ($parameters['subgroups'] as $subgroup)
                {
                if ($subgroup['id'] != '_main_')
                    {
                    $id=$subgroup['id'];
                    unset($subgroup['id']);
                    $newid         =$this->init_subgroup($rid, $subgroup);
                    $subgroups[$id]=$newid;
                    }
                }
            }

        if ($parameters['properties'])
            {
            foreach ($parameters['properties'] as $property)
                {
                if ($property['Prop_subgroup'] != '_main_')
                    {
                    if ($subgroups[$property['Prop_subgroup']])
                        {
                        $property['Prop_subgroup']=$subgroups[$property['Prop_subgroup']];
                        }
                    else
                        {
                        $property['Prop_subgroup']='_main_';
                        }
                    }

                ;

                $id=$this->init_property($rid, strtolower($property['Name']), $property);

                switch ($property['Type'])
                    {
                    case 'SELECTOR':
                    case 'CURRENCY':
                    case 'SFORM':
                        //DebugBreak();
                        unset($property['catselector'][' ']);

                        $this->init_propertyvals($id, $property['catselector']);
                        break;
                    }
                }
            }
        }


    function save_new_catgroup($parameters)
        {
        $parameters['properties']['Name']                =$parameters['data']['Name'];
        $parameters['properties']['Property_set_default']=$parameters['data']['Property_set_default'];
        $parameters['properties']['Property_set']        =$parameters['data']['Property_set'];
        $this->date_convert_timestamp($parameters['properties']['Property_set'], $parameters['properties']);
        $this->init_group($parameters['data']['showGroupId'], $parameters['properties']);
        }

    function save_new_catobj($parameters)
        {
        $parameters['properties']['Property_set']=$parameters['catobj_data']['Property_set'];
        $parameters['properties']['Name']        =$parameters['catobj_data']['Name'];
        $parameters['properties']['Disable']     =$parameters['catobj_data']['Disable'];
        $this->date_convert_timestamp($parameters['properties']['Property_set'], $parameters['properties']);
        $this->init_catobj($parameters['catobj_data']['showGroupId'], $parameters['properties']);
        }

    function save_edited_catobj($parameters)
        {
        $parameters['properties']['Property_set']=$parameters['catobj_data']['Property_set'];
        $parameters['properties']['Name']        =$parameters['catobj_data']['Name'];
        $parameters['properties']['Disable']     =$parameters['catobj_data']['Disable'];
        $parameters['properties']['Title']       =$parameters['add_tunes']['Title'];
        $parameters['properties']['Keywords']    =$parameters['add_tunes']['Keywords'];
        $basic                                   =$parameters['add_tunes']['Basic'];
        $parameters['properties']['Description'] =$parameters['add_tunes']['Description'];
        $this->date_convert_timestamp($parameters['catobj_data']['Property_set'], $parameters['properties']);
        $this->reinit_catobj($parameters['id'], $basic, $parameters['properties']);

        $this->_tree->DelNode($parameters['id'], true);

        $this->save_connobjs($parameters['id'], $parameters['connobjs']);
        }

    function save_connobjs($id, $connobjs)
        {
        if (is_array($connobjs))
            {
            foreach ($connobjs as $cid)
                {
                $cid['params']['id'] = $cid['id'];
                unset($cid['params']['ConName']);
                $this->init_catconnobj($id, $cid['params']);
                }
            }
        }

    function del_sform($parameters) { $this->result['del_sform']=$this->_common_obj->search_forms_tree->DelNode(
                                                                     $parameters['id']); }

    function edit_sform($parameters)
        {
        static $property_sets;

        if ($sform=$this->_common_obj->search_forms_tree->getNodeInfo($parameters['id']))
            {
            $this->result['sform']  =$sform['params'];
            $this->result['sfields']=$this->_common_obj->search_forms_tree->GetChildsParam($sform['id'], '%');

            while (list($k, $v)=each($this->result['sfields']))
                {
                $propset_name = $this->_property_tree->ReadNodeParam($v['property_set'], 'Name');

                if (!$property_sets[$v['property_set']])
                    {
                    $property_sets[$v['property_set']]=XARRAY::arr_to_lev(
                                                           $this->_common_obj->get_properties($v['property_set']),
                                                           'basic',
                                                           'params',
                                                           'Alias');
                    }

                $this->result['sfields'][$k]['_fields']=array
                    (
                    'property_set' => $propset_name,
                    'criteria'     => $v['criteria'],
                    'property'     => $property_sets[$v['property_set']][$v['property']]
                    );
                }
            }
        }

    function save_sform($parameters)
        {
        if (!$parameters['id'])
            {
            $id=$this->init_sform($parameters['sform']);
            }
        else
            {
            $id=$parameters['id'];
            $id=$this->reinit_sform($id, $parameters['sform']);
            $this->_common_obj->search_forms_tree->DelNode($id, true);
            }

        if (is_array($parameters['sfields']))
            {
            foreach ($parameters['sfields'] as $field)
                {
                unset($field['_fields'], $field['id']);
                $this->init_sfield($id, $field);
                }
            }
        }

    function sforms_table($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array('_SFORM');

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['preventDots']=true;

        $options['columnsAsStructs']=array('id' => 'id');

        $options['gridFormat']=1;
        $TD->init_from_source($this->_common_obj->search_forms_tree);
        $TD->setOptions($options);
        $TD->CreateView(1);
        $this->result=$TD->result;
        }

    function property_set_table($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array('_PROPERTYSET');

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['preventDots']=true;

        $options['columnsAsStructs']=array('id' => 'id');

        $options['gridFormat']=1;
        $TD->init_from_source($this->_property_tree);
        $TD->setOptions($options);
        $TD->CreateView(1);

        $this->result=$TD->result;
        }


    function load_xlist_data($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_CATGROUP',
            '_ROOT'
            );

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['columnsAsStructs']=array('image' => 'obj_type');

        $options['transformResults']['image']=array
            (
            '_CATGROUP' => 'group',
            '_ROOT'     => 'group'
            );

        $options['selectable']=array('image' => array
            (
            '_CATGROUP',
            '_ROOT'
            ));

        $this->result['data_set']=null;

        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

    function load_xlist_data_catobj($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array('_CATGROUP');

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['columnsAsStructs']=array('image' => 'obj_type');

        $options['transformResults']['image']=array('_CATGROUP' => 'group');

        $options['selectable']=array('image' => array('_CATGROUP'));

        
        $this->result['data_set']=null;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

    function load_xlist_data_all($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_CATGROUP',
            '_CATOBJ'
            );

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['columnsAsStructs']=array('image' => 'obj_type');

        $options['transformResults']['image']=array
            (
            '_CATGROUP' => 'group',
            '_CATOBJ'   => 'page'
            );

        $options['selectable']=array('image' => array
            (
            '_CATGROUP',
            '_CATOBJ'
            ));

        $options['endLeafs']=array('_CATOBJ');

        
        $this->result['data_set']=null;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

    function _copy($params) { $this->_common_obj->_copy($this, $params, array
        (
        '_CATOBJ',
        '_CATGROUP'
        )); }



    function load_actions($parameters) { $this->result['tune_actions']['Action']=XHTML::arr_select_opt(
                                                                                     XARRAY::askeyval(
                                                                                         $this->_common_obj->get_actions(),
                                                                                         'front_name'),
                                                                                     $parameters['selected'],
                                                                                     true); }

    function exportJSON($params)
        {
        global $_PATH;
        Common::inc_module_factory('JSON', true);
        $data =json_encode($this->_tree->ExportNodes($params['id'], array
            (
            '_ROOT',
            '_CATGROUP'
            )));

        $data =gzencode($data, 9);
        $fname=$params['filename'] . '.gzip';

        if (XFILES::filewrite($_PATH['PATH_MEDIA'] . '/export/' . $fname, $data))
            {
            $this->result['filepath']=$_PATH['WEB_PATH_MEDIA'] . 'export/' . $fname;
            }
        else
            {
            $this->result['ERROR']=1;
            }
        }
    /* $params[0]  -pset
    *  $params[0] - property
    */

    function expOne($obj, $lev = 0)
        {
        global $TMS;

        static $catalog;
        
        if(ENHANCE::$export_params)
        {
            
            if(!$catalog){
                $catalog=Common::module_factory('catalog.front');
            }
            
            $obj['Link']=$catalog->get_link_by_id($obj['id'], ENHANCE::$export_params['catalog_url']);
            
            $obj['HOST']=CHOST;
        
            
            if($filter=ENHANCE::$export_params['equalfilter'])
            {
                 foreach($filter as $field=>$filtval)
                 {
                     if($obj['params'][$field]!=$filtval){return;}
                 }    
            }
        }
        
        
        $TMS->clear_section_fields('export_line');
        $ext=$this->_common_obj->property_set_to_properties($obj['params']['Property_set'], $obj, 'value.');
        $TMS->AddReplace('export_line', '_lev', $lev);
        $TMS->AddMassReplace('export_line', $obj['params'] + $obj + $ext['props']);
        
        $TMS->parseSection('export_line',true);
        
        }
    function recursiveExport($startwith, $lev = 0)
        {
            $result='';
            foreach ($this->_tree->EXPcache[$startwith] as $id => $obj)
            {
            if ($this->_tree->EXPcache[$id])
                {
                $result.=$this->expOne($obj, $lev);
                $result.=$this->recursiveExport($id, $lev + 1);
                }
            else
                {
                $result.=$this->expOne($obj);
                }
            }

        return $result;
        }
        
        
    function gridformat($page_array)
    {
        while(list($k,$v)=each($page_array))
        {    
            array_unshift($v,$k);
            $result['rows'][$k]=array('data'=>array_values($v));
        }
        return  $result;
    }
    
    
            function property_set_to_properties_manager($pset, $properties,$props_types)
        {
       
        foreach ($props_types as $name => $pt)
            { 

                if ($pt == 'CATOBJ')
                    {
                    
                    $this->_tree->FullBonesMas=null;
                    $this->_tree->GetFullBonesUp($properties[$name]);
                        $result[$name]=$properties[$name];
 

                    if ($this->_tree->FullBonesMas)
                    {
                        $path=implode('/', XARRAY::arr_to_lev($this->_tree->FullBonesMas, 'id', 'params', 'Name'));
                        $result[$name]=$path;
                    }
                    
                    
                    }
                 elseif($pt=='IMAGE')
                    {
                        
                        $result[$name]=ENHANCE::image_transform($properties[$name],array(50,50),null,null); 
                        
                    }
                elseif ($pt == 'DATE')
                    {

                                $result[$name]=date('d-m-Y', $properties[$name]);
                       
                    }
                else
                    {      
                                $result[$name]=$properties[$name];
                    }
                
            }
                   return $result;
        }

        
    function  manager($params)
    {
        $sets=array();$sets_types=array();
        
        $pset_select_list=array('BOOL','DATE','ICURRENCY','CURRENCY','CURRENCY','FIELD','IMAGE','FILE','IFOLDER');
        
        $this->result['columns']=array();
        
        
        $struct=$this->_tree->GetNodeStruct($params['ancestor']);
        if($struct['obj_type']=='_CATOBJ')
        {
            $params['ancestor']=$this->_tree->GetAncestor($params['ancestor']);
        }
        $childs=$this->_tree->GetChildsParam($params['ancestor'], '%',true,null,'ASC');

                                                     
                                                     
        while (list($k, $v)=each($childs))
                {
        
                    if(!is_array($sets[$v['params']['Property_set']]))
                    {
                        if($property_set=$this->_common_obj->get_properties($v['params']['Property_set']))
                        {
                            foreach($property_set as $pkey=>$pset)
                            {
                                if(in_array($pset['params']['Type'],$pset_select_list))
                                {
                                    
                                    $cpset[$pkey]=$pset['params'];
                                    $sets_types[$pset['params']['Name']]=$pset['params']['Type'];
                                }
                            }
                        }
                        
                        $sets[$v['params']['Property_set']] =$cpset; 
                    }
                    
                      
                      
                    $objects[$k]=$this->property_set_to_properties_manager($v['params']['Property_set'],$v['params'],$sets_types);
                    $objects[$k]=array('obj_type'=>$childs[$k]['obj_type'],'Name'=>$v['params']['Name'])+$objects[$k];
                }
                
               
              
                foreach($sets as $pset)
                {
                   XARRAY::array_merge_plus($this->result['columns'],$pset); 
                }


              $this->result['data_set']=$this->gridformat($objects);
                                
    }
      
      
      function save_partial($params)
      {
          if($this->_tree->WriteNodeParam($params['id'],$params['param'],$params['value']))
          {
            x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
          }else{
            x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
          }
          
      } 

    function catalogExport($params)
        {
        global $TMS, $_PATH;

        
        $this->_tree->recursiveChildCollect($params['id'], '%', array('obj_type' => array
            (
            '_CATGROUP',
            '_CATOBJ'
            )),                             $order='ASC',  array
            (
            '_CATGROUP',
            '_CATOBJ'
            ));

        $p=array();

        $idc=array($params['id'] => $this->_tree->getNodeInfo($params['id']));



        if ($params['Property_sets'])
            {
            $p+=array('Property_set' => $params['Property_sets']);
            }

        $this->exportSettings=$p;

        if (is_array($this->_tree->EXPcache))
            {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
            $this->recursiveExport($params['id'], 1);

            if ($params['encoding'] != 'utf8')
                {
                $s=XCODE::utf2win($TMS->parseSection('export_head'), $params['encoding']);
                }
            else
                {
                $s=$TMS->parseSection('export_head');
                }

            if (XFILES::filewrite($_PATH['PATH_EXPORT'] . $params['filename'], $s))
                {
                $this->result['uploadfile']=$_PATH['WEB_PATH_EXPORT'] . $params['filename'];
                }
            else
                {
                $this->result['ERROR']=2;
                } //cant_write_file
            }
        else
            {
            $this->result['ERROR']=1;
            } //objects not found
        }

        
        
        function convertPropertySet($val) { return $this->_property_tree->ReadNodeParam($val, 'Name'); }

    function catalog_table($params)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$params['id'];

        $options['shownodesWithObjType']=array
            (
            '_CATGROUP',
            '_CATOBJ',
            '_ROOT'
            );

        $options['groups']=array
            (
            '_CATGROUP',
            '_ROOT'
            );

        $options['columnsAsParameters']=array
            (
            'Name'         => 'Name',
            'Property_set' => 'Property_set',
            'Disable'      => 'Disable'
            );

        $options['preventDots']=true;

        $options['columnsAsStructs']=array('id' => 'id','basic'=>'basic');

        $options['sequence']=array
            (            
            'Name',            
            'Property_set',            
            'id',
            'basic',            
            'Disable'
            
            );

        $options['callfunc']=array('Property_set' => array
            (
            $this,
            'convertPropertySet'
            ));

        $l=Common::get_module_lang('catalog', $_SESSION['lang'], 'common_catalog');

        $options['emulate_root']=array
            (
            $l['{root_name}'],
            '',
            ''
            );

        $options['gridFormat']=1;

        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($params['id']);        
        $this->result=$TD->result;
        }
        
    function showExport()
        {
        global $_PATH;
        $tpls=Common::get_module_template_list('catalog', array('.export.html'));
        $this->get_property_sets('catalogExport', 'Property_sets');

        $this->result['catalogExport']['Template']      =XHTML::arr_select_opt(XARRAY::combine($tpls, $tpls), $se,
                                                                               true);
        $this->result['catalogExport']['exportWritable']=is_writable($_PATH['PATH_EXPORT']);
        }

    function importJSON($params) { }

    function show_importXLS()
        {
        $this->get_property_sets('importXLS', 'categoryType');
        $this->get_property_sets('importXLS', 'objectType');
        }

    function parse_er($file)
        {
        $er  =Common::inc_module_factory('ExcelReader', true);
        $data=new Spreadsheet_Excel_Reader($file);
        return $data->dumptoarray();
        }

    function parse_ee($file)
        {
        Common::inc_module_factory('ExcelExplorer', true);
        $ee   =new ExcelExplorer;
        $sheet=0;

        $arr=array();

        $fsz=filesize($file);
        $fh =@fopen($file, 'rb');
        if (!$fh || ($fsz == 0))die('No file uploaded');
        $file=fread($fh, $fsz);
        @fclose($fh);

        if (strlen($file) < $fsz)
            die('Cannot read the file');

        $ee->Explore($file);

        if (!$ee->IsEmptyWorksheet($sheet))
            {
            for ($row=0; $row <= $ee->GetLastRowIndex($sheet); $row++)
                {
                if (!$ee->IsEmptyRow($sheet, $row))
                    {
                    for ($col=0; $col <= $ee->GetLastColumnIndex($sheet); $col++)
                        {
                        if (!$ee->IsEmptyColumn($sheet, $col))
                            {
                            $arr[$row][$col]=$ee->GetCellData($sheet, $col, $row);

                            switch ($ee->GetCellType($sheet, $col, $row))
                                {
                                case 3:
                                    $arr[$row][$col]=$ee->AsHTML($arr[$row][$col]);

                                    break;

                                case 2:
                                    $arr[$row][$col]=(100 * $arr[$row][$col]) . '%';

                                    break;

                                case 4:
                                    $arr[$row][$col]=($arr[$row][$col] ? 'TRUE' : 'FALSE');

                                    break;

                                case 6:
                                    $arr[$row][$col]        =$arr[$row][$col]['string'];

                                    $arr[$row][$col]['type']='Date';
                                    break;

                                default: break;
                                }
                            }
                        }
                    }
                }

            return $arr;
            }
        else
            {
            return false;
            }
        }
        
        
    function setSessionHandler(&$handler)
    {
        $this->sessionhandler=&$handler;    
    }
    

    function importXLS_prepare($params)
        {
    
            global $_CONFIG;
                
        $file_ext=strrchr($params['data']['filename'], '.');

        if ($file_ext == '.xls')
            {
                if($_CONFIG['catalog']['excelparser']=='ExcelExplorer')
                {
                    $ex         =$this->parse_ee(PATH_ . $params['data']['filename']);
                    $head_line_a=$ex[0];
                }else{
                    $ex         =$this->parse_er(PATH_ . $params['data']['filename']);
                    $head_line_a=$ex[1];
                }
                
            }
        elseif (($file_ext == '.txt') or ($file_ext == '.csv'))
            {
            $handle=fopen(PATH_ . $params['data']['filename'], "r");

            while (($data=fgets($handle, 16384)) !== FALSE)
                {
                $ex[]=explode(';', $data);
                }

            $head_line_a=$ex[0];
            }

        $this->session['step_l']     =$params['data']['step_l'];
        $this->session['xls_length'] =count($ex);
        $this->session['currentstep']=1;
        $this->session['curlev']     =0;

        if ($this->session['xlscache']=Common::cacheWrite(serialize($ex), $this->_module_name))
            {
            foreach ($head_line_a as $n => $head_line)
                {
                preg_match('/\[(.*?)\]/', $head_line, $matches);

                if ($matches[1])
                    {
                    $head_line=str_replace($matches[0], '', $head_line);
                    preg_match('/(.?)\((.*?)\)/', $matches[1], $minner);

                    if ($minner[2])
                        {
                        $HL[$n]['flag']     =strtoupper($minner[1]);
                        $HL[$n]['operation']=$minner[2];
                        }
                    else
                        {
                        $HL[$n]['flag']=strtoupper($matches[1]);
                        }
                    }

                $HL[$n]['key']=trim($head_line);
                }

            $this->session['HL']      =$HL;
            $this->session[0]['ctgid']=$params['id'];
            $this->result['parseComplete']            =1;
            }
        else
            {
            $this->result['parseComplete']=0;
            }
        }

    


    function importXLS($params)
        {
            
                    

            if(!$params['cron'])
            {
                $this->setSessionHandler($_SESSION);
            }
          

        $this->session=$this->sessionhandler['sdata'];
        
        if ($params['data']['clearbase'] && $params['step'] == 'parse')
            {
                //$this->_tree->DelNode($params['id'],true);
            }
        
        
        if ($params['step'] == 'parse')
            {
            $this->importXLS_prepare($params);
            $this->session['step']='load';  
            $this->sessionhandler['sdata']=$this->session;
            return;
            }
        else
            {
    
            $obj_fields_ancestors=Array();            
            $start=$this->session['currentstep'];
            $lev=$this->session['curlev'];
            

            if ($this->session['currentstep'] == 0)
                {
                $start=1;
                }


            $ex=unserialize(Common::cacheRead($this->_module_name, $this->session['xlscache']));
            $ex=array_slice($ex, $start, $this->session['step_l']);
    
            
            $HL=$this->session['HL'];
            $k =$start;

            if(!$ex)
            {
                $this->result['dataWriteEnd']=1; return;
            }
            
            $group_keys=array();
            foreach ($HL as $key => $hline) 
            {
                if ($hline["flag"]=="#") $group_keys[]=$key;
            }

            
            while (list($line, $record)=each($ex))
                {
                $k++;
                $vf=null;
                $pf=null;

                if (($recount=(count($HL) - count($record))) > 0)
                    {
                    for ($i=count($HL) - $recount; $i < count($HL); $i++)
                        {
                        $record[$i]='';
                        }
                    }

                    

                $connected=null;
                $sf=null;
                $qf=null;
                $required=array();
                
                foreach ($record as $fnum => $fieldval)
                    {
                                 
                            $fieldval = str_replace( '°','',$fieldval);
                    
                    if ($fieldval[0] == '#')
                        {
                        $lev=0;

                        while ($fieldval[$lev] == '#')
                            {
                            $lev++;
                            }

                        $this->session['curlev']=$lev;
                        $group_data['Name']                     =substr($fieldval, $lev);
                        $group_data['Disable']                  ='';
                        $group_data['Property_set']             =$params['data']['categoryType'];
                        
                        if ($group_keys) {
                            foreach ($group_keys as $key) {
                                $group_data[$HL[$key]["key"]]=$HL[$key]["operation"].$record[$key];
                            }
                        }

                        if ($params['data']['encoding'] != 'utf8')
                            {
                            $group_data=XCODE::win2utf($group_data, $params['data']['encoding']);
                            }

                        if (!$res=$this->_tree->JoinSearch(array(0 => array
                            (
                            'Name',
                            $group_data['Name']
                            )),array(0 => array
                            (
                            'ancestor',
                            $this->session[$lev - 1]['ctgid']
                            ))))
                            {
                            
                            
                            if ($group_data['basic']) 
                                {
                                    $this->session[$lev]['ctgid']=$this->init_group( $this->session[$lev - 1]['ctgid'], $group_data,$group_data['basic']);
                                } else {
                                    $this->session[$lev]['ctgid']=$this->init_group( $this->session[$lev - 1]['ctgid'], $group_data);
                                }
                                                                              
                            }
                        else
                            {
                            $res                                         =array_shift($res);
                            $this->session[$lev]['ctgid']=$res['id'];
                            }

                        break (1);
                        }

   if ($HL[$fnum]['flag'])
                        {
                        switch ($HL[$fnum]['flag'])
                            {
                      
                            case 'C':
                                
                                if ($params['data']['encoding'] != 'utf8') {
                                    $fieldval=XCODE::win2utf($fieldval, $params['data']['encoding']);
                                }
                                $connected=explode(',', $fieldval);
                                
                                                           

                              
                                if (strrchr($fieldval, '.'))
                                    {
                                    $connected=explode('.', $fieldval);
                                    }


                             
                                $t_childs[$HL[$fnum]['key']]=$connected[0];

                                break;

                            case 'R':
                                $required[]=$fieldval;
                                $required_enabled=true;
                                break;
                                
                            case 'P':
                                $pf[]=array
                                    (
                                    $HL[$fnum]['key'],
                                    $fieldval
                                    );

                                break;

                            //  ?????? ? ???? ?? ???????
                            case 'T':
                                $va=null;
                                $ra=null;

                                if ($sf)
                                    {
                                    $_df                   =$sf;
                                    $_df[$HL[$fnum]['key']]=$fieldval;

                                    foreach ($_df as $ka => $v)
                                        {
                                        $ra[] = '{' . $ka . '}';

                                        if ($t_childs[$ka])
                                            {
                                            $va[]=$t_childs[$ka];
                                            }
                                        else
                                            {
                                            $va[]=$v;
                                            }
                                        }
                                    }

                                $sf[$HL[$fnum]['key']]=str_replace($ra, $va, $HL[$fnum]['operation']);

                                break;
                                
                            case 'S':
                                $va=null;
                                $ra=null;
                                    
                                if ($sf)
                                    {
                                    $_df                   =$sf;
                                    $_df[$HL[$fnum]['key']]=$fieldval;

                                    foreach ($_df as $ka => $v)
                                        {
                                        $ra[] = '{' . $ka . '}';

                                        if ($t_childs[$ka])
                                            {
                                                $va[] = str_replace(array(" ", ",", ", ","\"","'","!",'(',')','/'), array("_", "", "","","","","_","_",''), $t_childs[$ka]);
                                            }
                                        else
                                            {
                                                $va[] = str_replace(array(" ", ",", ", ","\"","'","!",'(',')','/'), array("_", "", "","","","","_","_",''), $v);
                                            }
                                        }
                                    }

                                $sf[$HL[$fnum]['key']] = XCODE::translit(str_replace($ra, $va, $HL[$fnum]['operation']));

                                break;

                            CASE 'L':
                                $qf[$HL[$fnum]['key']]=XCODE::translit($fieldval);
                            break;
                            
                            
                            
                            CASE 'Q':
                                $qf[$HL[$fnum]['key']]=$fieldval;
                                break;

                            case 'Y':
                                list($cat_id, $yparam)=explode('=', $HL[$fnum]['operation']);
                                if($FOM = $this->_tree->DeepSearch($cat_id, array('_CATGROUP','_CATOBJ'), 0, array($yparam=>trim($fieldval)))) 
                                {
                                    $sf[$HL[$fnum]['key']] = $FOM[0];
                                    $sf['__'.$HL[$fnum]['key']] = $fieldval;
                                }
                                break;

                            case 'V':
                                $vf[$HL[$fnum]['key']]=0;

                                break;

                            case 'N':
                                $sf[$HL[$fnum]['key']]=$sf['Name'];
                                break;
                            }
                        }

                    
                    if (!in_array($HL[$fnum]['flag'], array
                        (
                        'N',
                        'T',
                        'S',
                        'Y'
                        )))
                        {
                        $sf[$HL[$fnum]['key']] = $fieldval;
                        }
                    }

                $t_childs=null;

                if(!$sf['Name']){continue;}
                $required_count=array_filter($required, 'strlen');
                if((($required_enabled)&&(count($required_count)<count($required)))or(($required_enabled)&&(!$required))){continue;}
                
                //unset($sf['']);
                
                if ($sf)
                    {
                    if ($params['data']['encoding'] != 'utf8')
                        {
                        $sf=XCODE::win2utf($sf, $params['data']['encoding']);
                        }
                        
                    if ((is_array($pf)) && ($res=$this->_tree->JoinSearch($pf, array(0 => array
                        (
                        'ancestor',
                        $this->session[$lev]['ctgid']
                        )))))
                        {
                        $res=array_shift($res);

                        if ($vf)
                            {
                            $sf=XARRAY::array_diff_key($sf, $vf);
                            }

                        if (!$sf['basic'])
                            {
                                $basic='%SAME%';
                            }
                        else
                            {
                                $basic=$sf['basic'];
                            }

                        $this->reinit_catobj($res['id'],$basic, $sf);

                        if ($connected)
                            {
                            foreach ($connected as $co)
                                {
                                if ($r=$this->_tree->DeepSearch($params['data']['showGroupId'], array('_CATGROUP'), 0,
                                                                array('Name' => $co)))
                                    {
                                    $r=current($r);
                                    $qf_update=array('id' => $r);

                                    if ($qf)
                                        {
                                          $qf_update=$qf_update + $qf;
                                        }


                                    if ($cos=$this->_tree->JoinSearch(array(0 => array
                                        ('id',$r)), array(0 => array('ancestor',$res['id']))))
                                        {
                                            $cos=current($cos);
                                            $this->_tree->DelNode($cos['id']);
                                        }

                                $this->init_catconnobj($res['id'], $qf_update);
                                }
                            }
                        }
                    }
                else
                    {
                        
                    if (!$sf['Property_set'])
                        {
                        $sf['Property_set']=$params['data']['objectType'];
                        }

                    $sf['Disable']='';
                    
                    if(!$sf['basic']){$sf['basic']='%SAMEASID%';}
                    $_id          =$this->init_catobj($this->session[$lev]['ctgid'],$sf, $sf['basic']);

                    if ($connected)
                        {
                        foreach ($connected as $co)
                            {
                            if ($r=$this->_tree->JoinSearch(array(0 => array
                                (
                                'Name',
                                $co
                                )),                         array(0 => array
                                (
                                'ancestor',
                                $params['data']['showGroupId']
                                ))))
                                {
                                $r=current($r);

                                $qf_update=array('id' => $r['id']);

                                if ($qf)
                                    {
                                    $qf_update=$qf_update + $qf;
                                    }

                                $this->init_catconnobj($_id, $qf_update);
                                }
                            }
                        }
                    }
                }
                
            } 

        if ($this->session['currentstep']
            < ($this->session['xls_length'] + $this->session['step_l']))
            {
            $this->session['currentstep']+=$this->session['step_l'];
            $this->result['dataWriteEnd']=0;
            $this->result['loadedCount'] =$k;
            }
        else
            {
            $this->result['dataWriteEnd']=1;
            Common::cacheClear($this->_module_name, $this->session['xlscache']);
            }
        }
            $this->sessionhandler['sdata']=$this->session;
        }
        
        
        
   function changeAncestorGrid($parameters) { $this->_common_obj->changeAncestorGrid($parameters, $this); }
   
   
   
   
   function check_uniq($params)
        {
        
        
        $anc=$this->_tree->GetAncestor($params['id']);
        if (is_array($q=$this->_tree->FindbyBasic($anc,$params['basic'])))
            {
            
            $this->result['uniq']=0;

            if (count($q) == 1)
                {
                $this->result['id']=$q[0];
                }
            }
        else
            {
            $this->result['uniq']=1;
            }
        }
        
        
        function switch_catalog_obj($params)
    {
        
       $this->_tree->WriteNodeParam($params['id'],'Disable',$params['state']);
    }

    function get_action_properties($parameters)
        {
        global $TMS, $Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
            $TMS->AddFileSection(
                $Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'ainterface')), true), true);

            Common::call_common_instance('pages');
            $pages=&pages_module_common::getInstance();

            if ($this->ishop)
                {
            
                $this->result['action_properties_form']['BasketPage']
                    =XHTML::arr_select_opt(
                         XARRAY::arr_to_lev($pages->get_page_module_servers('show_ishop_basket'), 'id', 'params',
                                            'Name'),
                         false,
                         true);
                $TMS->parseSection('ishop', true);
                }

            switch ($parameters['Action'])
                {
                case 'show_level_catmenu':
                    $this->result['action_properties']                             =true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    
                    $this->result['xlist']                                         =true;

                    $this->result['action_properties_form']['Destination_page']    =XHTML::arr_select_opt(
                                                                                        XARRAY::arr_to_lev(
                                                                                            $pages->get_page_module_servers(
                                                                                                'show_catalog_server'),
                                                                                            'id',
                                                                                            'params',
                                                                                            'Name'),
                                                                                        false,
                                                                                        true);

                    $this->result['action_properties_form']['Template']            =XHTML::arr_select_opt(
                                                                                        XARRAY::combine($files, $files),
                                                                                        $se,
                                                                                        true);
                    $this->lct['action_properties']                                =$TMS->parseSection(
                                                                                        $parameters['Action']);

                    $this->result['action_properties_form']['Cat_destination_page']=XHTML::arr_select_opt(
                                                                                        XARRAY::arr_to_lev(
                                                                                            $pages->get_page_module_servers(
                                                                                                'show_catalog_server'),
                                                                                            'id',
                                                                                            'params',
                                                                                            'Name'),
                                                                                        false,
                                                                                        true);

                    break;

                case 'show_catalog_server':
                    $this->result['action_properties']                         =true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html','.show_object.html','.show_category.html'));

                    $tpls                                                      =XHTML::arr_select_opt(
                                                                                    XARRAY::combine($files, $files),
                                                                                    $se,
                                                                                    true);
                    $this->result['action_properties_form']['CategoryTemplate']=$tpls;

                    $this->result['action_properties_form']['ObjectTemplate']  =$tpls;
                    $this->result['url_point_xlist']                                         =true;
                    $this->result['action_properties_form']['Default_action']  =XHTML::arr_select_opt(
                                                                                    XARRAY::askeyval(
                                                                                        $this->_common_obj->get_non_server_actions(),
                                                                                        'front_name'),
                                                                                    null,
                                                                                    true);

                    $this->lct['action_properties']                            =$TMS->parseSection(
                                                                                    $parameters['Action']);
                    break;

                case 'show_search_results':
                    $this->result['action_properties']                       =true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls                                                    =XHTML::arr_select_opt(
                                                                                  XARRAY::combine($files, $files), $se,
                                                                                  true);

                                                                                  
                                                                                  
                    $this->result['action_properties_form']['Destination_page']    =XHTML::arr_select_opt(
                                                                                        XARRAY::arr_to_lev(
                                                                                            $pages->get_page_module_servers(
                                                                                                'show_catalog_server'),
                                                                                            'id',
                                                                                            'params',
                                                                                            'Name'),
                                                                                        false,
                                                                                        true);                                                                                  

                                                                                  
                    $this->result['action_properties_form']['ResultTemplate']=$tpls;
                    $this->lct['action_properties']                          =$TMS->parseSection($parameters['Action']);

                    break;

                    case 'show_search_form':
                    $this->result['action_properties']=true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls =XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);
                    $this->result['action_properties_form']['SearchTemplate']  =$tpls;
                    $this->result['action_properties_form']['SearchForm'] =XHTML::arr_select_opt( XARRAY::askeyval( $this->_common_obj->search_forms_tree->GetChildsParam( 1, '%'), 'Name'), false, true);
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt( XARRAY::arr_to_lev( $pages->get_page_module_servers('show_search_results'), 'id',  'params', 'Name'), false, true);
                    $this->lct['action_properties'] =$TMS->parseSection($parameters['Action']);

                    break;
                    
                case 'show_smart_search_form':
                    $this->result['action_properties'] =true;
                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls  = XHTML::arr_select_opt( XARRAY::combine($files, $files), $se, true);
                    $this->result['action_properties_form']['SearchTemplate'] =$tpls;
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt( XARRAY::arr_to_lev( $pages->get_page_module_servers( 'show_search_results'), 'id', 'params', 'Name'), false, true);
                    $this->lct['action_properties'] =$TMS->parseSection( $parameters['Action']);

                    break;                    

                    
                case 'show_branch_info':
                    $this->result['action_properties']=true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls =XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);
                    $this->result['action_properties_form']['Template']  =$tpls;
                    $this->result['action_properties_form']['SearchForm'] =XHTML::arr_select_opt( XARRAY::askeyval( $this->_common_obj->search_forms_tree->GetChildsParam( 1, '%'), 'Name'), false, true);
                    $this->lct['action_properties'] =$TMS->parseSection($parameters['Action']);

                    break;
                    

                case 'catalog_filter':
                    $this->result['action_properties']                         =true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls                                                      =XHTML::arr_select_opt(
                                                                                    XARRAY::combine($files, $files),
                                                                                    $se,
                                                                                    true);
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_catalog_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $this->result['action_properties_form']['FilterTemplate']  =$tpls;
                    $this->lct['action_properties']                            =$TMS->parseSection(
                                                                                    $parameters['Action']);

                    break;

                case 'catalog_comparsion':
                    $this->result['action_properties']                           =true;

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls                                                        =XHTML::arr_select_opt(
                                                                                      XARRAY::combine($files, $files),
                                                                                      $se,
                                                                                      true);
                    $this->result['action_properties_form']['ComparsionTemplate']=$tpls;

                    $this->lct['action_properties']                              =$TMS->parseSection(
                                                                                      $parameters['Action']);

                    break;

                case 'show_category':
                    $this->result['action_properties']=true;

                    Common::call_common_instance('pages');
                    $pages                                                          =
                        &pages_module_common::getInstance();
                    
                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    
                    $this->result['xlist']                                          =true;

                    $tpls                                                           =XHTML::arr_select_opt(
                                                                                         XARRAY::combine($files,
                                                                                                         $files),
                                                                                         $se,
                                                                                         true);

                    $this->result['action_properties_form']['InnerCategoryTemplate']=$tpls;

                    $this->result['action_properties_form']['Destination_page']     =XHTML::arr_select_opt(
                                                                                         XARRAY::arr_to_lev(
                                                                                             $pages->get_page_module_servers(
                                                                                                 'show_catalog_server'),
                                                                                             'id',
                                                                                             'params',
                                                                                             'Name'),
                                                                                         false,
                                                                                         true);
                    $this->lct['action_properties']                                 =$TMS->parseSection(
                                                                                         $parameters['Action']);

                    break;



                case 'catalog_filter_results':
                    $this->result['action_properties']=true;

                    Common::call_common_instance('pages');
                    $pages                                                     =&pages_module_common::getInstance();
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_catalog_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));
                    $tpls                                                      =XHTML::arr_select_opt(
                                                                                    XARRAY::combine($files, $files),
                                                                                    $se,
                                                                                    true);

                    $this->result['action_properties_form']['ResultTemplate']  =$tpls;
                    $this->lct['action_properties']                            =$TMS->parseSection(
                                                                                    $parameters['Action']);

                    break;

                case 'show_react_menu':
                    $this->result['action_properties']                 =true;

                    $this->result['xlist']                             =true;
                    
                    
                    $this->result['action_properties_form']['CatObjDestination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_catalog_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);
                                                                                    

                    $files = Common::get_module_template_list('catalog',array('.'.$parameters['Action'].'.html'));

                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);

                    $this->lct['action_properties']                    =$TMS->parseSection($parameters['Action']);

                    break;
                }
            }
        }




    function set_root_data($site_name, $root_data)
        {
        $uniq_param['uniquetype']='unique_in_tree';
        $this->_tree->ReInitTreeOBJ(1, $site_name, $root_data, $uniq_param);
        }


   function init_group($id_anc, $group_data,$basic='%SAMEASID%')
        {
        $id=$this->_tree->InitTreeOBJ($id_anc, $basic, '_CATGROUP', $group_data, true);
        return $id;
        }
        
    function init_sform($sform_data)
        {
        $id=$this->_common_obj->search_forms_tree->InitTreeOBJ(1, '%SAMEASID%', '_SFORM', $sform_data, true);
        return $id;
        }

    function init_sfield($fid, $sfield_data)
        {
        $id=$this->_common_obj->search_forms_tree->InitTreeOBJ($fid, '%SAMEASID%', '_SFIELD', $sfield_data, true);
        return $id;
        }

    function reinit_sform($id, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_common_obj->search_forms_tree->ReInitTreeOBJ($id, '%SAME%', $data,
                                                                                       $uniq_param);
        return $id;
        }

    function reinit_sfield($id, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_common_obj->search_forms_tree->ReInitTreeOBJ($id, '%SAME%', $data,
                                                                                       $uniq_param);
        return $id;
        }

    function reinit_group($id, $data,$basic='%SAME%')
        {
        $uniq_param['uniquetype']='unique_in_anc';
        if(!$basic){$basic='%SAME%';}
        $id                      =$this->_tree->ReInitTreeOBJ($id, $basic, $data, $uniq_param);
        return $id;
        }

    function reinit_root($data)
        {
        $id=$this->_tree->ReInitTreeOBJ(1, '%SAME%', $data);
        return $id;
        }

    function init_catobj($id_anc, $data, $basic='%SAMEASID%')
        {
            $data['lastModified']=time();
            $id=$this->_tree->InitTreeOBJ($id_anc, $basic, '_CATOBJ', $data, true);
        
        return $id;
        }

    function reinit_catobj($id, $basic, $data)
        {
        $uniq_param['uniquetype']='unique_in_tree';
        $data['lastModified']=time();
        $id                      =$this->_tree->ReInitTreeOBJ($id, $basic, $data, $uniq_param);
        return $id;
        }

    function init_catconnobj($id_anc, $data)
        {
        $id=$this->_tree->InitTreeOBJ($id_anc, '%SAMEASID%', '_CATCONNOBJ', $data, true);
        return $id;
        }

    function init_property_set($set_data)
        {
        $id=$this->_property_tree->InitTreeOBJ(1, '%SAMEASID%', '_PROPERTYSET', $set_data, true);
        return $id;
        }

    function reinit_property_set($id, $set_data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_property_tree->ReInitTreeOBJ($id, '%SAME%', $set_data, $uniq_param);
        return $id;
        }

    function init_property($prop_set_id, $prop_basic, $prop_data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_property_tree->InitTreeOBJ($prop_set_id, $prop_basic, '_PROPERTY', $prop_data,
                                                                     true);
        return $id;
        }

    function init_subgroup($prop_set_id, $s_data)
        {
        $id=$this->_property_tree->InitTreeOBJ($prop_set_id, '%SAMEASID%', '_SUBGROUP', $s_data, true);
        return $id;
        }

    function init_propertyvals($prop_id, $prop_data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_property_tree->InitTreeOBJ($prop_id,   '%SAMEASID%', '_PROPERTYVALS',
                                                                     $prop_data, true);
        return $id;
        }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree',
            '_property_tree'
            ));
        }
        
   function load_xlist_fuser($parameters) {
        Common::call_common_instance('fusers');
        $fusers = &fusers_module_common::getInstance(true);
        $TD=Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];
        $options['shownodesWithObjType']=array('_FUSER','_FUSERSGROUP','_ROOT');
        $options['columnsAsParameters']=array('name' => 'Name');
        $options['columnsAsStructs']=array('image' => 'obj_type');
        $options['transformResults']['image']=array('_FUSERSGROUP' => 'group','_FUSER'  => 'page','_ROOT'  => 'group');
        $options['selectable']=array('image' => array('_FUSER'));
        $this->result['data_set']=null;
        $TD->init_from_source($fusers->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
    }    
        
        
   function load_xlist_gallery_albums($parameters) {
       Common::call_common_instance('gallery');
       $gallery = &gallery_module_common::getInstance(true);
       $TD=Common::inc_module_factory('TTreeSource');
       $options['startNode']=$parameters['anc_id'];
       $options['shownodesWithObjType']=array('_ROOT', '_GALLERY', '_ALBUM');
       $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name'=> 'Name');
       $options['columnsAsStructs']=array('image' => 'obj_type');
       $options['transformResults']['image']=array('_ROOT'  => 'group', '_GALLERY'  => 'group', '_ALBUM'  => 'page');
       $options['selectable']=array('image' => array( '_ALBUM' ));
       $this->result['data_set']=null;
       $TD->init_from_source($gallery->obj_tree);
       $TD->setOptions($options);
       $TD->CreateView($parameters['anc_id']);
       $this->result=array_merge_recursive($TD->result, $this->result);

   }                 
        
   function load_xlist_docs($parameters) {
       Common::call_common_instance('price');
       $price = &price_module_common::getInstance(true);
       $TD=Common::inc_module_factory('TTreeSource');
       $options['startNode']=$parameters['anc_id'];
       $options['shownodesWithObjType']=array('_ROOT', '_PRICEGROUP' );
       $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name'=> 'Name');
       $options['columnsAsStructs']=array('name'  => 'basic', 'image' => 'obj_type');
       $options['transformResults']['image']=array('_ROOT'  => 'group','_PRICEGROUP'  => 'group');
       $options['selectable']=array('image' => array( '_PRICEGROUP' ));
       $this->result['data_set']=null;
       $TD->init_from_source($price->obj_tree);
       $TD->setOptions($options);
       $TD->CreateView($parameters['anc_id']);
       $this->result=array_merge_recursive($TD->result, $this->result);
   }                 

   function load_faqs($parameters) {
       Common::call_common_instance('faq');
       $faq = &faq_module_common::getInstance(true);
       $r = $faq->obj_tree->GetChildsParam(1,array('Name','basic'));
       $faqs = array();
       foreach($r as $key =>$f){
           $faqs[]=array("id" => $key, "basic" => $f["basic"]);
       }
       $this->result["faqs"]=$faqs;
   }
   
        
        
   function load_sforms($parameters) {
       $r = $this->_common_obj->search_forms_tree->GetChildsParam(1,array('Name'));
       $sforms = array();
       foreach($r as $key =>$sform){
           $sforms[]=array("id" => $key, "Name" => $sform["Name"]);
       }
       $this->result["sforms"]=$sforms;
   }
                

    
    
    function on_page_module_position_change($module,$page_id)
    {
        if($module['params']['Action']=='show_catalog_server')
        {
            $pages=Common::module_factory('pages.back');
            $pages->set_routes_to_server($page_id,'show');
        }
        
    }
    
    function on_module_save($params,$page_id)
    {
        $pages=Common::module_factory('pages.back');
        $pages->set_routes_to_server($page_id,'show');
        return $params;
    }
    
    
    
        
        
    }
?>