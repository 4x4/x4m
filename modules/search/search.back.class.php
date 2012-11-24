<?php

require_once($GLOBALS['_PATH']['PATH_INC'] . '/crawl/phpcrawler.class.php');
require_once($GLOBALS['_PATH']['PATH_INC'] . '/crawl/phpcrawlerpagerequest.class.php');

class XTRcrawler extends PHPCrawler
{
    public $pages = array();
    
    function XTRcrawler() {}

    function handlePageData(&$page_data)
    {
        global $TDB;
        
        if(strpos($page_data['url'], 'sec_pic.php') || strpos($page_data['url'], 'image.php'))
            return false; 
        
        if($page_data['insite_status']) 
            $page_data['http_status_code'] = $page_data['insite_status'];
           

        preg_match('/<title>(.*?)<\/title>/', $page_data['source'], $result);
        
        $title = ($result[1] && $page_data['http_status_code'] == 200) ? $result[1] : '';

        $pattern = '/<\!--<index>-->(.+)<\!--<\/index>-->/is';
         
        if($page_data['http_status_code'] == 200 && preg_match_all($pattern, $page_data['source'], $matches))
        {
            list(, $body) = each($matches[1]);

            $body = iconv('UTF-8', 'windows-1251', $body);
            $body  = mysql_escape_string(strip_tags(preg_replace(array('/\s+/', '#<script[^>]*>.*?</script>#is'), array(' ', ''), $body)));
            $index = XTRcrawler::Words2BaseForm(preg_replace('/\s+/', ' ', $body));
        }

        
        
        
        
        $TDB->insertIN('search_pages_index', array(
            'id'     => 'null',
            'url'    => $page_data['url'],
            'title'  => $title,
            'body'   => iconv('windows-1251', 'UTF-8', $body),
            'index'  => iconv('windows-1251', 'UTF-8', $index),
            'status' => $page_data['http_status_code']
        ));
    
        
        $this->pages[] = array(
            'url'            => $page_data['url'],
            'bytes_recieved' => XFILES::format_size($page_data['bytes_received']),
            'body' => $title,
            'status'         => $page_data['http_status_code']            
        );
    }
     function Words2BaseForm($text)
    {
        global $_COMMON_SITE_CONF;
        static $dict_bundle, $morphy;
              
        require_once($GLOBALS['_PATH']['PATH_INC'] . 'phpMorphy/src/common.php');
         
        if(!$dict_bundle)
        {
         
            $encoding = $_COMMON_SITE_CONF['encodings'][$_COMMON_SITE_CONF['site_encoding']];
            $dir = $GLOBALS['_PATH']['PATH_INC'] . 'phpMorphy/dicts/';
            $dict_bundle = new phpMorphy_FilesBundle($dir, 'rus');
        }
        
        if(!$morphy)
        {
            $opts = array(
                'storage'           => PHPMORPHY_STORAGE_MEM,
                'with_gramtab'      => false,
                'predict_by_suffix' => true, 
                'predict_by_db'     => true
            );
      
            $morphy = new phpMorphy($dict_bundle, $opts);
        }
    
        setlocale(LC_CTYPE, array('ru_RU.CP1251', 'rus_RUS.CP1251', 'rus_RUS.CP1251', 'Russian_Russia.1251'));
    
        $words = preg_replace('#\[.*\]#isU', '', $text);
        $words = preg_split('#\s|[,.:;В«В»!?"\'()]#', $words, -1, PREG_SPLIT_NO_EMPTY);
    
        $bulk_words = array();
        
        foreach($words as $v)
        {
            if (strlen($v) > 3)
            {
                $bulk_words[] = strtoupper($v);
            }
        }
        
        $base_form = $morphy->getBaseForm($bulk_words);
        $fullList = array();

        if(is_array($base_form) && count($base_form))
        {
            foreach($base_form as $k => $v)
            {
                if(is_array($v))
                {
                    foreach($v as $v1)
                    {
                        if(strlen($v1) > 3)
                        {
                            $fullList[$v1] = 1;    
                        }    
                    }    
                }    
            }
        }
        
        $words = join(' ', array_keys($fullList));
        
        return $words;
    }
}

class search_module_back
{
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;

    function search_module_back()
    {
        $this->_module_name = 'search';
    }

    function request_action_set($action) 
    { 
        $this->_common_obj->request_action_set($action); 
    }

    function common_call($front_call = null)
    {
        $this->_module_name = 'search';
        $this->_common_obj =& search_module_common::getInstance();
    }

    function execute($action, $parameters = null)
    {
        $this->common_call();
        return $this->_common_obj->execute(&$this, $action, $parameters);
    }
    
    function executex($action,$acontext)
    {
        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct = $this->lct;   
        $acontext->result = $this->result;
    }

