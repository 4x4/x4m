<?php

class fusers_module_front extends fusers_module_tpl
    {
    var $_module_name;
    var $_tree;
    var $_common_obj;

    function fusers_module_front()
        {
        $this->_module_name='fusers';        
        $this->_common_obj =fusers_module_common::getInstance(true);
        $this->_tree       =&$this->_common_obj->obj_tree;
        $this->_tree->enable_cache(true);
        parent::__construct();
        $this->context=null;
        }


    

    function show_auth_panel($parameters)
        {
        static $auth;
        if ($auth)
            return;

        global $TMS;
           
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
        $pages=&pages_module_common::getInstance();
        $plink=$pages->create_page_path($parameters['UserPanelPage']);

        if ($_SESSION['siteuser']['authorized'])
            {
            $TMS->AddMassReplace('fusers_authorized_panel', array
                (
                'user_panel_link' => $plink . '/~userpanel',
                'logout'          => $plink . '/~logout'
                ));

            $auth=true;
            return $TMS->parseSection('fusers_authorized_panel');
            }
        else
            {
            $TMS->AddMassReplace('fusers_auth_panel', array
                (
                'auth_link'         => $plink . '/~auth',
                'registration_link' => $plink . '/~registration',
                'forgotpassword_link'   => $plink . '/~forgotpassword'
                ));

            return $TMS->parseSection('fusers_auth_panel');
            }
        }

    function registration($parameters, $errors = null, $userdata = null)
        {
        global $TMS, $TPA;

        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if ($_SESSION['siteuser']['authorized'])
            {
            return $this->user_panel($parameters);
            }

        $pages=&pages_module_common::getInstance();
        $plink=$pages->create_page_path($TPA->page_node['id']);
        $TMS->AddMassReplace('fuser_registration', array('submit_new_user_link' => $plink . '/~submituser'));

        if ($errors)
            {
            $TMS->AddMassReplace('fuser_registration', $errors);
            }

        if ($userdata['ext_user_data'])
            {
            $userdata=array_merge($userdata, $userdata['ext_user_data']);
            unset($userdata['ext_user_data']);
            }

        if ($userdata)
            {
            $TMS->AddMassReplace('fuser_registration', $userdata);
            }

        return $TMS->parseSection('fuser_registration');
        }

    function edituser($parameters, $errors = null, $userdata = null)
        {
        global $TMS, $TPA;

        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        $pages=&pages_module_common::getInstance();
        $plink=$pages->create_page_path($TPA->page_node['id']);
        $TMS->AddMassReplace('fuser_edit_user', array('save_edit_user_link' => $plink . '/~save_profile'));

        if ($errors)
            {
            foreach ($errors as $k_e => $err_params)
                {
                $TMS->AddMassReplace($k_e, $err_params);
                $TMS->parseSection($k_e, true);
                }
            }

        if (!$userdata)
            {
            if ($userdata=$this->_tree->getNodeParam($_SESSION['siteuser']['id']))
                {
                if ($ext=$this->_tree->GetChildsParam($_SESSION['siteuser']['id'], '%'))
                    {
                    $userdata=array_merge($userdata, current($ext));
                    }

                $TMS->AddMassReplace('fuser_edit_user', $userdata);
                }

            return $TMS->parseSection('fuser_edit_user');
            }
        }

    function save_profile($parameters)
        {
        global $TMS, $TPA;

        $return['errors']=array();

        if (($_POST['UserName']) && ($_POST['Password']) && ($_POST['PasswordAgain'] == $_POST['Password']))
            {
            $passwords        =true;
            $_POST['Password']=md5(strrev($_POST['Password']));
            }
        else
            {
            unset($_POST['Password']);
            }

        unset($_POST['UserName']);

        $email=$this->_tree->JoinSearch(array(array
            (
            'Email',
            $_POST['Email']
            )));

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if ((!$email) or ($email[$_SESSION['siteuser']['id']]))
            {
            if ($uid=$this->_common_obj->reinit_fuser($_SESSION['siteuser']['id'], $_POST))
                {
                $node                            =$this->_tree->getNodeInfo($_SESSION['siteuser']['id']);
                $_SESSION['siteuser']['userdata']=$node['params'];

                if ($_POST['ext_user_data'] && $child=$this->_tree->GetChilds($uid))
                    {
                    $this->_common_obj->reinit_fuserextdata($child[0]['id'], $_POST['ext_user_data']);
                    $_SESSION['siteuser']['extuserdata']=$_POST['ext_user_data'];
                    }
                else
                    {
                    $this->_common_obj->init_fuserextdata($uid, $_POST['ext_user_data']);
                    }
                }
            else
                {
                $errors['fuser_registration_err_internal']=1;
                }
            }
        else
            {
            unset($_POST['Password']);
            unset($_POST['PasswordAgain']);

            if ($login)
                {
                $errors['fuser_registration_err_non_uniq']=1;
                }

            if ($email)
                {
                $errors['fuser_registration_err_non_uniq_email']=1;

                unset($_POST['Email']);
                }
            }

        if (count($errors) > 0)
            {
            unset($_POST['Password']);
            unset($_POST['PasswordAgain']);

            return $this->edituser($parameters, $errors, $_POST);
            }
        else
            {
            $parameters['profile_saved']=true;
            return $this->user_panel($parameters);
            }
        }

    function destroyuser($parameters)
    {    
        global $REQUEST_ASSOC, $TMS;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
        if (empty($REQUEST_ASSOC['code'])) return $TMS->parseSection('fuser_account_not_exists');

        $code=mysql_real_escape_string($REQUEST_ASSOC['code']);

        if ($is=$this->_tree->JoinSearch(array(array
            (
            'VerificationCode',
            $code
            ))))
            {
                list($id, $data)=each($is);
                $this->_tree->DelNode($id);
                return $TMS->parseSection('fuser_account_deleted');

            }
        
    }
    
    
        
    function _submit_user($parameters)
        {
        global $TPA, $TMS, $_COMMON_SITE_CONF;

        $errors=array();


            if ($_SESSION['captcha'] == $parameters['data']['captcha'])
                {
                    $code=true;
                }
        
        if (($parameters['data']['UserName']) && ($parameters['data']['Password'])
            && ($parameters['data']['PasswordAgain'] == $parameters['data']['Password']))
            {
                $passwords=true;
            }

        if ($code && $passwords)
            {
            $login=$this->_tree->SearhInBasics($parameters['data']['UserName'], array('_FUSER'));
            $email=$this->_tree->JoinSearch(array(array
                (
                'Email',
                $parameters['data']['Email']
                )));

            if ($parameters['doNotVerifyUser'])
                {
                $d_group                     ='DefaultRegisteredGroup';
                $parameters['data']['Active']=1;
                }
            else
                {
                $d_group='DefaultUnregisteredGroup';
                unset($parameters['data']['Active']);
                }

            if ((!$login) && (!$email))
                {
                if ($id=$this->_tree->ReadNodeParam(1, $d_group))
                    {
                    $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
                    $parameters['data']['Password']=md5(strrev($parameters['data']['Password']));

                    if ($uid=$this->_common_obj->init_fuser($id, $parameters['data']['UserName'], $parameters['data']))
                        {
                        if ($parameters['data']['ext_user_data'])
                            {
                            $this->_common_obj->init_fuserextdata($uid, $parameters['data']['ext_user_data']);
                            }

                        if (isset($parameters['UseEmailVerify']) && (!$parameters['doNotVerifyUser']))
                            {
                            $vcode=Common::GenerateHash();
                            $m    =Common::inc_module_factory('Mail');

                            $TMS->AddMassReplace('fusers_registration_mail_text', array
                                (
                                'Name'      => $parameters['data']['Name'],
                                'Login'     => $parameters['data']['UserName'],
                                'Password'  => $parameters['data']['Password'],
                                'HOST'      => HOST,
                                'VerifyUrl' => $TPA->page_link . '/~verifyuser/code/' . $vcode,
                                'DestroyUrl' => $TPA->page_link . '/~destroyuser/code/' . $vcode
                                ));

                            $m->From($_COMMON_SITE_CONF['admin_email']);
                            $m->To(array
                                (
                                $parameters['data']['Email'],
                                $_COMMON_SITE_CONF['admin_email']
                                ));

                            $m->Content_type('text/html');
                            $m->Subject(HOST . 'registration');
                            $m->Body($TMS->parseSection('fusers_registration_mail_text'),$_COMMON_SITE_CONF['site_encoding']);
                            $m->Send();
                            
                            $this->_tree->WriteNodeParam($uid, 'VerificationCode', $vcode);                        
                            return array('result' => array('fuser_email_registration_passed'
                                                         => array('email' => $parameters['data']['Email'])));
                            }

                        return array('result' => array('fuser_registration_passed' => 1));
                        }
                    else
                        {
                        $return['errors']['fuser_registration_err_internal']=1;
                        }
                    }
                }
            else
                {
                unset($parameters['data']['Password'], $parameters['data']['PasswordAgain']);

                if ($login)
                    {
                    $return['errors']['fuser_registration_err_non_uniq']=1;

                    unset($parameters['data']['UserName']);
                    }

                if ($email)
                    {
                    $return['errors']['fuser_registration_err_non_uniq_email']=1;
                    unset($parameters['data']['Email']);
                    }
                }
            }

        if (!$passwords)
            {
            $return['errors']['fuser_registration_err_passwords']=1;
            }

        if (!$code)
            {
            $return['errors']['fuser_registration_err_incorrect_code']=1;
            }

        if (count($return['errors']) > 0)
            {
            unset($parameters['data']['Password'], $parameters['data']['passwordAgain'], $parameters['data']['Code']);

            $return['data']=$parameters['data'];

            return $return;
            }
        }

    function submituser($parameters)
        {
        global $TMS, $TPA, $_COMMON_SITE_CONF;
        $parameters['data']=$_POST;

        $result            =$this->_submit_user($parameters);

        if (!$result['errors'])
            {
            $section=key($result['result']);
            $TMS->AddMassReplace($section, $result['result'][$section]);
            return $TMS->parseSection($section);
            }
        else
            {
            return $this->registration($parameters, $result['errors'], $result['data']);
            }
        }

    function verifyuser($parameters)
        {
        global $REQUEST_ASSOC, $TMS;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
                
        if (empty($REQUEST_ASSOC['code'])) return $TMS->parseSection('fuser_account_not_exists');

        $code=mysql_real_escape_string($REQUEST_ASSOC['code']);

        if ($is=$this->_tree->JoinSearch(array(array
            (
            'VerificationCode',
            $code
            ))))
            {
            list($id, $data)=each($is);
            $anc            =$this->_tree->ReadNodeParam(1, 'DefaultRegisteredGroup');

            if ($this->_tree->ChangeAncestor($id, $anc))
                {
                    $this->_tree->WriteNodeParam($id, 'Active', 1);
                    return $TMS->parseSection('fuser_account_confirm');
                }
            }
        }

    function auth($params)
        {
        global $TMS, $TPA;
        static $auth;

        if ($auth)
            return;

        if ($_SESSION['siteuser']['authorized'])
            {
            if ($params['StayOnSamePage'])
                {
                $pages=&pages_module_common::getInstance();
                $link =str_replace(HOST, '', $_SERVER['HTTP_REFERER']);
                $auth =true;
                $TPA->execute_page($link);
                die();
                }

            if ($params['LinkId'])
                {
                $pages=&pages_module_common::getInstance();
                $TPA->move_301_permanent($pages->create_page_path($params['LinkId']));
                die();
                }

            return $this->user_panel($params);
            }
        else
            {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));

            if (($_POST['login']) && ($_POST['password']) && $this->check_and_load_user($_POST['login'],
                                                                                        $_POST['password']))
                {
                $params['justlogin']=true;
                $auth               =true;
                $link               =str_replace(HOST, '', $_SERVER['HTTP_REFERER']);

                if ($url=$_SESSION['siteuser']['AskForUrl'])
                    {
                    $_SESSION['siteuser']['AskForUrl']=null;
                    $TPA->execute_page($url);
                    }
                elseif (($params['StayOnSamePage']) && ($TPA->request_action != 'auth')
                    && ($TPA->request_action != 'logout') && (strstr('~logout',
                                                                     $link) === false))
                    {
                    $pages=&pages_module_common::getInstance();
                    $auth =1;
                    $TPA->execute_page($link);
                    }
                elseif ($params['LinkId'])
                    {
                    $pages=&pages_module_common::getInstance();
                    $TPA->execute_page($pages->create_page_path($params['LinkId'], true));
                    }
                else
                    {
                    return $this->user_panel($params);
                    }
                }
            else
                {
                if (!empty($_POST))
                    {
                    $params['auth_failed']=true;
                    }

                return $this->needauth($params, true);
                }
            }
        }

    function needauth($params, $preventReauth = false)
        {
        global $TMS, $TPA;

        if ($TPA->page_redirect_params[$this->_module_name]['reason'] == 'no_access_granted')
            {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
            return $TMS->parseSection('fusers_auth_no_access_granted');
            }

        if (!($preventReauth) && ($_POST['login']) && ($_POST['password']))
            {
            return $this->auth($params);
            }

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));

        $pages=&pages_module_common::getInstance();
        $plink=$pages->create_page_path($TPA->page_node['id']);

        $TMS->AddMassReplace('fusers_auth_panel_up', array
            (
            'auth_failed'       => $params['auth_failed'],
            'auth_link'         => $plink . '/~auth',
            'registration_link' => $plink . '/~registration',
            'forgotpassword_link'   => $plink . '/~forgotpassword'
            ));

        return $TMS->parseSection('fusers_auth_panel_up');
        }

    function editmydata($params) { }

    function user_panel($params)
        {
        global $TMS, $TPA;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));

        if ($params['profile_saved'])
            {
            $TMS->parseSection('profile_saved', true);
            }

        if ($_SESSION['siteuser']['authorized'])
            {
            
                $pages=&pages_module_common::getInstance();
                $plink=$pages->create_page_path($TPA->page_node['id']);

                $TMS->parseSection('fuser_user_panel_menu',true);
                
                $TMS->AddMassReplace('fuser_user_panel', array
                    (
                    'logout'             => $plink . '/~logout',
                    'edit_user_link'     => $plink . '/~edituser',                    
                    'username'           => $_SESSION['siteuser']['userdata']['Name'],
                    'useremail'          => $_SESSION['siteuser']['userdata']['Email']
                    ));

                return $TMS->parseSection('fuser_user_panel');
                
            }
        else
            {
            return $this->auth($params);
            }
        }

    function userpanel($params) { return $this->user_panel($params); }
    
    
    function forgotpassword($params)
    {
        global $TMS, $TPA, $_COMMON_SITE_CONF;
        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
       
        if($_POST['Email'])
        {
            $usr_email = trim($_POST['Email']);
            $User = $this->_tree->Search(array('Email' => $usr_email), true, array('obj_type' =>'_FUSER'), null, '=', false);
            
                if(is_array($User))
                {
                   $uid = array_keys($User); $uid = $uid[0];
                   $New_password = substr(Common::GenerateHash(rand(), 12), 0, 8);
                   $New_password_hash = md5(strrev($New_password));
                   $l = $this->_common_obj->get_translation('back_interface');
                   $pages = &pages_module_common::getInstance();
                   $plink = $pages->create_page_path($TPA->page_node['id']);
                   
                   $TMS->AddMassReplace('fuser_forgot_password_mailtext', array(
                        'HOST'              => HOST,
                        'new_password'      => $New_password,                    
                        'auth_link'         => $plink . '/~auth'
                   ));
                   $mailtext = $TMS->parseSection('fuser_forgot_password_mailtext', true);

                   $m = Common::inc_module_factory('Mail');
                   $m->From($_COMMON_SITE_CONF['admin_email']);
                   $m->To($usr_email);
                   $m->Content_type('text/html');
                   $m->Subject($l['{password_recovery}'] . ' ('. HOST .')');
                   $m->Body($mailtext, $_COMMON_SITE_CONF['site_encoding']);
                   
                        if($this->_tree->WriteNodeParam($uid, 'password', $New_password_hash) && $m->Send())
                        {
                            $TMS->AddReplace('fuser_forgot_password_email_send', 'auth_link', $plink . '/~auth');
                            return $TMS->parseSection('fuser_forgot_password_email_send');
                        }
                        else
                        {
                            $error = $TMS->parseSection('fuser_forgot_password_email_notsend', true);
                            $TMS->AddReplace('fuser_forgot_password_enter_email', 'error', $error);
                            return $TMS->parseSection('fuser_forgot_password_enter_email');
                        }
                }
                else
                {
                    $error = $TMS->parseSection('fuser_forgot_password_email_notuser', true);
                    $TMS->AddReplace('fuser_forgot_password_enter_email', 'error', $error);
                    $TMS->AddReplace('fuser_forgot_password_enter_email', 'Email', $usr_email); 
                    return $TMS->parseSection('fuser_forgot_password_enter_email');
                }
        }
        else
        {
            return $TMS->parseSection('fuser_forgot_password_enter_email');
        }
    }

    

    function logout($params)
        {
        global $TMS;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
        unset($SESSION['siteuser']['userdata']);$_SESSION['siteuser']['authorized']=0;
        unset($_SESSION['siteuser']['usergroup']);
        unset($_SESSION['siteuser']['usergroupName']);

        $TMS->AddMassReplace('fuser_logout', array('fusers_auth_panel' => $this->needauth($params, true)));
        return $TMS->parseSection('fuser_logout');
        }

    function check_and_load_user($login, $password)
        {
        $login=$this->_tree->SearhInBasics($login, array('_FUSER'));

        if ($login[0])
            {
            $node=$this->_tree->getNodeInfo($login[0]);
            }
        else
            {
            return false;
            }

        if (($node['params']['Password'] == md5(strrev($password))) && ($node['params']['Active']))
            {                
            $_SESSION['siteuser']['id']               =$node['id'];
            $_SESSION['siteuser']['usergroup']        =$node['ancestor'];
            $_SESSION['siteuser']['usergroupName']    =$this->_tree->ReadNodeParam($node['ancestor'], 'Name');
            $_SESSION['siteuser']['authorized']       =true;
            
            if(!$node['params']['DiscountScheme'])
            {
                $node['params']['DiscountScheme']=$this->_tree->ReadNodeParam($node['ancestor'], 'DiscountScheme');
            }
            
            $_SESSION['siteuser']['userdata']         =$node['params'];
            
            $_SESSION['siteuser']['userdata']['login']=$node['basic'];

            if ($p=$this->_tree->GetChildsParam($node['id'], '%', true, $sp))
                {
                    $p=current($p);
                }

            $_SESSION['siteuser']['extuserdata']=$p['params'];

            return true;
            }
        else
            {
            return false;
            }
        }

    function execute_postoff() { $this->_common_obj->execute_postoff($this); }
    }
?>
