<?php

class x3_chaincall
{ 
    var $scriptcall;
    var $stop;
    
     public function stop()
     {
         $this->stop=true;
     }
     
     function x3_chaincall($scriptcall)
     {
        $this->scriptcall=$scriptcall;       
     } 
     
     
     function _get($type,$host,$port='80',$path='/',$data='') 
     {
        if(!empty($data)) foreach($data AS $k => $v) $str .= urlencode($k).'='.urlencode($v).'&'; $str = substr($str,0,-1);
        $fp = fsockopen($host,$port,$errno,$errstr,$timeout=30);
        if(!$fp) die($_err.$errstr.$errno); else {
            fputs($fp, "POST $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
            fputs($fp, "Content-length: ".strlen($str)."\r\n");
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $str."\r\n\r\n");
           
            fgets($fp,1);
            fclose($fp);
        } return $d;
    } 
     
     public function chain($gparams)
     {
         if(!$this->stop)
         {
             if($gparams)
             {         
                 foreach($gparams as $key=>$param)
                 {
                     $gp[]=$key.'='.$param;   
                 }
                 
                 $gp='&'.implode('&',$gp);
             }

             
             $callto='/'.$this->scriptcall.'?chaincall=1'.$gp;             

             $this->_get('http',$_SERVER['HTTP_HOST'],'80',$callto);
         }
         
         die();
         
     } 

}

class x3_file_session 
{

var $sessionid;
var $data= Array();    


    public function get_session_id()
    {
        return $this->sessionid;
    }    

    public function set_shutdown_serialize()
    {
        register_shutdown_function(array($this,"serialize_session"));        
    }
     
     function x3_file_session($id=null)
     {
         
         if(($id)or($_REQUEST['x3sid']))
         {
                $this->sessionid = (empty($_REQUEST['x3sid'])) ? $id : $_REQUEST['x3sid'];                
                $this->data=xCache::serializedRead('session',$this->sessionid,0);               
                
         }else{
                $this->sessionid=md5(time());                 
         }
            
     }
    /*
     * shutdown will serialize all data 
    */
        function serialize_session()
    {        
            xCache::serializedWrite($this->data,'session',$this->sessionid);           
    }


}


class connector
{
     var $result;  
     var $lct; 
     var $message;
     var $error;
     
     static $stackError;
     static $stackMessage;
           
     function connector()
     {
        XOAD_Server::allowClasses('connector');           
     }
            
     public static function  pushError($msg,$module='connector')
     {
           self::$stackError[]=array('message'=>$msg,'module'=>$module);
     
     }
     
     public static function  pushMessage($msg,$module='connector')
     {
           self::$stackMessage[]=array('message'=>$msg,'module'=>$module);     
     }      
                                      
     
     public function xroute($data)
      {     
        if(is_array($data))
            {
                
               foreach ($data as $namespace=>$function)


                    if($result=xNameSpaceHolder::call('module.'.$namespace,key($function),current($function)))
                    {
                        

                        if(($instance=xNameSpaceHolder::getLastInstance())&&(($instance->result)or($instance->lct)))
                        {
                            $this->result  = $instance->result;
                            $this->lct  = $instance->lct;
                        }
                        
                        $this->message=self::$stackMessage;
                        $this->error=self::$stackError;
                        
                        
                    }elseif($result===null){
                        self::pushError($namespace.'::'.key($function).' method not found');

                    }
                
                }

      }
      
      


      function xoadGetMeta()
        {
            XOAD_Client::privateVariables($this,array('stackError','stackMessage'));
            XOAD_Client::mapMethods($this, array('xroute'));
            XOAD_Client::publicMethods($this, array('xroute'));
        }
}


class xNameSpaceHolder
{
    private static $nameSpaces;
    private static $callModeles;
    private static $lastInstance;
     
     /**
     * Добавить все методы объекта в неймспейс
     *   
     * @param mixed $ns - имя нейспейса
     * @param mixed $object - объект
     */
    public static function addObjectToNS($ns,$object)
    {
        $className=get_class($object);  
        $f = new ReflectionClass($className);             
        
        if($methods=$f->getMethods(ReflectionMethod::IS_PUBLIC))
        {
            foreach ($methods as $method) 
            {
                if ($method->class == $className&&!(strstr($method->name,'__'))) 
                {
                        self::$nameSpaces[$ns][$method->name]=&$object;
                }
            }
        }
    }
    
    /**
    * Добавить метод в нейспейс
    * 
    * @param string $ns - имя нейспейса
    * @param mixed $methods - 1 метод либо массив методов
    * @param object $object - объект обработчик
    */
   public static function addMethodsToNS($ns,$methods,$object)
    {
         if(!is_array($methods)) {$methods=array($methods);}
          
           if($methods)
           {
                foreach($methods as $method)
                {
                    self::$nameSpaces[$ns][$method]=&$object;    
                } 
           }           

    }
    
    /**
    * Проверяет неймспейс на существование,если указан метод то проверяется существание  объякта у определенного  метода у данного неймспейса
    * 
    * @param string $ns = имя нейспейса
    * @param string $method = название метода
    */
    public static function isNameSpaceExists($ns,$method='')
    {
        
        if($method&&method_exists(self::$nameSpaces[$ns][$method],$method))
        {
            return true;
        
        }elseif(self::$nameSpaces[$ns]&&!$method)
        {
            return true;
            
        }else{
            
            return false;
        }
        
    }   
     /**
     * Добавить модель вызова
     * 
     * @param mixed $name - имя модели
     * @param mixed $wakeUpFunction - lyambda функция для вызова определенной модели
     * стандартные модели вызова module,plugin,classs 
     */
     
    public static function addCallModel($name,$wakeUpFunction)
    {
            self::$callModeles[$name]=$wakeUpFunction;
 
    }
    /**
    * получить последний объект с которого произошел последний вызов метода нейспейса
    * 
    */
    public static function getLastInstance()
    {
        return  self::$lastInstance;   
    }
    
    /**
    * Вызвать методо из опрежделенного неймспейса
    * 
    * @param string $ns  - название неймспейса
    * @param string $method - метод который необходимо вызвать из определенного интерфейса
    * @param mixed $arguments - обязательно ассоциативный  масссив
    * @return mixed
    */
    
    public static function call($ns,$method,$arguments=null,$additionalArguments=null)
    {         
        if(!method_exists(self::$nameSpaces[$ns][$method],$method))
        {
              $nsExpl=explode('.',$ns);
              if($wakeUpFunction=self::$callModeles[$nsExpl[0]])
              {
                   $wakeUpFunction($nsExpl);
              }
        }     
        
        if(method_exists(self::$nameSpaces[$ns][$method],$method))
        {
            self::$lastInstance=self::$nameSpaces[$ns][$method];    
            
            $result=call_user_func_array(array(self::$nameSpaces[$ns][$method],$method),array($arguments,$additionalArguments));        
             
             if(!$result){return true;}else{return $result;}      
        
        }else{
            
            return null;
        }
    }
        
    }    
    
    
// объявление стандартных моделей вызова
xNameSpaceHolder::addCallModel('module', function($params)
{
    switch($params[2])
    {
        case 'tpl':
            
            xTpl::__load($params[1]);
            
        break;
    
        case 'xfront':
            
        $xfrontModuleInstance=xCore::moduleFactory($params[1].'.'.$params[2]);
        xNameSpaceHolder::addObjectToNS('module.'.$params[1].'.xfront',$xfrontModuleInstance);
        $xfrontModuleInstance->initiateXfrontPlugins();
            
        break;
        
        
        case 'back':
            
            $backmoduleInstance=xCore::moduleFactory($params[1].'.'.$params[2]);
            $backModuleInstance->initiateBackPlugins();
            
        break;

        
    }

       
});



class tMultiSection {
    var $_enableExtendFields;
    var $screenedFields = array();
    var $Extended = array();
    var $Fields = array();
    var $Replacement = array();
    var $MFReplacement = array();
    var $MFFields = array();
    var $maindata = array();
    var $AssignedArrays = array();
    var $sectionNests = array();
    var $blocked_parse_section = array();
    var $pack_output = false;
    var $FastReplace = array();
    var $MainFields = array();
    var $ifSplitPattern = '/(\{%(?:if\(.*?\))%\})|(\{%else%\})|(\{%endif%\})/';
    var $sectionSplitPattern = '/({%section:.+?%})|{%(endsection:.+?)%}|{%(each\(.+?\))%}|{%(endeach)%}/';
    var $varsExtractPattern = "/{%(F|MF|->):(.*?)?({(.*?)}%}|\((.*?)\)%}|%})/";
    var $callFunc = array();
    var $actionLog = array();
    var $currLoggingName = null;
    var $Errors;
    var $noprsVals;
    var $cache;
    var $currentCacheBranch;
    var $handlerObj;
    var $aifs;
    public  $templatesAlias=array();
    
    function __construct($enableExtendFields = false) 
    {
        
        xNameSpaceHolder::addMethodsToNS('TMS',array('_each'),$this);
        $this->enableExtendFields($enableExtendFields);
    }
    
    function startLogging($logname) {
        $this->currLoggingName = $logname;
    }
    function setLog($source, $val) 
    {
        static $i;
        if (!$this->currLoggingName)
            return false;
        $this->actionLog[$this->currLoggingName][$source][$val] = 1;
    }
    
    function clearDataLoggedSources($sources, $logItems) {
        if ($logItems) {
            foreach ($logItems as $k => $item) {
                foreach ($sources as $src) {
                    $q =& $this->$src;
                    unset($q[$k]);
                }
            }
        }
    }
    function delFileSectionCache($name) 
    {
        unset($this->cache[$name]);
    }
    
    function clearLogged($logname) {
        if ($this->actionLog[$this->currLoggingName]) {
            foreach ($this->actionLog[$this->currLoggingName] as $logSource => $logItems) {
                switch ($logSource) {
                    case 'MFFields':
                        $this->clearDataLoggedSources(array(
                            'MFFields',
                            'MFReplacement'
                        ), $logItems);
                        break;
                    case 'Fields':
                        $this->clearDataLoggedSources(array(
                            'Fields',
                            'Replacement',
                            'potentialKeys'
                        ), $logItems);
                        break;
                    case 'maindata':
                        $this->clearDataLoggedSources(array(
                            'Fields',
                            'potentialKeys',
                            'Extended',
                            'Replacement',
                            'SectionNests',
                            'FastReplace',
                            'maindata',
                            'aifs',
                            'Format',
                            'callFunc'
                        ), $logItems);
                        break;
                }
            }
            unset($this->actionLog[$this->currLoggingName]);
            $this->currLoggingName = null;
        }
    }
    function enableExtendFields($switch = false) {
        $this->_enableExtendFields = $switch;
    }
    function block_parse($section) {
        if (is_array($section)) {
            for ($i = 0; $i < count($section); $i++) {
                $this->blocked_parse_section[] = $section;
            }
        } else {
            $this->blocked_parse_section[] = $section;
        }
    }
    function nullSection($section) {
        if ($this->maindata[$section]) {
            $this->maindata[$section] = null;
        }
    }
    function clear_section_fields($section, $el = '') {
        if ($this->Fields[$section]) {
            $this->Fields[$section] = array_fill(0, count($this->Fields[$section]), $el);
        }
    }
    function delSection($section, $is_prefix = null) {
        if (is_array($section)) {
            foreach ($section as $sec_item) {
                if ($this->maindata[$sec_item]) {
                    unset($this->maindata[$sec_item]);
                    unset($this->Replacement[$sec_item]);
                    unset($this->Fields[$sec_item]);
                }
            }
        } elseif ($is_prefix) {
            $existing_section = array_keys($this->maindata);
            foreach ($existing_section as $existing_item) {
                if (strpos($existing_item, $section) === 0) {
                    unset($this->maindata[$existing_item]);
                    unset($this->Replacement[$existing_item]);
                    unset($this->Fields[$existing_item]);
                    unset($this->FastReplace[$existing_item]);
                }
            }
            foreach ($this->MFReplacement as $num => $value) {
                if (strpos($this->MFReplacement[$num], '{%->:' . $section) === 0) {
                    unset($this->MFReplacement[$num]);
                    unset($this->MFFields[$num]);
                }
            }
        } else {
            if ($this->maindata[$section]) {
                unset($this->maindata[$section]);
                unset($this->Replacement[$section]);
                unset($this->Fields[$section]);
                unset($this->Extended[$section]);
            }
        }
    }
    function isSectionDefined($section) {
        if (in_array($section, array_keys($this->maindata))) {
            return true;
        }
    }
    function AddMassReplace($section, $ARR) {
        if (is_array($ARR)) {
            foreach ($ARR as $key => $val) {
                $this->AddReplace($section, $key, $val);
            }
        }
    }
    function ReturnData() {
        return $this->maindata;
    }
    function FindReplacement($section, $Repl) {
        if (isset($this->maindata[$section])) {
            if ($this->Replacement[$section]) {
                $fkey = array_search('{%F:' . $Repl . '%}', $this->Replacement[$section]);
            }
            return $fkey;
        } else {
            return false;
        }
    }
    function getMFSectionReplacements() {
        if ($this->MFReplacement) {
            foreach ($this->MFReplacement as $repl) {
                preg_match("/{%->:(.*?)%}/", $repl, $match);
                $ext[] = $match[1];
            }
            return $ext;
        }
    }
    function getFastReplace() {
        if ($fr = array_keys($this->FastReplace)) {
            foreach ($fr as $v) {
                $f[] = str_replace('@', '', $v);
            }
            return $f;
        }
    }
    function getSectionReplacements($section, $s = '', $e = '') {
        if ($this->Replacement[$section]) {
            foreach ($this->Replacement[$section] as $repl) {
                $ext[] = $s . substr($repl, 4, strlen($repl) - 6) . $e;
            }
            return $ext;
        }
    }
    function KillField($section, $Repl) {
        $fkey = $this->FindReplacement($section, $Repl);
        if ($fkey !== false) {
            $this->Fields[$section][$fkey] = "";
        }
    }
    function KillMFields($FastSection) {
        $fkey = array_search('{%->:' . $FastSection . '%}', $this->MFReplacement);
        if ($fkey !== false) {
            $this->MFFields[$fkey] = '';
        }
    }
    function AddMFMassReplace($arr) {
        if ($arr) {
            foreach ($arr as $key => $val) {
                $this->AddMFReplace($key, $val);
            }
        }
    }
    function AddMFReplace($addMF, $addMR, $glue = false) {
        if ($strop = strpos($addMF, '%->:')) {
            $fkey = array_search($addMF, $this->MFReplacement);
        } else {
            $fkey = array_search('{%MF:' . $addMF . '%}', $this->MFReplacement);
        }
        if ($fkey !== false) {
            if ($glue) {
                $this->MFFields[$fkey] .= $addMR;
                $this->setLog('MFFields', $fkey);
            } else {
                $this->MFFields[$fkey] = $addMR;
                $this->setLog('MFFields', $fkey);
            }
        }
    }
    function AssignArray($section, $arr) {
        $this->AssignedArrays[$section] = $arr;
    }
    
    
    
