<?php

//Описание структуры дерева
class recycle_module_common
    extends x3_module_common
    {
    
        static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new recycle_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        } 
        

    function recycle_module_common()
        {

            if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
            parent::x3_module_common();
            
            $this->set_obj_tree('recycle_container',true);
            $this->obj_tree->UniqueBasics(1);
            $this->obj_tree->setObject('_ROOT',array('LastModified'));                   
            $this->obj_tree->setObject('_RECYCLEOBJ',array('createTime','module','Name','ancestor'),'_ROOT');       
        }
    
    
        function recycle_it($recycle_obj_id,$recycle_treeobj,$recycle_module)
        {
           
            if($node= $recycle_treeobj->getNodeInfo($recycle_obj_id))
            {
                $recycle_treeobj->ExportNodes($recycle_obj_id,$recycle_treeobj->getBrachOT());
                
                   
                $r_obj=array(
                                 'Name'=>$node['params']['Name'],
                                 'createTime'=>time(),
                                 'module'=>$recycle_module,                             
                                 'ancestor'=>$node['ancestor']
                             );
                
                $id=$this->obj_tree->InitTreeOBJ(1,'%SAMEASID%','_RECYCLEOBJ',$r_obj,true);            
                $this->obj_tree->ImportNodes($id,$recycle_obj_id,$recycle_treeobj->EXPcache,$node);
                return $id;                                             
            }               
        }
            
    
    } #endclass    
?>