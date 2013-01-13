<?php


 /*  Установка опций
     
     
     
     $options['shownodesWithObjType'] - массив нод разрешенных для показа с определенным objType     
     $options['emulateRoot']=array('data'=>'','image');
     $options['endLeafs'] ->array('OBJTYPE')
     $options['imagesIcon']=array('_GROUP'=>'folder.gif');
     
     
     $options['columns']=array('>LastMod'=>                       //для параметров в params используем >paramName для структуры без стрелки
                                array('name'=>'LastModified',  //имя в визуальной модели если не задано берется имя заданное в параметрах
                                      'transformList'=>array('_GROUP'=>'og.gif');          // массив трансформинга переменной в случае если нужно преобразование данных
                                      'onAttribute'=>function($realDataValue,$id) use ($params) { return $transformedData}   //функция преобразования переменной , можно использовать базовые функции класса
                                      'onAttributeParams'=>array('')   //массив параметров для функции onattribute
                                );         
     
     $options['onRecord']=function($record) use ($params){}  // функция  выполняема на уровне трансформинга записи(все аттрибуты)
    
    
     $this->_options['limitDown']= интервал начала выборки       
     $this->_options['count'] = количество(шаг) выборки
    */

    
class treeJsonSource
    {
    var $_options;
    var $_tree;
    var $result;
    
    
    //статичексая базовая функция преобразование даты
    public static $fromTimeStamp;
    //статичексая базовая функция обрезка до нужного количества слов
    public static $cutWords;
    

    
    
    public function __construct($tree) 
    { 
        $this->_tree=$tree;
    }

   
    function setOptions($options) { $this->_options=$options; }
    
    function createView($id = 1)
        {

            
        if($this->_options['emulateRoot']&&$id==0)
        {
                $result['data_set']['rows'][1]=array('data'=>$this->_options['emulateRoot']['data'],'xmlkids'=>1,'image'=>$this->_options['emulateRoot']['image']);
                return $result;
        }
        
        if($this->_options['shownodesWithObjType'])$addWhere=array(array('@obj_type','=',$this->_options['shownodesWithObjType']));
        
        $addWhereNest=array(array('@obj_type','=',$this->_options['nested']));
        
        if($id==0){$curId=1;$lev=1;}else{$curId=$id;$lev=2;}
        $childsNodes  =$this->_tree->selectStruct('*')->childs($curId,$lev)->where($addWhereNest,true)->asTree()->run();
     
        if ($nodes  =$this->_tree->selectParams('*')->selectStruct('*')->childs($id,1)->where($addWhere,true)->run())
            {
             foreach ($nodes as $node)
                {             
                   
                    $nodeId=$node['id'];
                   
                    if (is_array($this->_options['columns']))
                    {
                        
                        
                        reset($this->_options['columns']);
                        while (list($key, $tempValue)=each($this->_options['columns']))
                        {
                            
                                if($key[0] == '>'){$paramedKey=true; $key=substr($key, 1);}else{$paramedKey=false;}
                                
                                //замена имени
                                if(!$tempValue['name'])
                                {
                                    $tempValue['name']=$key;
                                }
                                
                                $extData[$tempValue['name']]=($paramedKey)?$node['params'][$key]:$node[$key];
                                
                                //трансформ по листу
                                if($tempValue['transformList'])
                                {
                                    $extData[$tempValue['name']]=$tempValue['transformList'][$extData[$tempValue['name']]];
                                }
                               
                               
                                //трансформ по функции
                                if($tempValue['onAttribute'])
                                {                                           
                                    $extData[$tempValue['name']]=$tempValue['onAttribute']($tempValue['onAttributeParams'],$extData[$tempValue['name']],$nodeId);
                                } 
                           
                                                        
                        }
                        
                        
                        if($this->_options['onRecord'])
                        {
                          $extData=$this->_options['onRecord']($extData);
                        }
                        

                        
                        if($this->_options['gridFormat'])
                        {                   
                         
                            $r=array('id'=>$nodeId,'image'=>$this->_options['imagesIcon'][$node['obj_type']],'data'=>array_values($extData),'obj_type'=>$node['obj_type']);                        
                            
                            if($childsNodes->hasChilds($nodeId))
                                {  
                                    $r['xmlkids']=1;
                                }
                                

                            $result['data_set']['rows'][$nodeId]=$r;
                        
                        }else{
                        
                            $result['data_set'][$nodeId]=$extData;
                        }
                    }
                    
            }
            
            return $result;
        }
        }
        
        
    } //endclass
    
    
  treeJsonSource::$fromTimeStamp=function($params,$value,$id)
    {
        return date($params['format'],$value);
    };
    
    
  treeJsonSource::$cutWords=function($params,$value)
    {        
        return  XSTRING::findncut_symbol_positon($value, " ", $params['count']);
    }
    

    
?>