    function indexing($data)
    {
        global $TDB, $_PATH,$_CONFIG; 

        if(!$data['iterating'])
        {
            $crawler = new XTRcrawler();
            
            // URL to crawl
            $crawler->setURL(HTTP_HOST);
            
            // Only receive content of files with content-type "text/html"
            // (regular expression, preg)
            $crawler->addReceiveContentType('/text\/html/');
            
            // Ignore links to pictures, dont even request pictures
            // (preg_match)
            $crawler->addNonFollowMatch('/.(jpg|gif|png|js|css|pdf)$/i');
            
            // Store and send cookie-data like a browser does
            $crawler->setCookieHandling(true);
            $crawler->setTimeLimit($_CONFIG['search']['index_time_limit']);
            
            $_SESSION['search']['indexed_pages_count'] = 0;
            $crawler->disableExtendedLinkInfo(true);

            $TDB->query('TRUNCATE TABLE `search_pages_index`');
            $this->result['iterating'] = true;    
            
            $_SESSION['search']['indexed_pages_count']=0;
            
            unset($_SESSION['pages']);
        }
        else
        {
            if($_SESSION['search']['crawler'])
            {
                $crawler = unserialize($_SESSION['search']['crawler']);
                $crawler->initCrawler(true);
            }
        }
        
        $crawler->pages = array(); 
                    
        if(2 == $crawler->go())
        {
            $this->result['finished'] = false;
            $_SESSION['pages'] = array_merge($crawler->pages, (array) $_SESSION['pages']);                
            $_SESSION['search']['crawler'] = serialize($crawler);
        }
        else
        {   
            $this->result['finished'] = true;    
            $this->result['search']['report'] = $crawler->getReport();
            $_SESSION['pages'] = array_merge($crawler->pages, (array) $_SESSION['pages']);    
        }
        $this->result['search']['pages'] =$this->gridformat($crawler->pages,$_SESSION['search']['indexed_pages_count']);
        $_SESSION['search']['indexed_pages_count'] += count($crawler->pages);        
        $this->result['search']['indexed_pages_count'] = $_SESSION['search']['indexed_pages_count'];
    }

    
    
    function gridformat($page_array,$idx)
    {
        while(list($k,$v)=each($page_array))
        {
            array_unshift($v,$k);
            $idx++;
            $v[0]=$idx;
            $result['rows'][$idx]=array('data'=>array_values($v));
        }
        return  $result;
    }
    
    function get_current_indexes($parameters)
    {
        global $TDB; 
        
        $this->result['indexes'] = $TDB->get_results('SELECT id, url, title, status FROM `search_pages_index`');
    }
    
    
        function indexes_table($parameters)
        {
        global $_CONFIG;
     
                   
        $TTS                     =Common::inc_module_factory('TTableSource');
        $options['table']        ='search_pages_index';
        $options['where']        ='1=1 order by id';
        $options['gridFormat']=1;
        
        $options['columns']=array
            (
            'id',
            'url',
            'title',
            'body',
            'status'
            );
        
        $options['sequence']=array('id','url','title','body','status');
        $options['filter']['body']=array('name'=>'cutwords','count'=>'30');
        $TTS->setOptions($options);

        $this->result['data_set']=$TTS->CreateView();        
        
        
        }
        
    function generateSitemap()
    {      
        global $_PATH;
                       
        if(isset($_SESSION['pages']) && !empty($_SESSION['pages']))
        {
            $doc = new DOMDocument('1.0', 'utf-8');

            $urlset = $doc->createElement('urlset');
            $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
  
            $doc->appendChild($urlset);  

            while(list($key, $item) = each($_SESSION['pages']))
            {
                $url = $doc->createElement('url');
                $urlset->appendChild($url);
        
                $loc = $doc->createElement('loc', $item['url']);
                $url->appendChild($loc);
        
                $changefreq = $doc->createElement('changefreq', 'monthly');
                $url->appendChild($changefreq);
            }
             
            if($fh = fopen($_PATH['SITEMAP'], 'w'))
            {
                $data = $doc->saveXML();
                
                fwrite($fh, $data, strlen($data));
                fclose($fh);
            }
        }
    }

    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_ainterface()
    {
        global $TMS;

        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
        $this->lct['ainterface'] = $TMS->parseSection('a_interface');
    }

    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'), $parameters['selected'], true);
    }
    
    function get_action_properties($parameters)
    {
        global $TMS,$Adm;
      
        if(array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
            switch ($parameters['Action'])
            {
                case 'show_search_form':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list('search',array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);
            
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                                   
                    $this->result['action_properties_form']['Destination_page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('search_server'),'id','params','Name'), false, true);   
                              
                    $this->lct['action_properties'] = $TMS->parseSection('show_search_form');
                    break;
                case 'search_server':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);
                    $this->lct['action_properties'] = $TMS->parseSection('search_server');
                    break;
            }
        }
    }

    /*--------------------------------------------------------------------------------------------*/

    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj', '_tree'));
    }
}
?>