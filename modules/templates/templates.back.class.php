<?php
class templates_module_back
    {
    var $result;
    var $_common_obj;  
    var $_module_name='templates';
    var $_tree=null;
    var $lct;
    
    function templates_module_back()
        {   
            $this->common_call();
            $_module_name='templates';

            $this->_common_obj->refresh_main_tpls();
            $this->_common_obj=null;
            
        }

       
       function common_call()
             {
                $this->_common_obj=templates_module_common::getInstance();
                 if(!$this->_tree)
                {             
            //proxy for tree
                $this->_tree =$this->_common_obj->obj_tree;
                }
             }        
        
            function execute($action, $parameters = null)
            {        
                
                $this->common_call();                        
                return   $this->_common_obj->execute($this,$action,$parameters);
            }

            function executex($action,$acontext)
                {

                    $this->common_call();
                    $this->_common_obj->execute($this, $action);
                    $acontext->lct = $this->lct;   
                    $acontext->result = $this->result;
                }
              

    
    function  save_template($parameters)    
    {
        global $_PATH;  

        $error='';
        if(file_exists($file_path=$_PATH['PATH_TEMPLATES'].$parameters['fullpath']))
        {
            $expl=explode('/',$parameters['fullpath']);
            
            //базовый шаблон
            if($expl[1]=='_index.html')
            {
                
                //находим в дереве
                $id=$this->_tree->FindbyBasic(1,$expl[0]);
                $this->_common_obj->reinit_tgroup($id[0], '%SAME%', $parameters); 
                
            }
            
            //запись файла
            $handle = fopen($file_path, "w");                                    
             
              if(!fwrite($handle,$parameters['tplbody']))
              {              
                $error='Error write';
              }else{
                  if($expl[1]=='_index.html') {
               $this->_common_obj->process_main_template($expl[0]);
              }  
              }
            
            
            if($error){            
                $this->result['saved']=false;
                $this->result['error']=$error;
            }else{
             
                $this->result['saved']=true;
            }
            
            fclose($handle);                   
        }
    }
    
    
     function load_template($parameters)
     {
            global $TMS, $_PATH;     
               
                //главный шаблон
 
                if(file_exists($fullpath=$_PATH['PATH_TEMPLATES'].$parameters['tpl_id']))
                {                     
                $expl=explode('/',$parameters['tpl_id']);
              //базовый шаблон
                    if($expl[1]=='_index.html')
                    {            
                        //находим в дереве
                        $id=$this->_tree->FindbyBasic(1,$expl[0],true);
                        //достаем из кеша функции findbyBasic;
                        $data['Name']=$this->_tree->LastResult['params']['Name'];
                    }           
                                        
                     $data['tplbody']=implode(file($fullpath));                 
                     $data['fullpath']=$parameters['tpl_id'];
                     $this->result['tpldata']=$data;
                     
                }                
         
     }
        
        
    function edit_slotz_alias($parameters)
        {
        global $TMS,$Adm;
                            
        $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'tpl_slotz_alias')),true),true);  
        if ($slotz=$this->_tree->GetChildsParam($parameters['tpl_id'], array('SlotAlias'), true))
            {
            foreach ($slotz as $slot_id => $slot)
                {
                $MR=array
                    (
                    'slot_id'    => $slot_id,
                    'slot_name'  => $slot['basic'],
                    'slot_alias' => $slot['params']['SlotAlias'],
                    );

                $TMS->AddMassReplace('one_slot', $MR);
                $TMS->parseSection('one_slot', true);
                }

                
            $this->result['slot_alias']=$TMS->parseSection('tpl_slotz');
            return true;
            }
        }
        
        
    function save_slotz_alias($parameters)
    {
      
        if(is_array($parameters['slotz']))
        {
             foreach($parameters['slotz'] as $slot=>$alias)
             {
                      
                 $this->_tree->WriteNodeParam((int)substr($slot,5,strlen($slot)-4),'SlotAlias',$alias);
             
             }
        
        }        
    
    }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this,array('_common_obj','_tree'));    
        }
    }



?>
