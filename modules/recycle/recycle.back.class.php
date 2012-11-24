<?php
class recycle_module_back
{
    
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function recycle_module_back()
        {
        $this->_module_name='recycle';
        }

        function common_call($front_call = null)
        {

            $this->_common_obj=&recycle_module_common::getInstance();
            $this->_tree      =&$this->_common_obj->obj_tree;
        }
        
        function executex($action,$acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }
        

        function list_recycled($parameters)
        {
                
            $TD                =Common::inc_module_factory('TTreeSource');      
            $options['startNode']=$parameters['anc_id'];
            $options['shownodesWithObjType']=array('_RECYCLEOBJ');
            $options['columnsAsParameters']=array('name' => 'Name','createTime'=>'createTime','module'=>'module');
            $options['filter']['createTime']=array('name'=>'fromtimestamp','format'=>'d-m-y H:i:s'); 
            $options['preventDots']=true;
            $options['columnsAsStructs']=array('id' => 'id');            
            $options['sequence'] = array('id','name','createTime','module');
            $options['gridFormat']=1;
        

            $TD->init_from_source($this->_tree );
            $TD->setOptions($options);
            $TD->CreateView(1);
        
            $this->result['data_set']=$TD->result['data_set'];
 
        
        }

       function clear()
       {
         $this->_tree->Cleartree();
       }
       
        
        function del_recycle($params)
        {

            if (is_array($params['id']))
            {

            foreach ($params['id'] as $id)
                {                    
                    if ($this->_tree->DelNode($id))
                    {
                            $this->result['isDel']=true;
                    }
                }
            }
        else
            {
                if ($this->_tree->DelNode($params['id']))
                {
                    $this->result['isDel']=true;
                }
            }   
            
        }
        
        function restore_it($params)
        {      
            if (is_array($params['id']))
            {
                foreach ($params['id'] as $id)
                {
            if($rnode=$this->_tree->getNodeInfo($id))
            {
                $m=Common::module_factory($rnode['params']['module'].'.back');
                $m->common_call();
                if($m->_tree->IsNodeExist($rnode['params']['ancestor']))
                {
                    $this->_tree->ExportNodes($id,$m->_tree->getBrachOT());
                    $m->_tree->ImportNodes($rnode['params']['ancestor'],$id,$this->_tree->EXPcache);
                    $this->_tree->DelNode($id);
                    $this->result['isRestor']=true;
                }            
            }            
        }
        }
        }
}   


?>
