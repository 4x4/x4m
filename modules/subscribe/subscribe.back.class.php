<?php

class subscribe_module_back
{
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;
    var $mtype = array(
        'rar'  => 'application/x-tar',
        'zip'  => 'application/x-zip-compressed, application/zip',
        'gz'   => 'application/x-gzip',
        'tar'  => 'application/x-tar',
        'avi'  => 'video/x-msvideo',
        'mpe'  => 'video/mpeg',
        'mpg'  => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mov'  => 'video/quicktime',
        'qt'   => 'video/quicktime',
        'asf'  => 'video/x-ms-asf',
        'wmv'  => 'video/x-ms-wmv',
        'aif'  => 'audio/aiff',
        'au'   => 'audio/basic',
        'snd'  => 'audio/basic',
        'midi' => 'audio/mid',
        'mp3'  => 'audio/mpeg',
        'wav'  => 'audio/x-wav',
        'wma'  => 'audio/x-ms-wma',
        'bmp'  => 'image/bmp',
        'gif'  => 'image/gif',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'pdf'  => 'application/pdf',
        'html' => 'text/html',
        'xls'  => 'application/x-msexcel',
        'doc'  => 'application/msword',
        'exe'  => 'application/octet-stream',
        'txt'  => 'text/plain'
    );

    function subscribe_module_back()
    {
        $this->_module_name = 'subscribe';
    }


    function common_call($front_call = null)
    {
            $this->_common_obj =& subscribe_module_common::getInstance();
            $this->_tree =& $this->_common_obj->obj_tree;
    }

    function execute($action, $parameters = null)
    {
        $this->common_call();
        return $this->_common_obj->execute(&$this, $action, $parameters);
    }
        
    function executex($action,$acontext)
    {
        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct=$this->lct;   
        $acontext->result=$this->result;
    }
    
    function changeAncestor($parameters) 
    {
        //включена проверка на дублирующий basic
        $this->result['dragOK'] = $this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'], $parameters['relative']);
    }
    
    function _copy($params)
    {
        global $TDB;

        if(isset($params['anc']) && isset($params['node']))
        {
            if(!is_array($params['node'])) $params['node'] = array($params['node']);
            
            foreach($params['node'] as $node)
            {
                if($node != $params['anc']) $nodes = $this->_tree->GetNodesByIdArray(array($params['anc'], $node));

                if($this->_tree->CheckAncestorType($nodes[$params['anc']]['obj_type'], $nodes[$node]['obj_type']))
                {
                    $id = $this->_tree->CopyNodes($params['anc'], $node, array('_SUBSCRIBEGROUP'));
                    
                    // рассылки копируемой категории
                    $subs = $TDB->get_results('SELECT * FROM subscribe WHERE cat_id = ' . $node);
                    
                    foreach($subs as $sub)
                    {
                        $TDB->query('INSERT INTO subscribe (cat_id, date, msg, news, files, status, theme) VALUES(\'' . $id . '\',\''. $sub['date'] . '\',\''. $sub['msg'] . '\',\''. $sub['news'] . '\',\''. $sub['files'] . '\',\''. $sub['status'] . '\',\''. $sub['theme'] .  '\')');
                    }
                    
                    $users = $TDB->get_results('SELECT user_id FROM subscribers_params WHERE subscribe_id = ' . $node);
                    
                    foreach($users as $user)
                    {
                        $TDB->query('INSERT INTO subscribers_params (subscribe_id, user_id) VALUES (' . $id . ', ' . $user['user_id'] . ')');
                    }
                }

                $this->result['nodecopy'] = true;
            }
        }
    }

