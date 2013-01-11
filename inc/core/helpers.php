<?php

class xSingleton
{          
        private static $objInstances = array();   
        private function __clone() {}
        public static function getInstance($className=null) 
        {
            if(!$className){$className = get_called_class();}
            
            if (!isset(self::$objInstances[$className])) 
            {
                $instance = new ReflectionClass($className);
                $instance->getConstructor();
                self::$objInstances[$className] = $instance->newInstance();    
                //регистрируем в реестре
                xRegistry::set($className,self::$objInstances[$className]);
            }            
            return self::$objInstances[$className];
        } 

}

class xEventMachine extends xSingleton
  {
    protected $_callbacks = array();
    
         /**  
         *  определяет действие по событию
         * @param string $eventName before@module:event  after@module:event   module:event
         * @param string $callback method
         * @param $callbackContext objectContext
         */
                
        function on($eventName,$callback,$callbackContext) 
        {
            $this->_callbacks[$eventName][] = array('context'=>$callbackContext,'callback'=>$callback);
        }
  
        /**
        */
        
        function unregister($eventName,$callback=null) 
        {
            if($callback)
            {
                while(list($k,$v)=each($this->_callbacks[$eventName]))
                {
                    
                    if($this->_callbacks[$eventName][$k]['callback']==$callback)
                    {
                            unset($this->_callbacks[$eventName][$k]['callback']);  
                    }
                    
                }
                
                reset($this->_callbacks[$eventName]);
                
            }else
            {
                unset($this->_callbacks[$eventName]);
            }
        }
         
         /**  
         *   генерирует событие
         * @param string $eventName
         * @param array $data - данные отправляемые по событию
         */
        function fire($eventName,$data=null) 
        {
            if(isset($this->_callbacks[$eventName]))
            {
            
            foreach ($this->_callbacks[$eventName] as  $callback) 
            {
                if(method_exists($callback['context'],$callback['callback']))
                {
              
                    
                    if($return=call_user_func_array(array($callback['context'],$callback['callback']), array(array('context'=>$callback['context'],'data'=>&$data))))
                    {
                           if(is_array($return))
                           {
                               return $return;
                           }
                           
                           return true;
                        
                    }else{
                        
                        return;
                    }
               }
               else
                {                    
                    trigger_error('event function not defined '.get_class($callback['context']).'-'.$callback['callback']);
               }
            }
        }
        }
  }
  
class xConfig
{
    protected static $store = array();
    
    public static function set($branch,$param,$value)
    {
        self::$store[$branch][$param]=$value;
    }
    
    public static function get($branch,$param)
    {
        return self::$store[$branch][$param];
    }
    
    public static function getBranch($branch)
    {
        return self::$store[$branch];
    }
    
    public static function  setBranch($branch,$paramset)
    {
        self::$store[$branch]=$paramset;
    }
}
 

class jsCollector
{
    private static $jslist;
    
    /***
    * put your comment there...
    * 
    * @param mixed $ns
    * @param mixed $js
    * @param mixed $priority
    */
    
    public static function push($ns,$js,$priority=10)
    {
        self::$jslist[$ns][$priority][]=$js;
    }
    
    public static function get($ns,$compress=false)
    {
        if($list=self::$jslist[$ns])
        {
            ksort($list);
            
            $klist=array();
            
            foreach($list as $larr)
            {
              $klist=array_merge($klist,$larr);  
            }
            
            
            foreach($klist as $item) 
            {
                if(!$compress)
                {
                    $js[] ='<script type="text/javascript" src="'.$item.'"></script>';   
                
                }else{
                    
                    $js[]=str_replace('.js',$item,'');
                    
                }
            }
            
            if(!$compress)
            {
                    return implode($js,"\r\n");    
            }else{

                    return  '<script type="text/javascript" src="'.implode($js,",").'.cjs"></script>';
            }
            
            
               
        }
    }
    
    public static function pushJsDir($ns,$dir,$priority=10)
    {
        if($files=XFILES::files_list($dir,'files',array('.js')))
        {
            foreach($files as $file)
            {
                $file=str_replace(PATH_,HOST,$file);
                self::push($ns,$file,$priority);
            }
        }
    }
    
} 
  
class xRegistry 
{
    
    protected static $store = array();
    protected function __construct() {}
    protected function __clone() {}
 
    /**
     * Проверяет существуют ли данные по ключу
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name) 
    {
        $s=self::$store;
        return isset(self::$store[$name]);
    }
 
    /**
     * Возвращает данные по ключу или null, если не данных нет    
     * @param string $name
     * @return unknown
     */
    public static function get($name) 
    {
        return (isset(self::$store[$name])) ? self::$store[$name] : null;
    }
 
    /**
     * Сохраняет данные по ключу в статическом хранилище
     *
     * @param string or object with static property name $name 
     * @param unknown $obj
     * @return unknown
     */
    public static function set($name, $obj=null) 
    {
         if(is_object($name))
         {
            return self::$store[$name->name] = $name;
         }else{
            return self::$store[$name] = $obj; 
         }
    }
}


class xDate
{
    function convertFromDatePicker($date, $format = 'Y-m-d H:i:s')
    {
        return date($format, strtotime($date));
    }
}

