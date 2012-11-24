<?php
class logger
    {
    //singleton
    function &getInstance()
        {
        static $instance = array();

        if (!$instance)
            {
            $token          ='___logger_singleton___';
            $GLOBALS[$token]=true;
            $instance[0]    =new logger();
            unset ($GLOBALS[$token]);
            }

        return $instance[0];
        }

    function logger()
        {
        $token='___logger_singleton___';

        if (!array_key_exists($token, $GLOBALS))
            {
            trigger_error ('singleton can\'t be created initally');
            }
        }

    function getLogs($byField = null, $byValue = null, $lines = 5, $from = 0, $user_id = null)
        {
        global $TDB;

        if (($byField) && ($byValue))
            {
            if (!$user_id)
                {
                $where=$byField . '="' . $byValue . '"';
                }
            else
                {
                $where=$byField . '="' . $byValue . '" AND user_id=' . $user_id;
                }
            }
        elseif ($user_id)
            {
            $where='user_id=' . $user_id;
            }
        else
            {
            $where='';
            }

        return $TDB->SelectIN('*', 'log', $where, 'ORDER BY `time` DESC limit ' . $from . ',' . $lines);
        }

    function show_logs_on_main_page($xcall = false)
        {
        global $TMS,$_PATH;

    
        if (!$TMS->isSectionDefined('last_modification'))
            {
                    $lang=Common::get_module_lang('admin',$_SESSION['lang'],'run');        
                    $TMS->AddFileSection(Common::translate_to(file($_PATH['PATH_ADM'] . 'tpl/run.html'),$lang));
            }

        if ($logs=logger::getLogs())
            {
            Common::call_common_instance('users');
            $users     =&users_module_common::getInstance();
            $users_list=XARRAY::arr_to_lev($users->load_users_list(), 'id', 'params', 'Name');
            $mod_list  =XARRAY::arr_to_keyarr(admin_mod::get_module_list(), 'name', 'alias');
            $actions   =$users->collect_module_actions();
            $lm        ='';
             
            foreach ($logs as $log)
                {
                    if($action=$actions[$log['module']][$log['action']])
                    {
                    $log['action'] = $action;
                    }
                $log['module'] =$mod_list[$log['module']];
                $log['user']   =$users_list[$log['user_id']];
                $log['time']   =date("d.m.y G:i:s", $log['time']);

                $TMS->AddMassReplace('last_modification', $log);

                if (!$xcall)
                    {
                    $TMS->parseSection('last_modification', true);
                    }
                else
                    {
                    $lm.=$TMS->parseSection('last_modification',false,true);
                    }
                }
            if ($xcall)
                {
                  
                return $lm;
                }
            }
        }

    function logAction($module, $action, $params = null)
        {
        global $TDB;

        if (($module) && ($action))
            {
            $TDB->insertIN('log', array
                (
                'id'      => 'null',
                'module'  => $module,
                'action'  => $action,
                'params'  => serialize($params),
                'time'    => time(),
                'user_id' => $_SESSION['user']['id']
                ));
            }
        }
    } #endclass
?>