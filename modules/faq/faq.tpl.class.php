<?php
class faq_module_tpl extends x3_module
{
    //aliasSets
    public function __construct()
    { 
        global $TMS;
        $TMS->registerHandlerObj($this->_module_name, $this);   
    }
    


    function set_question_alias($params){
        $this->question_alias=$params[0];
    }
    
    // получает номер вопроса внутри категории
    // $params[0] = id вопроса
    // $params[1] = id категории вопроса
    // 
    function get_abs_num($params){
        global $TDB;
        if (!$params[1]) return;
        $items = $TDB->get_results('select `id` from `faq` where active=1 and cat_id='.$params[1].' order by -`id` ');
        $pos = 0;
        foreach ($items as $key => $item) {
            if ($item['id'] == $params[0]) {
                $pos=(int)$key;
                break;
            }
            
        }
        return (count($items)-$pos+1);
    }
    
    // получить инфо о категории
    // $params[0] = id категории
    // $params[1] = адрес страницы-сервера
    function get_category_params($params){

        $node=$this->_tree->getNodeInfo($params[0]);
        $node["Link"]=$params[1]."/~show_category/".$node["basic"];
        return $node;
        
    }
    
    
}
?>