    function setArrayDependency($section, $addF, $addR) {
        if ($this->Replacement[$section]) {
            foreach ($this->Replacement[$section] as $rkey => $repl) {
                if (strstr($repl, '{%F:' . $addF . '>') !== false) {
                    if ($Matched = preg_match($this->varsExtractPattern, $repl, $matches)) {
                        $temp_addr  = $addR;
                        if($matches[2])
                        {
                            $s = explode('>', $matches[2]);
                            $s = $this->replaceSlice($section, $s);
                            array_shift($s);
                            foreach ($s as $sep) {
                                $temp_addr = $temp_addr[$sep];
                            }
                            $this->Fields[$section][$rkey] = $temp_addr;
                        //    $this->checkIsFormat($section, $matches[2], $temp_addr);
                        }
                    }
                }
            }
        }
    }
    
    /*
    function checkIsFormat($section, $addF, $addR) {
        if (is_array($this->Format[$section][$addF])) {
            foreach ($this->Format[$section][$addF] as $fs) 
            {
                $fvalues = null;
                if ($fs['values']) {
                    foreach ($fs['values'] as $v) {
                        $fvalues[] = $this->replaceSlice($section, $v);
                    }
                }
                
                if (strstr($fs['method'], ':')) 
                {
                    $sep = explode(':', $fs['method']);
                    $this->Fields[$section][$fs['index']]=xNameSpaceHolder::call($handler,$sep[1],array('val'=>$addR,'params'=>$fvalues,'section'=>$section,'field'=>$addF));
                    $this->setLog('Fields', $section);
               }
        }
    }
}     */
    
    function AddReplace($section, $addF, $addR, $prevent_cycle = false) 
    {
        $fkey = $this->FindReplacement($section, $addF);
        if (is_array($addR)) {            
            $this->setArrayDependency($section, $addF, $addR);
        }
        
        //$this->checkIsFormat($section, $addF, $addR);
        
        if ($fkey !== false) {
            $this->Fields[$section][$fkey] = $addR;
            $this->setLog('Fields', $section);
        } else {
            $this->potentialKeys[$section][$addF] = $addR;
        }
    }
    
    function createSection($sectionInfo) {
        if (!$this->isSectionDefined($sectionInfo[1])) 
        {
            $sectionName = $sectionInfo[1];
            if ($sectionInfo[2] == '->%}') {
                $this->FastReplace[$sectionName]                                     = $sectionName;
                $this->cache[$this->currentCacheBranch]['FastReplace'][$sectionName] = $sectionName;
                
            } elseif ($sectionInfo[3]) {
                $this->FastReplace[$sectionName]                                     = $sectionInfo[3];
                $this->cache[$this->currentCacheBranch]['FastReplace'][$sectionName] = $sectionInfo[3];
            }
            $this->maindata[$sectionName] = '';
            $this->setLog('maindata', $sectionName);
            return true;
        } else {
            unset($this->aifs[$sectionInfo[1]]);
        }
    }
    function addToSection($sectionName, $text) {
        if ($this->isSectionDefined($sectionName)) {
            $this->maindata[$sectionName] .= $text;
            if ($this->currentCacheBranch) {
                $this->cache[$this->currentCacheBranch]['maindata'][$sectionName] .= $text;
            }
        }
    }
    function processSectionVars($sectionName) {
        if (preg_match_all('/\{%?if\((.*?)\)%\}/', $this->maindata[$sectionName], $ifmached)) {

            foreach ($ifmached as $ifm) {
                $this->parse_func_values($ifm[0], $sectionName,true);
            }
        }
        if ($Matched = preg_match_all($this->varsExtractPattern, $this->maindata[$sectionName], $match)) {
            $k = 0;
            if ($Matched) {
                foreach ($match[0] as $field) {
                    if ($match[1][$k] == 'MF') {
                        $this->MainFields[] = $match[2][$k];
                        if ($this->currentCacheBranch)
                            $this->cache[$this->currentCacheBranch]['MainFields'][] = $match[2][$k];
                        $this->MFReplacement[] = '{%MF:' . $match[2][$k] . '%}';
                        if ($this->currentCacheBranch)
                            $this->cache[$this->currentCacheBranch]['MFReplacement'][] = '{%MF:' . $match[2][$k] . '%}';
                        $this->MFFields[] = '';
                        $k++;
                    } elseif ($match[1][$k] == '->') {
                        $this->SectionNests[$sectionName][] = $match[2][$k];
                        $this->MFReplacement[]             = '{%->:' . $match[2][$k] . '%}';
                        if ($this->currentCacheBranch)
                            $this->cache[$this->currentCacheBranch]['MFReplacement'][] = '{%->:' . $match[2][$k] . '%}';
                        $this->MFFields[] = '';
                        $this->setLog('MFFields', count($this->MFFields));
                        $k++;
                    } else {
                        
                        $field = trim($field);
                   
                        $fkey  = false;
                        if ($this->Replacement[$sectionName]) {
                            $fkey = array_search($field, $this->Replacement[$sectionName]);
                        }
                        if ($fkey === false) {
                            $_field = '{%F:' . $match[2][$k] . '%}';
                            if (($match[4][$k])) {
                                $this->maindata[$sectionName]                                     = str_replace($field, $_field, $this->maindata[$sectionName]);
                                $this->cache[$this->currentCacheBranch]['maindata'][$sectionName] = $this->maindata[$sectionName];
                                $this->Extended[$sectionName][$match[2][$k]]                      = $this->expl_extended($match[4][$k]);
                                if ($this->currentCacheBranch)
                                    $this->cache[$this->currentCacheBranch]['Extended'][$sectionName][$match[2][$k]] = $this->Extended[$sectionName][$match[2][$k]];
                            }

                            if ($match[5][$k]) {
                                preg_match('/(.*?)\((.*?)\)/', $match[5][$k], $m);
                                
                                  if(substr($match[2][$k],-1)=='=')
                                    {
                                        $match[2][$k]='#'.substr($match[2][$k],0,(strlen($match[2][$k])-1));
                                    }
                                                             
                                if (($match[2][$k][0] == '#') or ($match[2][$k][0] == '+') or ($match[2][$k][0] == '-') or ($match[2][$k][0] == '@')) {
                                    if ($retval = substr($match[2][$k], 1)) {
                                        if ($this->currentCacheBranch) {
                                            $this->cache[$this->currentCacheBranch]['Replacement'][$sectionName][] = '{%F:' . $retval . '%}';
                                        }
                                        //if ($match[2][$k][0] != '@') {
                                            $this->Fields[$sectionName][]      = "";
                                            $this->Replacement[$sectionName][] = '{%F:' . $retval . '%}';
                                        //}
                                    }
                                    
                                    $json_stop=false;
                                    if($m[1]=='TMS:_each')
                                    {
                                        $json_stop   =true;
                                    }

                                    
                                    $this->callFunc[$sectionName][md5($field)] = array(
                                        'method' => $m[1],
                                        'values' => $this->parse_func_values($m[2], $sectionName,$json_stop),
                                        'index' => count($this->Fields[$sectionName]),
                                        'priority' => $match[2][$k][0],
                                        'return' => $retval,
                                        'ffield' => $match[0][$k]
                                    );
                                    if ($this->currentCacheBranch)
                                        $this->cache[$this->currentCacheBranch]['callFunc'][$sectionName][md5($field)] = end($this->callFunc[$sectionName]);
                                } else {
                                    $fkey = false;
                                    if ($this->Replacement[$sectionName]) {
                                        $fkey = array_search('{%F:' . $match[2][$k] . '%}', $this->Replacement[$sectionName]);
                                    }
                                    if ($fkey === false) {
                                        $this->Fields[$sectionName][]      = "";
                                        $this->Replacement[$sectionName][] = '{%F:' . $match[2][$k] . '%}';
                                    }
                                    $index                                      = count($this->Fields[$sectionName]);
                                    
                                  /*  $this->Format[$sectionName][$match[2][$k]][] = array(
                                        'method' => $m[1],
                                        'values' => $this->parse_func_values($m[2], $sectionName),
                                        'index' => $index
                                    );
                                   */ 
                                    
                                    
                                    if ($this->currentCacheBranch && ($fkey === false)) {
                                        $this->cache[$this->currentCacheBranch]['Replacement'][$sectionName][]           = '{%F:' . $match[2][$k] . '%}';
                                    //    $this->cache[$this->currentCacheBranch]['Format'][$sectionName][$match[2][$k]][] = end($this->Format[$sectionName][$match[2][$k]]);
                                    }
                                }
                                $_field = $field;
                            }
                            $this->Fields[$sectionName][]      = "";
                            $this->Replacement[$sectionName][] = $_field;
                            if ($this->currentCacheBranch)
                                $this->cache[$this->currentCacheBranch]['Replacement'][$sectionName][] = $_field;
                        }
                        $k++;
                    }
                }
                $Matched = null;
                $match   = null;
            }
        }
    }
    function ProcessIncluded($lines) {
        
        if($lines[0][0]=='@')$this->templatesAlias[$this->currentCacheBranch]=substr($lines[0],1);                
        $l           = implode($lines);
        $sectonStack = array();
        if ($tcode = preg_split($this->sectionSplitPattern, $l, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)) {
            while (list($line, $code) = each($tcode)) {
                if (strpos($code, '{%section:') !== false) {
                    $sectionStart = preg_match("!{%section:(.+?)(%}|->(.*?)%})!si", $code, $sectionInfo);
                    if ($this->createSection($sectionInfo))
                        $sectionName = $sectionInfo[1];
                } elseif (strpos($code, 'endsection:') === 0) {
                    $sectionStart = false;
                    $this->processSectionVars($sectionName);
                    $this->callOuterFuncs($sectionName, array('+'));
                    $sectionName = array_pop($sectonStack);
                } elseif ((strpos($code, 'each(') === 0) && ($sectionStart)) {
                    $tempSection = $sectionName;
                    array_push($sectonStack, $sectionName);
                    $sectionName = '_each' . $line . md5($code . $tempSection);
                    $this->addToSection($tempSection, '{%F:@' . $sectionName . '(TMS:_' . $code . ')%}');
                    $this->createSection(array(
                        '',
                        $sectionName
                    ));
                } elseif (($code == 'endeach') && ($sectionStart)) {
                    $this->processSectionVars($sectionName);
                    $sectionName = array_pop($sectonStack);
                } elseif (($sectionStart or $eachStart) && $sectionName) {
                    $this->addToSection($sectionName, $code);
                }
            }
        }
    }
    function _each($params, $section) {
        if (is_array($params[0])) {
            if ($this->Replacement[$section['section']] && $this->Fields[$section['section']]) {
                $scopeGlobal = array_combine($this->getSectionReplacements($section['section']), $this->Fields[$section['section']]);
                $this->AddMassReplace($section['return'], $scopeGlobal);
            }
            
            
            foreach ($params[0] as $key => $value) 
            {
                if ($params[2]) {
                    $scopeLocal = array(
                        $params[1] => $key,
                        $params[2] => $value
                    );
                } else {
                    $scopeLocal = array(
                        $params[1] => $value
                    );
                }

                $this->AddMassReplace($section['return'], $scopeLocal);
                $eachText .= $this->parseSection($section['return']);
            }
            //back scope
            $backScope = array_combine($this->getSectionReplacements($section['return']), $this->Fields[$section['return']]);            
            $this->AddMassReplace($section['section'], $backScope); 
            
            return $eachText;
        }
    }
    
    function parse_func_values($text, $sectionName,$ifsState=false) 
    {
                  
        if($text){
        if (preg_match_all('/{F:(.*?)}/', $text, $m)) 
        {
            foreach ($m[1] as $f) {
                $fkey = false;
                if ($this->Replacement[$sectionName]) {
                    $fkey = array_search('{%F:' . $f . '%}', $this->Replacement[$sectionName]);
                }
                if ($fkey === false) {
                    $this->Fields[$sectionName][]      = "";
                    $this->Replacement[$sectionName][] = '{%F:' . $f . '%}';
                }
                if ($this->currentCacheBranch && $fkey === false)
                    $this->cache[$this->currentCacheBranch]['Replacement'][$sectionName][] = '{%F:' . $f . '%}';
                    }
            }
            //check_json_is_valid
            
            if(!$ifsState)
            {
                if(!$result=json_decode($text,true))
                {

                    trigger_error('JSON parse error - | '.$text.' | see section: '.$sectionName,E_USER_WARNING);
                }else{
                   
                    
                   return $result; 
                }
                
            }else{
                return  explode(',',$text);
            }
        
        }
    }
    
    function expl_extended($exl) {
        if ($exl = explode(',', $exl)) {
            for ($i = 0; $i < count($exl); $i++) {
                $_ex           = explode(':', $exl[$i]);
                $_exl[$_ex[0]] = $_ex[1];
            }
            return $_exl;
        }
    }
    function AddFileSection($filename, $astext = false) {
        if (is_array($filename)) {
            $this->ProcessIncluded($filename);
        } elseif (file_exists($filename)) {
            if ($this->cache[$filename]) {
                if ($this->cache[$filename]['maindata']) {
                    $this->maindata = array_merge($this->cache[$filename]['maindata'], $this->maindata);
                }
                foreach ($this->cache[$filename]['maindata'] as $sectionName => $v) {
                    $this->setLog('Fields', $sectionName);
                    $this->setLog('maindata', $sectionName);
                    if ($this->cache[$filename]['callFunc'][$sectionName])
                        $this->callFunc[$sectionName] = $this->cache[$filename]['callFunc'][$sectionName];
                    if ($this->cache[$filename]['Extended'][$sectionName])
                        $this->Extended[$sectionName] = $this->cache[$filename]['Extended'][$sectionName];
                    if ($this->cache[$filename]['Format'][$sectionName])
                        $this->Format[$sectionName] = $this->cache[$filename]['Format'][$sectionName];
                    if ($this->cache[$filename]['Replacement'][$sectionName]) {
                        $this->Replacement[$sectionName] = $this->cache[$filename]['Replacement'][$sectionName];
                        $this->Fields[$sectionName]      = array_fill(0, count($this->Replacement[$sectionName]), '');
                    }
                    $this->callOuterFuncs($sectionName, array(
                        '+'
                    ));
                }
                if ($this->cache[$filename]['FastReplace'])
                    $this->FastReplace = array_unique(array_merge($this->cache[$filename]['FastReplace'], $this->FastReplace));
                if ($this->cache[$filename]['MainFields']) {
                    $this->MainFields = array_unique(array_merge($this->cache[$filename]['MainFields'], $this->MainFields));
                }
                if ($this->cache[$filename]['MFReplacement']) {
                    foreach ($this->cache[$filename]['MFReplacement'] as $MFR) {
                        $this->MFReplacement[] = $MFR;
                        $this->MFFields[]      = '';
                    }
                }
            } else {
                $this->currentCacheBranch = $filename;
                $this->ProcessIncluded(file($filename));
                return $this->templatesAlias[$filename];
            }
        } elseif ($astext) {
            $this->ProcessIncluded(explode("\n", $filename));
            return $this->templatesAlias[$filename];
        } else {
            trigger_error(' Template not found -> ' . $filename, E_USER_ERROR);
        }
    }
    function AddMassFileSection($f_sections) {
        if (is_array($f_sections)) {
            foreach ($f_sections as $fsection) {
                $this->AddFileSection($fsection);
            }
        }
    }
    