class XARRAY
    {
        
        function array_msort($array, $cols)
        {
            $colarr = array();
            foreach ($cols as $col => $order) {
                $colarr[$col] = array();
                foreach ($array as $k => $row) {
                
                if($order[1]=='SORT_DATE')
                {
                    $colarr[$col]['_'.$k] = strtotime($row[$col]); 
                    $cols[$col][1]='SORT_NUMERIC';
                    
                    }else{
                     $colarr[$col]['_'.$k] = strtolower($row[$col]); 
                    }
                }
            }
            $eval = 'array_multisort(';
            foreach ($cols as $col => $order) 
            {        
                if(!is_array($order))
                {
                             $order=array('SORT_ASC');
                }
                
                $eval .= '$colarr[\''.$col.'\'],'.implode(',',$order).',';
            }
            
            $eval = substr($eval,0,-1).');';
            eval($eval);
            $ret = array();
            
            if($colarr){$colarr=current($colarr);
                foreach (array_keys($colarr) as $k)
                {
                    $ids[] = substr($k,1);            
                }
            
            return $ids;
            }

        }
        
        function array_depth($array) 
        {
            $max_indentation = 1;

            $array_str = print_r($array, true);
            $lines = explode("\n", $array_str);

            foreach ($lines as $line) {
                $indentation = (strlen($line) - strlen(ltrim($line))) / 4;

                if ($indentation > $max_indentation) {
                        $max_indentation = $indentation;
                }
            }

            return ceil(($max_indentation - 1) / 2) + 1;
    }


        function  convert_to_link($array)
        {
                    if($array){
                    foreach($array as $k =>$v)
                            {
                            
                                $lget.=$k.'/' . $v . '/';
                            }
                    return $lget;
                    }
                            
        }
        
        public static function clearEmptyItems($array, $new_enumeration = false)
        {
        if (is_array($array))
            {
            foreach ($array as $key => $value)
                {
                    if(is_array($value))
                    {
                    
                        $value=XARRAY:: clearEmptyItems($value, $new_enumeration);  
                    }
                    if ((empty($value))or($value === null)or($value === false) or (trim($value) === '') && !$new_enumeration)
                    {
                    unset ($array[$key]);
                    }
                elseif ($value)
                    {
                    $array2[]=$value;
                    }           
                }

            if ($new_enumeration)
                {
                return $array2;
                }

            return $array;
            }
        }


    /**
    * Преобразование ассоциативного массива в URL строку
    * 
    **/
    public static function    convert_to_get_line($arr)
        {
        if (is_array($arr) && !empty($arr))
            {
            
                $arr=XARRAY::clearEmptyItems($arr);

            foreach ($arr as $key => $val)
                {
                $val = XARRAY::clearEmptyItems($val);

                if (is_array($val) && (count($val) > 0))
                    {
                    foreach ($val as $svk => $svv)
                        {
                        if(is_array($svv)){$svv=implode('>',$svv);}
                        $sv[]=$svk . ':' . $svv;
                        }

                    $val=implode('&', $sv);
                    unset ($sv);
                    $getter_line.='/' . $key . '/' . $val;
                    }
                elseif (!is_array($val) && ($val))
                    {
                    $getter_line.='/' . $key . '/' . $val;
                    }
                }

            return $getter_line;
            }
        }

  public static  function combine($arr, $arr_to_key)
        {
        $i=0;

        foreach ($arr_to_key AS $value)
            {
            $arr_combined[$value] = $arr[$i];
            $i++;
            }

        RETURN $arr_combined;
        }

  public static  function array_recursive_search($needle, $haystack, $strict = false, $path = array())
        {
        if (!is_array($haystack))
            {
            return false;
            }

        foreach ($haystack as $key => $val)
            {
            if (is_array($val) && $subPath=array_searchRecursive($needle, $val, $strict, $path))
                {
                $path=array_merge($path, array($key), $subPath);
                return $path;
                }
            elseif ((!$strict && $val == $needle) || ($strict && $val === $needle))
                {
                $path[]=$key;
                return $path;
                }
            }

        return false;
        }


public static function  multiarray_keys($ar,$level=0,$sl=0) {

            if(($level)&&($level<$sl))return;    
            $keys = array();              
            foreach($ar as $k => $v) {
                $keys[] = $k;
                if (is_array($ar[$k]))
                    if($ke=XARRAY::multiarray_keys($ar[$k],$level,$sl+1))$keys = array_merge($keys,$ke);
            }
            return array_unique($keys);
        }
        
        
             
public static    function multidim_value_key_collect($arr)
    {    
         $ext=array();             
         XARRAY::array_walk_recursive2($arr,'multidim_value_key_collect');
         return multidim_value_key_collect(0,0,1);

    }            

 public static   function array_walk_recursive2(&$input, $funcname, $userdata = "")
        {   
        if (!is_callable($funcname))
            {
            return false;
            }

        if (!is_array($input))
            {
            return false;
            }

        foreach ($input AS $key => $value)
            {
            if (is_array($input[$key]))
                {
                XARRAY::array_walk_recursive2($input[$key], $funcname, $userdata);
                }
            else
                {
                $saved_value=$value;
                $saved_key  =$key;

                if (!empty($userdata))
                    {
                    $funcname($value, $key, $userdata);
                    }
                else
                    {
                    $funcname($value, $key);
                    }

                if ($value != $saved_value || $saved_key != $key)
                    {
                     $input[$key]=$value;
                    }
                }
            }

        return true;
        }

 public static   function askeyval($arr, $key)
    {
    $new_arr = array();        
        
    if(is_array($arr) AND !empty($arr))
        {
            foreach ($arr as $k_ar => $val)
            {
                $new_arr[$k_ar] = $val[$key];
            }

            return $new_arr;
        }

        return false;
    }

    //$a[]=array('key'=>'val')  => a['key']='val'
 public static   function arr_to_keyarr($arr, $key, $field)
        {
        if (is_array($arr))
            {
            foreach ($arr as $val)
                {
                $new_arr[$val[$key]]=$val[$field];
                }

            return $new_arr;
            }
        }
    
 public static   function array_intersect_key_recursive()
    {
        $arrs = func_get_args();
        $result = array_shift($arrs);
        foreach ($arrs as $array) {
            foreach ($result as $key => $v) 
            {
                if (!array_key_exists($key, $array)) 
                {  
                    unset($result[$key]);
                }elseif(is_array($v))
                {   
                    $result[$key]=XARRAY::array_intersect_key_recursive($array[$key],$v);
                    
                }
            }
        }
        return $result;
   }

   
public static    function array_merge_plus(&$arr1,&$arr2,$keys=false)
    {           
        while (list($k,$v)=each($arr2))
        { 
            if($keys){$arr1[$k]=$v;}else{$arr1[]=$v;}
        }reset($arr1);reset($arr2);    
    }           
   
    //a[id][param]=array(key=>val))

    //a[id][param_key]=param_val

    //=>     a[param_val]=valk;
    //ключевой параметр   //параметр преобразования //
    
 public static   function arr_to_lev($arr, $param_key, $param, $key)
        {
        if ($arr)
            {
            foreach ($arr as $ar)
                {
                $newarr[$ar[$param_key]]=$ar[$param][$key];
                }

            return $newarr;
            }
        }

        
 public static       function arr_to_lev2($arr, $param_key, $param_name, $param, $key)
        {
        if ($arr)
            {
            foreach ($arr as $ar)
                {
                $newarr[$ar[$param_key][$param_name]]=$ar[$param][$key];
                }

            return $newarr;
            }
        }
        

 public static   function add_key_prefix($arr,$key) 
    {
        if(is_array($arr))
        {
            while(list($k,$v)=each($arr))
             {
                $rarr[$key.$k]=$v;
             }
             
             return $rarr;
            
        }
    }


public static   function array_diff_key()
    {
        $argCount   = func_num_args();
        $argValues  = func_get_args();
        $valuesDiff = array();
       
        if ($argCount < 2)
        {
            return false;
        }
       
        foreach ($argValues as $argParam)
        {
            if (!is_array($argParam))
            {
                return false;
            }
        }
       
        foreach ($argValues[0] as $valueKey => $valueData)
        {
            for ($i = 1; $i < $argCount; $i++)
            {
                if (isset($argValues[$i][$valueKey]))
                {
                    continue 2;
                }
            }
           
            $valuesDiff[$valueKey] = $valueData;
        }
       
        return $valuesDiff;
    }
    
    
  public static  function sortByField($array, $on, $order='asc')
            {
                $new_array = array();
                $sortable_array = array();

                if (count($array) > 0) {
                    foreach ($array as $k => $v) {
                        if (is_array($v)) {
                            foreach ($v as $k2 => $v2) {
                                if ($k2 == $on) {
                                    $sortable_array[$k] = $v2;
                                }
                            }
                        } else {
                            $sortable_array[$k] = $v;
                        }
                    }

                    switch ($order) {
                        case 'asc':
                            asort($sortable_array);
                        break;
                        case 'dsc':
                            arsort($sortable_array);
                        break;
                    }

                    foreach ($sortable_array as $k => $v) {
                        $new_array[$k] = $array[$k];
                    }
                }

                return $new_array;
            }
    
    
    }
