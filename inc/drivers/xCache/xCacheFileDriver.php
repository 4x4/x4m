<?php

class xCacheFileDriver implements xCacheDriver
{
    public static function initDriver()
    {
        
    }
    
    public static function  serializedRead($module, $id, $timeout = null)
    {
        if ($data = xCacheFileDriver::read($module, $id, $timeout))
        {
            return unserialize($data);
        }
    }
    public static  function serializedWrite($data, $module, $id, $timeout = null)
    {
        xCacheFileDriver::write(serialize($data), $module, $id, $timeout);
    }
    
    public static function writeFile($data, $module, $fileName = '')
    {
    
        if (!$fileName)
        {
            $fileName = Common::GenerateHash();
        }
        if (!is_dir(xConfig::get('PATH', 'CACHE') . '/' . $module))
        {
            mkdir(xConfig::get('PATH', 'CACHE') . '/' . $module, 0777);
        }
        if (file_put_contents(xConfig::get('PATH', 'CACHE') . '/' . $module . '/' . $fileName, $data))
        {
            return $fileName;
        }
    }
    
    public  static function write($data, $module, $id, $timeout = null)
    {
        clearstatcache();
        if ($timeout === null)
        {
            $timeout = xConfig::get('GLOBAL', 'cacheTimeout');
        }
        $file_exists = file_exists($file = xConfig::get('PATH', 'CACHE') . '/' . $module . '/' . md5($module . $id));
        if ($timeout === false)
        {
            $timer = true;
        }
        elseif ($file_exists)
        {
            $timer = (filemtime($file) + $timeout) > time();
        }
        else
        {
            $timer = $timeout;
        }
       
        if ((!$file) || ($timer))
        {
            xCacheFileDriver::writeFile($data, $module, md5($module . $id));
        }
    }
    
    public static  function read($module, $id, $timeout = null)
    {
        clearstatcache();
        if ($timeout === null)
        {
            $timeout = xConfig::get('GLOBAL', 'cacheTimeout');
        }
        $file_exists = file_exists($file = xConfig::get('PATH', 'CACHE') . '/' . $module . '/' . md5($module . $id));
        if ($timeout === false)
        {
            $timer = true;
        }
        elseif ($file_exists)
        {
            $timer = (filemtime($file) + $timeout) > time();
        }
        if (($file) && ($timer))
        {
            return xCacheFileDriver::readFile($module, md5($module . $id));
        
        }elseif(!$timer&&$file)
        {
            xCacheFileDriver::clear($module, $id);    
        }
    }
    
    public static function clearBranch($modules)
    {
        global $_PATH;
        if (is_array($modules))
        {
            foreach ($modules as $dir)
            {
                XFILES::unlink_recursive(xConfig::get('PATH', 'CACHE') . $dir, 0);
            }
        }
    }
    
    public static  function clear($module, $id)
    {                                  
        global $_PATH;
        if(file_exists($file=xConfig::get('PATH', 'CACHE') . '/' . $module . '/' . md5($module . $id)))
        unlink($file);
    }
    
    public  static function readFile($module, $file)
    {
        global $_PATH;
        if(file_exists($file=xConfig::get('PATH', 'CACHE') . '/' . $module . '/' . $file))
        {
            if ($res = file_get_contents($file))
            {
                return $res;
            }
        }
    }
}
?>