    function load_templates()
    {
        $files = Common::get_module_template_list($this->_module_name); 
        $this->result['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), $se, true);
    }
        
    function load_news_cats()
    {
        global $TDB;
        
        $cats = $TDB->get_results("SELECT id, basic FROM _tree_news_container_struct WHERE obj_type = '_NEWSGROUP' ORDER BY basic");
        foreach ($cats as $c)
        {
            $arr[] = array('id' => $c['id'], 'basic' => $c['basic']);    
        }
        
        $this->result['cats'] = $arr;    
    }
    
    function load_news_pages()
    {
        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();
        $this->result['news_page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_news_server'),'id','params','Name'), false,true);
    }

    function save_category($params)
    {
        $params['data']['news_cats'] = $params['news'];    

        if($id = $this->init_subscrgroup($params['data']['basic'], $params['data']))
        {
            $this->result['is_saved'] = true;
        }

        $this->result['is_saved'] = true;
        
        return $this->result;
    }

    function save_edited_category($parameters)
    {
        $parameters['data']['news_cats'] = $parameters['news'];
        if($this->reinit_subscrgroup($parameters['id'], $parameters['data']['basic'], $parameters['data']))
        {
            $this->result['is_saved'] = true;
        }
    }
    
    function delete_obj($params)
    {
        global $TDB;

        if(!is_array($params['id']))
        {
            $params['id'] = array($params['id']);
        }
        
        foreach($params['id'] as $id)
        {
            if($this->_tree->DelNode($id))
            {
                $this->result['deleted'][] = $id;
                $TDB->get_results('DELETE FROM subscribe WHERE cat_id = ' . $id);
                $TDB->get_results('DELETE FROM subscribers_params WHERE subscribe_id = ' . $id);
            }
        }
    }
     
     
    function delete_user($params)
    {
        global $TDB;
        
            if(!is_array($params['id'])){$params['id'] = array($params['id']);}
            
        $id = implode($params['id'],"','");
        $where = 'in (\''. $id . '\')';
    
        $TDB->get_results('DELETE FROM subscribers_params WHERE user_id ' . $where);
        
        if($from_params = $TDB->result)
        {
            $TDB->get_results('DELETE FROM subscribers_list WHERE id ' . $where);
            $from_list = $TDB->result;
            $this->result['deleted'] = $from_list;
        }
        else 
        {
            $this->result['deleted'] = $from_params;
        }
    }
     
        
    function delete_subscribe($params)
    {
        global $TDB;

            if(!is_array($params['id'])){$params['id'] = array($params['id']);}
            
        $id = implode($params['id'],"','");
        $where = 'in (\''. $id . '\')';
             
        $TDB->get_results('DELETE FROM subscribe WHERE id ' . $where);
        $this->result['deleted'] = $TDB->result;
    }

    function load_category($params)
    {
        $cat = $this->_tree->GetNodeInfo($params['id']);
        $this->result['cat'] = $cat['params'];
    }
        
    function load_categories($params)
    {            
        $this->result['forms_data']['category'] = XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $params['group_id'],true);
    }
      
    function load_category_data($params)
    {
        global $TDB;

        $d = $this->_tree->GetNodeParam($params['category']);
        $this->result['params'] = $d;
        $news = $TDB->get_results("SELECT id, ctg_id, header, DATE_FORMAT( date, '%d.%m.%y' ) AS date FROM news WHERE ctg_id IN (" . $d['news_cats'] . ") ORDER BY date DESC LIMIT " . $d['news_number']);                            

        foreach($news as $k=>$v)
        {
            $nn[] = $v;
        }

        $this->result['news'] = $nn;
        $cats = $TDB->get_results("SELECT id, basic FROM _tree_news_container_struct WHERE obj_type=\"_NEWSGROUP\"  AND id IN ({$d[news_cats]}) ORDER BY basic");

        foreach ($cats as $c)
        {
            $arr[] = $c;    
        }
        $this->result['cats'] = $arr;
    }

    function load_subscribe_data($params)
    {
        global $TDB;
        $data = $TDB->get_results('select * from subscribe where id=' . $params['id']);
        $this->result['data'] = $data[1];
        $this->result['cat_data'] = $this->_tree->GetNodeParam($data[1]['cat_id']);
    }    
    
    function save_subscribe($params)
    {
        global $TDB;

        $date = strftime('%d-%m-%y');
        $TDB->get_results("INSERT INTO subscribe VALUES (NULL, '" . $params['main']['category'] . "', '" . $date . "', '" . $params['main']['message'] . "', '" . $params['news'] . "', '" . $params['files'] . "', '" . $params['main']['status'] . "', '" . $params['main']['theme'] . "')");
        $this->result['is_saved'] = $TDB->result;   
    }
                
    function update_subscribe($params)
    {
        global $TDB;
        $date = strftime('%d-%m-%y');
        $TDB->get_results("UPDATE `subscribe` SET `msg` = '" . $params['main']['message'] . "',`news` = '" . $params['news'] . "', `files` = '" . $params['files'] . "', `status`='" . $params['main']['status'] . "', theme = '" . $params['main']['theme'] .  "' WHERE `subscribe`.`id`=" . $params['main']['id']);
        $this->result['is_saved'] = $TDB->result;   
    }        
       

    function init_subscrgroup($basic,$data)
    {
        $data['LastModified'] = time();
        $id = $this->_tree->InitTreeOBJ(1, $basic, '_SUBSCRIBEGROUP', $data, true);    
        return $id;
    }

    function reinit_subscrgroup($id, $basic, $data)
    {
        $data['LastModified'] = time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id = $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
    }

    function get_tree_inheritance()
    {
        $this->result['tree_inheritance'] = $this->_tree->LOCK_OBJ_ANC;
    }   
 
    function load_xlist_data($parameters)
    {
        $TD = Common::inc_module_factory('TTreeSource');
        $options['startNode'] = $parameters['anc_id'];
        $options['shownodesWithObjType'] = array('_SUBSCRIBEGROUP');
        $options['columnsAsParameters'] = array('LastModified' => 'LastModified');
        $options['columnsAsStructs'] = array('name'  => 'basic', 'image' => 'obj_type');
        $options['transformResults']['image'] = array('_SUBSCRIBEGROUP' => 'group');
        $options['selectable'] = array('image' => array('_SUBSCRIBEGROUP'));
        
        $this->result['data_set'] = null;

        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result = array_merge_recursive($TD->result, $this->result);
    }

    function load_subscribe_list($parameters)
    {
        global $_CONFIG;

        $TTS = Common::inc_module_factory('TTableSource');
        $options['startRow'] = 0;
        $options['table'] = 'subscribe';
        $options['where'] = 'cat_id = ' . $parameters['id'] . ' ORDER BY status, date, id DESC';
        $options['rows_per_page'] = 50;
        $options['columns'] = array('id', 'date', 'theme','status');
        $options['sequence'] = array('id', 'date', 'theme','status');
        $options['gridFormat'] =1;
        
        $dispatch_condition  = array('incoming', 'waiting', 'sent');
        $replace = array('Ожидание отправки', 'Отложено', 'Отправлено');

        $TTS->setOptions($options);
        
        if($data_set = $TTS->CreateView())
        {
            foreach ($data_set['rows'] as $k=>$v)
            {
                $data_set['rows'][$k]['data'][3] = str_replace($dispatch_condition, $replace, $v['data'][3]);           
            }
        }
        
        $this->result['data_set'] = $data_set;

        
    }
    
    function load_subscribers_list($parameters)
    {
        global $_CONFIG, $TDB;

        if($parameters['anc_id'] = (int) $parameters['anc_id'])
        {
            $query = 'SELECT user_id FROM subscribers_params WHERE subscribe_id = ' . $parameters['anc_id'];
        }
        else
        {
            $query = 'SELECT user_id FROM subscribers_params';
        }
        
        if($users = $TDB->get_results($query))
        {
            foreach ($users as $u)
            {
                $in[] = $u['user_id'];
            }
            $in = implode(',',$in);
        }

        $TTS = Common::inc_module_factory('TTableSource');
        $options['startRow'] = 0;
        $options['table'] = 'subscribers_list';
        $options['where'] = "id IN (" . $in . ") ORDER BY id DESC";
        $options['rows_per_page'] = count($users);
        $options['columns'] = array('id', 'email', 'status');
        $options['gridFormat'] = 1;                                                                                 
        $options['sequence'] = array('id', 'email', 'status');

        $TTS->setOptions($options);
        $search  = array('inactive', 'unauth', 'active');
        $replace = array('Неактивен', 'Не авторизован', 'Активен');

        if($data_set = $TTS->CreateView())
        {
            foreach ($data_set['rows'] as $k=>$v)
            {
                $data_set['rows'][$k]['data'][2] = str_replace($search, $replace, $v['data'][2]);           
            }
        }
        
        $this->result['data_set'] = $data_set;
    }

    
    
    function get_user_data($params)
    {
        global $TDB; 

        $user = $TDB->get_results('SELECT id,email,status FROM subscribers_list WHERE id = ' . $params['id']);
        $this->result['user'] = $user[1];
        
        if($subscribes = $TDB->get_results('SELECT subscribe_id FROM subscribers_params WHERE user_id = ' . $params['id']))
        {
            foreach ($subscribes as $s)
            {
                $this->result['user']['s_'.$s['subscribe_id']] = true;    
            }
        }
    }
    
    function save_edited_user($params)
    {
        global $TDB; 
    
        $TDB->get_results('UPDATE subscribers_list SET status = "' . $params['data']['status'] . '" WHERE id = ' . $params['data']['id']);    
        $TDB->get_results('DELETE FROM subscribers_params WHERE user_id = ' . $params['data']['id']);
        
        if(sizeof($params['cats']) > 0)
        {                                                   
            $query = 'INSERT INTO subscribers_params (`id`,`subscribe_id`,`user_id`) VALUES ';
            
            foreach ($params['cats'] as $s)
            {
                $list[] = "(NULL, " . $s . ", " . $params['data']['id'] . ')';
            }
            
            $query .= implode(',',$list);
            $TDB->get_results($query);
        }
        $this->result['saved'] = $TDB->result;
    }
    
    function get_subscribeslist()
    {
        if($items = $this->_tree->getChilds(1))
         {
            foreach($items as $i)
            {
                $this->result['cats'][] = array('id' => $i['id'], 'basic' => $i['basic']);
            }
        }
    }
    
    function add_user($params)
    {
        global $TDB;    
        
        if(!$users = $TDB->get_results('SELECT `id` FROM subscribers_list WHERE email = "' . $params['email'] . '"'))
        {
            $TDB->get_results("INSERT INTO subscribers_list (`id`, `email`, `status`, `date`) VALUES (NULL, '{$params['email']}', 'active', " . time() . ")");
            
            if($TDB->result)
            {
                foreach($params['cats'] as $c)
                {
                    $list[] = "(null, " . $c . ", " . $TDB->insert_id . ')';    
                }            
                
                $query = 'INSERT INTO subscribers_params VALUES ' . implode(',', $list);
                $TDB->get_results($query);
                
                $this->result['saved'] = $TDB->result;
            }
        }
        else
        {
            // Пользователь уже существует
            $this->result['saved'] = false;
        }
        
        return $this->result;
    }
    
    function find_user($params)
    {
        global $TDB;
        $user = $TDB->get_results('SELECT id FROM subscribers_list WHERE email = "' . $params['email'] . '"');
        $this->result['user'] = $user[1];
    }
    
    function start_subscribe($params)
    {
        global $TDB, $TMS, $_CONFIG; 

        if($params['first']) unset($_SESSION['emails'], $_SESSION['slist'], $_SESSION['sended'], $_SESSION['total'], $_SESSION['subcur']);

        if(!isset($_SESSION['slist']) && !isset($_SESSION['emails']))
        {
         
            if($subs = $TDB->get_results("SELECT * FROM subscribe WHERE status = 'incoming' ORDER BY cat_id, id"))
            {
                $i = 0;
                
                $ucount = 0;
                $scount = count($subs);
                
                // сохраняем список рассылки сессию
                foreach($subs as $sub)
                {
                    $_SESSION['slist'][$i] = $sub;
                    
                    $users = $TDB->get_results('
                            SELECT subscribers_params.user_id AS user_id, subscribers_list.email AS email
                            FROM subscribers_params
                            LEFT JOIN subscribers_list ON subscribers_params.user_id = subscribers_list.id
                            WHERE subscribers_params.subscribe_id = ' . $sub['cat_id'] . ' AND subscribers_list.status = \'active\'
                    ');
            
                    $ucount += count($users);
                    
                    $_SESSION['emails'][$i] = array_values($users);
                    
                    $i++;
                }
                
                $_SESSION['sended'] = 0;
                $_SESSION['total'] = $ucount;
            }
            else  
            {     
               // рассылок, ожидающих отправки нет  
               $this->result['ready'] = false;
               $this->result['error'] = true;                                      
               return $this->result;
            }
            // возвращаем для повторного входа
            $this->result['ready'] = true;
            $this->result['error'] = false;
            $this->result['scount'] = $scount; 
            $this->result['ucount'] = $ucount;
            return $this->result;
        }
        
        
        
        if(isset($_SESSION['emails']) && is_array($_SESSION['emails']) && isset($_SESSION['slist']) && is_array($_SESSION['slist']))
        {
            $list = $_SESSION['slist']; $arr = $_SESSION['emails']; $last_sub = end(array_keys($list));  
            reset($list); reset($arr);
            
            $sended = array();
            
            while(list($key, $item) = each($list)) 
            {
                if(isset($arr[$key]))
                {
                    $cat_params = $this->_tree->getNodeParam($item['cat_id']);

                    $fields = $this->parseMessageFields($item, $cat_params);
                        
                    $theme = $cat_params['theme'];
                    
                    if(!empty($item['theme'])) $theme = $item['theme'];
                        
                        
                        
                    $theme=XCODE::win2utf($theme);
                        

                    $m = Common::inc_module_factory('Mail');
                    $m->From($cat_params['from']);
                    $m->Subject($theme);
                    $m->Content_type($fields['content-type']);
                    $m->Body($fields['message'], $fields['encoding']);

                    if(!empty($item['files']))
                    {
                        if(is_string($item['files']))
                            $item['files'] = split(',', $item['files']);
                        
                        if(is_array($item['files']))
                        {
                            foreach($item['files'] as $f)
                            {
                                $type = pathinfo($f, PATHINFO_EXTENSION);
                      $f = DOCUMENT_ROOT . $f;
                                if(isset($this->mtype[$type]))
                                    $m->Attach($f, $this->mtype[$type]);                                
                                else
                                    $m->Attach($f);
                            }    
                        }
                    }
                    
                    $start = Common::getmicrotime();
                    $last_key = end(array_keys($arr[$key])); 

                    foreach($arr[$key] as $k => $i)
                    {
                        $m->sendto=array();                        
                        
                        $m->To($i['email']);
                        
                        

                        if($m->Send())
                        {
                            $sended = array_shift($_SESSION['emails'][$key]);
                            $_SESSION['sended']++;                          
                            
                            if($last_key == $k) 
                            {
                                // если последний адрес
                                $this->markSent($item['cat_id']);
                                
//                                array_shift($_SESSION['slist'][$key]);    
                                unset($_SESSION['slist'][$key]);    
                            }
                            
                            if((Common::getmicrotime() - $start) > $_CONFIG['subscribe']['update_interval'])
                            {
                                $this->result['next'] = true; 
                                $this->result['sub'] = $item; // инфо о рассылке
                                $this->result['sended'] = $sended; 
                                
                            }
                        }
                    }
                    
                    if($last_sub == $key)
                    {
                        $this->result['next'] = false; 
                        $this->result['complete'] = true; 
                        $this->result['sub'] = $item;
                        $this->result['sended'] = $sended; 
                    }
                }
                else
                {
                    // подписчики отсутствуют у данной рассылки
                    unset($_SESSION['slist'][$key]);
                    $this->result['next'] = true; 
                    $this->result['sended'] = NULL; 

                }
            }
        }
    }
        
    function clear_session()
    {
        unset($_SESSION['emails'], $_SESSION['slist'], $_SESSION['sended'], $_SESSION['total'], $_SESSION['subcur']);
    }

    function parseMessageFields($sub_params, $cat_params)
    {
        global $TDB, $TMS,$_COMMON_SITE_CONF;


        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $cat_params['Template']));
        
        $msg_type = 'text';
        
        if($cat_params['html'])
        {
            $fields['content-type'] = 'text/html';
            $msg_type = 'html';   
        }
        else 
        {
            $fields['content-type'] = 'text/plain';     
        }
        
        $fields['encoding'] = $_COMMON_SITE_CONF['site_encoding'];

        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();       
        
        $news_page = $pages->create_page_path($cat_params['news_page']);
        
        if($sub_params['news'])
        {
            $query = 'SELECT * FROM news WHERE id IN(' . $sub_params['news'] . ')';
            $news = $TDB->get_results($query);
            
            foreach($news as $n)
            {
                $n['link'] = $news_page . '/~shownews/' . $n['id'];
                $TMS->AddMassReplace('news_item', $n);
                $TMS->parseSection('news_item', true);
            }
            
            $TMS->parseSection('news');
        }


        $TMS->AddMassReplace($msg_type . '_message', array_merge($cat_params, $sub_params));
        $fields['message'] = $TMS->parseSection($msg_type . '_message');
        
        $fields['files'] = array();
        
        if($files = explode(',',$sub_params['files']))
        {
            if(is_array($files))
            {
                foreach($files as $f)
                {   
                    $info = pathinfo($f, PATHINFO_EXTENSION);
                    
                    $fields['files'][] = DOCUMENT_ROOT . $f;
                }    
            };
        }
        
        return $fields;
    }
    
    function markSent($cat_id)
    {
        global $TDB;
        $query = "UPDATE subscribe SET status = 'sent' WHERE cat_id = " . $cat_id;
        $TDB->get_results($query);
        return $TDB->result;
    }
  
    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'), $parameters['selected'], true);
    }

    function get_action_properties($parameters)
    {
        global $TMS,$Adm;
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name' => 'ainterface')), true), true);
                
        if(array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {
            switch ($parameters['Action'])
            {  
                case 'show_subscribe_form':
                    $this->result['xlist'] = true;
                    $this->result['action_properties'] = true;
                    
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                    
                    $this->result['action_properties_form']['news_page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_news_server'), 'id', 'params', 'Name'), false, true);
                    $this->result['action_properties_form']['page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('subscriber_page'), 'id', 'params', 'Name'), false, true);
                    
                    
             
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    break;
       
             case 'subscriber_page':
                    $albums = $this->_tree->GetChilds();
                    $this->result['action_properties_form']['subscribeStart'] = XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected, true);
                                   
                    $this->result['action_properties'] = true;
                    $this->result['xlist'] = false;
                    
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['aTemplate'] = XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);         
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt(
                    XARRAY::askeyval($this->_common_obj->get_non_server_actions(),'front_name'),null,true);
                    break;             
             }
        }
    }
    
    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj', '_tree', '_module_name'));
    }
}

?>