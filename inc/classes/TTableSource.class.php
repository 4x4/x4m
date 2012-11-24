<?php
class TTableSource
    {
    var $pages_num;
    var $_options;

    function TTableSource()
        {
        $this->result=null;
        }


    /*  Установка опций
     
     $options['transformResults']['column_name_in_visual_model']=array('_GROUP'=>'og.gif');         
     $options['startRow'] - стартовая страница
     $options['rows_per_page']=x
     $options['table']=  //запрос указывается без лимитов
     $options['where']
     $options['columns']
     $options['customSqlQuery']     
     $options['filter']['field']=$filter['name']='cutwords'
                                        ['param']='20';
     $options['page_num_where']  -для того чтобы узнать кол-во страниц

 
    */

    function setOptions($options)
        {
        $this->_options=$options;
        }

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

    function CreateView()
        {
        global $TDB;

        if (!$this->_options['page_num_where'])
            {
            $this->_options['page_num_where']=$this->_options['where'];
            }

            $this->pages_num=Common::get_nav_pages($this->_options['table'], $this->_options['rows_per_page'],
                                               $this->_options['page_num_where']);

        if (!$this->_options['startRow'])
            {
            $this->_options['startRow']=0;
            }

        if($this->_options['rows_per_page'])
        {
            $limit=' limit ' . $this->_options['startRow'] . ' , ' . $this->_options['rows_per_page'];

        }else{

            $limit='';
        }

        if ($this->_options['where'])
            {
            $where=' where ' . $this->_options['where'];
            }
            
            
            if(!$this->_options['customSqlQuery'])
            {
                $query='select ' . implode(',',$this->_options['columns']) . ' from ' . $this->_options['table'] . $where . $limit;            
            }else
            {            
                $query=$this->_options['customSqlQuery'];
            }


        if ($results=$TDB->get_results($query))
            {
            if ($this->_options['filter'] or $this->_options['gridFormat'])
            {
                while (list($id, $record)=each($results))
                    {

                    if($this->_options['filter'])
                    {
                    foreach ($this->_options['filter'] as $field => $filter)
                        {
                        switch ($filter['name'])
                            {
                                case 'fromtimestamp':
                                $record[$field]= date($filter['format'],$record[$field]);
                                break;
                                
                                case 'cutwords':

                                $record[$field]=XSTRING::findncut_symbol_positon($record[$field], " ", $filter['count']);
                                break;
                            }
                            
                        }
                    }
                    $result[$id]=$record;                        
                    if($this->_options['gridFormat'])
                     {
                            foreach($record as $key => $val)
                            {                       
                        
                                    if($this->_options['callfunc'][$key])
                                        {
                                             $context=$this->_options['callfunc'][$key][0];$func=$this->_options['callfunc'][$key][1];
                                             $result[$id][$key]=$context->$func($val,$id);
                                        }
                                    
                             }
                             $nsq=null;        
                                 if(is_array($this->_options['sequence']))
                                    {
                                        foreach($this->_options['sequence'] as $sq)
                                        {
                                            $nsq[]=$result[$id][$sq];
                                        }                        
                                 
                                        $result[$id]=$nsq;
                                    }

                     $result['rows'][$result[$id][0]]=array('data'=>array_values($result[$id]));
                     
                     unset($result[$id]);
                     } 
                    
                    }
             
             return  $result;     
            }else{return $results;}
            }
        }
    } //endclass
?>