class XSTRING
{
    function trimall($str, $charlist = " \n\r")
    {
        return str_replace(str_split($charlist), '', $str);
    }
    function is_string_int($int)
    {
        if (is_numeric($int) === TRUE)
        {
            if ((int) $int == $int)
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
    function declination($number, $titles)
    {
        $cases = array(
            2,
            0,
            1,
            1,
            1,
            2
        );
        return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
    }
    function date_recognize($date)
    {
        if (preg_match('/^\s*(\d\d?)[^\w](\d\d?)[^\w](\d{1,4}\s*$)/', $date, $match))
        {
            return strtotime($date);
        }
        else
        {
            return $date;
        }
    }
    function Reg($word)
    {
        $word     = preg_replace("/(\.|\-|\_)/", "", strtolower($word));
        $patterns = file('censor.bws');
        for ($i = 0; $i < count($patterns); $i++)
        {
            $patterns[$i] = trim($patterns[$i]);
            if (preg_match($patterns[$i], $word))
                return true;
        }
        return false;
    }
    function censorfilter($string)
    {
        $string    = trim($string);
        $str_words = explode(' ', $string);
        for ($i = 0; $i < count($str_words); $i++)
        {
            if (self::Reg($str_words[$i]))
            {
                $str_words[$i] = ' <font color=red>[censored]</font> ';
            }
        }
        return $string = implode(' ', $str_words);
    }
    
    
    function findncut_symbol_positon($str, $symbol, $maxposition, $end = '...')
    {
        $offset = 0;
        $str    = trim($str);
        $l      = strlen($str);
        while (($l > $offset) and ($maxposition != $pos))
        {
            $pos++;
            $cur    = strpos($str, $symbol, $offset + 1);
            $offset = $cur;
        }
        if ($cur)
        {
            if ($str_ = substr($str, 0, $cur + 1))
            {
                return $str_ . $end;
            }
        }
        else
        {
            return $str;
        }
    }
}
class XFILES
{
    
    public static function  isWritable($path) 
    {

        if ($path{strlen($path)-1}=='/')
            return is__writable($path.uniqid(mt_rand()).'.tmp');

        if (file_exists($path)) {
            if (!($f = @fopen($path, 'r+')))
                return false;
            fclose($f);
            return true;
        }

        if (!($f = @fopen($path, 'w')))
            return false;
        fclose($f);
        unlink($path);
        return true;
    }

   public static function filewrite($filename, $data)
    {
        if (!$handle = fopen($filename, 'w'))
        {
            exit;
        }
        if (fwrite($handle, $data) === FALSE)
        {
            exit;
        }
        fclose($handle);
        return true;
    }
    
   public static function directory_list($pth, $types = 'directories', $recursive = 0, $full = false)
    {
        static $pt;
        if (!$pt)
        {
            $pt = $pth;
        }
        if ($dir = opendir($pth))
        {
            $file_list = array();
            while (false !== $file = readdir($dir))
            {
                if (($file != '.' AND $file != '..'))
                {
                    if ((is_dir($pth . '/' . $file) AND ($types == 'directories' OR $types == 'all')))
                    {
                        if (!$full)
                        {
                            $p           = str_replace($pt, '', $pth . $file);
                            $file_list[] = $p;
                        }
                        else
                        {
                            $file_list[] = $pth . $file;
                        }
                        if ($recursive)
                        {
                            $file_list = array_merge($file_list, XFILES::directory_list($pth . '/' . $file . '/', $types, $recursive));
                            continue;
                        }
                        continue;
                    }
                    if (($types == 'files' OR $types == 'all'))
                    {                        
                          if (!$full)
                        {
                            $p           = str_replace($pt, '', $pth . $file);
                            $file_list[] = $p;
                        }
                        else
                        {
                            $file_list[] = $pth . $file;
                        }
                        continue;
                    }
                    continue;
                }
            }
            closedir($dir);
            return $file_list;
        }
        else
        {
            return FALSE;
        }
    }
  public static  function format_size($size, $round = 2)
    {
        $sizes = array(
            'B',
            'kB',
            'MB',
            'GB',
            'TB',
            'PB',
            'EB',
            'ZB',
            'YB'
        );
        for ($i = 0; $size > 1024 && isset($sizes[$i + 1]); $i++)
            $size /= 1024;
        return round($size, $round) . $sizes[$i];
    }

public static function unlink_recursive($dir, $deleteRootToo)
    {
        if (!$dh = @opendir($dir))
        {
            return;
        }
        while (false !== ($obj = readdir($dh)))
        {
            if ($obj == '.' || $obj == '..')
            {
                continue;
            }
            if (!@unlink($dir . '/' . $obj))
            {
                XFILES::unlink_recursive($dir . '/' . $obj, true);
            }
        }
        closedir($dh);
        if ($deleteRootToo)
        {
            @rmdir($dir);
        }
        return;
    }

public static function files_list($pth, $types = 'files', $allow_types = null, $recursive = 0, $get_basenames = false)
    {
        if ($dir = @opendir($pth))
        {
            $file_list = array();
            while (FALSE !== $file = readdir($dir))
            {
                if (($file != '.' AND $file != '..'))
                {
                    if ((is_dir($pth . '/' . $file) AND ($types == 'directories' OR $types == 'all')))
                    {
                        $file_list[] = $file;
                        if ($recursive)
                        {
                            $file_list = array_merge($file_list, XFILES::directory_list($pth . '/' . $file . '/', $types, $recursive,!$get_basenames));
                            continue;
                        }
                        continue;
                    }
                    if (($types == 'files' AND !is_dir($pth . '/' . $file)))
                    {
                        if (is_array($allow_types))
                        {
                            preg_match("/\.(.?)+/", $file, $ftype);
                            if (in_array($ftype[0], $allow_types) != false)
                            {
                                if (!$get_basenames)
                                {
                                    $file_list[] = $pth . '/' . $file;
                                }
                                else
                                {
                                    $file_list[] = $file;
                                }
                            }
                        }
                        else
                        {
                            if (!$get_basenames)
                            {
                                $file_list[] = $pth . '/' . $file;
                            }
                            else
                            {
                                $file_list[] = $file;
                            }
                        }
                        continue;
                    }
                    else
                    {
                        if ($types == 'all')
                        {
                            
                            if (is_array($allow_types))
                            {
                                preg_match("/\.(.?)+/", $file, $ftype);
                                if (in_array(strtolower($ftype[0]), $allow_types) != false)
                                {
                                    if (!$get_basenames)
                                    {
                                        $file_list[] = $pth . '/' . $file;
                                    }
                                    else
                                    {
                                        $file_list[] = $file;
                                    }
                                }
                            }
                            else
                            {
                                if (!$get_basenames)
                                {
                                    $file_list[] = $pth . '/' . $file;
                                }
                                else
                                {
                                    $file_list[] = $file;
                                }
                            }
                            continue;
                        }
                        continue;
                    }
                    continue;
                }
            }
            closedir($dir);
            return $file_list;
        }
        return FALSE;
    }
}
 
class xHTML
    {
        
        function xss_clean($data)
        {
            // Fix &entity\n;
            $data = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $data);
            $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
            $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

            // Remove any attribute starting with "on" or xmlns
            $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

            // Remove javascript: and vbscript: protocols
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

            // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

            // Remove namespaced elements (we do not need them)
            $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
            do
            {
                    // Remove really unwanted tags
                    $old_data = $data;
                    $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
            }
            while ($old_data !== $data);

            // we are done...
            return $data;
        }
        

    function as_select_opt($arr_assoc, $elementname = null, $is_empty = '')
        {
        if (is_array($arr_assoc))
            {
                         
                if (XSTRING::is_string_int($elementname))
                {
                    $elementname=(int)$elementname;
                }

            foreach ($arr_assoc as $key => $val)
                {

                //for multiply selectors
                if (is_array($elementname))
                    {
                    $fkey=array_key_exists($key, $elementname);

                    if ($fkey)
                        {
                        $selected="selected";
                        }
                    else
                        {
                        $selected='';
                        }
                    }
                else
                    {
                    if ($elementname === $key)
                        {
                        $selected="selected";
                        }
                    else
                        {
                        $selected="";
                        }
                    }

                $ext=$ext . "<option value='$key' $selected >$val</option>\r";
                }

                if ($is_empty)
                {
                $ext="<option value=' ' selected >$is_empty</option>\r".$ext;
                }
                
            return $ext;
            }
        }
        
        
        function  as_checkboxes($arr, $chxname='',$pretag='span',$obtag='li',$checked=false)
        {
            if(is_array($arr)) 
                {
                foreach($arr as $a=>$k)
                {
                 if(is_array($checked)&&in_array($a,$checked)){$ch='checked';}else{$ch="";}
                    $c.='<'.$obtag.'>'.'<input type=checkbox name="'.$chxname.'" '.$ch.' value="'.$a.'"></checkbox><'.$pretag.'>'.$k.'</'.$pretag.'>'.'</'.$obtag.'>';   
                    
                }
                return $c;
            }
            
        }

    // генерация массива для json передачи options     
    function arr_select_opt($options_arr, $selected = '', $add_empty = false)
    {
        $newarr = array();
        
        if(is_array($options_arr) AND !empty($options_arr))
        {
            if($add_empty)
            {
                $newarr[] = array('value' => '', 'text' => '');
            }

            foreach ($options_arr as $key => $val)
            {
                     if(is_array($selected))
                     {
                            $fkey=array_key_exists($key, $selected);   
                            if($fkey!==false)
                            {
                                $newarr[] = array('value' => $key, 'text' => $val, 'selected' => true);
                            }
                     
                     }elseif($selected == $key)
                     {
                            $newarr[] = array('value' => $key, 'text' => $val, 'selected' => true);
                            
                     }else
                     {
                            $newarr[] = array('value' => $key, 'text' => $val);
                        
                     }
            }

            return $newarr;
        }
        
        return FALSE;
    }
}
       
interface xCacheDriver
{
   public static function initDriver();
   public static function serializedRead($module,$id,$timeout=null);
   public static function serializedWrite($data,$module,$id);
   public static function clear($module, $id);   
   public static function read($module,$id, $timeout = null);
   public static function write($data, $module, $id, $timeout = null);
   public static function clearBranch($modules);
   
} 

class xCache 
{
    static $driver;

