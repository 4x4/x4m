?
<?php
//content class
class comments_module_front
    extends comments_module_tpl
    {
    var $_module_name;
    var $_common_obj;
    var $_tree;
    var $last_added_comment;
    //только для callback
    var $current = array();

    function comments_module_front()
        {
        global $TMS;
        $this->_module_name='comments';
        //true так как front_call
        $this->_common_obj =comments_module_common::getInstance(true);
        $this->_tree       =$this->_common_obj->obj_tree;
        parent::__construct();
        $TMS->registerHandlerObj($this->_module_name, $this);
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

    function show_last_comments($params)
        {
        global $TMS;

        if ($comments=$this->get_last_comments($params))
            {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));

            foreach ($comments as $comment)
                {
                $TMS->AddMassReplace('_last_comment', $comment);
                $TMS->parseSection('_last_comment', true);
                }

            return $TMS->parseSection('_last_comments');
            }
        }

    function get_last_comments($params)
        {
        global $TDB;

        if ($params['count'])
            {
            if ($params["cobj"])
                {
                $cobjects=$params["cobj"];
                }
            else
                {
                if ($params['treads'])
                    {
                    if ($treads=explode(',', $params['treads']))
                        {
                        $cobjects=array();

                        foreach ($treads as $tread_id)
                            {
                            if ($tchilds=$this->_tree->getChilds($tread_id))
                                {
                                $cobjects=array_merge($cobjects, XARRAY::askeyval($tchilds, 'id'));
                                }
                            }
                        }
                    }
                }

            if ($cobjects)
                {
                ($params["sl"]) ? $offset=strval($params["sl"]) : $offset="0";
                ($params["asc"] == "DSC") ? $order='-' : $order='';

                $query='SELECT * FROM comments WHERE Active=1 AND cid in(' . implode(',',
                                                                                     $cobjects) . ') ORDER BY ' . $order
                    . 'date desc LIMIT ' . $offset . ', ' . $params['count'] . ';';

                if ($r=$TDB->get_results($query))
                    {
                    return $r;
                    }
                }
            else
                return array();
            }
        }

    function show_guestbook($params)
        {
        global $TMS, $TPA, $REQUEST_ASSOC, $TDB;

        $sl          =isset($REQUEST_ASSOC['sl']) ? (int)$REQUEST_ASSOC['sl'] : 0;
        $params["sl"]=$sl;

        $node        =$this->_tree->getNodeInfo($params["treads"]);
        $cobjects    =$this->_tree->GetChilds($params["treads"]);

        $found       =$this->_tree->Search(array
            (
            'CobjectId' => $TPA->page_node["id"],
            'Module'    => 'pages'
            ),                             false);

        $cobj=array();

        for ($i=0; $i < count($found); $i++)
            {
            for ($j=0; $j < count($cobjects); $j++)
                if ($found[$i] == $cobjects[$j]["id"])
                    {
                    $cobj[]=$found[$i];
                    break;
                    }
            }

        if (is_array($cobj) && count($cobj))
            {
            $query='select count(*) as xcount from comments where Active=1 and cid in (' . implode(',', $cobj) . ');';

            if ($r=$TDB->get_results($query))
                {
                $total=(int)$r[1]["xcount"];
                }
            else
                $total=0;
            }
        else
            $total=0;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
        $TMS->AddMassReplace("_send_comment_form", array
            (
            "tread_id" => $params["treads"],
            "tread"    => $node["basic"],
            "node_id"  => $TPA->page_node["id"],
            ));

        $TMS->AddMassReplace("_guestbook", array
            (
            "Alias"      => $node["params"]["Alias"],
            "Total"      => $total,
            "Moderation" => $node["params"]["Moderation"],
            "Captcha"    => $node["params"]["Captcha"]
            ));

        if ($total)
            {
            $comments=$this->get_last_comments($params);
            Common::parse_nav_pages($total, $params["count"], $sl, $TPA->page_link . '/~guestbook');

            foreach ($comments as $comment)
                {
                $TMS->AddMassReplace('_comment', $comment);
                $TMS->parseSection('_comment', true);
                }
            }
        else
            {
            $TMS->parseSection('_empty', true);
            }

        $TMS->parseSection('_send_comment_form', true);
        return $TMS->parseSection('_guestbook');
        }


    //xfront compat
    /*
    *   1-tread not active 
    *   2-cobject not active or closed
    */

    function _get_comments($params)
        {
        global $TDB;

        if ($tread=$this->_common_obj->get_tread_by_name($params['tread']))
            {
            if ($tread['params']['Active'])
                {
                if ($cobj=$this->_common_obj->get_cobject($tread['id'], $params['CobjectId']))
                    {
                    if ($cobj['params']['Active'] && !($cobj['params']['Closed']))
                        {
                        ($params["count"]) ? $limit=' limit 0, ' . $params['count'] : $limit='';
                        ($params["asc"] == "DSC") ? $order='-' : $order='';

                        return $TDB->SelectIN('*', 'comments', 'cid=' . $cobj['id'] . ' and Active=1',
                                              'order by ' . $order . 'Date ' . $tread['params']['TreadSort'] . $limit);
                        }
                    else
                        {
                        return 2;
                        }
                    }
                }
            else
                {
                return 1;
                }
            }
        }

    function check_captcha_code($params)
        {
        if ($_SESSION['captcha'][$params['tread']] == $params['captcha'])
            {
            $this->result['captcha']=true;
            return true;
            }
        else
            {
            $this->result['captcha']=false;
            return false;
            }
        }


    /*
    * Tread индефицируется по basic'у
    * ('_COBJECT',array('LastModified','Module','Marker','Active','Closed','CobjectId'),'_TREAD');
    *  return 
    *  1 - tread  do not exists       
    *  2 -tread closed
    *  3 - comment success
    */

    function _addcomment($co_params, $c_params)
        {
        if ($tread=$this->_common_obj->get_tread_by_name($co_params['tread']))
            {
            if ($tread["params"]["Captcha"])
                {
                if (isset($c_params["captcha_" . $tread["id"]]))
                    {
                    if ($this->check_captcha_code(array
                        (
                        "tread"   => (int)$tread["id"],
                        "captcha" => $c_params["captcha_" . $tread["id"]]
                        )))
                        {
                        unset ($c_params["captcha_" . $tread["id"]]);
                        unset ($_SESSION["captcha"][$tread["id"]]);
                        }
                    else
                        return 3;
                    }
                }
            else
                unset ($c_params["captcha_" . $tread["id"]]);

            if ($tread['params']['Active'])
                {
                if (!$cobj=$this->_common_obj->get_cobject($tread['id'], $co_params['CobjectId']))
                    {
                    $cid=$this->init_cobject($tread['id'], $co_params);
                    }
                else
                    {
                    $cid=$cobj['id'];
                    }

                if ($tread['params']['Moderation'])
                    {
                    $c_params['Active']=0;
                    }
                else
                    {
                    $c_params['Active']=1;
                    }

                if (!isset($c_params["Header"]))
                    $c_params["Header"]='No subject';

                if ($_SESSION["siteuser"]["authorized"])
                    {
                    $c_params["UserName"]=$_SESSION["siteuser"]["userdata"]["Name"]; //!! CHECK FUSERS TROUBLE
                    }

                if ($this->last_added_comment=$this->init_comment($cid, $c_params))
                    {
                    $_SESSION['comments']['last_comment']=array
                        (
                        'tread'     => $co_params['tread'],
                        'commentid' => $this->last_added_comment
                        );

                    $this->result["comment_added"]=true;
                    return 0;
                    }
                }
            else
                {
                return 2;
                }
            }

        return 1;
        }

    function init_comment($id, $data)
        {
        global $TDB;

        // проверка на авторизацию. сессия после логаута почему-то не вычищается
        if ($_SESSION["siteuser"]["authorized"])
            {
            if (!$data['UserId'])
                $data['UserId']=$_SESSION['siteuser']['id'];

            if (!$data['UserName'])
                $data['UserName']=$_SESSION['siteuser']['Name'];
            }

        //DebugBreak();
        if (!$data['UserId'])
            $data['UserId']='NULL';

        if (!$data['ReplyId'])
            $data['ReplyId']='NULL';

        $data['Date']    =$data['LastModified']=time();
        $data['cid']     =$id;

        $data['Message'] =XHTML::xss_clean($data['Message']);
        $data['UserName']=XHTML::xss_clean($data['UserName']);
        $data['Header']  =XHTML::xss_clean($data['Header']);

        return $TDB->InsertIN('comments', $data);
        }

    function init_cobject($id, $data)
        {
        $data['LastModified']=time();
        $data['Active']      =1;
        $data['Closed']      ='';
        return $this->_tree->InitTreeOBJ($id, '%SAMEASID%', '_COBJECT', $data, true);
        }
    } #endclass
?>