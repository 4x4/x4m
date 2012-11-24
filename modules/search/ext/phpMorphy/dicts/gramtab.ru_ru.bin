<?php
class search_module_front
{
    var $_module_name;
    var $_common_obj;

    function search_module_front()
    {
        $this->_module_name = 'search';
        $this->_common_obj =& search_module_common::getInstance(true);
    }

    function execute($action_data)
    {
        if(is_array($action_data))
        {
            if($action = $this->_common_obj->is_action($action_data['Action']))
            {
                return $this->$action($action_data);
            }
        }
    }

    function request_action_set($action) 
    { 
        $this->_common_obj->request_action_set($action); 
    }

    /**
    * Возвращает все словоформы слов поискового запроса
    */
    function Words2AllForms($text)
    {
        require_once($GLOBALS['_PATH']['PATH_INC'] . 'phpMorphy/src/common.php');

        $opts = array(
//            PHPMORPHY_STORAGE_FILE - использует файловые операции (fread, fseek) для доступа к словарям
//            PHPMORPHY_STORAGE_SHM - загружает словари в общую память (используя расширение PHP shmop)
//            PHPMORPHY_STORAGE_MEM - загружает словари в память
            'storage' => PHPMORPHY_STORAGE_MEM,
//            Extend graminfo for getAllFormsWithGramInfo method call
            'with_gramtab' => false,
            'predict_by_suffix' => true, 
            'predict_by_db' => true
        );
    
        $dir = $GLOBALS['_PATH']['PATH_INC'] . 'phpMorphy/dicts';
    
//        Создаем объект словаря
        $dict_bundle = new phpMorphy_FilesBundle($dir, 'rus');
        $morphy = new phpMorphy($dict_bundle, $opts);
    
//        $codepage = $morphy->getCodepage();
        setlocale(LC_CTYPE, array('ru_RU.CP1251', 'Russian_Russia.1251'));
        
        $words = preg_split('#\s|[,.:;!?"\'()]#', $text, -1, PREG_SPLIT_NO_EMPTY);
        $bulk_words = array();
        
        foreach($words as $v)
        {
            if(strlen($v) > 3)
            {
                $v = iconv("UTF-8", "windows-1251", $v);
                $bulk_words[] = strtoupper($v);    
            }    
        }

        return $morphy->getAllForms($bulk_words);
    }

    function search_server()
    {
        global $REQUEST_VARS;

        if(!$REQUEST_VARS[1])
        {
            //действие по умлочанию
            $parameters['Action']=$parameters['Default_action'];
            unset($parameters['Default_action']);
            return $this->execute($parameters);
        }
    }

