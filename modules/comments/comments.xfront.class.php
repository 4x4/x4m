<?php
class comments_module_xfront
    extends comments_module_front
    {
    function executex($action, $acontext)
        {
        $this->_common_obj->execute(&$this, $action);
        $acontext->result=$this->result;
        }

    function add_comment_news($params)
        {


        Common::call_common_instance('news');
        $news=news_module_common::getInstance();

        if ($n=$news->select_news($params['id']))
            {
            
            if($tread=$news->obj_tree->ReadNodeParam($n['ctg_id'],'Tread'))
            {
            
                $this->result["result_code"]=$this->_addcomment(array
                    (
                    'CobjectId' => $params['id'],
                    'tread'     => $tread,
                    'Module'    => 'news',
                    'Marker'    => $n['header']
                    ),$params['comment']);
            }
        }
        }

    function add_comment_catalog($params)
        {
            Common::call_common_instance('catalog');
            $catalog=catalog_module_common::getInstance();
            $marker=$catalog->obj_tree->ReadNodeParam($params['id'],'Name');
            
            $this->result["result_code"]=$this->_addcomment(array
            (
                'CobjectId' => $params['id'],
                'tread'     => $params['tread'],
                'Module'    => 'catalog',
                'Marker'    => $marker
            ),$params['comment']);
        }

    function add_comment($params)
        {
        $this->result["result_code"]=$this->_addcomment(array
            (
            'CobjectId' => $params['id'],
            'tread'     => $params['tread'],
            'Module'    => $params['Module'],
            'Marker'    => $params['Marker']
            ),                                          $params['comment']);
        }
    } #endclass
?>