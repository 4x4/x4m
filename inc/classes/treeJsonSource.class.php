<?php
class treeJsonSource
    {
    var $_options;
    var $_tree;
    var $result;
    

    public function __construct($tree) 
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

    function resultTransformer($key_visual, $value)
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
        
                debugbreak();

        if($this->_options['emulate_root']&&$id==0)
        {
                $this->result['data_set']['rows'][1]=array('data'=>$this->_options['emulate_root'],'xmlkids'=>1,'image'=>$this->_options['imageMap']['_ROOT']);
                return;
        }
        
        if($this->_options['shownodesWithObjType'])$addWhere=array(array('@obj_type','=',$this->_options['shownodesWithObjType']));
        
        if ($childsNodes  =$this->_tree->selectParams('*')->selectStruct('*')->childs($id,1)->where($addWhere,true)->run())
            {
            while (list($id, $childNode)=each($childsNodes))
                {
                //структуры в результат
                if($id==1){$childNode['basic']='';}
                
                if (is_array($this->_options['columnsAsStructs']))
                    {
                        $localData=array();
                        
                        while (list($keyInVisibleModel, $tempValue)=each($this->_options['columnsAsStructs']))
                        {
                            if($this->_options['callfunc'][$tempValue])
                            {
                                $context=$this->_options['callfunc'][$tempValue][0];$func=$this->_options['callfunc'][$tempValue][1];
                                $localData[$keyInVisibleModel]=$context->$func($localData[$keyInVisibleModel],$id);
                                
                            }
                    
                        if (!($value=$this->resultTransformer($keyInVisibleModel, $childNode[$tempValue])))
                            {
                                $value=$childNode[$tempValue];
                            }                        
                            $localData[$keyInVisibleModel]=$value;                        
                        }
                        
                        if($this->_options['gridFormat'])
                        {                        
                            $r=array('id'=>$id,'data'=>$localData,'obj_type'=>$childNode['obj_type']);
                            
                            if(in_array($childNode['obj_type'],$this->_options['groups']))
                            {
                                if($this->source->HasChild($id))
                                {  
                                    $r['xmlkids']=1;
                                }
                                $r['image']='folder.gif';
                            }
                            $this->result['data_set']['rows'][$id]=$r;
                        
                        }else{
                        
                            $this->result['data_set'][$id]=$localData;
                        }
                        
                    reset ($this->_options['columnsAsStructs']);
                    }

                //параметры  в результат 
                $localData=array(); 
                
                while (list($keyInVisibleModel, $tempValue)=each($this->_options['columnsAsParameters']))
                    {

                    $localData[$keyInVisibleModel]=$childNode['params'][$tempValue];
                    
                    if($this->_options['callfunc'][$tempValue])
                    {
                    
                        $context=$this->_options['callfunc'][$tempValue][0];$func=$this->_options['callfunc'][$tempValue][1];
                        $localData[$keyInVisibleModel]=$context->$func($localData[$keyInVisibleModel],$id);
                        
                    }

                    if ($filter=$this->_options['filter'][$keyInVisibleModel])
                    {

                        switch ($filter['name'])
                            {
                                case 'fromtimestamp':
                                if($this->_options['gridFormat']){
                                    $localData[$keyInVisibleModel]= date($filter['format'],$localData[$keyInVisibleModel]);
                                }else{                                                        
                                    $this->result['data_set'][$id][$keyInVisibleModel]= date($filter['format'],$this->result['data_set'][$id][$keyInVisibleModel]);
                                }
                    
                                break;
                                
                                case 'cutwords':
                                $this->result['data_set'][$id][$keyInVisibleModel]=XSTRING::findncut_symbol_positon($this->result['data_set'][$id][$keyInVisibleModel], " ", $filter['count']);
                                break;
                            }
                        
                        }
                    
                    }
                    
                    
                      if($this->_options['gridFormat'])
                        {          
                            $vls=$localData;
                            XARRAY:: array_merge_plus($this->result['data_set']['rows'][$id]['data'],$vls,true);                             
                            
                        }else{

                            $this->result['data_set'][$id]+=$localData;
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