    public static function getCurrentDriver()
    {
        return self::$driver;
    }
    
    /**
    * инициализация механизма кеширования..
    *    
    * @param mixed $driver - File,MemCache,Auto    
    */
    
    public static function initialize($driver='File')
    {
            if($driver=='Auto')
            {
                  if(class_exists('Memcache'))
                  {
                    $driver='MemCache';              
                    
                    }else{
                        
                    $driver='File';
                  }
            }

            $driverName='xCache'.$driver.'Driver';
            Common::loadDriver(__CLASS__,$driverName);
            self::$driver=$driverName;
            self::initDriver();            
    }
    
    
    final public static function __callStatic($chrMethod, $arrArguments=array()) 
    {
     if(isset(self::$driver))return call_user_func_array(self::$driver.'::'.$chrMethod,$arrArguments);
    } 

}


class Common
    {
        
        public static $page_nav_view='pages';
        public static $page_move_chunk=0;
            
      public static  function isFileExists($file)
        {             
            if(file_exists(PATH_.$file)&&$file)
            {
              return true;  
            }
            
        }
        
        public static function loadDriver($device,$driver)
        {
            require_once(xConfig::get('PATH','DRIVERS').$device.'/'.$driver.'.php');
        }
        
        public static function compress_output(&$output,$level=9)
        {
            if (@$_SERVER["HTTP_ACCEPT_ENCODING"] && FALSE !== strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip'))
            {
                 $output=gzencode($output, $level);
                 header('Content-Encoding: gzip');
                 header('Content-Length: ' . strlen($output));
            }
            return $output;
        }
        
        
        public static function createMark($markSource)
           {
               return md5(var_export($markSource,true));
           } 
           
        
     public static   function isFolderWriteable($dir)
        {
            if(!is_writeable($dir))
            {
                trigger_error('Directory must be writeable : '.$dir,E_USER_ERROR);
            }
        }
        
    public static    function getModuleTemplateListAsSelector($module, $extensions = array('.html'),$langversion='')
        {
                if($files=Common::getModuleTemplateList($module, $extensions = array('.html'),$langversion))
                {
                    return xHTML::arr_select_opt(XARRAY::combine($files, $files),null, true);          
                }
        }
        
    public static    function getModuleTemplateList($module, $extensions = array('.html'),$langversion='')
        {
            if (is_array($files=XFILES::files_list(xConfig::get('PATH','MODULES_TEMPLATES') . $module, 'files', $extensions, 0, true)))
            {
                return $files;
            }
        }             
        
       
 public static   function getFrontModuleTplPath($module, $template)
        {
            return xConfig::get('PATH','MODULES_TEMPLATES'). $module . '/' . $template;
        }

  public static  function WriteLog($data, $prefix = '')
        {
        if (is_array($data))
            {
            $data=print_r($data, true);
                }

            $f=fopen('log/log.txt', 'a+');
            fwrite($f, ' [' . date("H:i:s") . '] ' . $prefix . ' ' . $data . "\n\r");
            fclose ($f);
        }

    //DEPRICATE THIS TTABLESOURCE ONLY 
    // не используем DB Layer так как нужно быстрое выполненеие данной функции
    //{template использует приципы шаблонной системы tmultisection
    //'section_prefix префикс для обработки секций страниц
    //шаблон
    //$prefix   _nav_pages
    //$prefix   _nav_page->$prefix   _nav_pages
    //$prefix   _nav_page_selected->$prefix   _nav_pages

  public static  function get_nav_pages($table, $chunk_size, $where = '', $current = null, $return_full_info = false)
        {
        global $TDB;
        global $TMS;

        if ($where)
            {
            $where=' AND ' . $where;
            }

        $query ="SELECT COUNT(*) as count FROM $table WHERE 1=1 $where ";
        $result=mysql_query($query);
       
        if ($m=mysql_fetch_assoc($result))
            {
            $pages_count=ceil($m['count'] / $chunk_size);
            }

        if ($return_full_info)
            {
            $pages_count['pages_count']=$pages_count;
            $pages_count['rows']       =$m['count'];
            }

        return $pages_count;
        }

public static  function create_action_link($server,$action,$params)
    {
        if($params){
            foreach($params as $k=>$p)
            {
                $ps.='/'.$k.'/'.$p;
            }
        }
        return  $server.'/~'.$action.$ps;
        
    }    
        
public static function parse_nav_pages($obj_count, $chunk_size, $current, $link, $section_prefix = '',$paginame='sl')
    {
        global $TMS;
        
        if($chunk_size == 0) return false;
        if($obj_count > $chunk_size)
        {
            if (strlen($paginame)) $paginame.='/';
            $cpage=0;
            //prevent division by zero;
            if (!$chunk_size)
            {
                $chunk_size=DEFAULT_CHUNK_SIZE;
            }
            
            $pages_count=ceil($obj_count / $chunk_size);
            
            if($move_chunk=Common::$page_move_chunk)
            {
                if(($move_chunk*2+1) < $pages_count){$move_chunk_all=$move_chunk*2+1;}else{$move_chunk_all = $pages_count;}

                $pages_count_current=ceil($current/$chunk_size)+1;
                
                if(($move_chunk+$pages_count_current)<$pages_count)
                {
                    $movch_pages_count=$move_chunk_all;
                    
                    if($move_chunk+1<=$pages_count_current)
                    {
                        $pages_count_current-=$move_chunk+1;
                        $movch_pages_count+=$pages_count_current;
                    }else{
                        $pages_count_current=0;
                    }
                
                }else{
                    $movch_pages_count= $pages_count;
                    
                    if($pages_count>$move_chunk_all)
                    {
                        $pages_count_current=$pages_count-$move_chunk_all;
                    }else{
                        $pages_count_current=0;
                    }
                }
                
                if($current==0&&($move_chunk+$pages_count_current>$pages_count)){
                    $movch_pages_count= $pages_count;
                }
                
                $pages_real_count=$movch_pages_count;
            
            }else{
                $pages_count_current=0;
                $pages_real_count=$pages_count;
            }
            
            $page_line ='';
            $i=$pages_count_current;
            
            if($pages_real_count)
                while ($i < $pages_real_count)
                {
                    $i++;
                    
                    if(Common::$page_nav_view=='items')
                    {
                        $pnum=$cpage.'-'.($cpage+$chunk_size);

                    }elseif(Common::$page_nav_view=='pages')
                    {
                        $pnum=$i;
                    }
                    
                    $cpage=($i-1)*$chunk_size;
                    $data=array(
                        'link' => $link . '/'.$paginame. $cpage . '/',
                        'pnum' =>$pnum,
                        'start' => $cpage+1,
                        'end' => $cpage+1+$chunk_size
                    );
                    
                    if($cpage == $current)
                    {
                        $TMS->AddMassReplace($section_prefix . 'one_page_selected', $data);
                        $page_line.=$TMS->parseSection($section_prefix . 'one_page_selected');
                    }
                    else
                    {
                        $TMS->AddMassReplace($section_prefix . 'one_page', $data);
                        $page_line.=$TMS->parseSection($section_prefix . 'one_page');
                    }
                    
                    $cpage+=$chunk_size;
                }
                
                if(ceil($current/$chunk_size) < $pages_count-1)
                {
                    $np=$current+ $chunk_size;
                    $TMS->AddMassReplace($section_prefix .'next_page',array('link'=> $link . '/'.$paginame. $np. '/', ));
                    $TMS->parseSection($section_prefix.'next_page' ,true);
                }
                
                if(ceil($current>0))
                {
                    $np=$current-$chunk_size;
                    $TMS->AddMassReplace($section_prefix .'previous_page',array('link'=> $link . '/'.$paginame. $np. '/', ));
                    $TMS->parseSection($section_prefix.'previous_page' ,true);
                }

                $TMS->AddMassReplace($section_prefix . 'page_line', array('page_line' => $page_line,'pages_count'=>$pages_count,'count'=>$obj_count));
                $TMS->parseSection($section_prefix . 'page_line', true);
        }
    }


public static function &classesFactory($classname,$args,$do_not_call=false)
        {
                        
        if (file_exists($filepath=xConfig::get('PATH','CLASSES') . $classname . '.class.php'))
            {
            
            require_once ($filepath);
            
            if(!$do_not_call)
                {
                    
                    $class = new ReflectionClass($classname);
                    $instance = $class->newInstanceArgs($args);
                    return $instance;
                }
            }
        }

        
 public static   function map_module($module, $return_path = false)
        {
        global $_PATH;

        $module      =explode('.', $module);
        $ar['common']=$_PATH['MOD'] . $module[0] . '/' . $module[0] . '.common.class.php';
        $ar['intf']  =$_PATH['MOD'] . $module[0] . '/' . $module[0] . '.' . $module[1] . '.class.php';
        return $ar;
        }

   
        
 public static   function call_module_method($module,$func,$params)
    {
        if(!is_object($module))
        {
            $m=Common::module_factory($module);
            
        }else{
            
            $m=$module;
        }
        
        if(method_exists($m,$func))
            {
               return call_user_func_array(array($m, $func),$params);
            }    
    }
        
    //module page.back   page.front
    


public static   function media_path($module)
        {
            return xConfig::get('PATH','MEDIA'). $module . '/';
        }

        
 public static   function webMediaPath($module)
        {
            return xConfig::get('PATH','WEB_PATH_MEDIA'). $module . '/';
        }

    
  public static    function translate_to($data,$langsource)
        {
               if(is_array($data)&&$langsource)
               {
                   return explode('@@',str_replace(array_keys($langsource),$langsource, implode('@@',$data)));
               
               }elseif($langsource)
               {
                   return str_replace(array_keys($langsource),$langsource,$data);
               }else{
                   
                   return $data;
               }
            
        }
        
  
  /**
  * Получить языковые данные по модулю
  * 
  * @param mixed $module
  * @param mixed $lang
  * @param mixed $tpl
  * @return array
  */
  
public static  function getModuleLang($module, $lang, $tpl)
        {
            
            static  $langCache;
            static  $commonLang;

            if(!$commonLang)
            {
                if(file_exists(xConfig::get('PATH','ADM') . '/glang/'.$lang.'.lang.php'))
                {
                    require_once(xConfig::get('PATH','ADM') . '/glang/'.$lang.'.lang.php');                        
                    $commonLang=$LANG;
                    unset($LANG);
                }
            }
                

            if(!$langCache[$module])
            {
                
                if($module!='adminPanel'&& file_exists($f=xConfig::get('PATH','MOD') . $module . '/tpl/lang/'.$lang.'.lang.php'))
                {
                        require_once($f);        
                                        
                    
                }elseif($module=='adminPanel'&&file_exists($f=xConfig::get('PATH','ADM') . 'tpl/lang/'.$lang.'.lang.php'))
                {
                        require_once($f);                        
                }
                
                elseif($module=='core'&&file_exists($f=xConfig::get('PATH','MOD') . 'lang/'.$lang.'.lang.php'))
                {
                        require_once($f);                        
                }
        
                // преобразуем переменные для замены
                if($LANG)
                {
                    $langCache[$module]=$LANG;
                }
                
            }
      
                if($langCache[$module][$tpl])
                {
                    
                    $m=array_merge($commonLang,$langCache[$module][$tpl]);
                    return  $m;
                
                }else{
                                
                    return $commonLang;
                }
            
            }
        
  
     public static   function getModuleTpl($module, $tpl)
            {
                    return xConfig::get('PATH','MOD'). $module . '/tpl/' . $tpl;            
            }

     public static   function getAdminTpl($tpl)
            {
                return xConfig::get('PATH','ADM') . 'tpl/' . $tpl;
            }

        
public static    function pack_data($data)
        {
        if ($data)
            {
            if (is_array($data))
                {
                $data=implode('', $data);
                }

            $data=str_replace(array
                (
                '  ',
                "\n",
                "\r",
                "\t"
                ),            array
                (
                ' ',
                '',
                '',
                ''
                ),            $data);

            return $data;
            }
        }

 public static   function GenerateHash($rand = 1,$returnLength=0)
        {
        $time=Common::getmicrotime();

        if ($rand != 1)
            {
            $k=$rand . $time . rand(0, 1000);
            }
        $Hashed=md5($time . $k);
        if(!$returnLength){ return $Hashed;}else{return substr($Hashed, 0,$returnLength );}
        
        }

   public static function RelativeHash($key, $hash)
        {
        $newhash=$hash . $key;
        $newhash=strrev($newhash);
        return $newhash;
        }

    public static function   getmicrotime()
        {
        list($usec, $sec)=explode(" ", microtime());
        return ((float)$usec + (float)$sec);
        }

 public static   function get_site_tpl($module, $tpl, $prefix = '_common')
        {
            global $_PATH;
            return $_PATH['TEMPLATES'] . $prefix . '/' . $module . '/' . $tpl;
        }
    } #endclass 
    
class XCODE
{
    function encode_entities($s)
    {
        static $trans;
        if (!is_array($trans))
        {
            $trans = get_html_translation_table(HTML_ENTITIES);
            $trans = array_slice($trans, 0, 27);
        }
        if (is_array($s))
        {
            XARRAY::array_walk_recursive2($s, 'entities_recursive');
            return $s;
        }
        $s = str_replace(array(
            'Ё',
            'ё'
        ), array(
            '&#203;',
            '&#235;'
        ), $s);
        return strtr($s, $trans);
    }

