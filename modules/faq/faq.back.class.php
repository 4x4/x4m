<?php
class faq_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function faq_module_back()
        {
        $this->_module_name='faq';
        }


    /*внимание здесь исполняются только массив action результаты в свойстве класса $result*/

    function common_call($front_call = null)
        {
            $this->_module_name='faq';
            $this->_common_obj=&faq_module_common::getInstance();
            $this->_tree      =&$this->_common_obj->obj_tree;
        }


    function executex($action,$acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }
       
    function changeAncestor($parameters) {
    $this->result['dragOK']=$this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],
                                                              $parameters['relative']); }  
    
        
        
    function get_categories($flows, $category_selected, $ext = true,$sec_flow='ctg_id')
        {
       
        $this->result[$flows][$sec_flow]
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'Name'), $category_selected,
                                   $ext);
        }

    function add_forms($params) { $this->get_categories('add_forms', $params['parent']); }

    function save_category($params){
        $parent=$params["data"]["ParentCategoryId"];
        unset($params["data"]["ParentCategoryId"]);
        unset($params["data"]["ParentCategory"]);
        if ($id=$this->init_formsgroup($parent, $params['data']['basic'],$params['data'])){
            $this->result['is_saved']=true;
            $this->result["id"]=$id;
        }
    }

    function save_edited_folder($parameters){ 
        if ($this->reinit_folder($parameters['id'], $parameters['data']['basic'], $parameters['data'])) $this->result['is_saved']=true;  
    }
    
    function save_folder($parameters){ 
        if ($id = $this->init_folder($parameters['data']['basic'], $parameters['data'])) {
            $this->result['is_saved']=true; 
            $this->result['id']=$id;
        }
    }
    
    function load_folder($parameters){
        $folder=$this->_tree->getNodeInfo($parameters["id"]);
        $this->result["folder_data"]["params"]=$folder["params"];
        $this->result["folder_data"]["params"]["basic"]=$folder["basic"];
    }
    
    function load_root($parameters){
        $root=$this->_tree->getNodeInfo(1);
        $this->result["root_data"]["params"]=$root["params"];
    }
    
    function save_edited_root($parameters){ if ($this->reinit_root( $parameters['data'])) $this->result['is_saved']=true; }
    
    function save_edited_category($parameters){
        if ($this->reinit_formsgroup($parameters['id'], $parameters['data']['basic'], $parameters['data'])){
            $this->result['is_saved']=true;
        }
    }
              
    
    function switch_question($params){
            global $TDB; 
            if($TDB->UpdateIN('faq',(int)$params['id'],array('Active'=>(int)$params['state'])))
            {
                  x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
            }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }
            $query = "SELECT `cat_id` FROM `faq` WHERE `id`= ".$params["id"]." ;";
            $cat=$TDB->get_results($query);
            
            $this->reinit_formsgroup($cat[1]["cat_id"]);
    }
              
   function delete_question($params){
       global $TDB;
        
        if (is_array($params['id']))
            {
                $id=implode($params['id'],"','");
                $w='id in (\''. $id . '\')';
                $query = "SELECT `cat_id` FROM `faq` WHERE `id` in ('".implode($params["id"], "','")."') GROUP BY `cat_id`;";
                $cats=$TDB->get_results($query);
            }
        else
            {
                $w='id="' . $params['id'] . '"';
                $query = "SELECT `cat_id` FROM `faq` WHERE `id`= ".$params["id"]." ;";
                $cats=$TDB->get_results($query);
                
            }
        $q = 'DELETE FROM `faq` WHERE '.$w;

        $TDB->get_results($q);
        $this->result['isDel']=$TDB->result;
        foreach ($cats as $cat) {
            $node = $this->_tree->getNodeInfo($cat["cat_id"]);
            $query="SELECT count(`id`) as `xcount` FROM `faq` WHERE `cat_id`=".$node["id"]."  AND `active`=1;";
            $r=$TDB->get_results($query);
            $node['params']['count']=$r[1]["xcount"];            
            $this->_tree->ReInitTreeOBJ($node["id"],'%SAME%',$node["params"]);
        }        
        
   }

    function show_edit_form($params)
        {
        $this->result['forms_data']=$this->_common_obj->select_forms($params['id']);
        $this->get_categories('forms_data', $this->result['news_data']['ctg_id'], false);
        }

    function load_category($params)
        {
            $this->result['category_data']['category'] = $this->_tree->GetNodeInfo($params['id']);
            $files=Common::get_module_template_list($this->_module_name,array('.answer_letter.html')); 
            $this->result['answer_templates']['answer_template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
        }
        
    function new_category_info($params)
        {
            $files=Common::get_module_template_list($this->_module_name,array('.answer_letter.html')); 
            $this->result['answer_template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
            $folders = $this->_tree->GetChildsParam(1,array('Name'),true);
            $sel_folders=array( array("text" => "", "value" => ""));
            foreach ($folders as $folder) {
                $sel_folders[] = array("text" => $folder['params']['Name'], "value" => $folder["id"]);
            }
            $this->result['ParentCategoryId']=$sel_folders;
        }
        
      
    function load_question_data($params){
        global $TDB;
        $d = $TDB->get_results('SELECT * FROM faq WHERE id='.$params['id']);
        $this->result['faq_data']=$d[1];
        if ($d[1]['active']==2){$this->result['faq_data']['send_answer']=true;}
        $t = $this->_tree->GetNodeParam($d[1]['cat_id']);
        $this->result["faq_data"]["category"]=$d[1]['cat_id'];
        $this->result["faq_data"]["categoryAlias"]=$t["Name"];
        $files=Common::get_module_template_list($this->_module_name,array('.answer_letter.html')); 
        $templates=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
        $cat = $this->_tree->GetNodeParam($d[1]["cat_id"]);
        if ($tmp = $cat["answer_template"]) {
            $keys = array_keys($templates);
            foreach ($keys as $key) {
                if ($templates[$key]["value"] == $tmp) {
                    $templates[$key]["selected"]=1;
                    break;
                }
            }
        } 
        $this->result['faq_data']['lTemplate']=$templates;
        $this->result['faq_data']['send_answer']=false;
    }              
        

    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj', '_tree'));
    }

        
    function get_module_options($params)
        {
            global $_CONFIG; 
            $this->result['options']['rows_per_page'] = $_CONFIG['faq']['admin_rows_per_page'];   
        }       

    function init_formsgroup($anc,$basic,$data)
        {
            $data['LastModified']=time();
            return $this->_tree->InitTreeOBJ($anc, $basic, '_FAQGROUP', $data, true);
        }

    function reinit_formsgroup($id, $basic=null, $data=null)
        {
            global $TDB;
            $node=$this->_tree->getNodeInfo($id);
            if($node["basic"] == $basic || !$basic) $basic='%SAME%';
            $query="SELECT count(`id`) as `xcount` FROM `faq` WHERE `cat_id`=".$node["id"]."  AND `active`=1;";
            $r=$TDB->get_results($query);
            $data['LastModified']=time();
            $data['count']=$r[1]["xcount"];
            $this->result["count"]=$data["count"];
            $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
            return $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        }
        
    function init_folder($basic,$data)
        {
            $data['LastModified']=time();
            return $this->_tree->InitTreeOBJ(1, $basic, '_FAQFOLDER', $data, true);
        }

    function reinit_folder($id, $basic, $data)
        {
            $node=$this->_tree->getNodeInfo($id);
            if($node["basic"] == $basic) $basic='%SAME%';
            $data['LastModified']=time();
            $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
            return $this->_tree->ReInitTreeOBJ($id, $basic, $data);
        }        


    function reinit_root($data)
        {
            $data['LastModified']=time();
            return $this->_tree->ReInitTreeOBJ(1, '%SAME%', $data);
        }        
        
    function delete_item($id){
        global $TDB;
        $q = 'delete from faq where cat_id='.$id;
        $TDB->get_results($q);
        if ($this->_tree->DelNode($id)&&$TDB->result){
            return true;
        }
        return false;
        
        }

    function delete_obj($data)
        {
            
        if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                    
                if ($this->delete_item($id))
                    {
                    $this->result['deleted'][]=$id;
                    }
                }
            }
        else
            {
            if ($this->delete_item($data['id']))
                {
                $this->result['deleted'][]=$data['id'];
                }
            }
        }
        

        
   function check_uniq($params)
        {
        if (is_array($q=$this->_tree->Search(false, false, array
            ('basic'    => $params['basic']))))
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
        
        
    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }   
 
     
    function load_xlist_data($parameters) {
        $TD =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];
        $options['shownodesWithObjType']=array('_ROOT', '_FAQGROUP', '_FAQFOLDER');
        $options['columnsAsParameters']=array('LastModified' => 'LastModified','name'=>'Name');
        $options['columnsAsStructs']=array(/*'name'  => 'basic',*/ 'image' => 'obj_type' );
        $options['transformResults']['image']=array('_FAQGROUP' => 'page', '_FAQFOLDER' => 'group', '_ROOT' => 'group');
        $options['selectable']=array('image' => array('_FAQGROUP'));
        $this->result['data_set']=null;
        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
    }
    
    function load_xlist_folder($parameters) {
        $TD =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];
        $options['shownodesWithObjType']=array('_ROOT', '_FAQGROUP', '_FAQFOLDER');
        $options['columnsAsParameters']=array('LastModified' => 'LastModified','name'=>'Name');
        $options['columnsAsStructs']=array(/*'name'  => 'basic',*/ 'image' => 'obj_type' );
        $options['transformResults']['image']=array('_FAQGROUP' => 'page', '_FAQFOLDER' => 'group', '_ROOT' => 'group');
        $options['selectable']=array('image' => array('_FAQFOLDER','_ROOT'));
        $this->result['data_set']=null;
        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
    }
    
  
 function load_actions($parameters)
        {
            
        $this->result['tune_actions']['Action']
            =XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),
                                   $parameters['selected'],
                                   true);
        }
        
    function load_questions_data($parameters)
        {
        global $_CONFIG;
        $TTS=Common::inc_module_factory('TTableSource');
        $options['startRow']     =$parameters['startRow'];
        $options['table']        ='faq';
        if($parameters['activity'] != 2)
        {
            $options['where']        ='cat_id=' . $parameters['id'] . ' and active in (0,1) order by id DESC';
        }else{
            
            $options['where']        ="active=2 order by id DESC";
            $options['filter']['question']=array('name'=>'cutwords','count'=>$_CONFIG['faq']['question_words_count']);
        }
        
        if(!$parameters['rows_per_page'])
        {
            $options['rows_per_page']=$parameters['rows_per_page'];
        }else{                   
        
             $options['rows_per_page']=$_CONFIG['faq']['admin_rows_per_page'];     
        }


        $options['filter']['question']=array('name'=>'cutwords','count'=>$_CONFIG['faq']['question_words_count']);      
    
        $options['gridFormat']=1;                                                                                 


    
        $options['columns']=array
                (
                'id',
                'date',
                'user',
                'question',
                'active'
                );
                
            $this->result['data_set']=null;
            $options['sequence']=array
                (
                    'id',
                    'date',
                    'user',
                    'question',
                    'active'
                );

            $TTS->setOptions($options);

            $this->result['data_set']=$TTS->CreateView();

            
        }

        
    function switch_state($params){
        global $TDB;
        $q = $TDB->get_results('select * from faq where id='.$params['id']);

        if ($q[1]['active']){
            $q = 'update faq set active=0 where id='.$params['id'];
        } 
        else $q = 'update faq set active=1 where id='.$params['id'];
        $TDB->get_results($q);
        $this->result['switched'] = $TDB->result;
    }

        

    function get_action_properties($parameters)
        {
        global $TMS,$Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
           $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                    
            switch ($parameters['Action'])
                {  
                case 'show_categories':
                    $this->result['xlist'] =true;
                    $this->result['action_properties'] =true;
                    Common::call_common_instance('pages');
                    $pages=&pages_module_common::getInstance();
                    $this->result['action_properties_form']['page']=XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_faq_server'),'id','params','Name'),false,true);                    
                    $root = $this->_tree->GetChildsParam(1);
                    $this->result['action_properties_form']['folder']=XHTML::arr_select_opt(XARRAY::askeyval($root, 'Name'),null, true);
                    
                    
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    $files=Common::get_module_template_list($this->_module_name,array('.show_categories.html')); 
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    break;
                    
                case 'show_folder_questions':
                    $this->result['xlist'] =true;
                    $this->result['action_properties'] =true;
                    Common::call_common_instance('pages');
                    $pages=&pages_module_common::getInstance();
                    $this->result['action_properties_form']['page']=XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_faq_server'),'id','params','Name'),false,true);                    
                    $root = $this->_tree->GetChildsParam(1);
                    $this->result['action_properties_form']['folder']=XHTML::arr_select_opt(XARRAY::askeyval($root, 'Name'),null, true);
                    
                    
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    $files=Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    break;                    
                    
                    
             case 'show_faq_server':

                    $this->result['action_properties'] =true;

                    $this->result['xlist'] =false;                    
                    
                    $files=Common::get_module_template_list($this->_module_name,array('.show_question.html')); 
                    $this->result['action_properties_form']['qTemplate']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    $files=Common::get_module_template_list($this->_module_name,array('.show_category.html')); 
                    $this->result['action_properties_form']['cTemplate']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);
                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt( XARRAY::askeyval($this->_common_obj->get_non_server_actions(), 'front_name'), null, true);
                    
                    $root = $this->_tree->GetChildsParam(1);
                    $this->result['action_properties_form']['root']=XHTML::arr_select_opt(XARRAY::askeyval($root, 'Name'),null, true);
                    
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;
                    
             case 'show_selected_category':

                    $this->result['action_properties'] =true;
                    Common::call_common_instance('pages');  
                    $pages=&pages_module_common::getInstance();                      
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt
                    (                    
                        XARRAY::arr_to_lev($pages->get_page_module_servers('show_faq_server'),'id','params','Name')
                    ,false,true);

                    $this->result['xlist'] =true;    
                    $files=Common::get_module_template_list($this->_module_name,array('.show_category.html')); 
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);         
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break; 
                    
             case 'show_faq_search':
                    
                    $this->result['action_properties'] =true;

                    Common::call_common_instance('pages');  
                    $pages=&pages_module_common::getInstance();                      
                    $this->result['action_properties_form']['Destination_page']=XHTML::arr_select_opt( XARRAY::arr_to_lev($pages->get_page_module_servers('show_faq_search_server'),'id','params','Name'),false,true);
                    $this->result['xlist'] =false;                    
                    $files=Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true);         
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break; 

             case 'show_faq_search_server':
                    $this->result['action_properties'] =true;
                    $this->result['xlist'] =false; 
                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt( XARRAY::askeyval($this->_common_obj->get_non_server_actions(), 'front_name'), null, true);
                    Common::call_common_instance('pages');  
                    $pages=&pages_module_common::getInstance();                      
                    
                    $this->result['action_properties_form']['MDestination_page']=XHTML::arr_select_opt( XARRAY::arr_to_lev($pages->get_page_module_servers('show_faq_server'),'id','params','Name'),false,true);
                    
                    $files =Common::get_module_template_list( $this->_module_name, array ('.show_search_results.html'));
                    $this->result['action_properties_form']['MTemplate'] =XHTML::arr_select_opt( XARRAY::combine($files, $files), $se, true);
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;                    

                                
                }
            }
        }
        


    function save_question($params) {
        global $TDB;
        
        ($params["main"]["active"]) ? $active = "1" : $active = "0";
        $fields = array(
            "cat_id" => (int)$params["main"]["category"],
            "user" => $params["main"]["user"],
            "date" => $params["main"]["date"],
            "question" => $params["main"]["question"],
            "answer" => $params["main"]["answer"],
            "sanswer" => $params["main"]["sanswer"],
            "active" => (int)$active,
            "email" => $params["main"]["email"]
        );
        $HL=array_keys($fields);
        foreach ($HL as $key) {
            if ($fields[$key]==null) unset($fields[$key]);
            $fields[$key]="'".mysql_real_escape_string($fields[$key])."'";
            if ( $fields[$key]=="''" ) unset($fields[$key]);
        }
        
        $fields["date"] = 'NOW() ';
        $HL=array_keys($fields);
        $q = "INSERT INTO `faq` (`". implode('`, `',$HL)."`) VALUES(".implode(", " ,array_values($fields)).");";
        $TDB->get_results($q);
        if ($TDB->rows_affected) {
            $this->result['is_saved'] = $TDB->result;
            if ($params["main"]["active"]) $this->reinit_formsgroup($params["main"]["category"]);
        }
    }
    
        
    function save_edited_question($params)
    {
        global $_COMMON_SITE_CONF, $TMS;
        if($params)
        {
            
            global $TDB;
            $q = "UPDATE `faq` SET `cat_id` = '".(int) $params['category']."'".
                ",`question` = '" . mysql_real_escape_string($params['question'])."'".
                ",`date` = '". mysql_real_escape_string($params['date'])."'".
                ",`sanswer` = '" . mysql_real_escape_string($params['sanswer'])."'".
                ",`answer`  = '" . mysql_real_escape_string($params['answer'])."'".
                ",`user`    = '" . mysql_real_escape_string($params['user'])."'".
                ",`email`   = '" . mysql_real_escape_string($params['email'])."'".
                ",`active`  =  '".(int)$params['active']."'".
            ' WHERE id = '.(int)$params['id'];
            $TDB->get_results($q);
            
            
            $this->result['is_saved'] = $TDB->result;
        
            $cat = $this->_tree->getNodeParam($params['category']);
            $answer = $params['answer'];
            if ($params["lTemplate"] && $params["send_answer"]) {
                $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params["lTemplate"]));
                $TMS->AddMassReplace('letter',$params);
                $TMS->AddMassReplace('letter', array('server' => $_SERVER['HTTP_HOST']));
                $TMS->AddReplace('letter', 'category_name', $cat["Name"]);
                $TMS->AddReplace('letter_subject', 'category_name', $cat["Name"]);
            
                $m =Common::inc_module_factory('Mail');
                $m->To($params['email']);
                if ($cat["email"]) $m->From($cat["email"]);
                $m->Subject($TMS->parseSection('letter_subject'));
                $m->Content_type('text/html');
                $m->Body( $TMS->parseSection('letter'), $_COMMON_SITE_CONF['site_encoding'] );                
                $this->result['emailed'] = $m->Send();
            }
            else {
                $this->result['emailed'] = false;
            }
            
            $this->reinit_formsgroup($params['category']);
        }
    }
        
    }


    
?>
