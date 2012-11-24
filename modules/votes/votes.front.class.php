<?php
class votes_module_front
{
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function votes_module_front()
    {
        $this->_module_name='votes';
        $this->_common_obj =& votes_module_common::getInstance(true);
        $this->_tree =& $this->_common_obj->obj_tree;
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

    function get_variants($vote_id)
    {
        global $TDB;
        $q = 'SELECT var_id, text FROM votes_params WHERE vote_id = ' . $vote_id . ' ORDER BY ord';
        return $TDB->get_results($q);
    }

    function check_user($vote_id)
    {
        global $TDB;
       
        $ip = getenv("REMOTE_ADDR");
        $query = 'SELECT * FROM votes_server WHERE vote_id = ' . $vote_id . ' AND ip="' . $ip . '"';
        
        if($r = $TDB->get_results($query)) 
            return false;
        
        return true;
    }

    function check_date($start, $end)
    {
        $s = explode('-', $start);
        $e = explode('-', $end);

        $ds = mktime(0, 0, 0, $s[1], $s[0], $s[2]);
        $de = mktime(0, 0, 0, $e[1], $e[0], $e[2]);
        $dt = time();

        if(($dt >= $ds) && ($dt <= $de))
            return true;

        return false;
    }

    function addvote($params)
    {
        global $TDB,$TMS, $REQUEST_ASSOC;

        if(!$a = $this->_tree->getNodeParam($REQUEST_ASSOC['id'])) return '';

        if($REQUEST_ASSOC['id'])
        {
            if($this->check_user($REQUEST_ASSOC['id']))
            {            
                $TMS->parseSection('thanks');
            }
            else
            {                
                return $this->showvresult($params, "nothanks"); 
            }
             
            $vars = array();
            
            if($a['vote_multiple'])
            {
                foreach ($_POST as $k => $v)
                {
                    $t = explode('_', $k);
                    switch($t[0])
                    {
                        case 'var':
                            if($v)
                                $vars[] = array('variant' => $t[1], 'text' => '');
                            break;
                        case 'other':
                            $vars[] = array('variant' => $t[0],'text' => $v);
                            break;
                        default: 
                            break;
                    }
                }
            }
            else
            {
                switch($_POST['variant'])
                {
                    case 'other':
                        $vars[] = array('variant' => $_POST['variant'],'text' => mysql_real_escape_string( $_POST['other']));
                        break;
                    default:
                        $vars[] = array('variant' => (int) str_replace('var_', '',   $_POST['variant']),'text' => '');
                        break;
                }
            }

            $query = 'INSERT INTO votes_server VALUES ';
            $ip = getenv("REMOTE_ADDR");
            $q = array();
            
            foreach($vars as $v)
            {
                $q[] = "(NULL, {$REQUEST_ASSOC['id']}, '{$v['variant']}', 1, '{$v['text']}', '{$ip}')";
            }

            if(!empty($q))
            {
                $query .= implode(',', $q) . ";";
                $TDB->get_results($query);
            }
            $params['id'] = $REQUEST_ASSOC['id'];    
            return $this->showvresult($params, "thanks");
        }
    }

    function show_vote($params)
    {
        global $TMS, $TDB;
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('votes', $params['Template']));
        $a = $this->_tree->getNodeInfo($params['votesId']);
        Common::call_common_instance('pages');
        if ($a["id"] === null) return $TMS->parseSection('vote_deleted');;
        $pages =& pages_module_common::getInstance();
        $link = $pages->create_page_path($params['page']);
        $a['results_link'] = $link . '/~showvresult/id/' . $params['votesId'];
        $a['action'] = $link . '/~addvote/id/' . $params['votesId'];

        if($this->check_date($a['params']['date1'], $a['params']['date2']))
        {
            $variants = $this->get_variants($params['votesId']);
            $multi = 0;
            if($a['params']['vote_multiple'] == 1)
            {
                $rrr='check';                             
                $multi = 1;
            }
            else
            {
                $rrr='radio';
            }
            $TMS->AddReplace('multiple', 'value', $multi);
            $TMS->parseSection('multiple', true);
            $tmp = '';
            foreach($variants as $v)
            {
                $TMS->AddMassReplace($rrr, $v);
                $tmp .= $TMS->parseSection($rrr);
            }
            $TMS->AddReplace('vote','inputs', $tmp);
            
            if($a['params']['another_variant'] == 1)
            {
                $TMS->AddMassReplace('another_variant_' . $rrr, $v);
                $tmp = $TMS->parseSection('another_variant_'.$rrr);
                $TMS->AddReplace('vote', 'another', $tmp);
            }

            $TMS->AddMassReplace('vote', $a['params']);
            $TMS->AddMassReplace('vote', $a);
            return $TMS->parseSection('vote');
        }
        else
        {
            $a['show_results'] = $link . '/~showvresult/id/' . $params['votesId'];
            $TMS->AddMassReplace('vote_completed', $a);
            $TMS->AddMassReplace('vote_completed', $a['params']);
            return $menu = $TMS->parseSection('vote_completed');
        }
    }

