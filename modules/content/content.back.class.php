<?php
/**
 * Back-end for content module class for x3m.cms
 *  all method support  async call
 * @author x3m group <info@x3m.by>
 * @version 1.0
 */

class content_module_back
    {
    var $lct;
    var $result;

    var $_module_name;
    var $_common_obj;
    var $_tree;

    function content_module_back()
        {
        $this->_module_name='content';
        }

    function request_action_set($action) { $this->_common_obj->request_action_set($action); }

    function common_call($front_call = null)
        {
        $this->_module_name='content';
        $this->_common_obj =&content_module_common::getInstance();
        $this->_tree       =&$this->_common_obj->obj_tree;
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
        $acontext->lct   =$this->lct;
        $acontext->result=$this->result;
        }

    function delete_obj($data) { $this->_common_obj->delete_obj(&$this, $data); }

    function changeAncestor($parameters)
        {
        $this->result['dragOK']
            =$this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'], $parameters['relative']);
        }

    function get_component_location($params)
        {
        Common::call_common_instance('pages');
        $pages=&pages_module_common::getInstance();

        if ($modules=$pages->obj_tree->Search(array
            (
            'contentId' => $params['id'],
            'Action'    => 'show_content'
            )))
            {
            foreach ($modules as $module_id)
                {
                $page_id = $pages->obj_tree->GetAncestor($pages->obj_tree->GetAncestor($module_id));
                $page    =$pages->obj_tree->getNodeInfo($page_id);
                $pages->obj_tree->GetBones($page_id, 1);
                $bl=$pages->obj_tree->GetBonesLine('Name', '/', true);

                $this->result['rows'][$page_id]=array('data' => array
                    (
                    $page_id,
                    $bl,
                    $page['basic']
                    ));
                }
            }
        }

    function get_intitial_outer_link($dpage = false)
        {
        Common::call_common_instance('pages');
        $pages                                        =&pages_module_common::getInstance();
        $this->result['outerLink']['Destination_page']=XHTML::arr_select_opt(
                                                           XARRAY::arr_to_lev(
                                                               $pages->get_page_module_servers('content_server'),
                                                               'id',
                                                               'params',
                                                               'Name'),
                                                           $dpage,
                                                           true);
        }

    function load_tpl($parameters)
        {
        $this->result['default_tpl']=$this->_tree->ReadNodeParam($parameters['id'], 'Template');
        }

    function tpl_content_edit()
        {
        global $TMS, $Adm;

        $TMS->AddFileSection(
            $Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'edit_content')), true), true);
        $this->lct['edit_content']=$TMS->noparse('edit_content');
        }

    function get_initial_category_data($tpl_selected = null)
        {
        global $_PATH;

        if ($files=Common::get_module_template_list($this->_module_name))
            {
            $this->result['category_data']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),
                                                                             $tpl_selected);
            }
        }

    function add_category()
        {
        $this->get_initial_category_data();
        $this->get_intitial_outer_link();
        }

    function add_content($parameters)
        {
        global $_PATH;

        if ($files=Common::get_module_template_list($this->_module_name))
            {
            $this->load_initial_content_data($parameters['group_id']);
            $this->parse_content_tpl(array('tpl_file' => $files[0]), true);
            }
        }

    function load_initial_content_data($category_selected = null, $tpl_selected = null)
        {
        global $_PATH;

        $files=Common::get_module_template_list($this->_module_name);

        if ($category_selected && !$tpl_selected)
            {
            if ($category=$this->_tree->GetNodeInfo($category_selected))
                {
                $tpl_selected=$category['params']['Template'];
                }
            }

        $this->result['content_data']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files), $tpl_selected);
        $this->load_categories($category_selected);
        }

    function load_categories($category_selected)
        {
        $this->result['content_data']['category']
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'),
                                   $category_selected);
        }

    function load_content($parameters)
        {
        global $TMS, $_PATH, $Adm;

        $TMS->AddFileSection(
            $Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'edit_content')), true), true);

        $node                                   =$this->_tree->getNodeInfo($parameters['content_id']);
        $this->result['tunes']['basic']         =$node['basic'];
        $this->result['tunes']['Title']         =$node['params']['Title'];
        $this->result['tunes']['Keywords']      =$node['params']['Keywords'];
        $this->result['tunes']['Description']   =$node['params']['Description'];
        $this->result['content_data']['Name']   =$node['params']['Name'];
        $this->result['content_data']['Disable']=$node['params']['Disable'];

        $this->load_initial_content_data($node['ancestor'], $node['params']['Template']);

        $this->parse_content_tpl(array('tpl_file' => $node['params']['Template']));

        $this->result['content_data']['fields_data']=$this->_common_obj->get_fields_data($parameters['content_id']);
        }

    function create_outer_link_content($content_node)
        {
        $pages=common::module_factory('pages.back');
        $pages->common_call();

        $node=$this->_tree->getNodeInfo($content_node['anc']);
        $pages->create_outer_link($node['params']['isOuterLink'],         $node['params']['Destination_page'],
                                  '~content/cid/' . $content_node['anc'], $this->_module_name . $content_node['anc'],
                                  $content_node['basic']);
        }

    function create_outer_link_group(&$parameters)
        {
        $pages=common::module_factory('pages.back');
        $pages->common_call();

        if ($child_contents=$this->_tree->GetChilds($parameters['id']))
            {
            foreach ($child_contents as $conts)
                {
                $pages->create_outer_link($parameters['outerLink']['isOuterLink'],
                                          $parameters['outerLink']['Destination_page'],
                                          '~content/cid/' . $conts['id'],
                                          $this->_module_name . $conts['id'],
                                          $conts['basic']);
                }
            }

        if ($parameters['outerLink']['isOuterLink'])
            {
            $parameters['data']['Destination_page']=$parameters['outerLink']['Destination_page'];
            $parameters['data']['isOuterLink']     =$parameters['outerLink']['isOuterLink'];
            }
        else
            {
            $parameters['data']['isOuterLink']='';
            }
        }

    function load_category($parameters)
        {
        if ($node=$this->_tree->getNodeInfo($parameters['id']))
            {
            $this->get_initial_category_data($node['params']['Template']);
            $this->get_intitial_outer_link($node['params']['Destination_page']);
            $this->result['outerLink']['isOuterLink']    =$node['params']['isOuterLink'];
            //$this->result['category_data']['basic']  =$node['basic'];
            $this->result['category_data']['Name']       =$node['params']['Name'];
            $this->result['category_data']['Disable']    =$node['params']['Disable'];
            $this->result['category_data']['description']=$node['params']['description'];
            $this->result['category_data']['view_group'] =$node['params']['view_group'];
            $this->result['tunes']['basic']              =$node['basic'];
            $this->result['tunes']['Title']              =$node['params']['Title'];
            $this->result['tunes']['Keywords']           =$node['params']['Keywords'];
            $this->result['tunes']['Description']        =$node['params']['Description'];
            }
        }

    function save_category($parameters)
        {
        //if ($this->init_contentgroup($parameters['data']['basic'], $parameters['data']))
        if ($this->init_contentgroup($parameters['tunes']['basic'], $parameters['data']))
            {
            $this->result['is_saved']=true;
            }
        }

    function save_edited_category($parameters)
        {
        $this->create_outer_link_group($parameters);

        if ($this->reinit_contentgroup($parameters['id'], $parameters['tunes']['basic'],
                                       array_merge($parameters['data'], $parameters['tunes'])))
            {
            $this->result['is_saved']=true;
            }
        }

    function save_edited_content($parameters)
        {

        /*$parameters['properties']['Name']                =$parameters['data']['Name'];
$parameters['properties']['Disable']             =$parameters['data']['Disable'];
$parameters['properties']['Title']               =$parameters['tunes']['Title'];
$parameters['properties']['Keywords']            =$parameters['tunes']['Keywords'];
$parameters['properties']['Description']         =$parameters['tunes']['Description'];*/

        $tms=new TMutiSection(true);
        $tms->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['main']['Template']));

        //инициализация объекта в дереве _CONTENT
        if ($id=$this->reinit_content($parameters['id'], $parameters['tunes']['basic'],
                                      array_merge($parameters['main'], $parameters['tunes'])))
            {
            //очищаем потомков _CONTENT

            $this->_tree->DelNode($parameters['id'], true);
            $this->create_outer_link_content(array
                (
                'basic' => $parameters['main']['basic'],
                'id'    => $parameters['id'],
                'anc'   => $this->_tree->GetAncestor($parameters['id'])
                ));

            //инициализация полей объекта _FIELD
            foreach ($parameters['inner_fields'] as $field_name => $field_value)
                {
                if (in_array($field_name, array_keys($tms->Extended['xtr_content'])))
                    {
                    $this->init_field($id, $field_name, $field_value, true);
                    }
                }

            $this->result['is_saved']=true;
            }
        }

    function save_content($parameters)
        {
        $tms=new TMutiSection(true);
        //кешируем страницу     
        $tms->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['main']['Template']));
        /*$tms->AddMassReplace('xtr_content', $parameters['inner_fields']);
        $parameters['main']['Cache']=$tms->parseSection('xtr_content');*/
        $parameters['main']['LastModified']=time();

        //инициализация объекта в дереве

        if ($id=$this->init_content($parameters['main']['category'], $parameters['tunes']['basic'],
                                    $parameters['main']))
            {
            $this->create_outer_link_content(array
                (
                'basic' => $parameters['main']['basic'],
                'id'    => $parameters['id'],
                'anc'   => $parameters['main']['category']
                ));

            //инициализация полей объекта
            foreach ($parameters['inner_fields'] as $field_name => $field_value)
                {
                if (in_array($field_name, array_keys($tms->Extended['xtr_content'])))
                    {
                    $this->init_field($id, $field_name, $field_value);
                    }
                }

            $this->result['is_saved']=true;
            }
        }

    function _copy($params) { $this->_common_obj->_copy($this, $params, array('_CONTENT')); }

    function parse_content_tpl($params)
        {
        global $TMS, $Adm;

        $tms=new TMutiSection(true);

        $TMS->AddFileSection(
            $Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'edit_content')), true), true);

        $tms->AddFileSection(Common::get_site_tpl($this->_module_name, $params['tpl_file']));

        if ($tms->Extended)
            {
            foreach ($tms->Extended['xtr_content'] as $field_name => $ext)
                {
                switch ($ext['type'])
                    {
                    case 'ARTICLE':
                        if ($cnt=$this->_tree->Search('', true, array('obj_type' => array
                            (
                            '_CONTENT',
                            '_CONTENTGROUP'
                            ))))
                            {
                            while (list($id, $cntObj)=each($cnt))
                                {
                                if ($cntObj['obj_type'] == '_CONTENT')
                                    {
                                    $cntNew[$id]=$cnt[$cntObj['ancestor']]['basic'] . '/' . $cntObj['basic'];
                                    }
                                }

                            if ($cntNew)
                                {
                                asort($cntNew);
                                $articlesList=(XHTML::as_select_opt($cntNew));
                                }
                            }

                        $TMS->AddReplace($ext['type'], '_field_initial', $articlesList);
                        $TMS->AddReplace($ext['type'], '_field_name', $field_name);
                        $TMS->AddMassReplace($ext['type'], $ext);
                        $TMS->parseSection($ext['type'], true);
                        break;

                    case 'IMAGE':
                    case 'TEXT':
                    case 'INPUT':
                        $TMS->AddReplace($ext['type'], '_field_name', $field_name);

                        $TMS->AddMassReplace($ext['type'], $ext);
                        $TMS->parseSection($ext['type'], true);
                        break;
                    }
                }

            $this->result['fields']=$TMS->parseSection('fields');
            }
        }


    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_ainterface()
        {
        global $TMS;

        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
        $this->lct['ainterface']=$TMS->parseSection('a_interface');
        }

    function load_actions($parameters)
        {
        $this->result['tune_actions']['Action']
            =XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),
                                   $parameters['selected'],
                                   true);
        }

    function get_action_properties($parameters)
        {
        global $TMS, $Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
            $TMS->AddFileSection(
                $Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'ainterface')), true), true);

            switch ($parameters['Action'])
                {
                case 'show_content':
                    $this->result['action_properties']                 =true;

                    $files                                             =Common::get_module_template_list('content',
                                                                                                         array
                                                                                                             ('.' . $parameters['Action'] . '.html'));
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    //включает xlist
                    $this->result['xlist']                             =true;
                    $this->result['action_template']                   =true;

                    $this->lct['action_properties']                    =$TMS->parseSection('show_content');
                    break;

                case 'show_content_announce':
                    $this->result['action_properties']                                              =true;

                    $files                                                                          =
                        Common::get_module_template_list(
                            'content',
                            array
                        (
                        'show_content',
                        '.' . $parameters['Action'] . '.html'
                        ));

                    $this->result['action_properties_form']['aTemplate']                            =
                        XHTML::arr_select_opt(
                            XARRAY::combine($files, $files),
                            $se,
                            true);
                    $this->result['action_properties_form']['show_category_with_link_contents_list']=
                        Common::call_common_instance('pages');
                    $pages                                                                          =
                        &pages_module_common::getInstance();
                    $this->result['action_properties_form']['page']                                 =
                        XHTML::arr_select_opt(
                            XARRAY::arr_to_lev($pages->get_page_module_servers('content_server'), 'id', 'params',
                                               'Name'),
                            false,
                            true);
                    //включает xlist
                    $this->result['xlist']                                                          =true;
                    $this->result['action_template']                                                =true;

                    $this->lct['action_properties']                                                 =$TMS->parseSection(
                                                                                                         'show_content_announce');
                    break;

                case 'show_contents_list':
                    $this->result['action_properties']                  =true;

                    $categories                                         =
                        $this->result['Category']=$files=Common::get_module_template_list(
                                                             'content',
                                                             array('.' . $parameters['Action'] . '.html'));
                    $this->result['action_properties_form']['xTemplate']=XHTML::arr_select_opt(
                                                                             XARRAY::combine($files, $files), $se,
                                                                             true);

                    Common::call_common_instance('pages');
                    $pages                                             =&pages_module_common::getInstance();
                    $this->result['action_properties_form']['Category']=XHTML::arr_select_opt(
                                                                            XARRAY::arr_to_keyarr(
                                                                                $this->_tree->GetChilds(1), 'id',
                                                                                'basic'),
                                                                            false,
                                                                            true);

                    $this->result['action_properties_form']['page']    =XHTML::arr_select_opt(
                                                                            XARRAY::arr_to_lev(
                                                                                $pages->get_page_module_servers(
                                                                                    'content_server'),
                                                                                'id',
                                                                                'params',
                                                                                'Name'),
                                                                            false,
                                                                            true);

                    $this->result['xlist']                             =false;
                    $this->result['action_template']                   =true;

                    $this->lct['action_properties']                    =$TMS->parseSection('show_contents_list');
                    break;

                case 'show_contentgroups_list':
                    $this->result['action_properties']                 =true;

                    $files                                             =Common::get_module_template_list('content',
                                                                                                         array
                                                                                                             ('.' . $parameters['Action'] . '.html'));
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    Common::call_common_instance('pages');
                    $pages                                         =&pages_module_common::getInstance();
                    $this->result['action_properties_form']['page']=XHTML::arr_select_opt(
                                                                        XARRAY::arr_to_lev(
                                                                            $pages->get_page_module_servers(
                                                                                'content_server'),
                                                                            'id',
                                                                            'params',
                                                                            'Name'),
                                                                        false,
                                                                        true);
                    //$this->result['xlist']                         =true;
                    $this->lct['action_properties']                =$TMS->parseSection('show_contentgroups_list');
                    break;

                case 'content_server':
                    $this->result['action_properties']                 =true;

                    $files                                             =Common::get_module_template_list(
                                                                            $this->_module_name, array
                        (
                        '.show_content.html',
                        '.' . $parameters['Action'] . '.html'
                        ));

                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);

                    Common::call_common_instance('pages');
                    $pages                                                   =&pages_module_common::getInstance();

                    $this->result['xlist']                                   =false;
                    $this->result['action_template']                         =true;

                    $this->lct['action_properties']                          =$TMS->parseSection('content_server');
                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt(
                                                                                  XARRAY::askeyval(
                                                                                      $this->_common_obj->get_non_server_actions(),
                                                                                      'front_name'),
                                                                                  null,
                                                                                  true);
                    break;
                }
            }
        }

    function content_table($params)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$params['id'];

        $options['shownodesWithObjType']=array
            (
            '_CONTENTGROUP',
            '_CONTENT',
            '_ROOT'
            );

        $options['groups']=array
            (
            '_CONTENTGROUP',
            '_ROOT'
            );

        $options['columnsAsParameters']=array
            (
            'Name'     => 'Name',
            'template' => 'Template'
            );

        $options['preventDots']=true;

        $options['columnsAsStructs']=array('id' => 'id');

        $options['sequence']=array
            (
            'Name',
            'template'
            );

        $l=Common::get_module_lang('content', $_SESSION['lang'], 'common_content');

        $options['emulate_root']=array
            (
            $l['{root_name}'],
            ''
            );

        $options['gridFormat']=1;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);

        $TD->CreateView($params['id']);
        $this->result=$TD->result;
        }


    //специальная функция сервер данных для xlist
    function load_xlist_data($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_CONTENTGROUP',
            '_CONTENT'
            );

        $options['columnsAsParameters']=array('LastModified' => 'LastModified','name' => 'Name');

        $options['columnsAsStructs']=array
            (
            'image' => 'obj_type'
            );

        $options['transformResults']['image']=array
            (
            '_CONTENTGROUP' => 'group',
            '_CONTENT'      => 'page'
            );

        $options['selectable']=array('image' => array('_CONTENT'));


        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;

        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);

        $this->result['data_set']=$TD->result['data_set'];
        }

    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }

    function changeAncestorGrid($parameters) { $this->_common_obj->changeAncestorGrid($parameters, $this); }


    /*--------------------------------------------------------------------------------------------*/

    function init_field($anc, $basic, $field_value)
        {
        $data=Array('field_value' => $field_value);

        $id=$this->_tree->InitTreeOBJ($anc, $basic, '_FIELD', $data, true);
        return $id;
        }

    function init_content($anc, $basic, $data)
        {
        $data['LastModified']=time();
        //$id                  =$this->_tree->InitTreeOBJ($anc, $basic, '_CONTENT', $data, true);
        $id                  =$this->_tree->InitTreeOBJ($anc, '%SAMEASID%', '_CONTENT', $data, true);
        return $id;
        }

    function reinit_content($id, $basic, $data)
        {
        $data['LastModified']    =time();
        $uniq_param['uniquetype']='unique_in_anc';
        $this->_tree->SetUniques($uniq_param);
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }

    function init_contentgroup($basic, $data)
        {
        $data['LastModified']=time();
        //$id                  =$this->_tree->InitTreeOBJ(1, $basic, '_CONTENTGROUP', $data, true);
        $id                  =$this->_tree->InitTreeOBJ(1, '%SAMEASID%', '_CONTENTGROUP', $data, true);
        return $id;
        }

    function reinit_contentgroup($id, $basic, $data)
        {
        $data['LastModified']=time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree'
            ));
        }

    function on_page_module_position_change($module, $page_id)
        {
        if ($module['params']['Action'] == 'content_server')
            {
            $pages=Common::module_factory('pages.back');
            $pages->set_routes_to_server($page_id, 'showcontent');
            }
        }

    function on_module_save($params, $page_id)
        {
        $pages=Common::module_factory('pages.back');
        $pages->set_routes_to_server($page_id, 'showcontent');
        return $params;
        }
    }

/*if ($_REQUEST['xoadCall'])
    {
    XOAD_Server::allowClasses('content_module_back');
    }*/
?>