<?php
require_once 'slir.class.php';  
require_once 'core/image.php';  

class SLIRConfig extends SLIRConfigDefaults {
    public static $cacheDir = '/cache/images';
    
    public static $enableErrorImages  = false;
    
    public static function init(){
        parent::init();
        self::$enableRequestCache = (bool) IMAGE::$config['cache'];
        self::$defaultImagePath = (string) IMAGE::$config['defaultImage'];
        self::$defaultQuality = (int) IMAGE::$config['defaultQuality'];
        self::$browserCacheTTL = (int) IMAGE::$config['browserCacheLifetime'];
        self::$garbageCollectFileCacheMaxLifetime = (int) IMAGE::$config['serverCacheLifetime'];
        self::$cacheDir = (string) IMAGE::$config['cacheDir'];
        self::$pathToCacheDir = (string) IMAGE::$config['pathToCacheDir'];
        if (!is_dir(self::$pathToCacheDir)) {
            $cachePath = explode('/',self::$cacheDir);
            $count = count($cachePath);
            $currentPath = IMAGE::$config['documentRoot'];
            for ($i=0; $i<$count; $i++) {
                if ($cachePath[$i]!==''){
                    $currentPath = $currentPath.'/'.$cachePath[$i];
                    if (!is_dir($currentPath)) mkdir($currentPath);
                }
            }
            chmod(self::$pathToCacheDir, 0777);
        }
    }
    
}

$IMAGE = new IMAGE();   
SLIRConfig::init();

/*
* Надстройка над SLIRRequest
*/
class xSLIRRequest extends SLIRRequest {
    
    public $params = array();
                
    public function getParameters() {      
    if (!$this->isUsingQueryString()) {
      // версия mod_rewrite 
      return $this->params = $this->getParametersFromURL();
    } else {
      // обычный GET
      return $this->params = $_GET;
    }
    }
    
    public function getParametersFromURL($request = ''){ 
        return IMAGE::getParamsFromURL((!$request)? $_SERVER['REQUEST_URI'] : $request);
    }
    
}
class xSLIR extends SLIR {
    public $request;
    
    // Возвращает имя файла для кешируемой картинки
    public function requestCacheFilename($params = null){ 
        return '/'.IMAGE::getCacheFilename(($params)? $params : $this->request->params);
    }
    
    // Функция кеширования
    public function cache(){    
        if (SLIRConfig::$enableRequestCache === true && !$this->request->isUsingDefaultImagePath()) {
            return $this->cacheRequest($this->getRendered()->getData(), true);
        } else {
            return true;
        }
    }
    
    public function getRequestCacheDir(){
        return SLIRConfig::$pathToCacheDir;
    }
}
