<?php
class news_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function news_module_back()
        {
        $this->_module_name='news';
        }


    /*внимание здесь исполняются только массив action результаты в свойстве класса $result*/

    function common_call($front_call = null)
        {
        $this->_module_name='news';
        $this->_common_obj =&news_module_common::getInstance();
        $this->_tree       =&$this->_common_obj->obj_tree;
        }

    //убрать 
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

    function get_categories($flows, $category_selected, $ext = true, $sec_flow = 'ctg_id')
        {
        $this->result[$flows][$sec_flow]
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,
                                   $ext);
        }

    function add_news($params) { $this->get_categories('add_news', $params['parent']); }

    function save_group($params)
        {
            
            
        if ($id=$this->init_newsgroup($params['group_data']['basic']))
            {
            $this->result['saved']=true;
            }
        }

    //news 
    // one  -false  news by  id
    //   -true news by category       

    function delete_news($params)
        {
        global $TDB;

        if (is_array($params['id']))
            {
            $id=implode($params['id'], "','");
            $w ='id in (\'' . $id . '\')';
            }
        else
            {
            $w='id="' . $params['id'] . '"';
            }

        $query='delete from news where ' . $w;

        if ($TDB->query($query))
            {
            $this->result['is_del']=true;
            }
        }

    function delete_obj($data)
        {
        if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                if ($this->_tree->DelNode($id))
                    {
                    $this->result['deleted'][]=$id;
                    $this->delete_news(array
                        (
                        'id'  => $id,
                        'one' => true
                        ));
                    }
                }
            }
        else
            {
            if ($this->_tree->DelNode($data['id']))
                {
                $this->result['deleted'][]=$data['id'];
                $this->delete_news(array
                    (
                    'id'  => $data['id'],
                    'one' => true
                    ));
                }
            }
        }
        
    
    function check_uniq($params)
    {
      global $TDB;
      
        $res=$TDB->get_results('SELECT id from news where basic="'.$params['basic'].'"');

        if($res[1]['id'])
            {            
                $this->result['uniq']=$res[1]['id'];                
            }else{
                $this->result['uniq']=false;                
            }
    }

    function show_edit_category($params)
        {
            $n=$this->_tree->getNodeInfo($params['id']);
            if(Common::is_module_exists('comments'))
            {
                Common::call_common_instance('comments');
                $comments=&comments_module_common::getInstance();  
                
                $treads=XARRAY::arr_to_lev($comments->obj_tree->GetChildsParam(1, '%',true),'basic','params','Alias');
            
                $this->result['category_data']['Tread']=XHTML::arr_select_opt($treads,$n['params']['Tread']);
                unset($n['params']['Tread']);
            }

            $this->result['category_data']['basic']=$n['basic'];
            $this->result['category_data']=array_merge($this->result['category_data'], $n['params']);
        }
        

    function save_edited_category($params)
        {

            $this->result['is_save']=$this->reinit_newsgroup($params['id'], $params['data']['basic'], $params['data']);
        }

    function show_edit($params)
        {
        $this->result['news_data']               =$this->_common_obj->select_news($params['id'], '%d-%m-%Y %H:%i:%s');
        $l                                       =Common::get_module_lang('news', $_SESSION['lang'], 'user_types');
        $user                                    =$this->_common_obj->get_author(
                                                      $this->result['news_data']['author_id'],
                                                      $this->result['news_data']['author_type']);
        $this->result['news_data']['author_id']  =$user['params']['Name'];
        $this->result['news_data']['author_type']=$l['{n-' . $this->result['news_data']['author_type'] . '}'];
        $this->get_categories('news_data', $this->result['news_data']['ctg_id'], false);
        }

    function switch_news($params)
        {
        global $TDB;

        if ($TDB->UpdateIN('news', (int)$params['id'], array('active' => (int)$params['state'])))
            {
              x3_message::push($this->_common_obj->translate('saved'), $this->_module_name);
            }
        else
            {
              x3_error::push($this->_common_obj->translate('save_error'), $this->_module_name);
            }
        }

    function change_news_category($params)
        {
        global $TDB;

        if ($params['id'])
            {
            $ctg_id=$TDB->SelectIN('ctg_id', 'news', 'id=' . $params['id']);

            $ctg_id=current($ctg_id);

            if ($ctg_id['ctg_id'] != $params['anc'])
                {
                $TDB->UpdateIN('news', (int)$params['id'], array('ctg_id' => $params['anc']));
                $this->result['changed']=true;
                return;
                }
            }

        $this->result['changed']=false;
        }

    function save_news($params)
        {
        global $TDB;

        if ($params['news_data']['ctg_id'])
            {
            $params['news_data']['date']=XDATE::convertFromDatePicker($params['news_data']['news_date']);

            if ($_SESSION['user']['id'])
                {
                $params['news_data']['author_type']='users';
                $params['news_data']['author_id']  =$_SESSION['user']['id'];
                }

            if ($params['news_data']['id'])
                {
                $id=$params['news_data']['id'];
                unset ($params['news_data']['id']);
                unset ($params['news_data']['news_date']);

                $TDB->UpdateIN('news', (int)$id, $params['news_data']);
                $this->result['saved']=true;
                }
            else
                { //при создании новости активны 
                $params['news_data']['active']=1;
                
                $params['news_data']['id']    ='null';

                if ($TDB->InsertIN('news', $params['news_data']))
                    {
                        $this->result['saved']=true;
                    }
                }
            }
        }

    function get_module_options()
        {
        global $_CONFIG;
        $this->result['options']['rows_per_page']=$_CONFIG['news']['admin_rows_per_page'];
        }

    function news_table($parameters)
        {
        global $_CONFIG;

        $TTS                      =Common::inc_module_factory('TTableSource');
        $options['startRow']      =$parameters['startRow'];
        $options['table']         ='news';
        $options['where']         ='ctg_id=' . $parameters['id'] . ' order by sortdate DESC';

        $options['page_num_where']='ctg_id=' . $parameters['anc_id'];
        $options['gridFormat']    =1;

        $options['columns']=array
            (
            'id',
            'DATE_FORMAT(date,"%d-%m-%Y %H:%i:%s") as date',
            'header',
            'date as sortdate',
            'active'
            );

        $options['sequence']=array
            (
            'id',
            'date',
            'header',
            'active'
            );

        $this->result['data_set']=null;

        $TTS->setOptions($options);

        $this->result['data_set'] =$TTS->CreateView();
        $this->result['pages_num']=$TTS->pages_num;
        }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree',
            '_module_name'
            ));
        }

    function init_newsgroup($basic)
        {
        $data['LastModified']=time();
        $id                  =$this->_tree->InitTreeOBJ(1, $basic, '_NEWSGROUP', $data, true);
        return $id;
        }

    function reinit_newsgroup($id, $basic, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_tree->ReInitTreeOBJ($id, $basic, $data, $uniq_param);
        return $id;
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
                    case 'show_news_by_author':
                    $this->result['action_properties']=true;
                    Common::call_common_instance('pages');
                    $pages                                                     =&pages_module_common::getInstance();

                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_news_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $files                                                     =Common::get_module_template_list(
                                                                                    $this->_module_name, array('.show_news_interval.html','.'.                                                                                                              $parameters['Action']. '.html'));

                    $this->result['action_properties_form']['Template']
                                                   =XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);

                                                   
                                                   
                    Common::call_common_instance('users');
                    $users                                                     =&users_module_common::getInstance();
                    
                    $this->result['action_properties_form']['author_id']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $users->load_users_list(true),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);              
                                   
                                                   
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;
                    
                case 'show_news_interval':
                    $this->result['action_properties']=true;

                    Common::call_common_instance('pages');
                    $pages                                                     =&pages_module_common::getInstance();

                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_news_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $files                                                     =Common::get_module_template_list(
                                                                                    $this->_module_name, array
                                                                                                             ('.' . $parameters['Action']
                                                                                                             . '.html'));
                    $this->get_categories('action_properties_form', null, true, 'Category');
                    $this->result['action_properties_form']['Template']
                                                   =XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);

                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;

                case 'show_news_archive':
                    $this->result['action_properties']=true;

                    Common::call_common_instance('pages');
                    $pages                                                     =&pages_module_common::getInstance();

                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_news_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $files                                                     =Common::get_module_template_list(
                                                                                    $this->_module_name, array
                        (
                        '.show_news_interval.html',
                        '.' . $parameters['Action'] . '.html'
                        ));

                    $this->get_categories('action_properties_form', null, true, 'Category');
                    $this->result['action_properties_form']['Template']
                                                   =XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);

                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;

                case 'show_news_categories':
                    $this->result['action_properties']=true;

                    Common::call_common_instance('pages');
                    $pages                                                     =&pages_module_common::getInstance();

                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt(
                                                                                    XARRAY::arr_to_lev(
                                                                                        $pages->get_page_module_servers(
                                                                                            'show_news_server'),
                                                                                        'id',
                                                                                        'params',
                                                                                        'Name'),
                                                                                    false,
                                                                                    true);

                    $files                                                     =Common::get_module_template_list(
                                                                                    $this->_module_name, array
                                                                                                             ('.' . $parameters['Action']
                                                                                                             . '.html'));
                    $this->result['action_properties_form']['Template']        =XHTML::arr_select_opt(
                                                                                    XARRAY::combine($files, $files),
                                                                                    $se,
                                                                                    true);
                    $this->result['action_properties_form']['TemplateInterval']=
                        $this->result['action_properties_form']['Template'];
                    $this->result['action_properties_form']['TemplateSingle']  =
                        $this->result['action_properties_form']['Template'];
                    $this->lct['action_properties']                            =$TMS->parseSection(
                                                                                    $parameters['Action']);
                    break;

                case 'show_news_server':
                    $this->result['action_properties']                       =true;

                    $files                                                   =Common::get_module_template_list(
                                                                                  $this->_module_name, array
                        (
                        '.show_news_interval.html',
                        '.' . $parameters['Action'] . '.html'
                        ));

                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt(
                                                                                  XARRAY::askeyval(
                                                                                      $this->_common_obj->get_non_server_actions(),
                                                                                      'front_name'),
                                                                                  null,
                                                                                  true);

                    $this->result['action_properties_form']['MTemplate']     =XHTML::arr_select_opt(
                                                                                  XARRAY::combine($files, $files), $se,
                                                                                  true);
                    $this->lct['action_properties']                          =$TMS->parseSection($parameters['Action']);
                }
            }
        }
    }
?>