    function ParseRecurs($sectionName, $Glue = 0) {
        if ($this->maindata[$sectionName]) {
            if ($sect_count = count($this->SectionNests[$sectionName])) {
                for ($i = 0; $i < $sect_count; $i++) {
                    if (!in_array($this->SectionNests[$sectionName][$i], $this->blocked_parse_section)) {
                        $this->ParseRecurs($this->SectionNests[$sectionName][$i], true);
                    }
                }
            }
            if ($Glue) {
                $this->parseSection($sectionName);
            } else {
                return $this->parseSection($sectionName);
            }
        }
    } 
    
    
    function getAllCurrentReplacements($section)
    {
        $sr = $this->getSectionReplacements($section, '{F:', '}');
        $repArray=array_combine($sr,$this->Fields[$section]);
        
        if ($this->potentialKeys[$section]) 
        {
            foreach ($this->potentialKeys[$section] as $k => $v) 
            {
                $repArray['{F:' . $k . '}'] =  $v; 
            }
        }
        
        return $repArray;        
    }
    
    
    function replaceSlice($section, $slice, $asArrayExport = false) {
        
        if (!is_array($slice)) {return;}
         
          $repArray=$this->getAllCurrentReplacements($section);
         
          if (!function_exists('recursiveJsonReplace'))
          {
                  function recursiveJsonReplace(&$val,&$key,$repArray)
                  {                      
                    if(is_array($repArray[$val]))
                    {
                        $val=$repArray[$val];
                        
                    }else
                    {
                        $val=str_replace(array_keys($repArray),$repArray,$val);
                    }
                  }
          }

         XARRAY::array_walk_recursive2($slice, 'recursiveJsonReplace',$repArray);
         return $slice;
        
    }
    
    function callphp($func, $args) {
        foreach ($args as $arg) {
            if (is_array($arg)) {
                $a[] = var_export($arg, true);
            } else {
                $a[] = $arg;
            }
        }
        if (count($a) > 0) {
            $t = '$r=' . $func . '(' . implode($a, ',') . ');';
        } else {
            $t = '$r=' . $func . '(' . $a . ');';
        }
        @eval($t);
        return $r;
    }
    
    function outerCall($func, $section) {
        if (strstr($func['method'], ':')) 
        {
                $sep = explode(':', $func['method']);
                $f = array();
                $func_values= $this->replaceSlice($section, $func['values']);
                if ($sep[0] == 'php') 
                {
                    $r = $this->callphp($sep[1], $f, $section, $func['return']);
                    
                } elseif (xNameSpaceHolder::isNameSpaceExists($sep[0],$sep[1])) 
                {
                    $rKey=$this->FindReplacement($section,$func['return']);
                    
                    $r=xNameSpaceHolder::call($sep[0],$sep[1],$func_values,array('value'=>$this->Fields[$section][$rKey], 'section'=>$section,'return'=>$func['return'])); 
                }                                                                                       
                
                if ($func['return']) {
                    if ($func['priority'] != '@') 
                    {
                        $this->AddReplace($section, $func['return'], $r);
                    } else {
                        $func['ffield'] = substr($func['ffield'], 4);
                        $func['ffield'] = substr($func['ffield'], 0, strlen($func['ffield']) - 2);
                        $this->AddReplace($section, $func['ffield'], $r);
                    }
                }
            
        }
    }
    
    function detectAndCallOuterFunc($text, $section) {
        preg_match_all("/{%F:(\#|\@)(.*?)%}/", $text, $m);
        if ($m[0][0]) {
            foreach ($m[0] as $funcname) {
                if ($f = $this->callFunc[$section][md5($funcname)]) {
                    $this->outerCall($f, $section);
                }
            }
        }
    }
    
    function callOuterFuncs($section, $priority = array('#')) {
        if (isset($this->callFunc[$section])&&is_array($this->callFunc[$section])) {
            foreach ($this->callFunc[$section] as $func) {
                if (in_array($func['priority'], $priority)) {
                    $this->outerCall($func, $section);
                }
            }
        }
    }
    
    function parseIf($section, $d_flag = 0) {
        if ($this->aifs[$section])
            return $this->aifs[$section];
        if ($tcode = preg_split($this->ifSplitPattern, $this->maindata[$section], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY)) {
            $lev = 0;
            $k   = 0;
            $en  = 0;
            foreach ($tcode as $citem) {
                if (preg_match('/\{%(?:if\((.*?)\))%\}/', $citem, $m)) {
                    $lev++;
                    $k                           = count($aif[$lev]) + 1;
                    $aif[$lev][$k]['logic']      = $m[1];
                    $aif[$lev][$k]['type']       = $aif[$lev][$k]['lt'] = 'if';
                    $aif[$lev][$k]['nestedfrom'] = count($aif[$lev - 1]);
                    $aif[$lev][$k]['source']     = $aif[$lev - 1][count($aif[$lev - 1])]['lt'];
                } elseif (trim($citem) == '{%else%}') {
                    $aif[$lev][$k]['lt'] = $aif[$lev][$k]['type'] = 'else';
                } elseif (trim($citem) == '{%endif%}') {
                    if ($lev > 0)
                        $aif[$lev][$k]['type'] = 'endif';
                } else {
                    if ($aif[$lev][$k]['type'] == 'endif') {
                        if ($lev == 1) {
                            $k                                  = count($aif[$lev - 1]);
                            $aif[0][$k][$aif[$lev][$k]['type']] = $citem;
                        } else {
                            $aif[$lev][$k][$aif[$lev][$k]['type']] = $citem;
                        }
                        $lev--;
                        $k = count($aif[$lev]);
                    } else {
                        $aif[$lev][$k][$aif[$lev][$k]['type']] = $citem;
                    }
                }
            }
            return $this->aifs[$section] = $aif;
        }
    }
    
    
    function checkLogic($section, $logic)
    {

                $repArray=$this->getAllCurrentReplacements($section);            
                preg_match('/{F:(.*?)}/',trim($logic),$a);
                foreach($repArray as $key=>$val)
                {
                    if(is_array($val))
                    {
                        $repArray[$key]=var_export($val,true);
                        
                    }elseif(!$val)
                    {
                            $repArray[$key]=null;
                    }
                }
                           
                return str_replace(array_keys($repArray),$repArray,$logic);
    }
    
    function ifReverse($aif, $section, $start = 0, $t = 0) {
        if (!$aif)
            return;
        if (!$start) {
            foreach ($aif[$start] as $iterm) {
                $t++;
                $it = current($iterm);
                $this->detectAndCallOuterFunc($it, $section);
                $result .= $it;
                if ($aif[$start + 1][$t]) {
                    $result .= $this->ifReverse($aif, $section, $start + 1, $t);
                }
            }
            return $result;
        } else {
            $it = $aif[$start][$t];
            if (strlen(trim($it['logic'])) > 0) {

                if (!$logic = $this->checkLogic($section, $it['logic'])) 
                {
                    $logic = 0;
                }
     
                $er = eval('(' . $logic . ')?$r=1:$r=0;');

                if ($er === false&&$error = error_get_last()) 
                {
                    
                    trigger_error('(IF) ERROR - section: |' . $section . '| logic: ' . $logic.' ', E_USER_WARNING);
                }
                if ($r) {
                    $this->detectAndCallOuterFunc($it['if'], $section);
                    $result .= $it['if'];
                    $y = 0;
                    if ($aif[$start + 1]) {
                        foreach ($aif[$start + 1] as $ai) {
                            $y++;
                            if ($ai['source'] == 'if' && $ai['nestedfrom'] == $t) {
                                $result .= $this->ifReverse($aif, $section, $start + 1, $y);
                            }
                        }
                    }
                    $this->detectAndCallOuterFunc($it['endif'], $section);
                    $result .= $it['endif'];
                } elseif ($it['else']) {
                    $this->detectAndCallOuterFunc($it['else'], $section);
                    $result .= $it['else'];
                    $y = 0;
                    if ($aif[$start + 1]) {
                        foreach ($aif[$start + 1] as $ai) {
                            $y++;
                            if ($ai['source'] == 'else' && $ai['nestedfrom'] == $t) {
                                $result .= $this->ifReverse($aif, $section, $start + 1, $y);
                            }
                        }
                    }
                    $this->detectAndCallOuterFunc($it['endif'], $section);
                    $result .= $it['endif'];
                } elseif ($it['endif']) {
                    $this->detectAndCallOuterFunc($it['endif'], $section);
                    $result .= $it['endif'];
                }
                return $result;
            }
        }
    }
    function implementLogic($section) {
        return $this->ifReverse($this->parseIf($section, 1), $section);
    }
    
    function parseSection($section, $glue = false, $force_return = null) {
        $section_content_prototype = $this->maindata[$section];
        $section_content           = $this->maindata[$section];
        $ext_str                   = '';
        if (strpos($this->maindata[$section], '{%if(') !== false) {
            $section_content_prototype = $this->implementLogic($section);
        } else {
            $this->callOuterFuncs($section, array(
                '@',
                '#'
            ));
        }
        $this->potentialKeys[$section] = array();
        if (!empty($this->AssignedArrays[$section])) {
            foreach ($this->AssignedArrays[$section] as $arr) {
                $this->AddMassReplace($section, $arr);
                if (!empty($this->Replacement[$section]) && !empty($this->Fields[$section])) {
                    $replacement = $this->Replacement[$section];
                    $fields      = $this->Fields[$section];
                }
                if (!empty($this->noprsVals[$section])) {
                    foreach ($this->noprsVals[$section] as $nprsval) {
                        if (($fkey = $this->FindReplacement($section, $nprsval) !== false)) {
                            unset($fields[$fkey]);
                            unset($replacement[$fkey]);
                        }
                    }
                }
                $section_content = str_replace(array_merge((array) $replacement, (array) $this->MFReplacement), array_merge((array) $fields, (array) $this->MFFields), $section_content_prototype);
                if ($this->pack_output) 
                {
                    $ext_str .= str_replace(array("\n","\r","\t"), array('','',''), $section_content);
                } else {
                    $ext_str .= $section_content;
                }
            }
        } else {
            if (!empty($this->Replacement[$section]) && !empty($this->Fields[$section])) {
                $replacement = $this->Replacement[$section];
                $fields      = $this->Fields[$section];
            }else{
                $fields=array();
                $replacement=array();
            }
            
            if (!empty($this->noprsVals[$section])) {
                foreach ($this->noprsVals[$section] as $nprsval) {
                    if (($fkey = $this->FindReplacement($section, $nprsval) !== false)) {
                        unset($fields[$fkey]);
                        unset($replacement[$fkey]);
                    }
                }
            }
            $m               = array_merge((array) $replacement, (array) $this->MFReplacement);
            $m2              = array_merge((array) $fields, (array) $this->MFFields);
            $section_content = str_replace(array_merge((array) $replacement, (array) $this->MFReplacement), array_merge((array) $fields, (array) $this->MFFields), $section_content_prototype);
            if ($section_content) {
                if ($this->pack_output) {
                    $ext_str .= str_replace(array(
                        "\n",
                        "\r",
                        "\t"
                    ), array(
                        '',
                        '',
                        ''
                    ), $section_content);
                } else {
                    $ext_str .= $section_content;
                }
            }
        }
        $this->callOuterFuncs($section, array(
            '-'
        ));
        if ((!empty($this->FastReplace[$section])) && (!$force_return)) {
            if ($glue) {
                $this->AddMFReplace('{%->:' . $this->FastReplace[$section] . '%}', $ext_str, true);
            } else {
                $this->AddMFReplace('{%->:' . $this->FastReplace[$section] . '%}', $ext_str);
            }
        } else {
            return $ext_str;
        }
    }
    function noparse($section, $return_splited = false) {
        return str_replace(array(
            "\n",
            "\r",
            "\t"
        ), array(
            '',
            '',
            ''
        ), $this->maindata[$section]);
    }
    function noparse_vals($section, $vals) {
        $this->noprsVals[$section] = $vals;
    }
}

interface xCommonInterface
{
    public function defineFrontActions();    
}

/**
* Родительский класс  для common составляющей модулей
*/
                     
class xCommon extends xSingleton
  {              
    public $_moduleName;
    public $_tree;
    public $_frontActionList;
    public $_useTree=false;

    
    function __construct($className) 
    {
        $this->_moduleName=str_replace('Common','',$className);
        
        if($this->_useTree)
        {
            $this->setTree($this->_moduleName);
        }
        $this->defineFrontActions();
    }       
  
    
        /*
         * @descr Определяет действие, которое возможно вызвать из front класса
         * @param string $action название действия(только латиница)
         * @param string $frontName алиас действия для отображения в списке действий модуля
         * @param array $subActions добавление данного параметра превращает действие в действие сервер и дает возможность выполнять субдействия
         * @param object  $callContext контекст вызова для данного действия если вызов идет не напрямую из класса модуля
         */
         
    function defineAction($action, $frontName='', $subActions=null,$callContext=null,$priority=3)
        {
            
            if(xConfig::get('GLOBAL','currentMode')!='front')
            {
                $frontName=$this->translateWord($action,'define_front_actions');     
                
            }else{
                
                $frontName='';
            }
            
            $this->_frontActionList[$action]=array
                (                
                    'frontName'=> $frontName,
                    'serverActions' =>$subActions,
                    'callContext'=>$callContext,
                    'priority'=>$priority
                );
        }
                  
    
    function  getAction($action)
    {
        return $this->_frontActionList[$action]; 
    }
                    
         /*
         * @descr получить все действия          
         */
         
    function getActions() { return $this->_frontActionList; }  
         /*
         * @descr получить все субдействия для заданного действия сервера
         * @param string  action
         */
            
    function getServerActions($action) { return $this->_frontActionList[$action]['serverActions']; }        
    
    
    function getServerActionExist($serverAction,$requestAction) 
    {         
        if(in_array($requestAction,$this->_frontActionList[$action]['subActions']))
        {
            return true;    
        }
        
    }        

        /**
         * @descr получить все действия у которых нет субдействий        
         */ 
            
    function getNonServerActions()
        {
        if ($this->_frontActionList)
            {
            foreach ($this->_frontActionList as $key => $iAction)
                {
                if (!is_array($iAction['serverActions']))
                    {
                        $nsa[$key]=$iAction;
                    }
                }

            return $nsa;
            }
        }
    
        /**
         * @descr установить основное дерево для модуля
         * @param string  $treeName
         */      
           
        function setTree()
        {         
            $this->_tree = new xte($this->_moduleName.'_container',xRegistry::get('xPDO'));
        }
    
    
    
        function moduleCoreRegister()
        {
                
        }      
        
        
        function translateWord($word,$template='common')
        {
            $l = Common::getModuleLang($this->_moduleName, $_SESSION['lang'], $template);   
            return $l[$word]; 
        }
        
        function getTranslation($template='common')
        {
           return Common::getModuleLang($this->_moduleName, $_SESSION['lang'], $template);  
        }
    
    
}

