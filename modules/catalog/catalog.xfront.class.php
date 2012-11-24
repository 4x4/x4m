<?php
class catalog_module_xfront extends catalog_module_front
{
    function executex($action, $acontext)
    {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result=$this->result;
    }
    
    function remove_favorite($params)
    {   
            global $TDB;                        
            $TDB->query('delete from favorite where obj_id='.$params['id']);
    
    }
    
    /*
    * Добавить к сравнению 
    * id = индефикатор объекта
    */    
    function add_comparse($params)
        {
            
            if (!isset($_SESSION['catalog']['comparsedata']))
            $_SESSION['catalog']['comparsedata']=array();

        if (is_array($params['id']))
            {
            foreach ($params['id'] as $ps)
                {
                $property_set = $this->_tree->ReadNodeParam($ps, 'Property_set');

                if ($_SESSION['catalog']['comparselast'] != $property_set)
                    {
                    $_SESSION['catalog']['comparselast']=$property_set;
                    unset ($_SESSION['catalog']['comparsedata']);
                    }

                $_SESSION['catalog']['comparsedata'][$ps]=$ps;
                }
            }
        else
            {
            $property_set=$this->_tree->ReadNodeParam($params['id'], 'Property_set');

            if ($_SESSION['catalog']['comparselast'] != $property_set)
                {
                $_SESSION['catalog']['comparselast']=$property_set;
                unset ($_SESSION['catalog']['comparsedata']);
                }

            $_SESSION['catalog']['comparsedata'][$params['id']]=$params['id'];
            }
        }
        
        
        
    function count_comparse() { return $this->result['count']=count($_SESSION['catalog']['comparsedata']); }

    

    
    function add_vote($params){


        global $TDB;
        $ip = ip2long($_SERVER['REMOTE_ADDR']);
        $a = $TDB->get_results('SELECT id FROM rate_count WHERE ip = '.$ip.' AND time-'.time().'<86400 AND id='.$params['id']);
        if (!$a)
        {        
            $node = $this->_tree->getNodeInfo($params['id']);
            $pv = $node['params']['people'] + 1;
            $rate = $node['params']['stars'] + $params['rate'];
            $this->_tree->WriteNodeParam($params['id'],'people',$pv);
            $this->_tree->WriteNodeParam($params['id'],'stars',$rate);
            $TDB->InsertIN('rate_count',array('time'=>time(),'ip'=>$ip,'id'=>$params['id']));
            $this->result['status'] = 0;
        }
        else{
            $this->result['status'] = 1;
        }
        
    }
    
    
    
    function add_to_favorite($params)
    {

        global $TDB;
        $this->result['error']=0;
        
        $d=$TDB->SelectIN('count(id) as idc' ,'favorite','user_id='.$_SESSION['siteuser']['id'].' and obj_id='.$params['id']);
        if(!$d[1]['idc']&&$_SESSION['siteuser']['id'])
        {
            $TDB->InsertIN('favorite',array('id'=>'null','user_id'=>$_SESSION['siteuser']['id'],'obj_id'=>$params['id']));
        }else{            
            $this->result['error']=1;
        }
        
    }
    
}
?>