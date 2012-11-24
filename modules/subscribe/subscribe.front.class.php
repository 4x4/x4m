<?php
class subscribe_module_front
{
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function subscribe_module_front()
    {
        $this->_module_name = 'subscribe';
        $this->_common_obj =& subscribe_module_common::getInstance(true);
        $this->_tree =& $this->_common_obj->obj_tree;
    }
                                                                                       
    function request_action_set($action) 
    {   
        $this->_common_obj->request_action_set($action); 
    }


      function execute($action_data,$back_slot=null)
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
    
        
    function show_subscribe_form($params, $msg = false)
    {
        global $TMS, $_WEBPATH;
        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();
          
        $subscr = $this->_tree->getNodeInfo($params['subscribeId']);

        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
      
        $TMS->AddMassReplace('subscribe_form', array(
           'action' =>  $pages->create_page_path($params['page']) . '/~subscriber_page',
           'subscribe_id' => $params['subscribeId'],
           'page_id' =>  $params['page'],
           'subscribeId' => $params["subscribeId"],
           'subscribeName' => $params["showsubscribeName"],
        ));    
          
        return $TMS->parseSection('subscribe_form');
    }
    
    function subscriber_page($params)
    {
        global $TMS, $TDB, $REQUEST_ASSOC, $TPA, $REQUEST_VARS;
        if(!is_array($params['subscribe_id']))
        {
            $params['subscribe_id'] = (array) $params['subscribe_id'];
        }
        $email = isset($params['email']) ? (string) $params['email'] : '';
        
        if(!$this->checkemail($email))
        {
          return $TMS->parseSection('incorrect_email');  
        }
   
         if($params['subscribe_id'])
        {
            foreach($params['subscribe_id'] as $subscribe_id)
            {
                $subscr = $this->_tree->getNodeInfo((int) $subscribe_id);
                
                $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['aTemplate']));
                
                $unsubscribe = $params['unsubscribe'];                        
                $in_list = $this->user_in_list($email, $subscribe_id);
                    
                if(!$in_list && !$unsubscribe)
                {
                    if($this->addUser($subscribe_id, $email, $params))
                    {
                        $TMS->AddMAssReplace('success', array('email' => $email));
                        $section = 'success';
                    }
                    else
                    {
                        $section = 'error';
                    }
                }
                
                if($in_list && $unsubscribe)
                {
                    if($this->removeUser($subscribe_id, $email, $params))
                    {
                        $TMS->AddMassReplace('unsubscribe_success', array('email' => $email));
                        $section = 'unsubscribe_success';
                    }
                    else
                    {
                        $section = 'error';
                    }
                }
                        
                if(!$in_list && $unsubscribe)
                {
                    $TMS->AddMAssReplace('not_exists', array('email' => $email));
                    $section = 'not_exists';
                }
                        
                if($in_list && !$unsubscribe)
                {
                    $TMS->AddMAssReplace('exists', array('email' => $email));
                    $section = 'exists';
                }
                
                $text .= $TMS->parseSection($section);
            }
            