interface xPluginListener{}

class xListener 
{
    private   $execClassName;
    public    $_EVM;
    public    $useModuleTplNS;   
    public    $useModuleXfrontNS;   
    
    function __construct($name)
    {
        $this->_EVM=xRegistry::get('EVM');       
        $this->execClassName=$name;
    }
    
    
    public function useModuleTplNamespace()
    {
        $this->useModuleTplNS=true;
    }
    
    public function useModuleXfrontNamespace()
    {
        $this->useModuleXfrontNS=true;
    }
    
    final public function __call($chrMethod, $arrArguments) 
    {        
        
        $implements=class_implements($this);
        
        if(in_array('xPluginListener',$implements))
        {
            $objInstance=xCore::pluginFactory($this->execClassName.'.front');    
        }
        
        return call_user_func_array(array(
            $objInstance,
            $chrMethod
        ), $arrArguments);
    } 
}
 
class xPlugin extends xSingleton
{
    private $modules;

    public function __construct()
    {   
        $this->_TMS=xRegistry::get('TMS');                 
        $this->_PDO=xRegistry::get('PDO');                 
        $this->_EVM=xRegistry::get('EVM');  
    }
    
    
    public function modulesRequire($modules)
    {
        foreach ($modules as $module)
        {
            xCore::moduleFactory($module.'.front');    
        }        
    }
    
    
}    

interface xPluginTpl{}
interface xModuleTpl{}    


class xTpl
{
      private  $execClassName;
      private static  $xTplInstance;
      private  $objModuleInstance;
      function __construct($name)
    {    
        $this->execClassName=$name;

    }
    
      public static function __load($module,$addModuleNamespace=false)
      {
         if(isset(self::$xTplInstance[$module])) return  self::$xTplInstance[$module];
      
         if(strpos($module,'.')!== false)  
        {                   
            $plugin=explode('.',$module);
            
            if(file_exists($tplClass=xConfig::get('PATH' ,'PLUGINS') . $module . '/' . $plugin[1] . '.' .'tpl.class.php'))
                        {
                            include_once($tplClass);
                            $tplClassName=$plugin[1].'Tpl';
                            self::$xTplInstance[$module]= new $tplClassName($plugin[1]);
                            
                            if($addModuleNamespace)
                            {        
                                xNameSpaceHolder::addObjectToNS('module.'.$plugin[0].'.tpl',self::$xTplInstance[$module]);                                                 
                            }   
                            xNameSpaceHolder::addObjectToNS('plugin.'.$module.'.tpl',self::$xTplInstance[$module]);
                            return self::$xTplInstance[$module];  
                        } 
            
        }else{
         
             if(file_exists($tplClass=xConfig::get('PATH' ,'MODULES') . $module . '/' . $module . '.' .'tpl.class.php'))
                        {
                            include_once($tplClass);
                            $tplClassName=$module.'Tpl';
                            self::$xTplInstance[$module]= new $tplClassName($module);   
                            xNameSpaceHolder::addObjectToNS('module.'.$module.'.tpl',self::$xTplInstance[$module]);
                            // стартуем tpl нейспейсы плагинов данного модуля
                            
                            if($mPlugins=xCore::getModulePluginsListeners($module))
                            {
                                 foreach($mPlugins as $mPlugName=>$mPlug)
                                 {
                                        if($mPlug->useModuleTplNS)
                                        {
                                             xTpl::__load($module.'.'.$mPlugName,true);
                                        }    
                                 }
                            }
                            
                            return self::$xTplInstance[$module];  
                            
                        }
        }
      }         
                
    
    final public function __call($chrMethod, $arrArguments) 
    {        
        if(!$this->objModuleInstance)
        {
                $implements=class_implements($this);
                
                if(in_array('xPluginTpl',$implements))
                {
                    $this->objModuleInstance=xCore::pluginFactory($this->execClassName.'.front');  
                      
                }else{
                    
                    $this->objModuleInstance=xCore::moduleFactory($this->execClassName.'.front');
                    
                }
        }
        return call_user_func_array(array(
            $this->objModuleInstance,
            $chrMethod
        ), $arrArguments);
    } 
    
}


class xCore
{
    private static $plugins;
    private static $pluginsListeners;
    private static $jsList;
    
     public static  function getLicense()
     {
            return HTTP_HOST.'|'.file_get_contents(PATH_.'license');
     }
        
    static function getVersion()
    {
        return '0.1';    
    }
    
    static function isModuleExists($name)
        {
            static $moduleList;  
            
            global $_PATH;  
            
            if($moduleList[$name])
                {
                    return $moduleList[$name];
                    
                }else{
                
                    $moduleList[$name]=xPDO::selectIN('*', 'modules', ' name="'.$name.'"');
                }
              
              if(($moduleList[$name])&&(file_exists(xConfig::get('PATH' ,'PLUGINS') . $name . '/js/' . $name. '.js')))
              {
                    return $moduleList[$name];
                    
              }else
              {
                  $moduleList[$name]=null;
              }
            }
        
        
    
    public static function pluginEventDetector()
    {
         if($plugs=self::pluginsList())
         {   
           foreach($plugs as $pModuleName=>$pModule)
           {  
            foreach($pModule as $plugName=>$plugFullName)
            {
                if(file_exists($ped=xConfig::get('PATH' ,'PLUGINS').$plugFullName.'/'.$plugName.'.listener.class.php'))
                {
                    include_once($ped);
                    $classname=$plugName.'Listener';
                    self::$pluginsListeners[$pModuleName][$plugName]= new $classname;
                }
            }
           }   
         }
         
    }
        public static function getModulePluginsListeners($module,$plugin=null)
    {
         if($module&&$plugin)
         {
            return self::$pluginsListeners[$module][$plugin];
            
         }else{
             
            return self::$pluginsListeners[$module];
         }
        
    }
    
    public static function getModulePlugins($module,$plugin=null)
    {
         if($module&&$plugin)
         {
            return self::$plugins[$module][$plugin];
            
         }else{
             
            return self::$plugins[$module];
         }
        
    }
    
    public static function pluginsList()
    {
        if(self::$plugins){return self::$plugins;}
        
        if($plugs=XFILES::directory_list(xConfig::get('PATH','PLUGINS')))
        {
            foreach($plugs as $plug)
            {
                $plugParts=explode('.',$plug);          
                self::$plugins[$plugParts[0]][$plugParts[1]]=$plug;
                
            }   
            
            return self::$plugins;
        }
        
    }
        
    public static function pluginFactory($plugin)
    {
        
            $plugin      =explode('.', $plugin);  
            
            if(count($plugin)==3)
            {
                $loadPrefix=array_shift($plugin);
                $loadPrefix .='.';    
            }
            
            if (xRegistry::exists($plugin[0]))
                {
                  return xRegistry::get($plugin[0]);
                }       
                
                
                $name=$plugin[1];
                $name[0]==strtoupper($name[0]);
                $classname=$plugin[0].$name;
                
                
                
                if($plugin[1] =='xfront')
                {
                    require_once (xConfig::get('PATH' ,'PLUGINS') .$loadPrefix. $plugin[0] . '/' . $plugin[0] . '.front.class.php');                                  } 
                    
                $moduleInstancePath =xConfig::get('PATH' ,'PLUGINS') .$loadPrefix. $plugin[0] . '/' . $plugin[0] . '.'.$plugin[1].'.class.php';                       require_once ($moduleInstancePath);   

             
        
                if (class_exists($classname))
                {
                    //все конструкторы класса без параметров
                    xRegistry::set($plugin[0].'.'.$plugin[1],$instance=new $classname());
                    
                    if($plugin[1] =='xfront')
                    {
                        xNameSpaceHolder::addObjectToNS('plugin.'.$loadPrefix. $plugin[0].'.xfront',$instance);
                    }
                    
                    //готовый класс
                    return xRegistry::get($plugin[0].'.'.$plugin[1]);
                
                }
    }
    
  public   static  function moduleFactory($modulename)
        {
        global $_PATH;
    
        $xRegCheck=explode('.',$modulename); $xRegName=$xRegCheck[0].strtoupper($xRegCheck[1][0]).substr($xRegCheck[1],1);

        if (xRegistry::exists($xRegName))
            {
              return xRegistry::get($xRegName);
            }
            
            $module      =explode('.', $modulename);
            
            xCore::callCommonInstance($module[0]);
        
            //в случае xfront должен быть подключен модуль front
            if(($module[1] =='xfront')&&(file_exists($inst=xConfig::get('PATH' ,'MODULES') . $module[0] . '/' . $module[0] .'.front.class.php')))
            {
                    require_once (xConfig::get('PATH' ,'MODULES') . $module[0] . '/' . $module[0] . '.front.class.php');
            }
                       
            if($module[1]=='cron')
            {
                xCore::moduleFactory($module[0].'.back');            
            }
             
            $moduleInstancePath =xConfig::get('PATH' ,'MODULES') . $module[0] . '/' . $module[0] . '.' . $module[1] . '.class.php';
   
            require_once ($moduleInstancePath);
                
            $module[1][0]=strtoupper($module[1][0]);
                
                
            //calling class
            if (class_exists($classname=$module[0].$module[1]))
                {
                    //все конструкторы класса без параметров
                    xRegistry::get('EVM')->fire($modulename.':beforeInit');
                    xRegistry::set($classname,$m=new $classname());
                    xRegistry::get('EVM')->fire($modulename.':afterInit',array('instance'=>$m));
                    xConfig::set('calledModules',$module[0],$m);
                    //готовый класс
                    return $m;
                
                }
   }
        
   public static function getModuleList($get_actionable_only = false)
        {
            global $_PATH;
            static $moduleList;
            
            if (is_array($moduleList)&&!($get_actionable_only))
                {
                return $moduleList;
                }

            if ($get_actionable_only)
                {
                $where=' actionable=1 order by id ASC';
                }
            else
                {
                $where='1 order by id ASC';
                }
                   
            if($moduleList=xPDO::selectIN('*', 'modules', $where))
            {
            
                $l=Common::getModuleLang('admin',$_SESSION['lang'],'modules');
                
                foreach($moduleList as $k => $module)
                    {
                        $moduleList[$k]['alias']= $l[$module['name']];
                        
                        if(!file_exists(xConfig::get('PATH' ,'MODULES') . $module['name'] . '/js/' . $module['name'] . '.js')){unset($moduleList[$module['name']]);}            
                        
                    }
            
            }

         return $moduleList;
         
        }
            
     public static function loadCommonClass($module)
     {
            self::callCommonInstance($module);
     
            return call_user_func($module.'Common::getInstance',$module.'Common');
     }
     
    
     public static function callCommonInstance($module)
     {
        require_once (xConfig::get('PATH' ,'MODULES') . $module . '/' . $module . '.common.class.php');
     }

}


class xModulePrototype
{
    public $_moduleName;
    public $_tree;
    public $_commonObj;    
    public $_TMS;
    public $_EVM;
    
   
   function __construct($className)
   {
        $this->_TMS=xRegistry::get('TMS');                 
        $this->_PDO=xRegistry::get('PDO');                 
        $this->_EVM=xEventMachine::getInstance();
        $this->_moduleName=str_replace(array('Front','Back'),array('',''),$className);
        $this->_commonObj=call_user_func($this->_moduleName.'Common::getInstance',$this->_moduleName.'Common');
        if($this->_commonObj->_tree)$this->_tree=$this->_commonObj->_tree;
   }



}     

/**
* Родительский класс  для back составляющей модулей
*/


class xModuleBack extends xModulePrototype
{
   
       function __construct($className)
       {
                parent::__construct($className);
       }
       
       
       function initiateBackPlugins()
       {
           
       }
       
       function loadTemplates()
       {
           
       }
       
       /**
        * Операции над объектами
        * 
        * @param $parameters
        * 
        * ['action']
        *           create 
        *           read
        *           udpate 
        *           new          
        *  
        * ['data']- object data
        * ['objectType']
        */
        
       
       function CRUN($parameters)
       {
           $parameters['action'][0]=strtoupper($parameters['action'][0]);
           if(method_exists($this,$mc='on'.$parameters['action'].$parameters['objType']))
           {
               $result=call_user_func_array(array($this,$mc,array($parameters['data'])));
               if($result===false)return;
               if(is_array($result))$parameters['data']=$result;

               if($parameters['action']=='New')
               {
                   global $adm;
                   $adm->loadModuleTpls($this->_moduleName,$parameters['tpl']);
                   $this->lct=$adm->lct;
               }
               
               if($parameters['action']=='Read')
               {
                   global $adm;
                   $adm->loadModuleTpls($this->_moduleName,$parameters['tpl']);
                   $this->lct=$adm->lct;
               }

               
               if($parameters['action']=='Create')
               {
                   $id=$this->_tree->initTreeObj($parameters['data']['ancestor'], $parameters['data']['basic'],$parameters['data']['objType'] ,$parameters['data']['params']);                   
                   $this->result['id']=$id;
               }
               
               if($parameters['action']=='Update')
               {
                   if(!$parameters['data']['basic'])$parameters['data']['basic']='%SAME%';                   
                   $this->_tree->reInitTreeObj($parameters['data']['id'], $parameters['data']['basic'],$parameters['data']['params']);
               }
                   
               return $result;    
                   
           }
       }
       
       
}       

/**
* Родительский класс  для front составляющей модулей
*/

class xModule extends xModulePrototype
{
    public $_meta;
    public $_requestAction;
   
       function __construct($className)
       {
                parent::__construct($className);
       }

   
    function loadModuleTemplate($tpl)
    {
        $this->_TMS->AddFileSection(Common::getFrontModuleTplPath($this->_moduleName, $tpl));    
    }
   
    function requestActionSet($action)
    {
         if ($action)
            {
            foreach ($this->_commonObj->_frontActionList as $iaction)
                {
                    if (is_array($iaction['serverActions']))
                    {
                        if (in_array($action, $iaction['serverActions']))
                            {
                                $this->requestAction=$action; return;
                            }
                    }
                }
            }
    }
    
    

    function isAction($actionData)
        {
                $actionData['fullActionData']=$this->_commonObj->getAction($action=$actionData['Action']);
                
                $server=$this->_commonObj->getServerActions($action);
                
                if($this->requestAction&&$server[$this->requestAction])
                {                            
                     $actionData['Action']=$this->requestAction;
                
                     if(!$context=$actionData['fullActionData']['callContext'])
                     {
                         $context=$this;
                     }
                        /*функция сервера выполняется в случае пришедшего действия*/                        
                     if($actionInnerData=call_user_func(array($context, $action),$actionData))
                     {
                         return   $actionInnerData;
                     }
                        
                }elseif(!$this->requestAction&&$actionData['DefaultAction'])
                {
                    $actionData['Action']=$actionData['DefaultAction'];
                }
                
                return $actionData;                      
        }
            
            
            
