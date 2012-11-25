<?php
class treeJsonSource
    {
    var $_options;
    var $result;
    var $_tree;

    public function treeJsonSource(&$tree) 
    { 
        $this->result=null;
        $this->_tree=$tree;
        $this->_options['imageMap']=array('_ROOT'=>'folder.gif');
    }


    /*  Установка опций
     
     $options['startNode'] - стартовая нода обхода дерева,она же топовая нода
     
     $options['shownodesWithObjType'] - массив нод разрешенных для показа с определенным objType     
     $options['endLeafs'] ->array('OBJTYPE')
     
     
     
     $options['transformResults']['column_name_in_visual_model']=array('_GROUP'=>'og.gif');         
      
      выбирать только если свойство соответствует значению значит этот объет выбираем
     $options['selectable']=array('if_column_name_in_visual_model'=>'value');         
     //пример:
     $options['columnsAsParameters']=array('LastMod'=>'LastModified');         
     $options['columnsAsStructs']=array('LastMod'=>'LastModified');         
     
     
     $options['callfunc']=array('LastMod'=>array(context,func));   
     
     $this->_options['limitDown']= интервал начала выборки  
     $this->_options['imageMap']=array('_GROUP'=>'folder.gif')
     $this->_options['count'] = количество(шаг) выборки
     $this->_options['type']= тип выборки AND и OR
    */

    function setOptions($options) { $this->_options=$options; }

    function result_transformer($key_visual, $value)
        {
        if (is_array($this->_options['transformResults']))
            {
            if (array_key_exists($key_visual, $this->_options['transformResults']))
                {
                return $this->_options['transformResults'][$key_visual][$value];
                }
            }

        return false;
        }

    
    function createView($id = 1)
        {
        
        $sp['obj_type']=$this->_options['shownodesWithObjType'];

        if($this->_options['emulate_root']&&$id==0)
        {
                $this->result['data_set']['rows'][1]=array('data'=>$this->_options['emulate_root'],'xmlkids'=>1,'image'=>$this->_options['imageMap']['_ROOT']);
                return;
        }
        
        
            $childsNodes  =$this->_tree->selectParams('*')->selectStruct('*')->childs($id)->run();
        
        
        //функция возврата вверх
        if ($id > 0)
            {
            /*$full_list
                =array_merge(array_keys($this->_options['columnsAsStructs']),
                             array_keys($this->_options['columnsAsParameters']));*/
                  $anc=$this->source->GetAncestor($id);
            }

            
        if ($childsNodes)
            {
            while (list($id, $child_node)=each($childsNodes))
                {
                //структуры в результат
                if($id==1){$child_node['basic']='';}
                
                if (is_array($this->_options['endLeafs']))
                    {
                    if (in_array($child_node['obj_type'], $this->_options['endLeafs']))
                        {
                        $local_a['_E_']=1;
                        }
                    }

                if (is_array($this->_options['columnsAsStructs']))
                    {
                        $local_a=array();
                        while (list($k_in_vis, $t_value)=each($this->_options['columnsAsStructs']))
                        {
                        //признак селективности

                        if (($child_node[$t_value]) && ($this->_options['selectable'][$k_in_vis])&& (in_array($child_node[$t_value],
                                                                 $this->_options['selectable'][$k_in_vis])))
                            {
                            //selectable
                            $local_a['_S_']=1;
                            }


                            if($this->_options['callfunc'][$t_value])
                            {
                            
                                $context=$this->_options['callfunc'][$t_value][0];$func=$this->_options['callfunc'][$t_value][1];
                                $local_a[$k_in_vis]=$context->$func($local_a[$k_in_vis],$id);
                                
                            }

                    
                        if (!($value=$this->result_transformer($k_in_vis, $child_node[$t_value])))
                            {
                                $value=$child_node[$t_value];
                            }                        
                            $local_a[$k_in_vis]=$value;                        
                            
                            
                        }
                        
                        if($this->_options['gridFormat'])
                        {                        
                            $r=array('id'=>$id,'data'=>$local_a,'obj_type'=>$child_node['obj_type']);
                            
                            if(in_array($child_node['obj_type'],$this->_options['groups']))
                            {
                                if($this->source->HasChild($id))
                                {  
                                    $r['xmlkids']=1;
                                }
                                $r['image']='folder.gif';
                            }
                            $this->result['data_set']['rows'][$id]=$r;
                        
                        }else{
                        
                            $this->result['data_set'][$id]=$local_a;
                        }
                        
                    reset ($this->_options['columnsAsStructs']);
                    }

                //параметры  в результат 
                $local_a=array(); 
                while (list($k_in_vis, $t_value)=each($this->_options['columnsAsParameters']))
                    {
                        
                    $local_a[$k_in_vis]=$child_node['params'][$t_value];
                    
                    
                    if($this->_options['callfunc'][$t_value])
                    {
                    
                        $context=$this->_options['callfunc'][$t_value][0];$func=$this->_options['callfunc'][$t_value][1];
                        $local_a[$k_in_vis]=$context->$func($local_a[$k_in_vis],$id);
                        
                    }
                    
                    

                    if ($filter=$this->_options['filter'][$k_in_vis])
                    {

                        switch ($filter['name'])
                            {
                                case 'fromtimestamp':
                                if($this->_options['gridFormat']){
                                    $local_a[$k_in_vis]= date($filter['format'],$local_a[$k_in_vis]);
                                }else{                                                        
                                $this->result['data_set'][$id][$k_in_vis]= date($filter['format'],$this->result['data_set'][$id][$k_in_vis]);
                                }
                    
                                break;
                                
                                case 'cutwords':
                           
                                $this->result['data_set'][$id][$k_in_vis]=XSTRING::findncut_symbol_positon($this->result['data_set'][$id][$k_in_vis], " ", $filter['count']);
                                break;
                            }
                        
                        }

                    if (($child_node[$t_value]) && ($this->_options['selectable'][$k_in_vis] == $child_node[$t_value]))
                        {
                        //selectable
                        $this->result['data_set'][$id]['_S_']=1;
                        }
                    }
                    
                    
                      if($this->_options['gridFormat'])
                        {          
                            $vls=$local_a;
                            XARRAY:: array_merge_plus($this->result['data_set']['rows'][$id]['data'],$vls,true);                             
                        }else{

                            $this->result['data_set'][$id]+=$local_a;
                        } 
                        
                      if($this->_options['gridFormat']) 
                      {                       
                        $nsq=null;
                        if(is_array($this->_options['sequence']))
                        {
                                foreach($this->_options['sequence'] as $sq)
                                {
                                    $nsq[]=$this->result['data_set']['rows'][$id]['data'][$sq];
                                }                        
                                
                                $this->result['data_set']['rows'][$id]['data']=$nsq;
                                
                        }else{
                            $this->result['data_set']['rows'][$id]['data']=array_values($this->result['data_set']['rows'][$id]['data']);
                        }
                      
                      }

                reset ($this->_options['columnsAsParameters']);
                }
            }
        }
    } //endclass
?>