    function utf2win($str, $force = false)
    {
        global $_COMMON_SITE_CONF;
        static $Encoding;
        if (($_COMMON_SITE_CONF['site_encoding'] == 'utf-8') && (!$force))
            return $str;
        if (is_array($str))
        {
            $str    = implode('@@', $str);
            $a_flag = 1;
        }
        if (!$force)
        {
            $force = $_COMMON_SITE_CONF['site_encoding'];
        }
        if (!$Encoding)
        {
            $Encoding = new ConvertCharset("utf-8", $force, $Entities);
        }
        if (!$m = $Encoding->Convert($str))
        {
            $m = $str;
        }
        if ($a_flag)
        {
            $m = explode('@@', $m);
        }
        return $m;
    }
    
    function translit($text,$dont_strip_tags=false, $dont_clean_specialchars = false){
    if (!$dont_strip_tags) $text=strip_tags($text);
        $filter = array("А"=>"A", "а"=>"a", "Б"=>"B", "б"=>"b", "В"=>"W", "в"=>"w", "Г"=>"G", "г"=>"g", "Д"=>"D", "д"=>"d", "Е"=>"E", "е"=>"e", "Ё"=>"Jo", "ё"=>"jo", "Ж"=>"J", "ж"=>"j", "З"=>"Z", "з"=>"z", "И"=>"I", "и"=>"i", "Й"=>"I", "й"=>"i", "К"=>"K", "к"=>"k", "Л"=>"L", "л"=>"l", "М"=>"M", "м"=>"m", "Н"=>"N", "н"=>"n", "О"=>"O", "о"=>"o", "П"=>"P", "п"=>"p", "Р"=>"R", "р"=>"r", "С"=>"S", "с"=>"s", "Т"=>"T", "т"=>"t", "У"=>"U", "у"=>"u", "Ф"=>"F", "ф"=>"f", "Х"=>"h", "х"=>"h", "Ц"=>"Ch", "ц"=>"ch", "Ч"=>"Tsch", "ч"=>"tsch", "Ш"=>"Sch", "ш"=>"sch", "Щ"=>"Sch", "щ"=>"sch", "Э"=>"E", "э"=>"e", "Ю"=>"Yu", "ю"=>"uy", "Я"=>"Ya", "я"=>"ya", "Ь"=>"", "ь"=>"", "Ъ"=>"", "ъ"=>"", "Ы"=>"I", "ы"=>"i", " "=>"_");
        if (!$dont_clean_specialchars) $filter += array('"' => '', "'" => "", "+" => "_plus_", "!" => "", "?" => "", '`' => '', '*' => '', '#' => '', '%' => '', '^' => '');
        return strtr($text,$filter);
    }  
         
    function win2utf($s, $force = false)
    {
        global $_COMMON_SITE_CONF;
        static $Encoding;
        if (($_COMMON_SITE_CONF['site_encoding'] == 'utf-8') && (!$force))
            return $s;
        if (!$force)
        {
            $force = $_COMMON_SITE_CONF['site_encoding'];
        }
        if (!is_object($Encoding))
        {
            $Encoding = new ConvertCharset($force, "utf-8", $Entities);
        }
        if (is_array($s))
        {
            XARRAY::array_walk_recursive2($s, 'winutf_recursive', $force);
            return $s;
        }
        if (!$m = $Encoding->Convert($s))
        {
            $m = $str;
        }
        return $m;
    }
}


?>