    public function initiateXfrontPlugins()
    {
                if($mPlugins=xCore::getModulePluginsListeners($this->_moduleName))
                            {
                                
                                 foreach($mPlugins as $mPlugName=>$mPlug)
                                 {
                                        if($mPlug->useModuleXfrontNS)
                                        {
                                             $pluginXfront=xCore::pluginFactory($this->_moduleName.'.'.$mPlugName.'.xfront');
                                             xNameSpaceHolder::addObjectToNS('module.'.$this->_moduleName.'.xfront',$pluginXfront);
                                        }    
                                 }
                            }
    }
    
    
   function execute($actionData, $backSlot = null)
    {
        global $TMS;     
        
        if(is_array($actionData) && $actionData = $this->isAction($actionData)) 
        {
            $TMS->startLogging($action=$actionData['Action']);
             
                if(!$context=$actionData['fullActionData']['callContext'])
                     {
                         $context=$this;
                     }
                
                if($q=call_user_func(array($context, $action),$actionData))
                
            $TMS->clearLogged($action);
            return $q;
        }
    }

    

}



class xPDO extends PDO 
{
    private static $objInstance;
    private static $host;
    private static $dbname;
    private static $user;
    private static $password;
    private static $encoding;
    
    public  static $lastInserted;
    
    function __construct() {}
    
    private function __clone() {}
    /*
     * создание PDO соединения
     * @param
     * @return $objInstance;
     */
     
    public static function setSource($host, $dbname, $user, $password, $encoding = 'utf8')
    {
        self::$host=$host;
        self::$dbname=$dbname;
        self::$user=$user;
        self::$password=$password;
        self::$encoding=$encoding;
        
    }
     
    public static function getInstance() {
        if (!self::$objInstance) {
                self::$objInstance = new PDO('mysql:host=' .self::$host . ';dbname=' . self::$dbname, self::$user, self::$password);
                self::$objInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$objInstance->exec('SET CHARACTER SET ' . self::$encoding);
                self::$objInstance->exec('set character_set_results=' . self::$encoding);
                self::$objInstance->exec('SET NAMES ' . self::$encoding);
        }
        return self::$objInstance;
    } // end method

    
/*
    final public static function __callStatic($chrMethod, $arrArguments) 
    {
        $objInstance = self::getInstance();
        return call_user_func_array(array(
            $objInstance,
            $chrMethod
        ), $arrArguments);
    } 
*/ 

   
    /**
     * Получить название колонок
     * @param $table
     * @return $mix
     */ 
 
    private static function getColumnNames($table) 
    {
        static $tables=array();        
        
        if($tables[$table])return $tables[$table];
        
        $PDO          = self::$objInstance;
        $sql = 'SHOW COLUMNS FROM ' . $table;        
        $stmt         = $PDO->prepare($sql);
        $column_names = array();
        try {
            if ($stmt->execute()) {
                while($raw_column_data = $stmt->fetch(PDO::FETCH_ASSOC))
                {
                    if($raw_column_data['Field']!='id')
                        {$column_names[$raw_column_data['Field']] = '';}else{$column_names[$raw_column_data['Field']] = 'NULL';}
                }
                    return $tables[$table]=$column_names;    
            }}
        catch (Exception $e) {
            return $e->getMessage();
        }
    }
    
    static function escape ($str)
        {
            return mysql_escape_string(stripslashes($str));                
        }
        
    /**
     * выбрать данные из таблицы(нескольких таблиц)
     * @param  array $select - список параметров
     * @param  string $from - таблицы
     * @param  string $where - условие
     * @return array
     */ 
         
    public static function selectIN($select = '*', $from, $where = '', $special_cond = '') 
    {
        $PDO          = self::$objInstance;
        if (is_array($select)) {$select = '`' . implode(`,`, $select) . '`';}
        if ($where) 
        {
            $where = 'where ' . $where;
        }
        $query = "select $select from $from $where  $special_cond";
        if($result=$PDO->query($query)) return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * вставить данные в таблицу
     * @param  string $table  - название таблицы
     * @param array  $insertVals -  ассоциативный массив значений
     * @return bool
     */      
    
    function insertIN($table, $insertVals) {
        
        $PDO          = self::$objInstance;
        $checkFields = self::getColumnNames($table);        
        foreach ($insertVals as $key => $val) 
        {
            if (array_key_exists($key, $checkFields)) {
                $checkFields[$key] = self::escape($val);
            }
        }
        $values=implode("','", array_values($checkFields)); 
        $values = str_replace(array("'NOW()'","'null'","'NULL'"), array('NOW()','null','NULL'), $values);        
        $query  = "INSERT INTO `$table` (" . '`' . implode('`,`', array_keys($checkFields)) . '`' . ")VALUES(" ."'" . $values . "'" . ')';
        
        if ($this->lastInserted = $PDO->exec($query)) 
        {
            return true;
        }
    }
    
    
    /**
     * обновить данные в таблице
     * @param  string $table  - название таблицы
     * @param  mixed $express выражение либо число в случае целого числа оно трактуется как параметр ID
     * @return bool
     */      
    
    function updateIN($table, $express, $updateVals) 
    {
        $PDO          = self::$objInstance;
        if (is_int($express)) {
            $express = "`id` = '$express' LIMIT 1";
        }
        foreach ($updateVals as $key => $val) 
        {
            $val = self::escape($val);
            $updateline .= "`$key` = '$val',";
        }
        $updateline = substr($updateline, 0, strlen($updateline) - 1);
        $query      = "UPDATE `$table` SET $updateline WHERE $express";
        
        return $num=$PDO->exec($query);
     
     
    }
    
}


/**
*  дочерний класс объектов к xte
*  используется для получения результатов в виде дерева 
*  дерево храниться в виде матрицы смежности с исключенными нулевыми пересечениями
*/

class xteTree {
    
    private $startNode;
    public $tree;
    public $nodes;
    
    function __construct($nodes, $startNode) {
        $this->startNode = $startNode;
        
        if (is_array($nodes)) {
            while (list($key, $val) = each($nodes)) {
                $this->nodes[$val['id']]=$val;                
                $this->tree[$val['ancestor']][$val['id']] = $val;
                
            }
        }
    }
    /**
    * удалить все данные о ноде
    * @param mixed $id - id ноды
    */
    
    function remove($id)
    {
        unset($this->nodes[$id]);    
        unset($this->tree[$id]);
        
        $nLength=count($this->tree);        
        $keysNodes=array_keys($this->tree);
        
        for($i=0;i<$nLength;$i++)
        {            
            $subLength=count($this->tree[$keysNodes[$i]]);        
            $subKeysNodes=array_keys($this->tree[$keysNodes[$i]]);       
            
            for($j=0;$j<$subLength;$j++)
            {            
                if($subKeysNodes[$j]==$id)
                {
                    unset($this->tree[$keysNodes[$i]][$subKeysNodes[$j]]);
                }
            }
        }
    }
     /**
     * есть ли дочерние элементы
     * 
     * @param mixed $id
     */
    function hasChilds($id)
    {
             if($this->tree[$id])return true;
    }
    
    /**
    *  подсчитать количество элементов в ветке
    * 
    * @param mixed $id
    * @return int
    */
    
    
    
    public function __get($id) 
    {
        return $this->nodes[$id];
    }
    
    function countBranch($id)
    {
            return count($this->tree[$id]);
    }
    
    
    /**
    * получить массив нод по значению предка    
    * @param mixed $ancestor  - id предка
    */
    
    function fetchArray($ancestor) 
    {
        return $this->tree[$ancestor];
    }
    
    /**
    * Рекурсивная  прохождение по дереву значений  с использованием callback функции
    * 
    * @param int $startNode - стартовая нода
    * @param object $context -контекст callback функции
    * @param string $function - имя функции 
    * @param mixed $extdata - необязательный параметр даполнительных данных
    */
    
    function recursiveStep($startNode,$context,$function,$extdata=null)
    {
        if(isset($this->tree[$startNode]))
        {
            foreach($this->tree[$startNode] as $node)
            {        
                if($this->tree[$node['id']])
                    {
                        call_user_func_array(array($context,$function),array($node,$startNode,$this,$extdata));
                        $this->recursiveStep($node['id'],$context,$function);
                    }else{
                        
                        call_user_func_array(array($context,$function),array($node,$startNode,$this,$extdata));
                    }
            }
        }
        
    }
     /**
     * функция для фетчинга нод дерева последовательно
     * 
     * @param mixed $ancestor
     * @return mixed
     */
    function fetch($ancestor = null) {
        static $reset;
        if (!$ancestor)$ancestor = $this->startNode;
        
        if(!isset($this->tree[$ancestor]))return;
        
        if (!$reset[$ancestor]) {
            reset($this->tree[$ancestor]);
            $reset[$ancestor] = true;
        }
        if (list($key, $val) = each($this->tree[$ancestor])) {
            return array(
                $key , $val
            );
        } else {
            $reset[$ancestor] = false;
            return;
        }
    }
}

class xte {
    public static $UNIQ_ANCESTOR = 1;
    public static $UNIQ_TREE = 2;    
    private $treeStructName;
    private $treeParamName;
    private $query;
    private $levels = 12;
    private $uniqType;    
    private $filter;
    private $nodeCache = array();
    private $lockObjType;
    private $formatTypes = array('normal', 'keyval');
    private $nativeStructFieldsList = array('id', 'obj_type', 'rate', 'basic');
    public  $lastNonUniqId;
    public  $exceptionHandlers=array();
    public  $PDO;
    private $cacheDir='tree';
    private $enableCache=false;
    private $cacheTimeout=3600;
    
    function setCacheParams($dir,$timeOut=3600)
    {
        $this->cacheDir=$dir;
        $this->cacheTimeout=$timeOut;
    }
    
    function enableCache($enable)
    {
        $this->enableCache=$enable;
    }
    
    function __construct($treeName, $PDO, $uniqType = 1) {
        $this->PDO =& $PDO;
        $this->treeStructName = strtolower("_tree_" . $treeName . "_struct");
        $this->treeParamName  = strtolower("_tree_" . $treeName . "_param");
        $this->uniqType       = $uniqType;
    }
    
    private function getSelectQueryString() {
        if ((is_string($this->query['selectStruct']) && $this->query['selectStruct'] == '*') or $this->query['basicpath']) {
            $selectStructString = '*';
        } elseif (is_array($this->query['selectStruct'])) {
            $this->query['selectVirtualStruct'] = array_diff($this->query['selectStruct'], $this->nativeStructFieldsList);
            $this->query['selectStruct']        = array_intersect($this->nativeStructFieldsList, $this->query['selectStruct']);
            $selectStruct                       = $this->query['selectStruct'];
            
            if (count($this->query['selectVirtualStruct']) > 0) 
            {
                for ($i = 1; $i < $this->levels + 1; $i++)
                    $selectStruct[] = 'x' . $i;
            }
            $selectStructString = implode(',', $selectStruct);
        }
        return array(
            'selectStructString' => $selectStructString
        );
    }
    private function getWhereQueryString() 
    {        
        if (isset($this->query['childsAncestor'])) {
            
            if(is_array($this->query['childsAncestor']))
            {
                
                for($level=1;$level<($this->levels); $level++)
                {                
                    if(($stopLevel=$level+$this->query['childsLevel'])>=$this->levels)
                    {                        
                        $stopLevel=$this->levels;                        
                    }

                     $sql[]= '( x' .$level . ' in ("' . implode($this->query['childsAncestor'],'","') . '") AND x'.($stopLevel).' IS NULL)';
                }
                
                 $sql= '('.implode($sql,' or ').')';
                
            }else{
                
                $ancestor = $this->getNodeStruct($this->query['childsAncestor']);
                $level    = count($ancestor['path']);
                $sql      = 'x' . ($level + 1) . ' = ' . $this->query['childsAncestor'];
                if ($this->query['childsLevel']) 
                {
                    $endlevel = $level + $this->query['childsLevel'];
                    $sql .= 'and x' . $endlevel . '  IS NULL)';
                }
            
            }
            $whereStruct[] = $sql;
        }
        
        $whereParams=array();
        
        if (is_array($this->query['where']) && (count($this->query['where']) > 0)) {
            foreach ($this->query['where'] as $pairs) {
                // struct var
                if (strpos($pairs[0], '@') === 0) {
                    $pairs[0] = substr($pairs[0], 1);
                    
                        if($pairs[0]=='id'&&!is_array($pairs[2]))
                        {
                            $this->query['singleResult']=true;    
                        }
                    
                    if ($pairs[0] == 'ancestor') 
                    {
                        $ancestor      = $this->getNodeStruct($pairs[2]);
                        $level         = count($ancestor['path']) + 1;
                        $pairs[0]      = 'x' . $level;
                        $whereStruct[] = ' x' . ($level + 1) . ' IS NULL ';
                    }
                    
                    if ($pairs[0] == 'inpath')
                    {
                            for ($i = 1;$i < $this->levels ; $i++)
                            $whereStruct[] = ' x' . ($level) . '='.$pairs[2];
                    } 
                    
                    
                    if (is_array($pairs[2]) and ($pairs[1] == '=')) {
                        $whereStruct[] = $pairs[0] . ' IN ("' . implode('","', $pairs[2]) . '")';
                    } else {
                        if (!is_numeric($pairs[2])) {
                            $pairs[2] = "'{$pairs[2]}'";
                        }
                        $whereStruct[] = $pairs[0] . $pairs[1] . $pairs[2];
                    }
                } else {
                    if (is_array($pairs[2]) and ($pairs[1] == '=')) {
                        $whereParams[] = "(parameter = '{$pairs[0]}'  AND value IN (" . implode('","', $pairs[2]) . '))';
                    } else {
                        if (!is_numeric($pairs[2])) {
                            $pairs[2] = "'{$pairs[2]}'";
                        }
                        $whereParams[] = "(parameter = '{$pairs[0]}'  AND value " . $pairs[1] . " {$pairs[2]})";
                    }
                }
            }
        }
        if ($whereStruct){ $result['whereStructString'] = implode(' AND ', $whereStruct); }
        
        
        if (!empty($whereParams)) $result['whereParamsString'] = implode(' OR ', $whereParams) . ' GROUP BY b.node_name HAVING CCOUNT =' . count($whereParams);
        
        return $result;
    }
    
