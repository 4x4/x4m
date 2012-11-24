<?php
class DEBUG
{
    function debugger()
    {
        debugbreak();
    }
    
    
    function var_dump($var)
    {
        $output = var_export($var, true);
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        return $output;
    }
    
    
    function get_vars($params, $section)
    {
        global $TMS, $_PATH;
        if ($TMS->potentialKeys[$section])
        {
            $sr = array_merge($TMS->getSectionReplacements($section), array_keys($TMS->potentialKeys[$section]));
            $fr = array_merge($TMS->Fields[$section], $TMS->potentialKeys[$section]);
        }
        else
        {
            $sr = $TMS->getSectionReplacements($section);
            $fr = $TMS->Fields[$section];
        }
        return ENHANCE::var_dump(array_combine($sr, $fr));
    }
}

 
    
class ENHANCE
{
    var $e = 0;


    function declination($params)
    {
        return XSTRING::declination($params[0], $params[1]);
    }
    function set_page_nav_view($params)
    {
        Common::$page_nav_view = $params[0];
    }
    function set_page_nav_move($params)
    {
        Common::$page_move_chunk = $params[0];
    }
    function add_file_section($params)
    {
        global $TMS, $_PATH;
        $TMS->AddFileSection($_PATH['PATH_TEMPLATES'] . $params[0]);
    }
    function add_section_vars($params, $section)
    {
        global $TMS, $_PATH;
        foreach ($params as $p)
        {
            $TMS->Replacement[$section][] = '{%F:' . $p . '%}';
            $TMS->Fields[$section][]      = '';
        }
    }
    function cut_words($params)
    {
        if (!$params[1])
        {
            $params[1] = ' ';
        }
        if (isset($params[3]))
            return XSTRING::findncut_symbol_positon($params[0], $params[1], $params[2], $params[3]);
        return XSTRING::findncut_symbol_positon($params[0], $params[1], $params[2]);
    }
    function cut_words2($params)
    {
        if (!$params[5])
            $str = (strip_tags(iconv("UTF-8", "WINDOWS-1251", $params[0])));
        if (strlen($str) <= $params[2])
            return stripslashes($params[0]);
        if (!$params[1])
            $params[1] = ' ';
        if (!$params[4])
            $params[4] = '...';
        $tmp = substr($str, 0, $params[2]);
        $pos = strpos($str, $params[1], $params[2]);
        if ($pos !== false)
            $tmp .= substr($str, $params[2], $pos - $params[2]) . $params[4];
        else
            return stripslashes($params[0]);
        if (!$params[5])
            $tmp = iconv("WINDOWS-1251", "UTF-8", $tmp);
        return stripslashes($tmp);
    }
    
    
    function call_module($params)
    {
        Common::module_factory($params[0]);
    }
    
    function get_pictures_from_folder($params)
    {
        $types = array(
            '.jpg',
            '.png',
            '.gif',
            '.JPG',
            '.PNG',
            '.GIF'
        );
        if ($params[0])
        {
            if (!$params[0]['folder'])
                return;
            if ($params[0]['types'])
            {
                $types = $params[0]['types'];
            }
            if ($files = XFILES::files_list(PATH_ . $params[0]['folder'], 'files', $types, 0, true))
            {
                if ($params[0]['sort'])
                {
                    switch ($params[0]['sort'])
                    {
                        case 'natsort':
                            natsort($files);
                            break;
                        case 'rsort':
                            rsort($files);
                            break;
                    }
                }
                foreach ($files as $file)
                {
                    $ext[] = array(
                        'image' => $params[0]['folder'] . $file
                    );
                }
                return $ext;
            }
        }
    }
    
    function parse_xls_file($params)
    {
        $params = $params[0];
        Common::inc_module_factory('ExcelReader', true);
        $data = new Spreadsheet_Excel_Reader(PATH_ . $params['xls_file']);
        return $data->dump(false, false, 0, $params['class']);
    }
    