    function showvresult($params, $info = null)
    { 
        global $TMS, $TDB, $REQUEST_ASSOC;
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('votes', $params['Template']));

        if($params['id'] > 0)
        {
            $id = $params['id'];
        }
        else
        {
            $id = abs((int)$REQUEST_ASSOC['id']);
        }

        if($id)
        {
            if(!$a = $this->_tree->getNodeInfo($id)) return '';

            if($info)
            {
                $TMS->parseSection($info);    
            }

            $this->generate_results_stats($REQUEST_ASSOC['id']);
            $TMS->AddMassReplace('results', $a);
            $TMS->AddMassReplace('results', $a['params']);
            $results = $TMS->parseSection('results');
        }
        return $results;
    }

    function generate_results_stats($vote_id)
    {
        global $TMS, $TDB;
        $var = $this->get_variants($vote_id);
        $vote = $this->_tree->getNodeInfo($vote_id);
        $count = 0;
        
        foreach ($var as $k => $v)
        {
            $ind = $v['var_id']; 
            $r = $TDB->get_results('SELECT Sum(value) as sumv FROM `votes_server` WHERE vote_id =' . $vote_id . ' AND var_id =' . $ind . ' order by sumv');
            $var[$k]['count']  = (int) $r[1]['sumv'];
            $rr[$ind]['count'] = (int) $r[1]['sumv'];
            $prc[] = (int) $r[1]['sumv'];
            $rr[$ind]['text'] = $v['text'];
            $count += $r[1]['sumv'];
        }
        
        if ($vote['params']['another_variant']){
            $r = $TDB->get_results('SELECT Sum(value) as sumv FROM `votes_server` WHERE vote_id =' . $vote_id . ' AND var_id = "other" GROUP BY var_id'); 
            $ind = sizeof($rr);
            $rr['other']['count'] = $r[1]['sumv'];
            $rr['other']['text'] = 'Другой вариант';
            $count += $r[1]['sumv'];
        }

        if ($count == 0) $count = 1;
        
        rsort($rr);

        foreach($rr as $w)
        {
            $w['percent'] = round($w['count'] / $count * 1000)/10;
            $TMS->AddMassReplace('result_row', $w);
            $TMS->parseSection('result_row', true);
        }

        if( $vote['params']['another_variant'] && $r = $TDB->get_results('SELECT text, Sum(value) as sumv FROM `votes_server` WHERE vote_id =' . $vote_id . ' AND var_id = "other" GROUP BY text'))
        {
            foreach($r as $w)
            {
                $q['count'] = $w['sumv'];
                $q['text']  = $w['text'];
                $TMS->AddMassReplace('other_variants', $q);
                $TMS->parseSection('other_variants', true);
            }
            $TMS->parseSection('result_other', true);
        }
    }

    function show_vote_server($params) { }

    function show_random_vote($params)
    {
        $items = $this->_tree->GetChilds($params['category']);
        if(sizeof($items) > 0)
        {
            $num = rand(0, sizeof($items) - 1);
            $rand = $items[$num]['id'];
            $params['votesId'] = $rand;
            return $this->show_vote($params);
        }
    }
}
?>