<?php
class subscribe_module_xfront extends subscribe_module_front
{
    function executex($action,$acontext)
    {          
        $this->_common_obj->execute(&$this, $action);            
        $acontext->result=$this->result;
    }
    
    function subscribe($params) 
    {
        Common::call_common_instance('pages');
        $pages = pages_module_common::getInstance();
        $params = array_merge($params, $pages->obj_tree->getNodeInfo($params['mod_id']));
        $pm = $pages->get_page_modules($params['params']['page'], null, 'subscribe');
        reset($pm); $pm = current($pm);
        $this->result['content'] = $this->subscriber_page(array_merge($params, array('aTemplate' => $pm['params']['aTemplate'])));
    }
    
    function getSubscribes($params)
    {
        if($subs = $this->_tree->GetChilds(1))
        {
            $this->result['subs'] = $subs;
        }
        else
        {
            $this->result['subs'] = FALSE;
        }
    }
}
?>