    private function nodeResult($pdoResult, $locCall = false) {
        if ($this->query['selectStruct'] == '*' or ($locCall)) {
            $nRes = array(
                'id' => $pdoResult['id'],
                'obj_type' => $pdoResult['obj_type'],
                'basic' => $pdoResult['basic'],
                'rate' => $pdoResult['rate'],
                
            );
            
           if(isset($pdoResult['params']))$nRes['params'] = $pdoResult['params'];
            
            unset($pdoResult['id'], $pdoResult['params']);
            $nRes['path'] = $levelsPath = array_filter(array_values(array_splice($pdoResult, 3, 1 + $this->levels)));
            if (isset($this->query['basicpath']) && $levelsPath) {
                $this->query['pathCache'] = array_merge($this->query['pathCache'], $levelsPath);
            }
            $nRes['ancestor']      = end($levelsPath);
            $nRes['ancestorLevel'] = count($levelsPath);
            return $nRes;
        } else {
            foreach ($this->query['selectStruct'] as $field_key) {
                $result[$field_key] = $pdoResult[$field_key];
            }
            $result['params'] = $pdoResult['params'];
            
            
            if (isset($this->query['selectVirtualStruct'])) {
                $levelsPath = array_filter(array_values(array_splice($pdoResult, count($this->query['selectStruct']), count($this->query['selectStruct']) - 1 + $this->levels)));
                foreach ($this->query['selectVirtualStruct'] as $vStruct)
                    switch ($vStruct) {
                        case 'path':
                            $result['path'] = $levelsPath;
                            $this->query['pathCache'] = array_merge($this->query['pathCache'], $levelsPath);                                
                            break;
                        case 'ancestor':
                            $result['ancestor'] = end($levelsPath);
                            break;
                        case 'ancestorLevel':
                            $result['ancestorLevel'] = count($levelsPath);
                            break;
                    }
            }
            return $result;
        }
    }
    /**
    * Сменить предка - перенос ноды в другую точку дерева
    * 
    * @param mixed $nodename индефикатор(id) ноды  - первичная нода
    * @param mixed $newancestor - предок к которому должны быть присоединена нода
    * @param mixed $relative - id относительной ноды ,  нода относительно который должна быть помещена первичная нода
    * @param mixed $position - позиция размещения last либо first
    */
    public function changeAncestor($nodename, $newancestor, $relative = null, $position = 'last') {
        
        $ids = $this->selectStruct(array('rate','id','path','ancestor','ancestorLevel'))->where(array('@id','=',array($nodename,$newancestor)))->format('keyval', 'id')->run();
        
        if ((count($ids) == 2) and ($newancestor != $nodename)) {
            $nxt   = $ids[$nodename][ancestorLevel] + 1;
            $query = "update {$this->treeStructName} set rate=rate-1 WHERE  `x{$ids[$nodename][ancestorLevel]}`='{$ids[$nodename][ancestor]}'  and  `x$nxt` is NULL   and rate>{$ids[$nodename][rate]}";
            $res   = $this->PDO->prepare($query);
            $res   = $res->execute();           
        
        /*
              if (!$relative)
            {
                $this->move_rate_fl($newancestor, $nodename, $position);
            }
            else
            {
                if ($position == 'last')
                $position='up';
                $this->move_rate($nodename, $relative, $position);
                }
            }
         */   
            
            foreach ($ids[$newancestor]['path'] as $pid => $pval) {
                $path_update_line[] = '`x' . ($pid + 1) . "`='$pval'";
            }
                $path_update_line[] = '`x' . ($ids[$newancestor]['ancestorLevel'] + 1) . "`='{$ids[$newancestor][id]}'";
                $query              = "update `{$this->treeStructName}` SET " . implode($path_update_line, ' , ') . " WHERE `id` = '$nodename' LIMIT 1";
                $pdoPoint           = $this->PDO->query($query);
                
            if ($pdoPoint->rowCount > 0) {
                return true;
            }
        }
    }
    
    /**
    * Получить предыдущую ноду относительно указанной
    * 
    * @param mixed $id индефикатор ноды
    * @param mixed $n необязательный параметр отступа, например взять предыдущую ноду через 2 относительно указанной
    * @param mixed $rev - внутренный параметр инверсии отступа
    * @return mixed
    */
    function getPrev($id, $n = 1, $rev = '<') {
        $id = $this->getNodeStruct($id);
        if ($rev == '<') {
            $order = 'desc';
        } else {
            $order = 'asc';
        }
        $nxt = $id[ancestorLevel] + 1;

        $query = 'select id  from ' . $this->treeStructName . " where `x{$id[ancestorLevel]}`='{$id[ancestor]}'  and  `x$nxt` is NULL and rate" . $rev . $id['rate'] . ' order by rate ' . $order . ' limit ' . ($n - 1) . ',1';
        if ($pdoResult = $this->PDO->query($query)) 
        {
            $row = $pdoResult->fetch(PDO::FETCH_NUM);
            return $row[0];
        } else {
            return null;
        }
    }
    
    /**
    * Получить последующую ноду относительно указанной
    * 
    * @param mixed $id индефикатор ноды
    * @param mixed $n необязательный параметр отступа, например взять следующую ноду через 2 относительно указанной
    * @return mixed
    */
    function getNext($id, $n = 1) {
        return $this->getPrev($id, $n, '>');
    }
    
    /**
    * Получить полную информацию о ноде
    * @param mixed $id
    */
    
    public function getNodeInfo($id) 
    {  
 
        if (!isset($this->nodeCache[$id])) 
        {
 
            $query='SELECT a. * , b.parameter, b.value FROM `' . $this->treeStructName . '` a ,`' . $this->treeParamName . '` b where a.id = b.node_name  and a.id = ' . $id;
            
            if ($pdoResult = $this->PDO->query($query)) 
            {
            
                if($row=$pdoResult->fetch(PDO::FETCH_ASSOC))
                {            
                    $ext=$row;
                    $ext['params'][$row['parameter']]=$row['value'];                
                    unset($ext['parameter'],$ext['value']);
                    

                while ($row=$pdoResult->fetch(PDO::FETCH_ASSOC))
                    {
                        $ext['params'][$row['parameter']]=$row['value'];                
                    }
                    
                    return $this->nodeCache[$id] = $this->nodeResult($ext,true);
               
                }
           }
        } elseif(empty($this->nodeCache[$id]['params'])&&$this->nodeCache[$id]['id']) 
        {
            
            $query='SELECT b.parameter, b.value FROM `' . $this->treeParamName . '` b where  b.node_name = ' . $id;        
            
            if ($pdoResult = $this->PDO->query($query)) 
            {
                while ($row=$pdoResult->fetch(PDO::FETCH_ASSOC))
                    {
                        $this->nodeCache[$id]['params'][$row['parameter']]=$row['value'];                
                    }
            }
            
            return  $this->nodeCache[$id];
            
        }else
        {
                return $this->nodeCache[$id];    
        }
    }
    
    
    /**
    * Получить информацию о структуре ноды
    * @param mixed $id
    */
    public function getNodeStruct($id) {
        
        if (!isset($this->nodeCache[$id])) 
        {
            $sql = 'SELECT * FROM `' . $this->treeStructName . '` WHERE id =' . $id . ' LIMIT 1';     

            if ($pdoResult = $this->PDO->query($sql)) 
            {
                return $this->nodeCache[$id] = $this->nodeResult($pdoResult->fetch(PDO::FETCH_ASSOC), true);
            }
        } else {
            return $this->nodeCache[$id];
        }
    }
    
    /**
    * Получить информацию о структуре ноды
    * @param mixed $id
    */
    
    function idByBasicPath($path, $objType = null, $root_include = null, $c_basic = 0) {
        if (!$path)
            return;    
        
        $xcount = count($path);
        
        if ($root_include) 
            {
                array_unshift($objType, '_ROOT');
                array_unshift($path, '%0%');
            }
            
            if ($objType) {
            $object_list = 'AND  `obj_type` in ("' . implode('","', $objType) . '")';
        }
        
        $query  = 'select *  from ' . $this->treeStructName . '  where x' . ($xcount + 1) . ' is NULL and `basic` in ("' . implode('","', $path) . '")' . $object_list;
        if ($pdoResult = $this->PDO->query($query)) {
            while ($bsc = $pdoResult->fetch(PDO::FETCH_ASSOC)) {
                $bsc         = array_filter($bsc);

                $bsc['path'] = array_values(array_splice($bsc, 3, 1 + $this->levels));
                if (count($bsc['path']) == $xcount) {
                    $gypot[] = $bsc;
                } else {
                    $basics[$bsc['id']] = $bsc;
                }
            }
            foreach ($gypot as $gyp) {
                $p = 0;
                foreach ($gyp['path'] as $gp) {
                    if ($basics[$gp]) {
                        $p++;
                    }
                }
                if ($p == $xcount) {
                    $fgyp = $gyp;
                    break;
                }
            }
            return $fgyp;
        }
    }
    
      
      function writeNodeParam($nodeName = "", $param, $value)
        {
            $query  = "select id,value,parameter from `$this->treeParamName` where `parameter`='$param'  and `node_name`='$nodeName'";        
            $result = $this->PDO->query($query);
            $m = $result->fetch(PDO::FETCH_ASSOC);
            if(!$id=$m[0]['id']){$id = 'NULL';}
                
            $query = "insert into `$this->treeParamName` (`id` , `node_name` , `parameter` , `value`) values ($id, '$nodeName', ".$param.", " . $this->PDO->quote($value) . ') on duplicate key update value=values(value)' ;
            return $this->PDO->exec($query);
        }
            
        
        

    
    function writeNodeParams($nodename, $paramPack) {
        
        if (!is_array($paramPack)) 
        {
            return false;
        }
        
        $query  = "select id,value,parameter from `$this->treeParamName` where `node_name`='$nodename'";
        $result = $this->PDO->query($query);
        while ($m = $result->fetch(PDO::FETCH_ASSOC)) {
            $ex_params[$m['parameter']] = $m;
        }
        $query = "insert into `$this->treeParamName` (`id` , `node_name` , `parameter` , `value`) values";
        reset($paramPack);
        while (list($param, $value) = each($paramPack)) {
            $id = 'NULL';
            if ($ex_params[$param]) {
                $id = $ex_params[$param]['id'];
            }
            $_queryArr[] = "($id, '$nodename', " . $this->PDO->quote($param) . ", " . $this->PDO->quote($value) . ")";
        }
        $query = $query . implode(',', $_queryArr) . ' on duplicate key update value=values(value)';
        return $this->PDO->exec($query);
    }
    
    function moveRate($id, $relative = null, $position = 'up', $newnode = false) {        
        $this->temp  = null;
        $main_struct = $this->getNodeStruct($id);
        if (!$relative) {
            if ($position == 'up') {
                $order = 'ASC';
            } else {
                $order = 'DESC';
            }
            $query = "select * from  $this->treeStructName where x$main_struct[ancestorLevel]='$main_struct[ancestor]' order by rate $order limit 1";
            if ($pdoResult = $this->PDO->query($query)) {
                $relative_struct = $this->nodeResult($pdoResult->fetch(PDO::FETCH_ASSOC),true);
            } else {
                return;
            }
            if ($id == $relative_struct['id'])
                return;
        }
        if (!$relative_struct)
            $relative_struct = $this->GetNodeStruct($relative);
        switch ($position) {
            case 'up':
                $rate_sign = '>=';
                $new_rate  = $relative_struct['rate'];
                break;
            case 'down':
                $rate_sign = '>';
                $new_rate  = $relative_struct['rate'] + 1;
                break;
        }
        if ($newnode) {
            $query = "UPDATE `$this->treeStructName` SET `rate` = '$new_rate' WHERE `id` = $id LIMIT 1";
            $this->PDO->query($query);
            return;
        }
        
        if ($relative_struct['ancestor'] != $main_struct['ancestor']) {    
            $query = "update $this->treeStructName  SET rate=rate+1   WHERE 
                      `x{$relative_struct[ancestorLevel]}`={$relative_struct[ancestor]}   AND  rate  $rate_sign {$relative_struct[rate]}";
        
        } else {
            if ($relative_struct['rate'] < $main_struct['rate']) {
                $query = "update $this->treeStructName   SET rate=rate+1  WHERE `x{$relative_struct[ancestorLevel]}`={$relative_struct[ancestor]}   AND  rate $rate_sign {$relative_struct[rate]}
                        AND  {$main_struct[rate]}  $rate_sign  rate";
            } else {
                /*if ($position == 'up')
                {
                $new_rate =$relative_struct['rate'] - 1;
                $rate_sign='>';
                }
                
                */
                if ($position == 'down') {
                    $rate_sign = '>';
                }
                $query = "update $this->treeStructName  SET rate=rate+1   WHERE  `x{$relative_struct[ancestorLevel]}`={$relative_struct[ancestor]}  AND rate $rate_sign {$relative_struct[rate]}";
            }
        }
        
        if ($result = $this->PDO->query($query)) {
            $query = "UPDATE `$this->treeStructName` SET `rate` = '$new_rate' WHERE `id` = $id LIMIT 1";
            $this->PDO->query($query);
        }
    }
    
    function addBasic($ancestor = 1, $basic, $objType = '_', $position = 'down') 
    {
        $this->enableCache(false);
        
        if ($this->uniqType == self::$UNIQ_ANCESTOR) 
        {
            if ($id=$this->selectStruct(array('id'))->where(array('@ancestor','=',$ancestor), array('@basic','=',$basic))->run()) 
            {
                
                $this->lastNonUniqId=$id[0]['id'];                
                throw new Exception('non-uniq-ancestor');
            }            
        
        }elseif($this->uniqType == self::$UNIQ_TREE)
        {
            
            if ($this->selectStruct(array('id'))->where(array('@basic','=',$basic))->run()) 
            {
                $this->lastNonUniqId=$id;
                throw new Exception('non-uniq-tree');
            }            
        }
        
        if(!isset($this->lockObjType[$objType]))    throw new Exception('parent-objectType-missed');
        
        if ($ancestorNode = $this->getNodeStruct($ancestor)) {
            
            if (!in_array($ancestorNode['obj_type'], $this->lockObjType[$objType])) 
            {
                throw new Exception('ancestor-lock-missed');
            }
            
            foreach ($ancestorNode['path'] as $pathKey => $pathElement) {
                $xArray['x' . ($pathKey+1)] = $pathElement;
            }
            
            $xArray['x' . (count($ancestorNode['path'])+1)] = $ancestor;
            $query                        = "insert into `$this->treeStructName` (`id`,`basic`,`obj_type`,`" . implode(array_keys($xArray), '`,`') . "`) VALUES (NULL,'$basic','$objType','" . implode($xArray, "','") . "')";
            $result                       = $this->PDO->query($query);
            $lastInserted                 = $this->PDO->lastInsertId();
            if ($basic == "%SAMEASID%") {
                $query = "update `$this->treeStructName` set basic='$lastInserted' where id=$lastInserted";
                $this->PDO->query($query);
            }
            $this->moveRate($lastInserted, null, $position, true);
            
            $this->enableCache(true);
            
            return $lastInserted;
        }
    }
    
    function setStructData($id,$param,$value)
    {        
        $query= "UPDATE `$this->treeStructName`  SET `$param` = '$value' WHERE `id` =$id";    
        $this->PDO->query($query); 
    }
    
    
    private function filterArrayData($data,$objectType)
        {
        if ($data)
            {            
            
            if(!$this->filter[$objectType])return $data;
            
            $extKeys=array_intersect(array_keys($data), $this->filter[$objectType]);
            
            foreach ($extKeys as $key)
                {
                  $extData[$key]=$data[$key];
                }

            return $extData;
            }
        }
    
