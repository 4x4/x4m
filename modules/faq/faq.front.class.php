<?php
class faq_module_front extends faq_module_tpl
    {
    var $_module_name;
    var $_common_obj;
    var $_tree;
    var $question_alias=null;

    function faq_module_front()
        {
        
        $this->_module_name='faq';
        $this->_common_obj =&faq_module_common::getInstance(true);
        $this->_tree =&$this->_common_obj->obj_tree;
        $this->_tree->enable_cache(true);
        parent::__construct();
        $this->context=null;        
        }
                                                                                       
    function request_action_set($action) { $this->_common_obj->request_action_set($action); }

    function execute($action_data)
        {
        global $TMS;
        
        if (is_array($action_data))
            {
            if ($action=$this->_common_obj->is_action($action_data['Action']))
                {
                    $TMS->startLogging($action_data['Action']);
                    $q=&$this->$action($action_data);
                    
                    $TMS->clearLogged($action_data['Action']);
                    return $q;

                }
            }
        }
    

    function add_question($params){
        global $TDB,$TMS,$TPA,$_COMMON_SITE_CONF; 
        

        if($_SESSION['captcha'][intval($_POST["category"])] == $_POST['captcha']) { 
          unset($_SESSION['captcha'][(int)$_POST["category"]]);
        }
        else  return $TMS->parseSection('error');    
        
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['cTemplate']));
        $cat_id = (int)$_POST['category'];
        $cat = $this->_tree->getNodeInfo($cat_id);
        
        ($cat["params"]["moderation"]) ? $active=2 :$active=1;
        $fields = array(
            "active"=>$active,
            "date" => "'".date('Y-m-d  G:i:s')."'",
            "user" => "'".mysql_real_escape_string($_POST["user"])."'",
            "email" => "'".mysql_real_escape_string($_POST["email"])."'",
            "question" => "'".mysql_real_escape_string($_POST["question"])."'",
            "sanswer" => "''",
            "answer" => "''"

        );
        

        $HL=array_keys($fields);
  
        
        $query = 'INSERT INTO `faq` (cat_id,'. implode(',',$HL).') VALUES('.$cat_id.', '.implode(',',array_values($fields)).');';
           
        if ($TDB->query($query)) {                
            if ($cat['params']['email']!=''){                    
                
                $TMS->AddMassReplace('letter',$fields);
                
                $TMS->AddReplace('letter', 'category_name', $cat['params']['Name']);
                $TMS->AddReplace('letter_subject', 'category_name', $cat['params']['Name']);                

                $m =Common::inc_module_factory('Mail');
                $m->To($cat['params']['email']);
                $m->From($_COMMON_SITE_CONF['admin_email']);
                $m->Subject($TMS->parseSection('letter_subject'));
                $m->Content_type('text/html');
                $m->Body($TMS->parseSection('letter'), $_COMMON_SITE_CONF['site_encoding']);                
                $m->Send();                
            }
            if (!$cat["params"]["moderation"]) {
                $query = "SELECT count(*) as xcount FROM `faq` WHERE `cat_id` = ".$cat["id"]." AND `active` = 1;";
                $r = $TDB->get_results($query);
                $cat["params"]["count"]= $r[1]["xcount"];
                $this->_tree->ReInitTreeOBJ($cat["id"],'%SAME%',$cat["params"]);
            }                    
               
            $TMS->AddReplace('success','backlink',$TPA->page_link);
            return $TMS->parseSection('success');    
        } 
        else 
            return $TMS->parseSection('error');
    }

        
    function show_categories($params) {
            
        global $TMS, $_WEBPATH, $REQUEST_ASSOC, $REQUEST_VARS, $TDB;
        Common::call_common_instance('pages');
        $pages      =&pages_module_common::getInstance();
        $Destination=$pages->create_page_path($params['page']);
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['Template']));
        $items=$this->_tree->GetChildsParam($params["folder"],Array('id','basic','description','hidden','Name','count'));
        
        $selected_basic = '';
        if ($REQUEST_VARS[0] == 'show_category') {
            $selected_basic = $REQUEST_VARS[1]; 
        } elseif ($REQUEST_ASSOC['qid']) {
            $q = 'select `cat_id` from `faq` where id='.(int)$REQUEST_ASSOC['qid']; 
            $cats = $TDB->get_results($q);
            $cat_id=$cats[1]['cat_id'];
            if ($cat_id) {
                $ancestor=$this->_tree->getNodeInfo($cat_id);
                $selected_basic=$ancestor['basic'];
            }
        }
        $k=0;
        foreach ($items as $id => $cat){
            if ($cat['hidden']==''){
                $k+=(int)$cat['count'];
                $cat['id']=$id;
                $cat['Link']=$Destination . '/~show_category/' . $cat["basic"];
                if ($selected_basic && $selected_basic == $cat['basic']) $cat['selected']=1;
                else $cat['selected']='';
                $TMS->KillMFields('category_item');
                $TMS->AddMassReplace('category_item', $cat);
                $TMS->parseSection('category_item', true);
            }
        }
        $folder=$this->_tree->getNodeInfo($params["folder"]);
        $folder['params']['basic']=$folder['basic'];
        $folder['params']['total']=$k;
        $folder['params']['Destination_page']= $Destination;
        $TMS->AddMassReplace('categories_list', $folder['params']);
        return $TMS->parseSection('categories_list');
    }
    
    function show_folder_questions($params){
        global $TMS,$TDB,$TPA,$REQUEST_ASSOC,$REQUEST_VARS;
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['Template']));
        Common::call_common_instance('pages');
        $pages=&pages_module_common::getInstance();
        if ($params['Destination_page']){
            $dst = $pages->create_page_path($params['Destination_page']);    
        } else {
            $dst = $TPA->page_link;
        }
        
        $start = $REQUEST_ASSOC['sl']? (int) $REQUEST_ASSOC['sl'] : 0;
        $categories=$this->_tree->GetChildsParam($params["folder"],Array('id','basic','description','hidden','Name','count'));
        $total=0;
        foreach ($categories as $cat) {
            $total+=(int)$cat['count'];
        }
        $cat_ids=array_keys($categories);
        if (!isset($params["num_per_page"]) || $params["num_per_page"] === '') $params["num_per_page"]=$params["OnPage"];
        $length = $params['num_per_page'];
        if($length)
            $limit = ' limit '.$start.' , '.$length;
        else
            $limit = '';
         $q = 'select * from faq where active=1 and cat_id in ('.implode(',',$cat_ids).') order by -`id` '.$limit; 
         $items = $TDB->get_results($q);
         $_SESSION['captcha_settings'][0]=4;
         if (count($items)){
            $params['action']= $faq_server_page = $TPA->page_link;        
            $k=1;
            foreach ($items as $item){
                $item['link'] = $dst.'/~show_question/qid/'.$item['id'];
                $item['category.Link']=$dst.'/~show_category/cid/'.$categories[(int)$item['cat_id']]['basic'];
                $item["timestamp"]=strtotime($item["date"]);
                $item['category.basic']=$categories[(int)$item['cat_id']]['basic'];
                $item['category.Name']=$categories[(int)$item['cat_id']]['Name'];
                $item['_num']=$k++;
                $item['_num_abs']=$k+$start-1;
                $TMS->addMassReplace('question',$item);
                $TMS->parseSection('question',true);
                
            }
            $startpage = $REQUEST_ASSOC['sl'] ? (int) $REQUEST_ASSOC['sl'] : 0;
            Common::parse_nav_pages($total, $params['num_per_page'], $startpage, $faq_server_page. '/~show_folder_questions');      
            
            $TMS->AddReplace('questions_list', 'faq_serv_link', $faq_server_page);
            $menu = $TMS->parseSection('questions_list');
            return $menu;
            
            
        }
        else{
            $TMS->AddMassReplace('form',$params);
            $TMS->AddMassReplace('form',array('action'=> $dst, 'basic' => $category['basic'], 'category' =>$category['id']));
            $TMS->AddReplace('empty','_form', $TMS->parseSection('form'));
            
            $TMS->AddMassReplace('empty', $category["params"]);
            $TMS->AddReplace('empty','Link', $dst.'/~show_category/'.$category["basic"]);
            return $TMS->parseSection('empty');
        }
        
    }
    
    
    function show_category($params){
        global $TMS,$TDB,$TPA,$REQUEST_ASSOC,$REQUEST_VARS;
        if ($params["cTemplate"])  $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['cTemplate'])); 
            else $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['Template']));
        
        Common::call_common_instance('pages');
        $pages=&pages_module_common::getInstance();
        $dst = $pages->create_page_path($params['Destination_page']);
        $start = $REQUEST_ASSOC['sl']? (int) $REQUEST_ASSOC['sl'] : 0;
        if( $REQUEST_ASSOC['cid'] || ($REQUEST_VARS[0] == 'show_category' && $REQUEST_VARS[1]) ) {
            ($REQUEST_ASSOC['cid']) ? $basic=$REQUEST_ASSOC['cid'] : $basic = $REQUEST_VARS[1];
            if (!$params["folder"] && $d=$pages->get_module_by_action($params['Destination_page'],'show_faq_server') ) {
                $params['folder']=$d['params']['root'];
            }
            $id = $this->_tree->FindbyBasic($params["folder"],$basic);
            if (is_array($id)) $id=$id[0];
        }
        elseif(isset($params['FaqStart']))
            $id = $params['FaqStart'];
        elseif(isset($params['category']))
            $id = $params['category'];

        if (!isset($params["num_per_page"]) || $params["num_per_page"] === '') $params["num_per_page"]=$params["OnPage"];
        $length = $params['num_per_page'];
        if($length)
            $limit = ' limit '.$start.' , '.$length;
        else
            $limit = '';
         $q = 'select * from faq where active=1 and cat_id='.$id.' order by -`id` '.$limit; 
         
        
        $category = $this->_tree->getNodeInfo($id);
        if ($REQUEST_VARS[0] == 'show_category') $this->get_bones($category);
        
       
        
        $params['category'] = $id;    
        $_SESSION['captcha_settings'][0]=4;
        if (!isset($_SESSION['captcha']["faq"])||$_POST['sec_code']==''){
            $TMS->AddMassReplace('form',$params);
            $TMS->AddMassReplace('form',array('action'=> $dst, 'basic' => $category['basic'], 'category' =>$category['id']));
            $TMS->AddReplace('questions_list','_form', $TMS->parseSection('form'));
        }
        elseif ($_SESSION['captcha']["faq"]!=$_POST['sec_code']){
            $TMS->parseSection('msg');
            $TMS->AddMassReplace('form',array_merge($params,$_POST));
            $TMS->AddMassReplace('form',array('action'=> $dst, 'basic' => $category['basic'], 'category' =>$category['id']));
            $TMS->AddReplace('questions_list','_form', $TMS->parseSection('form'));
        }
        elseif($_SESSION['captcha']["faq"]==$_POST['sec_code']){
            return $this->add_question($params);    
        }
        
        $a = $category['params'];
        $a['category'] = $category["params"]['Name'];        
        $a['id']=$category['id'];
        $a['basic']=$category['basic'];
        
        $items = $TDB->get_results($q);
        
        if (sizeof($items)!=0){
            
            $params['action']= $faq_server_page = $TPA->page_link;        
            
            $k=1;
            $total=(int)$category['params']['count'];
         
            foreach ($items as $item){
                if ($params['Destination_page']!=null) $item['link']=$dst;
                else $item['link'] = $TPA->page_link;
                $item['link'] .= '/~show_question/qid/'.$item['id'];
                $item["timestamp"]=strtotime($item["date"]);
                $item['_num']=$k++;
                $item['_num_abs']=$total-($k+$start)+2;
                $TMS->addMassReplace('question',array_merge($item,$a));
                $TMS->parseSection('question',true);
            }
        }
        else{
            $TMS->AddMassReplace('form',$params);
            $TMS->AddMassReplace('form',array('action'=> $dst, 'basic' => $category['basic'], 'category' =>$category['id']));
            $TMS->AddReplace('empty','_form', $TMS->parseSection('form'));
            
            $TMS->AddMassReplace('empty', $category["params"]);
            $TMS->AddReplace('empty','Link', $dst.'/~show_category/'.$category["basic"]);
            return $TMS->parseSection('empty');
        }
        
        
        $c=$category['params']['count'];
        
        $startpage = $REQUEST_ASSOC['sl'] ? (int) $REQUEST_ASSOC['sl'] : 0;
        Common::parse_nav_pages($c,$params['num_per_page'], $startpage, $faq_server_page. '/~show_category/cid/'.$category["basic"]);      
        
        $TMS->AddMassReplace('questions_list',$a);
        $TMS->AddReplace('questions_list', 'faq_serv_link', $faq_server_page);
        $menu = $TMS->parseSection('questions_list');
        return $menu;
    }
    
    
    function show_faq_server(&$params){
        global $REQUEST_VARS,$REQUEST_ASSOC;
        if (isset($_POST["category"])) return $this->add_question($params);
        if ($REQUEST_VARS[0] == 'show_question'&&$REQUEST_ASSOC['qid']>0) return  $this->show_question($params);            
        if ($REQUEST_VARS[0] == 'show_category' && $REQUEST_VARS[1]) return $this->show_category($params);
        $action=$params["Default_action"];
        if ($action) return $this->$action($params);

    }
    
    function show_selected_category($params){

        return $this->show_category($params);
    }
    
    function show_question($params){
        global $REQUEST_ASSOC,$TDB,$TMS,$TPA;
        $q = 'select * from faq where id='.$REQUEST_ASSOC['qid'];
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params['qTemplate']));
        $question = $TDB->get_results($q);
        $question = $question[1];
        $anc = $this->_tree->getNodeInfo($question['cat_id']);
        $question['category'] = $anc['params']['Name'];
        $question['ancestor'] = $anc['id'];
        $question['back']=$TPA->page_link.'/~show_category/cid/'.$anc['basic'];
        $TMS->AddMassReplace('question',$question);
        $tmp = $TMS->parseSection('question');
        
        if ($this->question_alias) {
            $question['Alias']=$this->question_alias;
            $this->question_alias=null;
        } else {
            $question['Alias']=Enhance::cut_words2(array($question["question"],' ',40,'','...',true));
        }
        $this->get_bones($anc,$question);
        
        return $tmp;
    }


    function show_faq_search($params){
        global $TDB,$TMS;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));        
        $pages=&pages_module_common::getInstance();
        
        if ($_POST["faqsearch"]) $value=$_POST["faqsearch"];
        elseif ($_SESSION["faq"]["last_search"])$value = $_SESSION["faq"]["last_search"];
        else $value='';
        
        $TMS->AddMassReplace('_search_form', array('action'=> $pages->create_page_path($params["Destination_page"]), 'value' => $value));
        return $TMS->parseSection('_search_form');
    }
    
     
     function show_faq_search_server($params) {
         global $TDB,$TMS,$TPA,$REQUEST_ASSOC;
         Common::call_common_instance('pages');  
         $pages=&pages_module_common::getInstance();                      
         $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $params["MTemplate"]));
         if (isset($REQUEST_ASSOC['sl'])) $request=$_SESSION["faq"]["last_search"];
         else $request = urldecode($_POST["faqsearch"]);
         
         if (!$request) return $TMS->parseSection('_no_request');
         $_SESSION["faq"]["last_search"]=$request;
         
         $words= explode(' ',$request);
         $OM=array();
         $sfields=array('question','answer','sanswer');
         $first=true;
         foreach ($words as $word) {
             $value=trim(mysql_real_escape_string($word));
             $cond = array();
             foreach ($sfields as $sfield) {
                 $cond[] = " `".$sfield."` LIKE '%".$value."%' ";
             }
             $query="SELECT `id` FROM `faq` WHERE (".implode('OR',$cond).") AND `active`=1;";
             $r=$TDB->get_results($query,'ARRAY_N');
             if (!count($r)) {
                 $TMS->AddReplace('_no_results','request', $request);
                 return $TMS->parseSection('_no_results');
             }
             
             $Mas=array();
             foreach($r as $v) { $Mas[]=$v[0]; }
             
             
             if ($first) $OM=$Mas;
             else {$OM = array_intersect($OM,$Mas);}
             
             if (!count($OM)) {
                 $TMS->AddReplace('_no_results','request', $request);
                 return $TMS->parseSection('_no_results');
             }

             $first=false;
         }
         
         
         $sl = $REQUEST_ASSOC['sl']? (int) $REQUEST_ASSOC['sl'] : 0;         
         $dst = $pages->create_page_path($params["MDestination_page"]);
         $total=count($OM);
         if ($params["OnPage"]<$total && $params["OnPage"]) {
             $OM=array_slice($OM, $sl, $params["OnPage"]);
             Common::parse_nav_pages($total, $params["OnPage"], $sl, $TPA->page_link . '/~faqsearch');
         }
         
         $query="SELECT * FROM `faq` WHERE `id` in ('". implode("', '",$OM) ."');";
         $questions = $TDB->get_results($query);
         
         foreach ($questions as $question){
             $question["timestamp"]=strtotime($question["date"]);
             $question["Link"]= $dst."/~show_question/qid/".$question["id"];
             $question["Destination"] = $dst;
             $TMS->AddMassReplace('_search_list_item',$question);
             $TMS->parseSection('_search_list_item',true);
         }
         
         $TMS->AddMassReplace('_faq_search_results',array("total"=>$total, "Destination" => $dst));
         return $TMS->parseSection('_faq_search_results');
         
     }
     
     function faqsearch(&$params){
         return $this->show_faq_search_server($params);
     }
     
    function get_bones($category=null, $question=null, $action_alias = null)
        {
            global $REQUEST_VARS;
            
            
            if ($category) { 
                if (count($this->bones_path)) return;
                
                $this->bones_path[]=array('basic'=>'~show_category/cid');
                
                $this->bones_path[]=$category;
                if ($question) {
                    $question["params"]["basic"]=$question["id"];
                    $question["params"]["Name"]= $question['Alias'] ;
                    $this->bones_path[]=$question;
                }
                return;
            }
            if ($action_alias) {
                if (count($this->bones_path)) return;
                $this->bones_path[]=array('basic'=>'~'. $REQUEST_VARS[0], 'params'=> array('Name'=>$action_alias));
            }
        }     
    } #endclass
?>
