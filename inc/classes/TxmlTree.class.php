<?php

/**
 * Создание XML-дерева структуры каталога без вложенности.
 *
 * Example:
 * <code>
 * <?php
 *     $XT =& new XMLTree();
 *     echo $XT->displayXML();
 * ?>
 * </code>
 */

class XMLTree
    extends Tree
    {
    /**
     * Строка, в которой собираем XML.
     * @var string
     */
    var $xml = '';
    var $images;
    var $a_params;
    var $allowed_params;
    var $params_redirect;
    var $fs_walk_params;
    var $end_leafs;
    var $preventChildDetection;


    /**
     * Конструктор класса.
     *
     * @param string $path полный путь к директории
     * @return XMLTree
     */
    function XMLTree($DBname, $TreeName,$is_numerical=false,$encoding='utf-8')
        {
        //инициализируем родительский класс
        $parent='Tree';
        $this->$parent($DBname, $TreeName, true, false,$is_numerical);
        $this->encoding=$encoding;
        }

    /*a_params  
    $param[param]={in xml param name}
    $allowed_params= массив только этих параметры будут выгружены в XML {вариант для редиректа параметров} 
    */
    function add_treexml_params($params, $allowed_params = null,$end_leafs=null)
        {
        $this->a_params      =$params;
        $this->allowed_params=$allowed_params;
        $this->end_leafs=$end_leafs;
        }

    function add_fs_walk_params($fs_walk_params)
        {
        /*    $this->fs_walk_params['path']         =$path;
            $this->fs_walk_params['files_ot']      =$files_ot;
            $this->fs_walk_params['image']          =$image;
            $this->fs_walk_params['allow_files_types']=$allow_files_types;
            $this->fs_walk_params['capture_type']='all';'files';dirs*/
            
                   $this->fs_walk_params=$fs_walk_params;
            
        }

    function set_params_redirect($params_redirect)
        {
        $this->params_redirect=$params_redirect;
        }

    
    function build_json_tree($anc, $root_source_name = '', $root_type = '_ROOT', $source_mode = 'tree')
        {
            
            
            if($this->fs_walk_params['exclude_path_prefix']&&$source_mode=='files')
            {
                 $anc=str_replace($this->fs_walk_params['exclude_path_prefix'],'',$anc);
            }

 
        $this->xml='{id:\''.$anc.'\', item:[';
        
        if (($anc == 0)and($source_mode=='tree'))
            {
            if(!$root_source_name){$root_source_name='ROOT';}else{$root_source_name=$this->ReadNodeParam(1,$root_source_name);        
            }
            
            
            $this->xml.="{id:'1', text:'$root_source_name', child:'1' ,obj_type:'$root_type'}";
            }
        else
            {
            if ($source_mode == 'tree')
                {
                    
                $this->_jwalk($anc);
                }
            else
                {

                //$anc здесь путь
                $this->_fs_walk($this->fs_walk_params['path']);
                }
            }

        $this->xml.=']}';
        }
        
        
        
        
        function _jwalk($anc)
        {
                

        if ((is_array($this->a_params)) && ($childs=$this->GetChildsParam($anc, $this->a_params, true)))
            {

            if(is_array($this->end_leafs)){
              $has_childs=array_filter($childs,array($this, "onexist"));
            }else{
            $has_childs=$childs;}
              
            $have_child=$this->HasChild(array_keys($has_childs));
            $cc=count($childs);
            $zp=',';
            foreach ($childs as $id => $obj_structs)
                {
                    $i++;
                if (!$have_child[$id]&&!($this->preventChildDetection))
                    {
                    $have='';
                    }
                else
                    {
                    $have=',child:1';
                    }

                if ($this->images[$obj_structs['obj_type']])
                    {
                        //из кеша
                        //    $image='m="' . $this->images[$obj_structs['obj_type']] . '"';
                    }
                else
                    {
                    $image='';
                    }

                foreach ($this->a_params as $akey => $apar)
                    {
                    if (($this->allowed_params) && (array_search($apar, $this->allowed_params) !== false))
                        {
                        $par_string.=' "' . $akey . '"="'.$obj_structs['params'][$aval]. '"';
                        }
                    }

                if ($this->params_redirect)
                    {
                    foreach ($this->params_redirect as $pkey => $pval)
                        {
                        $obj_structs[$pkey]=$obj_structs['params'][$pval];
                        }
                    }
                 
                 if($cc==$i)
                 {
                  $zp='';
                 }   
                    $this->xml.="{id:'$id', text:'".$obj_structs['basic']."'".$have.",obj_type:'".$obj_structs['obj_type']."'}".$zp;
                
                }

            return true;
            }
        else
            return false;
        }
        
    function build_xml_tree($anc, $root_source_name = '', $root_type = '_ROOT', $source_mode = 'tree')
        {
            
            
            if($this->fs_walk_params['exclude_path_prefix']&&$source_mode=='files')
            {
                 $anc=str_replace($this->fs_walk_params['exclude_path_prefix'],'',$anc);
            }
            
        $this->xml='<?xml version="1.0" encoding="'.$this->encoding.'"?>
            <t i="' . $anc . '">';
        
        if (($anc == 0)and($source_mode=='tree'))
            {
                 
                 if(!$root_source_name)
        {
            $root_source_name='ROOT';
        
        }else{
        
            $root_source_name=$this->ReadNodeParam(1,$root_source_name);
        
        }
        
            $this->xml.='<n i="1"  t="' . $root_source_name . '" o="' . $root_type . '"/>';
            }
        else
            {
            if ($source_mode == 'tree')
                {
                $this->_walk($anc);
                }
            else
                {

                //$anc здесь путь
                $this->_fs_walk($this->fs_walk_params['path']);
                }
            }

        $this->xml.='</t>';
        }


    /**
* обход директории
*/

    function _fs_walk($path)
        {
        $xml=&$this->xml;
       
       $zp=','; 

        if ($files=XFILES::files_list($path, $this->fs_walk_params['capture_type'], $this->fs_walk_params['allow_files_types'],0,0,true))
            {

            $cc=count($files); 
            foreach ($files as $file)                     
                {
                    if(is_dir($ndir=$path.'/'.$file)){
                        
                        $ot=$this->fs_walk_params['dir_ot'];

                        $p=",child:'1'";
                        $file=$ndir;
                    
                    }else{
                    
                        $ot=$this->fs_walk_params['files_ot'];                                                          
                        $p='';
                    }
                    
                    if($this->fs_walk_params['exclude_path_prefix']){
                        $filename=str_replace($this->fs_walk_params['exclude_path_prefix'],'',$file);
                    }else{$filename=$file;}
                    
                if($cc==$i)
                 {
                  $zp='';
                 }   
                    $this->xml.="{id:'$filename', text:'".basename ($file)."'".$p." ,obj_type:'".$ot."'}".$zp;         
                    
                }

            return $xml;
            }
        }

        function escapeforXML($str)
      {
          

          
          return  str_replace(array('"',chr(38),chr(1),"Ё"),array('','[and]','е','Е'),$str);
          
      }

    /**
     * Получение различных атрибутов (без вложенности).
     *
     * @access protected
     * @param string $path полный путь к каталогу
     * @return bool
     */
    function _walk($anc)
        {
        $xml=&$this->xml;
        
      

        if ((is_array($this->a_params)) && ($childs=$this->GetChildsParam($anc, $this->a_params, true)))
            {

            if(is_array($this->end_leafs)){
              $has_childs=array_filter($childs,array($this, "onexist"));
            }else{
            $has_childs=$childs;}
              
            $have_child=$this->HasChild(array_keys($has_childs));

            foreach ($childs as $id => $obj_structs)
                {
                if (!$have_child[$id])
                    {
                    $have='p="f"';
                    }
                else
                    {
                    $have='';
                    }

                if ($this->images[$obj_structs['obj_type']])
                    {
                        //из кеша
                        //    $image='m="' . $this->images[$obj_structs['obj_type']] . '"';
                    }
                else
                    {
                    $image='';
                    }

                foreach ($this->a_params as $akey => $apar)
                    {
                    if (($this->allowed_params) && (array_search($apar, $this->allowed_params) !== false))
                        {
                        $par_string.=' "' . $akey . '"="' .XMLTree::escapeforXML($obj_structs['params'][$aval]) . '"';
                        }
                    }

                if ($this->params_redirect)
                    {
                    foreach ($this->params_redirect as $pkey => $pval)
                        {
                        $obj_structs[$pkey]=$obj_structs['params'][$pval];
                        }
                    }

                $xml.='<n  ' . $have . '  i="' . $id . '" t="' . XMLTree::escapeforXML($obj_structs['basic']). '" o="'
                    . $obj_structs['obj_type'] . '" ' . $image . ' />';
                }

            return true;
            }
        else
            return false;
        }
        
        //псевдофункция для повеоки массиав элементов потомков
        function onexist($var) 
                {
                    if(!in_array($var['obj_type'],$this->end_leafs))
                    return true;
                }
        

    function set_images($images)
        {
        $this->images=$images;
        }

    /**
     * Отображение сгенерированного XML документа.
     */
    function displayXML()
        {
        if (!headers_sent())
            if (stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml'))
                {
                header('Content-type: application/xhtml+xml');
                }
            else
                {
                header('Content-type: text/xml');
                }

        print(Common::pack_data($this->xml));
        }
    } //endclass
?>