<?php
class news_module_tpl extends x3_module
{
    //aliasSets
    public function __construct()
    { 
        global $TMS;
        $TMS->registerHandlerObj($this->_module_name, $this);   
    }
    
    
    public function get_rss_link($params)
    {
        global $TPA;
        
            $cat_id = ($params[0]) ? $params[0] :$this->current['id'];
            if( $server = ($params[1]) ? $params[1] :$this->current['news_server_page']) return $server.'/~rss/id/'.$cat_id;
    
    }
    
    
    public function get_author($news_id)
    {
        
    }
    
    

}
?>