    private function setTreeObjData($id,$data,$objectType)
        {        
        if ($data)
            {
            if($filteredData=$this->filterArrayData($data,$objectType))
                {
                    $this->writeNodeParams($id, $filteredData);
                    
                }else{
                    $this->writeNodeParams($id, $data);
            }
            
        }
    }
    
       /***
    * Периницилзиция объекта в дереве
    * 
    * @param mixed $id предка
    * @param mixed $newbasic имя ноды
    * @param mixed $nodeData данные объекта
    */ 
    public function reInitTreeObj($id,$newbasic,$nodeData)
        {
                    if($newbasic!=='%SAME%')
                    {
                        $this->setStructData($id,'basic',$newbasic);
                    }
                    $node=$this->getNodeStruct($id);                    
                    $this->setTreeOBJData($id,$nodeData,$nodeData['obj_type']);//!                
        } 

    /***
    * Иницаилизация объекта в дереве
    * 
    * @param mixed $ancestor id предка
    * @param mixed $basic имя ноды
    * @param mixed $objType тип объекта
    * @param mixed $nodeData данные объекта
    */
     public function initTreeObj($ancestor, $basic, $objType,$nodeData=null)
        { 
            if(!$ancestor)return;
            try{
                    $id=$this->addBasic($ancestor,$basic,$objType);
                    $this->setTreeObjData($id,$nodeData,$objType);
                    return $id;
                
                }catch(Exception $e)
                {
                    $this->exceptionHandlers[]=$e;
                    return false;
                }
                
        }
    
    
    private function format_normal($record) {
        $this->recordsFormatCache[] = $this->nodeResult($record);
    }
    private function format_keyval($record) 
    {
        $_record = $this->nodeResult($record);
        $key     = $this->query['formatParams'][0];
        $val     = $this->query['formatParams'][1];
        if ($val) {
            $this->recordsFormatCache[$_record[$key]] = $_record[$val];
        } else {
            $this->recordsFormatCache[$_record[$key]] = $_record;
        }
    }
    

    private function format_paramsval($record) {
        $_record                                            = $this->nodeResult($record);
        $first                                                = $this->query['formatParams'][0];
        $second                                                = $this->query['formatParams'][1];
        $this->recordsFormatCache[$_record['params'][$first]] = $_record[$second];
    }
    
    private function format_valparams($record) {
        $_record                                            = $this->nodeResult($record);
        $first                                                = $this->query['formatParams'][0];
        $second                                                = $this->query['formatParams'][1];
        
        $this->recordsFormatCache[$_record[$first]] = $_record['params'][$second];
    }
    
        private function format_paramsparams($record) {
        $_record                                            = $this->nodeResult($record);
        $first                                                = $this->query['formatParams'][0];
        $second                                                = $this->query['formatParams'][1];
        
        $this->recordsFormatCache[$_record['params'][$second]] = $_record['params'][$second];
    }

    
    private function basicPathCalculate(&$nodes) 
    {
        if ($this->query['pathCache']) {
            $query = 'select id,basic  from ' . $this->treeStructName . '  where  `id` in ("' . implode('","', $this->query['pathCache']) . '")';
            if ($pdoResult = $this->PDO->query($query)) {
                while ($bsc = $pdoResult->fetch(PDO::FETCH_ASSOC)) {
                    $basics[$bsc['id']] = $bsc['basic'];
                }
            }
            unset($basics[1]);
            while (list($key, $val) = each($nodes)) {
                
                if ($val['path']) 
                {
                    foreach ($val['path'] as $pathElement) {
                        if ($basics[$pathElement])
                            $nodes[$key]['basicPath'][$pathElement] = $basics[$pathElement];
                    }
                    $nodes[$key]['basicPath'][$val['id']] = $val['basic'];
                
                    if($this->query['basicpath']['separator'])
                    {
                        $nodes[$key]['basicPathValue']        = implode($this->query['basicpath']['separator'], $nodes[$key]['basicPath']);
                    }
                    
                    
                }
            }
            /*порядок по возрастанию уровня вложенности если не указано другое*/
            if(!$this->query['sortByAncestorLevel'])
            {
                $nodes=XARRAY::sortByField($nodes,'ancestorLevel');
            }
        }
    }
    
    
    
    private function formatProcess($structRecords, $paramsRecords = null) {
        if (!isset($this->query['format'])) {
            $format = 'normal';
        } else {
            $format = $this->query['format'];
        }
        $funcName                 = 'format_' . $format;
        $this->query['pathCache'] = array();
        while (list($key, $record) = each($structRecords)) {
            $record['id'] = $key;
            if ($paramsRecords[$record['id']]) {
                $record['params'] = $paramsRecords[$record['id']];
                $this->$funcName($record);
            } else {
                $this->$funcName($record);
            }
        }
        
        $records                  = $this->recordsFormatCache;
        $this->recordsFormatCache = null;
        if (isset($this->query['basicpath'])){
            $this->basicPathCalculate($records);
        }
        if (isset($this->query['astree'])) {
            return new xteTree($records, $this->query['childsAncestor']);
        }
        
        
        if(isset($this->query['singleResult'])&&$records)
        {
            
            return $records[0];
        }
        
        return $records;
    }
    
    /**
    * установить объект в дереве
    * 
    * @param mixed $objectType -тип объекта
    * @param mixed $filter -поля объекта
    * @param array $ancestors - массив предков
    */
    public function setObject($objectType, $filter, $ancestors = null) 
    {
        $this->filter[$objectType]      = $filter;
        $this->lockObjType[$objectType] = $ancestors;
    }
    
    private function sortByParam($nodes) {
        if ($this->query['sortByParam']) {
            if ($this->query['sortByParam']['order']) {
                $order = 'asc';
            }
            if ($this->query['sortByParam']['cast']) {
                $cast = 'CAST(value AS ' . $this->query['sortByParam']['cast'] . ')';
            } else {
                $cast = 'value';
            }
            $q      = 'SELECT node_name FROM ' . $this->treeParamName . '  WHERE `parameter`="' . $this->query['sortByParam']['element'] . '" and node_name in("' . implode('","', $nodes) . '") order by ' . $cast . ' ' . $order;
            $result = $this->PDO->query($q);
            return $result->fetchAll(PDO::FETCH_COLUMN, 0);
        }
    }
    private function getParams($nodes) {
        if (is_array($nodes)) {
            $selectParams = '';
            if (is_array($this->query['selectParams'])) {
                $selectParams = ' AND parameter IN ("' . implode('","', $this->query['selectParams']) . '")';
            }
            $queryStr = 'select * from ' . $this->treeParamName . ' WHERE node_name IN ("' . implode('","', $nodes) . '")' . $selectParams;
            return $this->PDO->query($queryStr);
        }
    }
    public function readNodeParam($nodename, $param) {
        $query  = 'select value from ' . $this->treeParamName . " where `parameter`='$param' and node_name='$nodename' limit 1";
        $result = $this->PDO->query($query);
        if($r = $result->fetchAll(PDO::FETCH_COLUMN, 0))return $r[0];
    }
    private function deleteProcess($nodes) {

        $query = 'delete  from ' . $this->treeParamName .  ' WHERE  node_name IN ("' . implode('","', $nodes) . '")';
        $this->PDO->exec($query);
        $query = 'delete  from ' . $this->treeStructName  .  ' WHERE  id IN ("' . implode('","', $nodes) . '")';        
        unset($this->query);
        return $result = $this->PDO->exec($query);
    }
    
    public function subCopy($node,$ancestor,$tContext,&$extdata)
    {   
        static $maxCounter;
        if(!$maxCounter)
        {
            $maxCounter=$extdata['maxId'];
      
        }
        $maxCounter++;
        $this->innerTree[$node['id']]=array('id'=>$maxCounter);
        
        if($this->innerTree[$ancestor])
        {
            $path=$this->innerTree[$ancestor]['path'];
            $path[]=$ancestor;
            
        }else{
            $path=$extdata['path']; $path[]=$extdata['id'];   
        }
        $this->innerTree[$node['id']]['path']=$path;        
    }
    

    public function copyNodes($ancestor, $startNode)
    {
        $exNode  = $this->getNodeInfo($startNode);
        $ancNode = $this->getNodeInfo($ancestor);
        $nodes   = $this->selectParams('*')->selectStruct('*')->childs($startNode)->asTree()->run();
        if ($exNode['id'] == $exNode['basic'])
        {
            $exNode['basic'] = '%SAMEASID%';
        }
        $added = false;
        while (!$added)
        {
            try
            {
                $id = $this->addBasic($ancestor, $exNode['basic'], $exNode['obj_type']);
                $this->setTreeObjData($id, $nodeData, $exNode['obj_type']);
                $added = true;
            }
            catch (Exception $e)
            {
                if ($e->getMessage() == 'non-uniq-ancestor')
                {
                    $exNode['basic'] .= '_copy';
                }
            }
        }
        $ancNode['path'][] = $ancestor;
        $query             = 'select  max(id) from ' . $this->treeStructName;
        $result            = $this->PDO->query($query);
        $max               = $result->fetchAll(PDO::FETCH_COLUMN, 0);
        $extdata           = array(
            'id' => $id,
            'path' => $ancNode['path'],
            'maxId' => $max[0]
        );
        $nodes->recursiveStep($startNode, $this, 'subcopy', $extdata);
        if ($this->innerTree)
        {
            for ($i = 1; $i < $this->levels + 1; $i++)
            {
                $xArray[] = 'x' . $i;
            }
            $query  = "insert into `$this->treeStructName` (`id`,`basic`,`obj_type`,`rate`,`" . implode($xArray, '`,`') . '`) VALUES';
            $xArray = array();
            
            foreach ($this->innerTree as $key => $element)
            {
                for ($i = 0; $i < $this->levels; $i++)
                {
                    $xArray[$i] = ($element['path'][$i])?$element['path'][$i]:'NULL';
                }                                
                
                $sqlLine[] = '(' . $element['id'] . ',"' . $nodes->nodes[$key]['basic'] . '","' . $nodes->nodes[$key]['obj_type'] . '","' . $nodes->nodes[$key]['rate'] . '",' . implode($xArray, ",") . ')';                
            }
            
            $query .= implode($sqlLine, ',');               
            $this->PDO->exec($query);
            
        }
    }
   
        
    public function run() {
        $selectQs = $this->getSelectQueryString();
        $whereQs  = $this->getWhereQueryString();
        
        if (!isset($this->query['selectStruct']) && isset($this->query['selectParams'])) {
            $this->query['selectStruct']    = array(
                'id'
            );
            $selectQs['selectStructString'] = 'id';
        }
        if (isset($selectQs['selectStructString'])) {
            $queryStr = 'SELECT ' . $selectQs['selectStructString'] . ' FROM `' . $this->treeStructName . '` a ';
        }
        if (isset($whereQs['whereStructString'])) {
            $queryStr .= ' WHERE ' . $whereQs['whereStructString'];
        }
        if (isset($this->query['sortByStruct'])) {
            $queryStr .= 'order by ' . $this->query['sortByStruct']['element'] . ' ' . $this->query['sortByStruct']['order'];
        } else {
            $queryStr .= ' order by rate';
        }
        
        
        if(!isset($this->query['delete']))
        {
            $mark=Common::createMark($this->query);
            
            if($this->enableCache&&$result=xCache::serializedRead($this->cacheDir,$mark,$this->cacheTimeout))
            {           
                unset($this->query);
                return $result;
            }
        
        }

      
        if ($queryStr) {
            $pdoResult = $this->PDO->query($queryStr);
            if ($nStructResults = $pdoResult->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC)) {
                $nStructResults = array_map('reset', $nStructResults);
            }
        }
        // get all params as nodes
        if (isset($whereQs['whereParamsString'])) {
            $queryStr       = 'select node_name, count( node_name ) as CCOUNT from ' . $this->treeParamName . ' b  WHERE ' . $whereQs['whereParamsString'];
            $result         = $this->PDO->query($queryStr);
            $nParamsResults = $result->fetchAll(PDO::FETCH_COLUMN, 0);
            // no intersection no result
            if ($nStructResults && $nParamsResults && $nodesIntersect = array_intersect($nParamsResults, array_keys($nStructResults))) {
                foreach ($nodesIntersect as $k => $v) {
                    $structResults[$v] = $nStructResults[$v];
                }
                $nStructResults = $structResults;
            } else {
                $this->query = array();
                return;
            }
        } else {
            $nodesIntersect = array_keys($nStructResults);
        }
        if (isset($this->query['delete'])) {
            return $this->deleteProcess($nodesIntersect);
        }
        if (isset($this->query['selectParams'])) {
            $paramsResult = $this->getParams($nodesIntersect);
            while ($pf = $paramsResult->fetch(PDO::FETCH_ASSOC)) {
                $nodesParams[$pf['node_name']][$pf['parameter']] = $pf['value'];
            }
        }else{
            $nodesParams=null;
        }
        if ($pdoResult) {
            if (isset($this->query['sortByParam'])) {
                $nSortResults = $this->sortByParam(array_keys($nStructResults));
                foreach ($nSortResults as $k) {
                    $sorted[$k] = $nStructResults[$k];
                }
                $nStructResults = $sorted;
            }
            $result = $this->formatProcess($nStructResults, $nodesParams);
            
            
            if($this->enableCache&&!$this->query['delete'])
            {
                 xCache::serializedWrite($result,$this->cacheDir,$mark,$this->cacheTimeout);
            }
            
            unset($this->query);
            return $result;
        }
    }
    public function format($format = 'normal') {
        if (!method_exists($this, 'format_' . $format)) {
            trigger_error('defined format' . $format . ' is not exist');
        }
        $this->query['format'] = $format;
        if (count($args = func_get_args()) > 1) {
            $this->query['formatParams'] = array_slice($args, 1);
        }
        return $this;
    }
    public function asTree() {
        $this->query['astree'] = true;
        return $this;
    }
    public function childs($ancestor, $level = 0) {
        $this->query['childsAncestor'] = $ancestor;
        $this->query['childsLevel']    = $level;
        return $this;
    }
    public function selectStruct($selectFieldStruct) 
    {        
        if(is_array($selectFieldStruct) or $selectFieldStruct=='*')
        {
            $this->query['selectStruct'] = $selectFieldStruct;
            return $this;
        }else
        {
            trigger_error('selectStruct params error - must be array or *');    
        }
        
    }
    public function selectParams($selectFieldParams) {
        $this->query['selectParams'] = $selectFieldParams;
        return $this;
    }
    public function where() {
        $arg_list             = func_get_args();
        
        if($arg_list[1]===true)
        {
            $this->query['where'] = $arg_list[0];
        }else{
            $this->query['where'] = $arg_list;
        }
        return $this;
    }
    public function delete() {
        $this->query['delete']       = true;
        $this->query['selectStruct'] = array(
            'id'
        );
        return $this;
    }
    public function limit($start, $offset) {
        $this->query['limit'] = array(
            $start,
            $offset
        );
        return $this;
    }
    public function getBasicPath($separator = '/',$sortByAncestorLevel=false) {
        
        $this->query['sortByAncestorLevel']=$sortByAncestorLevel;
        $this->query['basicpath'] = array(
            'separator' => $separator
        );
        $this->query['selectStruct']='*';
        return $this;
    }
    public function sortby($el, $order, $cast = '') {
        if ($this->query['sortByParam'] && (strpos($el, '@') === 0) && (in_array($el = substr($el, 1), $this->nativeStructFieldsList))) {
            $this->query['sortByStruct'] = array(
                'element' => el,
                'order' => $order,
                'cast' => $cast
            );
        } elseif (!$this->query['sortByStruct']) {
            $this->query['sortByParam'] = array(
                'element' => $el,
                'order' => $order,
                'cast' => $cast
            );
        }
        return $this;
    }
}