            return $text;
        }
        elseif($default_action = $params['Default_action'])
        {
            return $this->$default_action($params);
        }
    }
    
    
    function registration_subscribe($params)
    {
        global $TDB, $TMS, $REQUEST_ASSOC;
        
        $sub_id = (int) $params['sub_id'];
        
        if(!$params['status']) {$params['status'] = 'inactive';}

        if($this->_tree->getNodeParam($sub_id))
        {
            $hash = md5($time = time());
            $query  = "INSERT INTO subscribers_list (id, email, status, code, date) VALUES (NULL, '" . $params['Email'] . "', '" . $params['status'] . "', '" . $hash . "', " . $time . ")";
            $TDB->get_results($query);
            $query = 'INSERT INTO subscribers_params (id, subscribe_id, user_id) VALUES (NULL, ' . $sub_id . ',' . $TDB->insert_id . ')';
            $TDB->get_results($query);
                       
            return true;
        }
        else
        {
            return false;
        }
        
    }
    

    function complete_subscribe($params)
    {
        global $TDB, $TMS, $REQUEST_ASSOC;
        
        $sub_id = (int) $REQUEST_ASSOC['id'];
        $code = substr($REQUEST_ASSOC['code'], 0, 32);   

        if($code && $sub_id)
        {
            $query = sprintf("SELECT id, email FROM subscribers_list WHERE code='%s'", mysql_real_escape_string($code));

            if($user = $TDB->get_results($query))
            {
                $user_id = $user[1]['id'];
                
                $query = "UPDATE subscribers_list SET status = 'active', code = '' WHERE id = " . $user_id;
                $TDB->get_results($query);
                
                $query = 'INSERT INTO subscribers_params (id, subscribe_id, user_id) VALUES (NULL, ' . $sub_id . ',' . $user_id . ')';
                $TDB->get_results($query);
                
                $subscr = $this->_tree->getNodeParam($sub_id);

                $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['aTemplate']));
                $TMS->AddMassReplace('subscribe_completed', array(
                    'email'     => $user[1]['email'], 
                    'subscribe' => $subscr['basic']
                ));
                       
                return $TMS->parseSection('subscribe_completed');
            }
        }
    }
    
    function complete_unsubscribe($params)
    {
        global $TDB, $TMS, $REQUEST_ASSOC;
        
        $sub_id = (int) $REQUEST_ASSOC['id'];
        $code = substr($REQUEST_ASSOC['code'], 0, 32);
        
        if($REQUEST_ASSOC['code'] && $sub_id)
        {
            $query = sprintf("SELECT id, email FROM subscribers_list WHERE code='%s'", mysql_real_escape_string($code));
                        
            if($user = $TDB->get_results($query))
            {
                
                $user_id = $user[1]['id'];
                
                $query = 'DELETE FROM subscribers_params WHERE user_id = ' . $user_id . ' AND subscribe_id = ' . $sub_id;
                $TDB->get_results($query);
                
                $query = 'DELETE FROM subscribers_list WHERE id = ' . $user_id;
                $TDB->get_results($query);
                
                $subscr = $this->_tree->getNodeParam($sub_id);
                
                $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['aTemplate']));
                
                $TMS->AddMassReplace('unsubscribe_completed', array(
                    'email'     => $user[1]['email'], 
                    'subscribe' => $subscr['basic']
                ));
                
                return $TMS->parseSection('unsubscribe_completed');
            }
        }
    }
      
    function checkemail($email) 
    {
        $pattern = '/^([a-z0-9])(([-a-z0-9._])*([a-z0-9]))*\@([a-z0-9])' . 
                   '(([a-z0-9-])*([a-z0-9]))+' . 
                   '(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';
                   
        if(preg_match($pattern, $email)) 
            return true;
        
        return false;
    }
    
    function user_in_list($user, $subscribe_id)
    {
        global $TDB; 
        
        $query = "SELECT * FROM subscribers_list AS t1, subscribers_params AS t2 WHERE t1.email = '" . $user . "' AND t2.subscribe_id = " . $subscribe_id;
        
        return count($TDB->get_results($query)) > 0;
    }
    
    function addUser($subscribe_id, $email, $params)
    {
        global $TDB, $TMS;
        
        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();
        $link = $pages->create_page_path((int)$params['params']['page']);
        
        if($link)
        {
            $hash = md5($time = time());
            
            if(!$subscribe_id)
            {
                $subscribe_id = (int) $params['subscribe_id'];    
            }
            
            $subscr = $this->_tree->getNodeParam($subscribe_id);
            $query  = "INSERT INTO subscribers_list (id, email, status, code, date) VALUES (NULL, '" . $email . "', 'inactive', '" . $hash . "', " . $time . ")";

            $TDB->get_results($query);
            $user_id = $TDB->insert_id;
            
            $query  = "INSERT INTO subscribers_params (id, subscribe_id, user_id) VALUES (NULL, " . $subscribe_id . "," . $user_id . ")";
            $TDB->get_results($query);

            $TMS->AddMassReplace('subscribe_message', array(
                'link'      => $link . '/~complete_subscribe/code/' . $hash . '/id/' . $subscribe_id, 
                'email'     => $email,
                'subscribe' => $subscr['basic']
            )); 
            
            $message = $TMS->parseSection('subscribe_message');
            
            $TMS->AddMassReplace('subscribe_message_header', array('subscribe' => $subscr['basic']));
            $header = $TMS->parseSection('subscribe_message_header');
            
            $this->sendMail($email, $message, $header, $subscr['from']);
            return $TDB->result;
        }
        return false;
    }
    
    function removeUser($subscribe_id, $email, $params)
    {
        global $TDB, $TMS;
        
        Common::call_common_instance('pages');
        $pages =& pages_module_common::getInstance();
        $link = $pages->create_page_path((int)$params['params']['page']);
         
        if($link)
        {
            $hash = md5(time());
            $query = "SELECT * FROM subscribers_list WHERE email = '" . $email . "'";
            
            if($results = $TDB->get_results($query))
            {
                $subscribe_id = (int) $params['subscribe_id'];  
                $subscr = $this->_tree->getNodeParam($subscribe_id);
                
                $TMS->AddMassReplace('unsubscribe_message', array(
                    'link'      => $link . '/~complete_unsubscribe/code/' . $hash . '/id/' . $subscribe_id,
                    'subscribe' => $subscr['basic']              
                ));
                $message = $TMS->parseSection('unsubscribe_message');   
                
                $TMS->AddMassReplace('unsubscribe_message_header', array('subscribe' => $subscr['basic']));
                $header = $TMS->parseSection('unsubscribe_message_header');
                $this->sendMail($email, $message, $header, $subscr['from']);
            }
            
            $query = "UPDATE subscribers_list SET code = '" . $hash . "' WHERE email = '" . $email . "'";
            $TDB->get_results($query);

            return $TDB->result;
        }
        return false;
    }
    
    function sendMail($email, $message, $header, $from)
    {    
        $headers = "From: " . $from . "\r\n" . "Content-type: text/html; charset=utf-8" . "\r\n"; 
        return @mail($email, $header, $message, $headers);
    }
}
?>