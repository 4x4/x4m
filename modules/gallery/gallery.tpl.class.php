<?php
class gallery_module_tpl extends x3_module
{
    //aliasSets

    public function __construct()
    {
        global $TMS;
        
        $TMS->registerHandlerObj($this->_module_name, $this);
    }

    function get_link($params){
        global $TPA;
        return $TPA->page_link.'/~show_gallery/'.$params[0];
    }
    
/*
    
    Вычищает путь страницы по галерее
    по умолчанию чистка полная
    если ($params[0] == true) - то оставляет только последний элемент

*/    
    
    function clear_branch($params){
      
      if ($params[0]) {
        $path = array($this->bones_path[0]);
        $bones=array();
        each($this->bones_path);
        while (list($key,$value) = each($this->bones_path)) {
            $bones[] = $value["basic"];
        }
        $node=array_pop($this->bones_path);
        $node["basic"]=implode('/',$bones);
        $path[] = $node;
        
        $this->bones_path=$path;
          
      } else $this->bones_path = null;
      
  }      
    
}
?>