    function _server_vars($params)
    {
        return $_SERVER[$params[0]];
    }
    
    function str_replace($params)
    {
        if (@$params = current($params))
            return str_replace($params[0], $params[1], $params[2]);
    }
    
    function str_repeat($params)
    {
        return str_repeat($params[0], $params[1]);
    }
    
    function round($val, $params)
    {
        return round($val, $params[0]);
    }
    
    function number_format($val, $params)
    {
        if ($val && !empty($params[0]))
        {
            return number_format($val, $params[0][0], $params[0][1], $params[0][2]);
        }
        else
        {
            return number_format($val, 0, ' ', ' ');
        }
    }
    
    
    
    function add_postifix_to_filename($params)
    {
        $pos = strrpos($params[0], '.');
        if (!strlen($params[1]) || $pos === false)
            return $fname;
        return substr($params[0], 0, $pos) . $params[1] . substr($params[0], $pos);
    }
    
    
    function get_from($params)
    {
        return Enhance::get($params[0], array(
            0 => $params[1]
        ));
    }
    
    function get($val, $params)
    {
        global $TMS;
        if ($s = explode('>', $params[0]))
        {
            foreach ($s as $sep)
            {
                $val = $val[trim($sep)];
            }
            return $val;
        }
    }
    
    function date_ru($val, $params)
    {
        static $q;
        if ($params[1])
        {
            $val = strtotime($val);
        }
        $formatum  = $params[0];
        $timestamp = (int) $val;
        if (($timestamp <= -1) || !is_numeric($timestamp))
            return '';
            
        $l      = Common::get_module_lang('core', $_COMMON_SITE_CONF['site_language'], 'date_format');
        if(!$q)
        {
          $q['q'] = array(-1 => 'w', 'воскресенье','понедельник', 'вторник', 'среда', 'четверг', 'пятница', 'суббота');
          $q['Q'] = array(-1 => 'w', 'Воскресенье','Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота');
          $q['v'] = array(-1 => 'w', 'вс','пн', 'вт', 'ср', 'чт', 'пт', 'сб');
          $q['V'] = array(-1 => 'w',  'Вс','Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб');
          $q['H'] = array(-1 => 'n', '', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');
          $q['x'] = array(-1 => 'n', '', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
          $q['X'] = array(-1 => 'n', '', 'Января', 'Февраля', 'Март', 'Апреля', 'Май', 'Июня', 'Июля', 'Август', 'Сентября', 'Октября', 'Ноября', 'Декабря');
          $q['f'] = array(-1 => 'n', '', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек');
          $q['F'] = array(-1 => 'n', '',  'Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек');
        }
          
        if ($timestamp == 0)
            $timestamp = time();
        $temp = '';
        $i    = 0;
        while ((strpos($formatum, 'q', $i) !== FALSE) || (strpos($formatum, 'Q', $i) !== FALSE) || (strpos($formatum, 'v', $i) !== FALSE) || (strpos($formatum, 'V', $i) !== FALSE) || (strpos($formatum, 'x', $i) !== FALSE) || (strpos($formatum, 'X', $i) !== FALSE) || (strpos($formatum, 'f', $i) !== FALSE) || (strpos($formatum, 'F', $i) !== FALSE) || (strpos($formatum, 'H', $i) !== FALSE))
        {
            $ch['q'] = strpos($formatum, 'q', $i);
            $ch['Q'] = strpos($formatum, 'Q', $i);
            $ch['v'] = strpos($formatum, 'v', $i);
            $ch['V'] = strpos($formatum, 'V', $i);
            $ch['H'] = strpos($formatum, 'H', $i);
            $ch['x'] = strpos($formatum, 'x', $i);
            $ch['X'] = strpos($formatum, 'X', $i);
            $ch['f'] = strpos($formatum, 'f', $i);
            $ch['F'] = strpos($formatum, 'F', $i);
            foreach ($ch as $k => $v)
                if ($v === FALSE)
                    unset($ch[$k]);
            $a = min($ch);
            $temp .= date(substr($formatum, $i, $a - $i), $timestamp) . $q[$formatum[$a]][date($q[$formatum[$a]][-1], $timestamp)];
            $i = $a + 1;
        }
        $temp .= date(substr($formatum, $i), $timestamp);
        return $temp;
    }
    function asort($params)
    {
        asort($params[0]);
        return $params[0];
    }
    function rsort($params)
    {
        rsort($params[0]);
        return $params[0];
    }
    function calc($params)
    {
        @eval('$r=' . $params[0] . ';');
        return $r;
    }
    function rand($params)
    {
        return rand();
    }
    function current($params)
    {
        if (is_array($params[0]))
        {
            return current($params[0]);
        }
    }
    function date_locale($val, $params)
    {
        global $_COMMON_SITE_CONF;
        if (!$params[1])
        {
            $params[1] = 'RUS';
        }
        $v = strtotime($val);
        setlocale(LC_ALL, $params[1]);
        $tdate = strftime($params[0], strtotime($val));
        $l     = Common::get_module_lang('core', $_COMMON_SITE_CONF['site_language'], 'date_format');
        if ($params[1] == 'RUS')
        {
            $treplace = array(
                $l['{January}'] => $l['{january}'],
                $l['{February}'] => $l['{february}'],
                $l['{March}'] => $l['{march}'],
                $l['{April}'] => $l['{april}'],
                $l['{May}'] => $l['{may}'],
                $l['{June}'] => $l['{june}'],
                $l['{July}'] => $l['{july}'],
                $l['{August}'] => $l['{august}'],
                $l['{September}'] => $l['{september}'],
                $l['{October}'] => $l['{october}'],
                $l['{November}'] => $l['{november}'],
                $l['{December}'] => $l['{december}'],
                $l['{Jan}'] => $l['{jan}'],
                $l['{Feb}'] => $l['{feb}'],
                $l['{Mar}'] => $l['{mar}'],
                $l['{Apr}'] => $l['{apr}'],
                $l['{May}'] => $l['{may}'],
                $l['{Jun}'] => $l['{jun}'],
                $l['{Jul}'] => $l['{jul}'],
                $l['{Aug}'] => $l['{aug}'],
                $l['{Sep}'] => $l['{sep}'],
                $l['{Oct}'] => $l['{oct}'],
                $l['{Nov}'] => $l['{nov}'],
                $l['{Dec}'] => $l['{dec}'],
                'January' => $l['{january}'],
                'February' => $l['{february}'],
                'March' => $l['{march}'],
                'April' => $l['{april}'],
                'May' => $l['{may}'],
                'June' => $l['{june}'],
                'July' => $l['{july}'],
                'August' => $l['{august}'],
                'September' => $l['{september}'],
                'October' => $l['{october}'],
                'November' => $l['{november}'],
                'December' => $l['{december}'],
                'Jan' => $l['{jan}'],
                'Feb' => $l['{feb}'],
                'Mar' => $l['{mar}'],
                'Apr' => $l['{apr}'],
                'May' => $l['{may}'],
                'Jun' => $l['{jun}'],
                'Jul' => $l['{jul}'],
                'Aug' => $l['{aug}'],
                'Sep' => $l['{sep}'],
                'Oct' => $l['{oct}'],
                'Nov' => $l['{nov}'],
                'Dec' => $l['{dec}']
            );
            return strtr($tdate, $treplace);
        }
        else
        {
            return $tdate;
        }
    }
    function date_format($val, $params)
    {
        if ($params[1])
        {
            $val = strtotime($val);
        }
        return date($params[0], $val);
    }
    
    
    function image_transform_return($params, $section)
    {
        $img = array_shift($params);
        return ENHANCE::image_transform($img, $params, $section);
    }
    
    
    function image_transformj($val, $params, $section, $addF)
    {
        global $TMS;
        if ($params[0]['return_size'] && file_exists(PATH_ . $val))
        {
            $size = GetImageSize(PATH_ . $val);
            if ($params[0]['width'] && $params[0]['height'])
            {
                $ratio  = $params[0]['width'] / $params[0]['height'];
                $sratio = $size[0] / $size[1];
                if ($ratio < $sratio)
                {
                    $tnHeight = ceil($params[0]['width'] / $sratio);
                    $tnWidth  = (int) $params[0]['width'];
                }
                else
                {
                    $tnWidth  = ceil($params[0]['height'] * $sratio);
                    $tnHeight = (int) $params[0]['height'];
                }
            }
            else
            {
                $tnWidth  = $size[0];
                $tnHeight = $size[1];
            }
            $TMS->AddMassReplace($section, array(
                'w_' . $addF => $tnWidth,
                'h_' . $addF => $tnHeight
            ));
        }
        if ($params[0]['crop'])
        {
            $crop = '&cropratio=' . $params[0]['crop'];
        }
        if (($params[0]['watermark']) && ($params[0]['wposition']))
        {
            $md                         = md5($val);
            $_SESSION[$md]['image']     = $val;
            $_SESSION[$md]['watermark'] = $params[0]['watermark'];
            $_SESSION[$md]['wposition'] = $params[0]['wposition'];
            $val                        = $md;
        }
        return '/image.php/' . $val . '?width=' . $params[0]['width'] . '&height=' . $params[0]['height'] . '&image=' . $val . $crop;
    }
    
    
    function image_transform($val, $params, $section, $addF)
    {
        global $TMS;
        if ($params[3] && file_exists(PATH_ . $val))
        {
            $size = GetImageSize(PATH_ . $val);
            if ($params[0] && $params[1])
            {
                $ratio  = $params[0] / $params[1];
                $sratio = $size[0] / $size[1];
                if ($ratio < $sratio)
                {
                    $tnHeight = ceil($params[0] / $sratio);
                    $tnWidth  = (int) $params[0];
                }
                else
                {
                    $tnWidth  = ceil($params[1] * $sratio);
                    $tnHeight = (int) $params[1];
                }
            }
            else
            {
                $tnWidth  = $size[0];
                $tnHeight = $size[1];
            }
            $TMS->AddMassReplace($section, array(
                'w_' . $addF => $tnWidth,
                'h_' . $addF => $tnHeight
            ));
        }
        if (($params[2]) && !file_exists(PATH_ . $val))
        {
            return $params[2];
        }
        if ($params[6])
        {
            $crop = '&cropratio=' . $params[6];
        }
        if (($params[4]) && ($params[5]))
        {
            $md                         = md5($val);
            $_SESSION[$md]['image']     = $val;
            $_SESSION[$md]['watermark'] = $params[4];
            $_SESSION[$md]['wposition'] = $params[5];
            $val                        = $md;
        }
        return '/image.php/' . $val . '?width=' . $params[0] . '&height=' . $params[1] . '&image=' . $val . $crop;
    }
 

    function count($params)
    {
        if ($params[0])
        {
            return count($params[0]);
        }
        else
        {
            return count($params);
        }
    }
    function assign($params)
    {
        return $params[0];
    }
  
    function set_session_module_param($params)
    {
        if ($params[0])
        {
            $_SESSION[$params[0]][$params[1]] = $params[2];
        }
    }
    
    function get_session($params)
    {
        return $_SESSION;
    }
    function get_session_module_param($params)
    {
        return $_SESSION[$params[0]][$params[1]];
    }
    function get_request_vars($params)
    {
        global $REQUEST_VARS;
        return $REQUEST_VARS[$params[0]];
    }
    function get_request_assoc($params)
    {
        global $REQUEST_ASSOC;
        return $REQUEST_ASSOC[$params[0]];
    }
    function get_request_action()
    {
        global $TPA;
        return $TPA->request_action;
    }
    function check_auth($params)
    {
        if ($_SESSION['siteuser']['authorized'])
        {
            return 1;
        }
        return 0;
    }
    
}   
    
    
    class RuntimeCache {
    private $cache = array();
    private $defaults;
    
    public function __construct($params = array()){
        $this->defaults = $params;
    }
    
    public function __call($method,$args){
        $name = $args[0];
        $args = (!empty($args[1]))? $args[1] : array();
        $action = substr($method,0,3);
        $method = substr($method,3);
        switch ($action) {
            case 'get':
                return isset($this->cache[$name][$method])? $this->cache[$name][$method] : $this->defaults[$method] ;
            break;
            case 'set':
                if (!isset($this->cache[$name])) $this->cache[$name] = $this->defaults;
                return $this->cache[$name][$method]=$args;
            break;
            case 'has':
                return (isset($this->cache[$name][$method]) and $this->cache[$name][$method]);
            break;
            default: return false;
            break;
        }
    }
}



/**
* Хелпер для работы с картинками. В шаблонах доступен по неймспейсу IMG. 
* Псевдопути картинок будут всегда статическими и иметь следующий формат:
*   /path/to/folder/{params}/image.jpg
* ,где {params} - новые размеры картинки /path/to/folder/image.jpg
* 
* Файл .htaccess настроен так, что если сервер не может найти картинку, 
* то запускается на выполнение скрипт image.php, который и обрабатывает запрос.
* 
* В папке /media должна находиться картинка noimage.jpg, которая отображается если запрашиваемая картинка не существует.
* 
* Параметры могут состоять из следующих значений:
*   w - (int) Максимальная ширина картинки.
*   h - (int) Максимальная высота картинки.
*   с - (string) Пропорции для новой картинки. Строка в формате (int)x(int). Первое число - ширина, второе - высота. 
*                Если не указано, берется исходные.
*   q - (int) Качество нового изображения. Число от 10 до 100. По умолчанию - 100
*   p - (bool) Прогрессив. 1 - да, 0 - нет.
*   b - (string) Цвет фона для прозрачных PNG картинок. Формат: FF00A1.
* 
* Метод IMAGE::getParams($url) будет возвращать вместе с параметрами еще одно значение:
*   i - (string) Путь к оригинальному изображению.
* 
* @category X4CMS
* @package Helpers::IMAGE
* @author Denis Mantsevich <denman@abiatec.com>
* @version 0.1.0
*/

class IMAGE {
    
    /**
    * Свойство класса, в котором храняться уже готовые результаты. Например для функция getParams(), getSize().
    * 
    * @var RuntimeCache
    */
    private static $temp;
    
    /**
    * Настройки по умолчанию
    * 
    * @var mixed
    */
    private static $config = array(
        // Список типов картинок для поиска в папках
        'types' => array('JPG', 'PNG', 'GIF', 'BMP', 'jpg', 'png', 'gif', 'bmp'),
        // Разделитель для параметров URL
        'splitter' => '-'
    );
    
    /**
    * Функция конструктор.
    * 
    * @param mixed $config  Настройки работы с изображениями
    */
    public function __construct($config = array()){
        if (!empty($config)) self::$config = $config;
        self::$temp = new RuntimeCache(array('Params'=>array(),'Size'=>array()));
    }
    
    /**
    * Получить полный путь к картинке на сервере.
    * 
    * @param string $val    Путь к изображению.
    * @return string
    */
    private static function getFullPath($val, $params = array()){
        return (!strpos($val,$_SERVER['DOCUMENT_ROOT']))? $_SERVER['DOCUMENT_ROOT'].$val : $val;
    }
    
    /**
    * Получить все параматеры из псевдопути изображения.
    * 
    * Пример:
    * Псевдопуть - /media/catalog/w100-h60-c1x1-bF00C1A/foo.jpg 
    * Функция вернет:
    * array(
    *   'i' => '/media/catalog/foo.jpg', 
    *   'w' => '100', 
    *   'h' => '60',
    *   'c' => '1x1',
    *   'b' => 'F00C1A'
    * )
    * 
    * @param string $val    Путь или псевдопуть к изображению.
    * @param json $params   Параметры. В текущей вресии не используется.
    * @return mixed
    */
    public static function getParams($val,$params = array()){
        $p = self::$temp->getParams($val);
        return !empty($p)? $p : self::getParamsFromURL($val);
    }
    
    /**
    * Получить все параматеры изображения исходя из строки. 
    * Если нет строгой необходимости использовать этот метод, то лучше использовать IMAGE::getParams().
    * Вернет параметры аналогичные методу IMAGE::getParams().
    * 
    * @param string $val    Путь или псевдопуть к изображению
    * @param json $params   Параметры. В текущей вресии не используется.
    * @return mixed
    */
    public static function getParamsFromURL($val,$params = array()){
       require_once 'slir/xslir.class.php';
       $request = new xSLIRRequest();
       return self::$temp->setParams($val,$request->getParametersFromURL($val)); 
    }
    
    /**
    * Проверяет существование картинки исходя из псевдопути.
    * 
    * @param string $val    Путь или пвсевдопуть к изображению
    * @param json $params   Параметры. В текущей версии не используется.
    * @return bool
    */
    public static function fileExists($val,$params = array()){
        if (file_exists(self::getFullPath($val))) {
            return true;
        } else {
            $p = self::getParams($val);
            return file_exists(self::getFullPath($p['i']));
        }
    }
    
    /**
    * Проверяет существует ли картинка в кеше. 
    * 
    * @param string $val    Псевдопуть к изображению
    * @param json $params   Параметры. В текущей врсии не используется.
    * @return bool
    */
    public static function cacheExists($val,$params = array()){
        require_once 'slir/xslir.class.php';
        $xslir=new xSLIR();
        return self::fileExists($xslir->getRequestCacheDir().$xslir->requestCacheFilename(self::getParams($val)));
    }
    
    /**
    * Находит и возвращает в виде массива все картинки в папке(папках) согласно фильтру.
    * 
    * @param json $params   Параметры в формате JSON.
    * Возможные значения ключей:
    *   'folder'    -   (string) Папка для поиска 
    *                            или 
    *                   (array)  Список папок для поиска.
    *   'types'     -   (string) Тип картинок
    *                            или
    *                   (array)  Список типов картинок.
    *   'limit'     -   (int)    Количество.
    *   'sort'      -   (string) Функция сортировки. Возможные значения: 'sort', 'rsort', 'shuffle', 'natsort'
    *   'preg'      -   (string) Регулярное выражение для фильтра имени. 
    *                            Обратите внимание, что это условие распространяется только на имя (без расширения).
    *   'recursive' -   (bool)   Искать файлы в подпапках. Поиск осуществляется в ширину.
    *   'filter'    -   (json)   Фильтр выборки. См. метод IMAGE::checkByFilter().
    *   'transform' -   (json)   Параметры для трансформации.
    * @return array
    */
    public static function getPicturesFromFolder($params){
        if (isset($params['folder']) && $params['folder']){
            $files = array();
            $folders = (array) $params['folder'];
            $limit = isset($params['limit'])? (int) $params['limit'] : -1; 
            $filter = (array) $params['filter']; 
            $transform = (array) $params['transform']; 
            $types = isset($params['types'])? (array)$params['types'] : (array) self::$config['types'] ;
            $nameFilter = "@".(isset($params['preg'])? $params['preg'] : '')."\.(".implode('|', $types).")$@";
            $count = count($folders);
            for ($i=0;$i<$count;$i++){
            $folder = $folders[$i];
                $fullPath = self::getFullPath($folder);
                if ($dir = @opendir($fullPath)){
                    while (false !== ($file = readdir($dir)) and $limit!==0) {
                        if ($file!=='.' and $file!=='..'){    
                            if (is_file($fullPath.$file)){
                                if (preg_match($nameFilter, $file) and self::checkByFilter($folder.$file, $filter)){
                                    array_push($files, self::transform($folder.$file, $transform));
                                    $limit--;
                                }
                            } else {
                                if ($params['recursive']) {
                                    array_push($folders, $folder.$file.'/');
                                    $count++;
                                }
                            }
                        }
                    }
                    if ($limit === 0) $count = 0;
                }
            }
            $params['sort']($files);
            return $files;
        } else {
            return false;
        }
    }
 
    /**
    * Проверяет, удовлетворяет ли картинка фильтру.
    * 
    * @param string $val    Путь или псевдопуть к картинке.
    * @param json $params   Фильтр в формате JSON.
    * Возможные значения ключей:
    *   'width'     -   (int)   Точная ширина картинки.
    *   'height'    -   (int)   Точная высота картинки.
    *   'maxWidth'  -   (int)   Максимальная ширина картинки.
    *   'maxHeight' -   (int)   Максимальная высота картинки.
    *   'minWidth'  -   (int)   Минимальная ширина картинки.
    *   'minHeight' -   (int)   Минимальная высота картинки.
    *   'ratio'     -   (array) Пропорции. Первый элемент -(int) ширина, второй - (int) высота.
    *                           или
    *                   (string)Строка в формате (int)x(int).
    * @return bool
    */
    public static function checkByFilter($val, $params = array()){
        if (!empty($params)){
            $valids = 0;
            $size = self::getSize($val);
            foreach ($params as $key=>$value){
                switch ($key) {
                    case 'width':
                        $valids += ((int) $size['w'] === (int) $value)? 1 : 0;
                    break;
                    case 'height':
                        $valids += ((int) $size['h'] === (int) $value)? 1 : 0;
                    break;
                    case 'maxWidth':
                        $valids += ((int) $size['w'] <= (int) $value)? 1 : 0;
                    break;
                    case 'maxHeight':
                        $valids += ((int) $size['h'] <= (int) $value)? 1 : 0;
                    break;
                    case 'minWidth':
                        $valids += ((int) $size['w'] >= (int) $value)? 1 : 0;
                    break;
                    case 'minHeight':
                        $valids += ((int) $size['h'] >= (int) $value)? 1 : 0;
                    break;
                    case 'ratio':
                        if (is_array($value)) {
                            $ratio = (int) $value[0] / (int) $value[1];
                        } else if($crop = explode('x',$value)){
                            $ratio = (int) $crop[0] / (int) $crop[1];
                        } else {
                            $ratio = (float) $value;
                        }
                        $valids += ((float) $size['w']/$size['h'] === (float) $ratio)? 1 : 0;
                    break;
                    default :
                        $valids++;
                    break;
                }
            }
            return ($valids === count($params));
        } else {
            return true;
        }
    }
    
    /**
    * Вернет размеры картинки исходя из псевдопути, где 'w' - ширина, 'h' - высота. 
    * Например:
    *   Для картинки /media/catalog/w100-h100-c1x2/image.jpg (оригинальные размеры: ширина - 160, высота - 120) 
    *   вернет array( 'w' => '50', 'h' => '100' ).
    * 
    * @param string $val    Путь или псевдопуть к картинке.
    * @param json $params   Параметры. В текущей версии не используется.
    * @return mixed
    */
    public static function getSize($val, $params = array()){    
        $size = self::$temp->getSize($val);
        if (!empty($size)){
            return $size;
        } else {
            if (self::fileExists($val)){
                $result = array();  
                $urlParams = self::getParams($val);
                $originalSize = self::$temp->getSize($urlParams['i']);
                if (empty($originalSize)){
                    $originalSize = @getimagesize(self::getFullPath($urlParams['i']));
                    $originalSize = array('w'=>(int) $originalSize[0],'h'=>(int) $originalSize[1]);
                    self::$temp->setSize($urlParams['i'],$originalSize);
                }
                if (count($urlParams)>1){
                    $originalRatio = $originalSize['w']/$originalSize['h'];
                    $height = $originalSize['h'];
                    $width = $originalSize['w'];
                    $maxWidth = (isset($urlParams['w']) and $urlParams['w']<$originalSize['w'])? (int) $urlParams['w'] : $originalSize['w'];
                    $maxHeight = (isset($urlParams['h']) and $urlParams['h']<$originalSize['h'])? (int) $urlParams['h'] : $originalSize['h'];
                    $newRatio = (isset($urlParams['c']) and $cropParams = explode('x',$urlParams['c']))? $cropParams[0] / $cropParams[1] : $originalRatio;
                    if ($originalRatio < $newRatio){
                        $height = $originalSize['w'] / $newRatio;
                        $width = $originalSize['w'];
                    } else {
                        $width = $originalSize['h'] * $newRatio;
                        $height = $originalSize['h'];
                    }
                    $xRatio = $maxWidth / $width;
                    $yRatio = $maxHeight / $height;
                    $result = ($xRatio * $height < $maxHeight)? array('w'=>(int) $maxWidth,'h'=>(int) round($xRatio * $height)) : array('w'=>(int) round($yRatio * $width),'h'=>(int) $maxHeight) ;
                    self::$temp->setSize($val,$result);
                } else {
                    $result = $originalSize;
                }
                return $result;
            }
        }
    }
    
    /**
    * Вернет ширину картинки
    * 
    * @param string $val Путь или псевдопуть к картинке.
    * @param json $params Параметры. В текущей версии не используется.
    * @return int
    */
    public static function getWidth($val,$params = array()){
        $size = self::getSize($val);
        return $size['w'];
    }
    
    /**
    * Вернет высоту картинки
    * 
    * @param string $val Путь или псевдопуть к картинке.
    * @param json $params Параметры. В текущей версии не используется.
    * @return int
    */
    public static function getHeight($val,$params = array()){
        $size = self::getSize($val);
        return $size['h'];
    }

    /**
    * Трансформировать картинку. Синоним метода IMAGE::transform().
    * 
    * @param string $val Путь или псевдопуть к картинке
    * @param json $params Параметры в формате JSON. См. описание метода IMAGE::transform().
    * @return string
    */
    public static function image_transform($val,$params = array()){
        return self::transform($val, $params);
    }
    
    /**
    * Трансформировать картинку.
    * 
    * @param string $val Путь или псевдопуть к картинке.
    * @param mixed $params Параметры в формате JSON.
    * Возможные значения ключей:
    *   'w' или 'width'      -   (int)   Максимальная ширина картинки.
    *   'h' или 'height'     -   (int)   Максимальня высота картинки.
    *   'r' или 'ratio'      -   (array) Пропорции картинки. Первый элемент -(int) ширина, второй - (int) высота
    *                                    или
    *                            (string)Строка в формате (int)x(int).
    *   'q' или 'quality'    -   (int)   Качество нового изображения. Число от 10 до 100. По умолчанию - 80.
    *   'p' или 'progressive'-   (bool)  Прогрессив. 1 - да, 0 - нет.
    *   'b' или 'background' -   (string)Цвет фона для прозрачных PNG картинок. Формат: FF00A1.
    * @return string
    */
    public static function transform($val, $params = array()){
        if (!empty($params)){
            $urlParams = self::getParams($val);
            if (isset($urlParams['i']) and $urlParams['i']!==''){
                $newParams = array('i'=>$urlParams['i']);
                $r = isset($params['ratio'])? 'ratio' : 'r';
                if (isset($params[$r])) {
                    $params['c'] = is_array($params[$r])? $params[$r][0].'x'.$params[$r][1] : $params[$r];
                }
                unset($params[$r]);
                foreach ($params as $key=>&$value){ 
                    $k = substr($key,0,1);
                    $newParams[$k] = $value;
                    $value = $k.$value; 
                }
                $path = explode('/',$urlParams['i']);
                $path[count($path)-2] .= '/'.implode(self::$config['splitter'],$params);
                $url = implode('/',$path);
                self::$temp->setParams($url,$newParams);
                return $url;
            } else {
                return false;
            }
        } else {
            return $val;
        }
    }
}

?>