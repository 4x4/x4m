<?php
class faq_module_xfront extends faq_module_front
{
    function executex($action, $acontext)
    {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result = $this->result;
    }
    
    function add_question_async($params){
        global $TDB,$TMS,$TPA,$_COMMON_SITE_CONF; 
        $fdata=$params["fdata"];
        if($_SESSION['captcha'][(int)$fdata["category"]] == $fdata['captcha'] || $_SESSION['captcha'][(int)$fdata["captcha_id"]] == $fdata['captcha']) { 
          unset($_SESSION['captcha'][(int)$fdata["category"]]);
          unset($_SESSION['captcha'][(int)$fdata["captcha_id"]]);
        }
        else {
            $this->result["send"]=false;
            $this->result["error"]="captcha";
            return false;
        }
        
        $cat_id = (int)$fdata['category'];
        $cat = $this->_tree->getNodeInfo($cat_id);
        if (!$cat_id || !$cat) {
            $this->result["send"]=false;
            $this->result["error"]="category";
            return;
        }
        
        if ($fdata["letter_template"] && file_exists(Common::get_fmodule_tpl_path('faq', $fdata["letter_template"].'.html'))) {
            $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', $fdata["letter_template"].'.html'));
            
        } else $TMS->AddFileSection(Common::get_fmodule_tpl_path('faq', 'letter.html'));
        
        
        
        $blocks = explode("\n", $fdata['question']);
        $question = '';
        if (count($blocks)) {
            foreach($blocks as $block) {
                $question .= '<p>'.mysql_real_escape_string($block).'</p>';
            }
        } else {
            $question = mysql_real_escape_string($fdata['question']);
        }
        
        ($cat["params"]["moderation"]) ? $active=2 :$active=1;
        $fields = array(
            "active"=>$active,
            "date" => "'".date('Y-m-d  G:i:s')."'",
            "user" => "'".mysql_real_escape_string($fdata["user"])."'",
            "email" => "'".mysql_real_escape_string($fdata["email"])."'",
            "question" => "'".$question."'",
            "sanswer" => "''",
            "answer" => "''"
        );
        
        $query = 'INSERT INTO `faq` (cat_id,`'. implode('`,`',array_keys($fields)).'`) VALUES('.$cat_id.', '.implode(',',array_values($fields)).');';
        if ($TDB->query($query)) {
            
            if ($cat['params']['email']!='') {                    
                $TMS->AddMassReplace('letter',$fdata);
                $TMS->AddReplace('letter', "category_name", $cat["params"]["Name"]);
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
            $this->result["send"]=true;
        } else {
            $this->result["send"]=false;
            $this->result["error"]="insert";
        }
    }


    function check_captcha_code($params) {
        if (is_string($_SESSION['captcha'])) $_SESSION['captcha']=array();
        if($_SESSION['captcha'][$params["cat_id"]] == $params['captcha']) {
            $this->result['captcha'] = true;
            return true;
        }
        else {
            $this->result['captcha'] = false;
            return false;
        }
    }   
    
}  
?>