    function find($parameters)
    {
        global $TMS, $TDB, $TPA, $REQUEST_VARS;

        if($REQUEST_VARS[1])  
        {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

            $REQUEST_VARS[1] = substr(strrchr($REQUEST_VARS[1], "="), 1);
            $REQUEST_VARS[1] = substr($REQUEST_VARS[1], 0, 64);
            
            if($words = $query = trim(mysql_real_escape_string(urldecode($REQUEST_VARS[1]))))
            {
                $start = (isset($REQUEST_VARS[3])) ? (int) $REQUEST_VARS[3] : 0;

                $forms = array(); $words = search_module_front::Words2AllForms($words);
                
                while(list(,$val) = each($words))
                {
                   if($val){
                    foreach($val as &$item)
                    {
                        $item = iconv('windows-1251', 'UTF-8', $item);
                    }                    
                    $forms = array_merge($forms, (array) $val);
                   }
                }
                if(is_array($forms)) $words .= ' ' . implode(' ', $forms);

                $results = $TDB->get_results('
                    SELECT SQL_CALC_FOUND_ROWS id, url, title, body 
                        FROM search_pages_index 
                        WHERE MATCH (`title`, `body`, `index`) AGAINST (\'' . $words . '\') 
                        AND status = 200 LIMIT ' . $start . ',' . $parameters['onPage']
                );
                
                $counter = $TDB->get_results('SELECT FOUND_ROWS()'); $counter = current($counter); $counter = $counter['FOUND_ROWS()']; 

                // если не нашлось в индексах - ищем RLIKE'ом
                
                if(!$counter)
                {
                    
                    $results = $TDB->get_results('SELECT SQL_CALC_FOUND_ROWS id, url, title, body FROM search_pages_index WHERE body  RLIKE ' . "'[[:<:]]$query" . "[[:>:]]'" . 'OR title RLIKE ' . "'[[:<:]]$query" . "[[:>:]]'" . ' AND STATUS = 200 LIMIT '  . $start . ',' . $parameters['onPage']);
                    $counter = $TDB->get_results('SELECT FOUND_ROWS()'); $counter = current($counter); $counter = $counter['FOUND_ROWS()'];
                    
                    $words=array();
                    $words[]=$query;
                    $words[]=$query;
                }
                
                
                
                if(!$counter)
                {
                    $results = $TDB->get_results('SELECT SQL_CALC_FOUND_ROWS id, url, title, body FROM search_pages_index WHERE body  LIKE ' . "'%$query%'" . ' OR title LIKE ' . "'%$query%'" . ' AND STATUS = 200 LIMIT '  . $start . ',' . $parameters['onPage']);
                    $counter = $TDB->get_results('SELECT FOUND_ROWS()'); $counter = current($counter); $counter = $counter['FOUND_ROWS()'];
                  
                }
                
                
                
                if($results)
                {
                    $pages =& pages_module_common::getInstance(true);

                    if($page = end($TPA->bones_path)) $search_server_page = $pages->create_page_path($page['id'], false, 'find');
                    
                    Common::parse_nav_pages($counter, $parameters['onPage'], $start, $search_server_page . '=' .$REQUEST_VARS[1] , 'search_');
                    
                    $i = $start;                    
                    
                    foreach($results as $result)
                    {
                        $result['num']   = ++$i;
                        
                        
                        //$result['body']  = XSTRING::findncut_symbol_positon($result['body'],' ',30).'...';
                        
                      
                        $result['body']  = $this->extract_sentence($result['body'],$words);
                        
                        $result['url']   = $result['url'];
                        $result['title'] = (empty($result['title'])) ? $result['url'] : $result['title'];
                            
                        $TMS->AddMassReplace('xtr_search_result', $result);
                        $TMS->parseSection('xtr_search_result',true);
                    }
                    
                    $query =strip_tags(htmlspecialchars($query));
                    
                    $TMS->AddMassReplace('xtr_search_results', array('query' => $query,'query_num' => $counter));
                    return $TMS->parseSection('xtr_search_results');    
                }
                else
                {
                    $query =strip_tags(htmlspecialchars($query));
                    $TMS->AddMassReplace('xtr_search_nothing_found', array('query' => $query));
                    return $TMS->parseSection('xtr_search_nothing_found');
                }
                
            }
            else
            {
                $TMS->AddMassReplace('xtr_search_empty', array('query' => $query));
                return $TMS->parseSection('xtr_search_empty');
            }
        }
    }
    
    
    function utf8_strtolower($string) 
    {

      $convert_to = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u",
        "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï",
        "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж",
        "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы",
        "ь", "э", "ю", "я"
      );
      $convert_from = array(
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U",
        "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï",
        "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж",
        "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ",
        "Ь", "Э", "Ю", "Я"
      );

    return str_replace($convert_from, $convert_to, $string);
    }
       
    /**
     * Возвращает первое предложение с подсвеченным словом
     */
    function extract_sentence($string = '', $words = array())
    {
              
        setlocale(LC_CTYPE, 'ru_RU.UTF8', 'ru.UTF8', 'ru_RU.UTF-8', 'ru.UTF-8');                                              
        if(is_string($words)) $words = explode(' ', $words);

        unset($words[0]); $words = array_values($words);
        
        $string = (!trim($string) ? "" : $string);

        if(!empty($words) && is_array($words) && !empty($string))
        {
        
        //$string= strtolower($string); //mb_strtolower $string= mb_strtolower($string, 'UTF-8');
        $sentences = preg_split('%[\.\!\?]%', $string);
                                    
                                    
        foreach($words as $word)
            {
                $word= $this->utf8_strtolower($word);  

                $patterns[] = "/\b($word)\b/s";

                $replacements[] = '<span class="highlight">\\1</span>';
            }
                                     
               
            if(!$sentences) 
            {
                foreach($words as $word)
                {
                    $word=$this->utf8_strtolower($word, 'UTF-8');       
                    $word = preg_quote($word);
                    
                    if(preg_match("/$word/s", $string, $matches)) 
                    {
                        return preg_replace($patterns, $replacements, $string); 
                    }
                }
                return false;
            }
        
            foreach($sentences as $item)
            {
                $item = trim($item);
                $item= $this->utf8_strtolower($item); 
                
                $word = preg_quote($word);
                
                
                foreach($words as $word)
                {
                
                $word= $this->utf8_strtolower($word); 
                    
                    if(preg_match("/$word/", $item, $matches)) 
                   {
                   
                       return preg_replace($patterns, $replacements, $item); 
                   }
                   
                }
            }
        }
        
        return false;
    }

    function show_search_form($parameters)
    {
        global $TMS;
        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
        $pages =& pages_module_common::getInstance(true);

        if($parameters['Destination_page']) $search_server_page=$pages->create_page_path($parameters['Destination_page'], false, 'find');

        $TMS->AddReplace('xtr_search', 'action', $search_server_page);

        return $TMS->parseSection('xtr_search');
    }

    function build_content($content_id)
    {
        global $TMS;
        $fields=XARRAY::arr_to_lev($this->_tree->GetChildsParam($id, array('field_value'), true), 'basic', 'params', 'field_value');
    }
}
?>