class tPageAgregator
    {
    var $pageNode;
    var $called_modules = array();
    var $modules_out;
    var $template_list;
    var $globalFields = array();
    var $modulesList;
    var $page_redirect_params;
    var $request_action;
    var $disablePageCaching=0;
    var $globalPostOff; //глобальные отложенные действия
    var $externalMeta;
    var $mainTemplate; 
    var $frontEditMode=false;
    var $TMS=null;
    

    function execute_page($web_page_path)
        {
    
        if (!$this->buildPage($web_page_path))
            {
                $this->show_e404_page();
            }
        else
            {            
                 return $this->processMainTemplate();
            }
    
        }

    
        
    function __construct()
    {
        $this->_TMS=xRegistry::get('TMS');        
        $this->_EVM=xRegistry::get('EVM');
    }
                                     
    function dispatch_action($action)
    {   
        global $TMS, $_PATH;
        switch($action)
        {        
            case 'robots':            
            $TMS->AddFileSection(xConfig::get('PATH','TEMPLATES') .'/robots.txt');
            $TMS->AddReplace('robots','host',HTTP_HOST);
            header("Content-Type: text/plain");
            echo $TMS->parseSection('robots');
            
            break;        

       case 'sitemap':            
            header('Content-type: application/xml; charset="utf-8"',true);            
            echo file_get_contents($_PATH['SITEMAP']);
            break;  
            die();
        }
        
    }    


    function page_access_denied($reason, $usercp=null)
    {
               $pages=&xCore::moduleFactory('pages.front');  
               if ($usercp) {
                   $this->move301Permanent(CHOST.'/'.$pages->_commonObj->createPagePath($usercp,true));        
                   
               } else {
                   if($server=$pages->_commonObj->get_page_module_servers('user_panel'))
                   {
                        $s=current($server);
                        $this->page_redirect_params['fusers']['reason']=$reason;
                        $this->buildPage($pages->_commonObj->createPagePath($s['id'],true).'/~needauth/',true); 
                   
                   }
               }
    }
    
    
    function processMainTemplate()
        {

             
        $this->_TMS->MFFields=array();
        $this->_TMS->MFReplacement=array();
        
        
        $pages = xCore::moduleFactory('pages.front');      
        
        jsCollector::pushJsDir('main',xConfig::get('PATH','TEMPLATES').'_base/js',1);
                   
        $this->globalFields['HOST']        = HOST;
        $this->globalFields['PAGE']        = $pages->page;
        $this->globalFields['DOMAIN']      = $pages->domain;
        $this->globalFields['LANGVERSION'] = $pages->langVersion;
        $this->globalFields['JS']          = jsCollector::get('main');

        
        /**
        * Подключение шаблона страницы если она не является страницей на главном шаблоне
        */  
             
             
        if (!strstr($this->mainTemplate['path'],'_index.html'))                
            {            
                $this->_TMS->AddFileSection(xConfig::get('PATH','COMMON_TEMPLATES') . $this->mainTemplate['path']);
            }

            
            if($this->externalMeta['Title'])
            {
               $this->globalFields['Title']=$this->externalMeta['Title'];
            }
            
            if($this->externalMeta['Keywords'])
            {
               $this->globalFields['Keywords']=$this->externalMeta['Keywords'];
            }
            
            if($this->externalMeta['Description'])
            {
               $this->globalFields['Description']=$this->externalMeta['Description'];
            }
            
            
            global $cache;                                                    
        
        //догружаем не объявленные секции        
           
           /*  
            if(!$_COMMON_SITE_CONF['disable_m_caching'])
            {                            
            //    $cache_name=md5($_SERVER['REQUEST_URI']).$_SESSION['currency']['id'].'titles.xtx';
             //   $cache->setCacheFile($cache_name);
               if($m_cache=$cache->getCache())
                {
                                    $seodata=unserialize($m_cache);                    
                                    $this->globalFields['Title']=$seodata['Title'];
                                    $this->globalFields['Keywords']=$seodata['Keywords'];
                                    $this->globalFields['Description']=$seodata['Description'];
                    
                }else{
             
               //     $cache->setCache(serialize(array('Title'=>$this->globalFields['Title'],'Keywords'=>$this->globalFields['Keywords'],'Description'=>$this->globalFields['Description'])));
                }          
            
            }
*/
            
            /**
            * Подключение главного шаблона для домена
            */      
            $this->_TMS->AddFileSection(xConfig::get('PATH','COMMON_TEMPLATES') .$pages->domain['basic'].'/_index.html');
            
            
            /**
            * Подключение главного шаблона кроссдоменного
            */
                    
            if(file_exists($mainTemplate=xConfig::get('PATH','TEMPLATES').'/_index.html'))
            {
                $this->_TMS->AddFileSection($mainTemplate);
            }
        
            //компонуем модули
            $this->_TMS->AddMFMassReplace($this->modules_out);
            //данные ноды  в странице


            $this->globalFields['ARES']=xConfig::get('PATH','WEB_ARES');
            $this->_TMS->AddFileSection(xConfig::get('PATH','TEMPLATES').'_base/tpl/connector.html');
            $this->_TMS->AddMassReplace('connector',array('xConnector'=>XOAD_Client::register(new connector()),        
                                                          'xoadHeader'=>XOAD_Utilities::header(xConfig::get('WEBPATH','XOAD'))));
            
            $this->globalFields['XFRONT_API'] =$this->_TMS->parseSection('connector');
            
            if($this->_TMS->SectionNests['MAIN'])
            {
                foreach($this->_TMS->SectionNests['MAIN'] as $section)
                {
                    $this->_TMS->AddMassReplace($section, $this->globalFields);                    
                }
            
            }

         //   $this->_TMS->AddMFMassReplace($_COMMON_SITE_CONF);
              
            return $this->_TMS->ParseRecurs('MAIN');
        }


    /**
    * получить массив REQUEST_ASSOC
    * 
    * @param array $vars
    * @return array
    */
        function parseRequestVars(&$vars)
        {
            if (!is_array($vars))
                return;
            for ($i = 1; $i < count($vars) - 1; $i += 2)
            {
                if (strrpos($vars[$i + 1], '='))
                {
                    $tok = strtok($vars[$i + 1], '&');
                    while ($tok)
                    {
                        if (is_array($inarr = explode('=', $tok)))
                        {
                            $RQV[$inarr[0]] = XCODE::utf2win(urldecode($inarr[1]));
                            if (strpos($RQV[$inarr[0]], '|') !== false)
                                $RQV[$inarr[0]] = explode('|', $RQV[$inarr[0]]);
                            $tok = strtok('&');
                        }
                    }
                }
                else
                {
                    if (strpos($vars[$i + 1], '|') !== false)
                        $vars[$i + 1] = explode('|', $vars[$i + 1]);
                    $RQV = $vars[$i + 1];
                }
                $REQUEST_ASSOC[$vars[$i]] = $RQV;
                $RQV                      = null;
            }
            return $REQUEST_ASSOC;
        }
 
    
    /**
    * Разобрать url на REQUEST_ASSOC и REQUEST_VARS
    * 
    * @param mixed $path
    */
    
    function requestVarsDetect($path)
    {        
        global $REQUEST_VARS, $REQUEST_ASSOC,$_COMMON_SITE_CONF; 

        if (is_array($REQUEST_VARS=explode('/', $path)))
            {
                $this->request_action=trim($REQUEST_VARS[0]);
                $REQUEST_ASSOC=$this->parseRequestVars($REQUEST_VARS);
            }
           
    }
    


    
    function rewrite($pagePath)
    {
          $pages=xCore::moduleFactory('pages.front'); 
        
           if($rewrites=$pages->getRewrites())
           {
               foreach($rewrites as $rewrite)
               {
                   $rewrite['from']=str_replace(array('/'),array('\/'),$rewrite['from']);

                    if($w=preg_replace('/'.$rewrite['from'].'/',$rewrite['to'],$pagePath))
                    {
                        if($w!==$pagePath)
                        {
                            if((int)$rewrite['is301'])
                                {
                                    $this->move301Permanent($w);
                                }
                            return $w;
                        }
                    }
                
               }
           }
    }

    
        function buildPage($pagePath, $innerAccess = false)
        {
            global $_PATH;
            global $REQUEST_VARS, $REQUEST_ASSOC, $_COMMON_SITE_CONF;

            $this->_EVM->fire('agregator:start', $pagePath);
            
            if ((!$innerAccess) && $url = $this->rewrite($pagePath))
            {
                return $this->buildPage($url, true);
            }
            $this->modules_out                           = null;
            $this->globalFields                          = Array();
            
            $_SESSION['pages']['previous_page_path']     = $_SESSION['pages']['current_page_full_path'];
            $_SESSION['pages']['current_page_full_path'] = $pagePath;
            if ($pos = strpos($pagePath, '@'))
            {
                $_SESSION['pages']['current_page_no_filter'] = substr($pagePath, 0, $pos - 1);
            }
            
            if (isset($_SESSION['fronted']['enabled']))
            {
                $this->frontEditMode = true;
            }
            //отделяем переменные и action        
            if (preg_match("/(\;|\)|\'|\"|\!|\<|\>|union)/", urldecode($pagePath)))
            {
                return false;
            }
            $pathExploded                              = explode('~', $pagePath);
            
            $_SESSION['pages']['current_page_path'] = $pathExploded[0];
            
            if (!isset($pathExploded[1]) && ($pos = strpos($pathExploded[0], '@')))
            {
                $pathExploded[1] = substr($pathExploded[0], $pos - 1);
                $pathExploded[0] = substr($pathExploded[0], 0, $pos);
            }
            
            if(isset($pathExploded[1]))
            {
                $this->pageParams = $pathExploded[1];
                //разбор переменных GET оформление ассоциативных массивов arrayname/key=value&key1=value
                $this->requestVarsDetect($this->pageParams);
                $_SESSION['pages']['current_get_params'] = substr($this->pageParams, 1 + strpos($this->pageParams, '/'));
            }
            
            if (isset($pathExploded[0]))
            {
                if (preg_match('/[0-9a-z_\-\/]/', $pathExploded[0]))
                {
                    $treePath = XARRAY::clearEmptyItems(explode('/', $pathExploded[0]), true);
                }
                else
                {
                    return false;
                }
            }
            
            /*
             *   action всегда  $REQUEST_VARS[0];
             *  приоритет action из $REQUEST_VARS[0] всегда выше чем у модуля по умолчанию если данное дествие разрешено
             */
            
            //вызов модуля страниц        
            
            $pages = xCore::moduleFactory('pages.front');
            

            
            
            

        //    $pages->createTest();
            
            if (!$pages->getPageIdByPath($pathExploded[0]))
                return false;
            //кросспост
            if ($_SESSION['POST'])
            {
                $_POST = $_SESSION['POST'];
                unset($_SESSION['POST']);
            }
            xCore::callCommonInstance('templates');
            $templates                         = templatesCommon::getInstance();


            if(!$pages->page['params']['Template']) return;
            

            
           $templates->refreshMainTpls();
                        
     
            $this->mainTemplate                          = $templates->getTpl($pages->page['params']['Template'], HTTP_HOST);
            
            $slotzCrotch                       = $pages->getSlotzCrotch($this->mainTemplate ['slotz']);
            
            //добываем модули слотов ветвления
            if (!empty($pages->modulesOrder))
            {
                foreach ($pages->modulesOrder as $moduleId => $priority)
                {
                    if ($module=$pages->execModules[$moduleId])
                    {
                        //всегда 1 экземпляр на уровне factory
                          if (is_object($moduleObject = xCore::moduleFactory($module['params']['Type'] . '.front')))
                        {
                            $moduleObject->requestActionSet($this->request_action);
                            $moduleTimeStart                                  = Common::getmicrotime();
                            $modulesOut[$moduleId]                            = $moduleObject->execute($module['params']);
                            $modules_crotch[$mk]['params']['__moduleExecutionTime'] = Common::getmicrotime() - $moduleTimeStart;
                        }
                       
                    }
                }
            }
            
            
            global $time;
            $y=Common::getmicrotime();
            echo $y-$time.' modules ready ';
            
            
            while (list($slot, $modules) = each($slotzCrotch))
            {
                foreach($modules as $moduleId)
                {
                    if (!$this->frontEditMode)
                    {
                       $this->slotzOut[$slot].=$modulesOut[$moduleId];  
                    
                    }else{
                    
/*                        while (list($module_id, $mout) = each($out))
                        {
                            $out[$module_id] = '<map etime="' . $modules_crotch[$module_id]['_etime'] . '" template="' . $modules_crotch[$module_id]['Template'] . '" alias="' . $modules_crotch[$module_id]['Alias'] . '" mtype="' . $modules_crotch[$module_id]['type'] . '" class="__module" id="_m' . $module_id . '">' . $mout . '</map>';
                        }
                        $this->modules_out[$slot] = '<form  alias="' . $tpl_slotz_all[$slot]['SlotAlias'] . '" class="__slot" id="_s' . $tpl_slotz_id[$slot] . '">' . implode('', $out) . '</form>';    
*/
                    
                    }
                
                }
            }
            
            /*  if (($this->frontEditMode) && ($tpl_slotz_all))
            {
                //добавляем пустые слоты для FED
                foreach ($tpl_slotz_all as $slot => $source)
                {
                    $this->modules_out[$slot] = '<form alias="' . $tpl_slotz_all[$slot]['SlotAlias'] . '" class="__slot" id="_s' . $tpl_slotz_id[$slot] . '"> </form>';
                }
            }*/
            
            return true;
        }

    
        function move301Permanent($link)
        {
            //preserve POST
            
            if(is_array($_POST))
            {
                   $_SESSION['POST']=$_POST;          
            }
            
            Header( "HTTP/1.1 301 Moved Permanently" );                                       
            Header( "Location:".$link);
            die();
        }
        
        
    //если ошибка пути или страница не существует    
    function show_e404_page()
        {
        global $_COMMON_SITE_CONF;
        
        header("HTTP/1.0 404 Not Found");
        $this->_TMS->AddFileSection(xConfig::get('PATH','TEMPLATES') . '404.htm');
        $this->_TMS->AddMassReplace('main',array('link_main_page'=>HOST,'admin_email'=>'mailto:'.$_COMMON_SITE_CONF['admin_email']));   
        echo $this->_TMS->parseSection('main');
        die();
        }
    } #endclass

?>
