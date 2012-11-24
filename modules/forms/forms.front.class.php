<?php

class forms_module_front extends forms_module_tpl
{
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function forms_module_front()
    {
        $this->_module_name = 'forms';
        $this->_common_obj = &forms_module_common::getInstance(true);
        $this->_tree = &$this->_common_obj->obj_tree;
        parent:: __construct();
    }
                                                                                       
    function request_action_set($action)
    {
        $this->_common_obj->request_action_set($action);
    }
    
    function execute($action_data)
    {
        global $TMS;
        
        if (is_array($action_data))
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
    
    
    function show_forms($params)
    {
        $node = $this->_tree->getNodeInfo($params['formsId']);
            if($_POST && !$_POST['notsend'])
            {
                $params['formsId'] = $_POST['formId'];
                return $this->recieve_message($params);
            }
            else
            {
                return $this->get_forms($params, $node['obj_type']);
            }
    }
    
    
    function getmicrotime($rtrn)
    {
        list($usec, $sec) = explode(' ', microtime());
            if($rtrn) return (float)$sec;
            else return ((float)$usec + (float)$sec);
    }
    
    
    function check_captcha_code($params)
    {
        if($_SESSION['captcha'][$params['formsId']] == $params['captcha']) {$this->result['captcha'] = true;}
        else {$this->result['captcha'] = false;}
    }
    
    
    function use_captcha($params)
    {
        $use_captcha = $this->_tree->ReadNodeParam($params['formsId'], 'use_captcha');
            if($use_captcha){return true;}
            else{return false;}
    }
    
    
    function timer_for_next_message($params)
    {
            if(isset($_SESSION['form'.$params['formsId']]['timer_for']) && array_key_exists($params['user_ip'], $_SESSION['form'.$params['formsId']]['timer_for']))
            {
                if(!$mtime = $this->_tree->ReadNodeParam($params['formsId'], 'timeout'))
                {
                    $mtime = 60; //$mtime = $this->_tree->ReadNodeParam($params['formsId'], 'timeout');
                }
                
                $curtime = $this->getmicrotime(true);
                $time_left = (int) $mtime - ($curtime - $_SESSION['form'.$params['formsId']]['timer_for'][$params['user_ip']]);
                    if($time_left <= 0)
                    {
                        $_SESSION['form'.$params['formsId']]['timer_for'][$params['user_ip']] = $this->getmicrotime(true);
                        return true;
                    }
                    else return $time_left;
            }
            else
            {
                $_SESSION['form'.$params['formsId']]['timer_for'][$params['user_ip']] = $this->getmicrotime(true);
                return true;
            }
    }
    
    
    function get_forms($params, $ntype = '_FORMSGROUP')
    {
        global $TMS, $TDB;

        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
        $swfupload = false;
            if ($ntype == '_FORMS')
            {
                $NodeChilds = array(array('id' => $params['formsId']));
            }
            else
            {
                $NodeChilds = $this->_tree->GetChilds($params['formsId']);
            }
            if($NodeChilds)
            {
            foreach($NodeChilds as $Child)
            {
                $form = $this->_tree->getNodeInfo($Child['id']);
                 if(!$form['params']['Disable'])
                 {
                     $fields = $this->_tree->GetChildsParam($form['id'], '%', true, array('obj_type' => array('_FIELDS')));
                     $validation = false;

                        foreach($fields as $id => $field)
                        {
                            $field = array('id' => $field['id']) + $field['params'];
                            $field['identifier'] = (!empty($field['identifier'])) ? $field['identifier'] : 'field_'. $form['id'] . $field['id'];
                            
                                if($field['field_type'] == 'select' || $field['field_type'] == 'multiselect')
                                {
                                    $options = explode("\n", $field['values']);
                                        foreach ($options as $option)
                                        {
                                            $opt_data = explode(':', $option);
                                            $opt['value'] = $opt_data[0]; 
                                            $opt['name'] = ($opt_data[1]) ? $opt_data[1] : $opt_data[0];
                                                if(isset($_POST[$field['identifier']]))
                                                {
                                                    if($field['field_type'] == 'multiselect')
                                                    {
                                                        foreach($_POST[$field['identifier']] as $v)
                                                        {
                                                            if($v == $opt['value']) {$opt['selected'] = 'selected';}
                                                            else {$opt['selected'] = '';}
                                                        }
                                                    }
                                                    else
                                                    {
                                                        if($_POST[$field['identifier']] == $opt['value']) {$opt['selected'] = 'selected';}
                                                        else {$opt['selected'] = '';}
                                                    }
                                                }
                                            $TMS->AddMassReplace('option', $opt);
                                            $TMS->parseSection('option', true);
                                        }   
                                }
                                else if($field['field_type'] == 'file')
                                {
                                    $swfupload = true;
                                }

                                if($field['compulsory_to_fill'])
                                {
                                    $validation = true;
                                    $field['validate_type'] = ($field['pattern']) ? 'pattern' : $field['type'];
                                    $TMS->AddMassReplace('validation', array('form_id' => $form['id']) + $field);
                                    $TMS->parseSection('validation', true);
                                }
                                elseif($field['pattern'])
                                {
                                    $field['validate_type'] = 'pattern';
                                    $TMS->AddMassReplace('validation', array('form_id' => $form['id']) + $field);
                                    $TMS->parseSection('validation', true);
                                }
                                
                                if($field['readonly']) {$field['readonly'] = 'readonly';}
                                if(isset($_POST[$field['identifier']])){$field['value'] = $_POST[$field['identifier']];}
                                if('url' == $field['type'] || 'email' == $field['type'] || 'numerical' == $field['type']){$field['type'] = 'text';}
                                
                                $TMS->AddMassReplace($field['field_type'], $field);
                                $TMS->parseSection($field['field_type'], true);
                                $TMS->KillMFields('options');
                        }
                     $form = array('form_id' => $form['id']) + $form['params'];

                        if($form['use_captcha'])
                        {
                            $_SESSION['captcha_settings'] = explode('-', $form['captcha_settings']);
                            $form['Length'] = $_SESSION['captcha_settings'][0];
                            $validation = true;
                            $TMS->AddMassReplace('captcha', $form);
                            $TMS->parseSection('captcha', true);
                            $TMS->AddMassReplace('validation', array('form_id' => $form['form_id'],
                                                                    'validate_type' => 'captcha',
                                                                    'identifier' => 'captcha_' . $form['form_id'],
                                                                    'Length' => $form['Length']
                                                                    ));
                            $TMS->parseSection('validation', true);
                        }
                        
                        if($validation)
                        {
                            $TMS->parseSection('validate_script', true);
                        }
                        
                        if(isset($params['error']['form'.$form['form_id']]) && is_array($params['error']['form'.$form['form_id']]))
                        {
                            $TMS->AddMassReplace('msg', $params['error']['form'.$form['form_id']]);
                            $form['msg'] = $TMS->parseSection('msg');
                        }
                     $TMS->AddMassReplace('form', $form);
                     $TMS->parseSection('form', true);
                     $TMS->KillMFields('fields');
                     $TMS->KillMFields('validation');
                     $TMS->KillMFields('validate_script');
                        if($swfupload){$TMS->parseSection('_forms_group_swfupload', true);}
                     $TMS->parseSection('_forms_list_item', true);
                     
                 }
                 $TMS->KillMFields('form'); 
            }
        }
            
        return $TMS->parseSection('_forms_group_list', true);
    }
    
    
    function recieve_message($params)
    {
        global $TMS;
        
        $timer_for_message = $this->timer_for_next_message(array('formsId' => $params['formsId'], 'user_ip' => getenv("REMOTE_ADDR")));
        
        if(gettype($timer_for_message) == 'boolean' && $timer_for_message == true){
            if($this->use_captcha(array('formsId' => $params['formsId'])))
            {
                if(isset($_SESSION['captcha'][$params['formsId']]) && $_POST)
                {
                    if($_SESSION['captcha'][$params['formsId']] != $_POST['captcha_' . $params['formsId']])
                    {
                        unset($_SESSION['captcha']); 
                        $_SESSION['sent'] = false;
                        $params['error']['form'.$params['formsId']] = array('error_type' => 'captcha');
                        
                        return $this->get_forms($params, '_FORMS');
                    }
                    elseif($_SESSION['captcha'][$params['formsId']] == $_POST['captcha_' . $params['formsId']])
                    {
                        unset($_SESSION['captcha']);
                        if($this->send_message($params))
                        {
                            $message_after = $this->_tree->ReadNodeParam($params['formsId'], 'message_after');
                            $_SESSION['sent'] = true;
                            $TMS->AddMassReplace('success', array('message_after' => $message_after));
                            return $TMS->parseSection('success');
                        }
                        else
                        {
                            unset($_SESSION['captcha']);
                            $_SESSION['sent'] = false;
                            $params['error']['form'.$params['formsId']] = array('error_type' => 'undefined');
                            
                            return $this->get_forms($params, '_FORMS');
                        }
                    }
                }
                else
                {
                    unset($_SESSION['captcha']); 
                    $_SESSION['sent'] = false;
                    $params['error']['form'.$params['formsId']] = array('error_type' => 'captcha');
                        
                    return $this->get_forms($params, '_FORMS');
                }
            }
            else
            {
                if($this->send_message($params))
                {
                    $message_after = $this->_tree->ReadNodeParam($params['formsId'], 'message_after');
                    $_SESSION['sent'] = true;
                    $TMS->AddMassReplace('success', array('message_after' => $message_after));
                    return $TMS->parseSection('success');
                }
                else
                {
                    unset($_SESSION['captcha']);
                    $_SESSION['sent'] = false;
                    $params['error']['form'.$params['formsId']] = array('error_type' => 'undefined');
                    
                    return $this->get_forms($params, '_FORMS'); 
                }
            }
        }
        elseif(gettype($timer_for_message) == 'double')
        {
            unset($_SESSION['captcha']);
            $_SESSION['sent'] = false;
            $params['error']['form'.$params['formsId']] = array('error_type' => 'timer', 'time_left' => $timer_for_message);

            return $this->get_forms($params, '_FORMS');
        }
    }
    
    
    function recieve_message_async($params)
    {   
        $params['Template1'] = 'form_to_send.show_forms.html';
        $timer_for_message = $this->timer_for_next_message(array('formsId' => $params['formsId'], 'user_ip' => getenv("REMOTE_ADDR")));
          
        if(gettype($timer_for_message) == 'boolean' && $timer_for_message == true){ 
            if($this->use_captcha(array('formsId' => $params['formsId'])))
            {
                if(isset($_SESSION['captcha'][$params['formsId']]) && $params['data']['captcha_'.$params['formsId']])
                {
                    if($_SESSION['captcha'][$params['formsId']] != $params['data']['captcha_'.$params['formsId']]) {$this->result['send'] = false;}
                    else {$this->result['send'] = $this->send_message($params);}
                }
                else {$this->result['send'] = false;}
            }
            else {$this->result['send'] = $this->send_message($params);}
        }
        else if(gettype($timer_for_message) == 'double')
        {
            $this->result['send'] = $timer_for_message;
        }
    }
    
    function construct_message($form_id, $tpl, $data = array())
    {
        global $TMS, $TDB;
            
            if(empty($data) && !empty($_POST))
            {
                $data = $_POST;
            }

        $TMS->AddFileSection(Common::get_fmodule_tpl_path('forms', $tpl));
        $form = $this->_tree->getNodeInfo($form_id);
        $fields = $this->_tree->GetChildsParam($form_id, '%', true, array('obj_type' => array('_FIELDS')));

            foreach($fields as $id => $field)
            {
                switch($field['params']['field_type'])
                {
                    case 'flag':
                        $value = ($data[$field['params']['identifier']] || $data['field_' . $form_id . $id]) ? 'Yes' : 'No';
                        break;
                        
                    default:
                        $value = ($field['params']['identifier']) ? $data[$field['params']['identifier']] : $data['field_' . $form_id . $id];
                        break;
                }
                
                if (is_array($value))
                {
                    $value  =  implode('<br />', $value);   
                }
                
                $TMS->AddMassReplace('row', array('name' => $field['params']['Name'], 'value' => $value));
                $TMS->parseSection('row',true);
            }
             
        $TMS->AddMassReplace('message', $form['params']);
        $TMS->AddMassReplace('saved_message', $form['params']);
        
        $message['to_email'] = XHTML::xss_clean($TMS->parseSection('message')); //htmlentities();
        $message['to_save'] = XHTML::xss_clean($TMS->parseSection('saved_message'));

        return $message;
    }
    
    function send_message($params)
    {
        $message = $this->construct_message($params['formsId'], $params['Template1'], $params['data']);            
        $form = $this->_tree->getNodeInfo($params['formsId']);
        $to_email_list = explode(',', $form['params']['email']);
        $headers = 'Content-type: text/html; charset=' . $form['params']['charset'] . '';
        $subject = $form['params']['subject'];
     
            if ($form['params']['save_to_server'])
            {
                $this->save_to_server($params['formsId'], $message['to_save']);
            }

            foreach($to_email_list as $to)
            {
                $to = trim($to);
                    if(@mail($to, $subject, $message['to_email'], $headers)) {$send = true;}
                    else {$send = false;}
            }

        return $send;
    }
    
    
    function save_to_server($form_id, $msg)
    {
        global $TDB;

        $form_name = $this->_tree->ReadNodeParam($form_id, 'Name');
        $TDB->InsertIN('messages', array('id' => 'null', 'form_id' => (int)$form_id, 'Name' => $form_name, 'date' => date("d.m.Y H:i:s"), 'message' => $msg, 'status' => 0, 'archive' => 0));
        //return $TDB->result;
    }
     
}

?>