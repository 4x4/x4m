<?php
class xCacheMemCacheDriver implements xCacheDriver
{
     public static $memcache;
     
     public static function initDriver()
    {
        self::$memcache = new Memcache;
        self::$memcache->connect('localhost', 11211) or die ("Could not connect");
    }

    
    public static function  serializedRead($module, $id, $timeout = null)
    {
         if ($data = self::read($module, $id, $timeout))
        {
            return unserialize($data);
        }
        
    }
    public static  function serializedWrite($data, $module, $id, $timeout = null)
    {
            self::write(serialize($data), $module, $id, $timeout);
    }
    

    
    public  static function write($data, $module, $id, $timeout = null)
    {
        
            if ($timeout === null)
            {
                $timeout = xConfig::get('GLOBAL', 'cacheTimeout');
            }
            
             self::$memcache->set($module.md5($id), $data,0,time()+$timeout);
    }
    
    public static  function read($module, $id, $timeout = null)
    {
        //         self::getKeys();
        
            return self::$memcache->get($module.md5($id));
    }
    
    public static function clearBranch($modules)
    {
       self::$memcache->flush();
    }
    
    public static function getKeys()
    {
        
            $allSlabs = self::$memcache->getExtendedStats('slabs');  
            $items =self::$memcache->getExtendedStats('items');  
              
            foreach ($allSlabs as $server => $slabs) {  
                foreach ($slabs as $slabId => $slabMeta) {  
                    if (!is_numeric($slabId)) {  
                        continue;  
                    }  
                  
                    $cdump = self::$memcache->getExtendedStats('cachedump', (int)$slabId, $limit);  
                      
                    foreach ($cdump as $server => $entries) {  
                        if (!$entries) {  
                            continue;  
                        }  
                          
                        foreach($entries as $eName => $eData) {  
                            $list[$eName] = array(  
                                'key' => $eName,  
                                'slabId' => $slabId,  
                                'size' => $eData[0],  
                                'age' => $eData[1]  
                            );  
                        }  
                    }  
                }  
            }  
              
            ksort($list);  
        
    }
    
    
    public static  function clear($module, $id)
    {                                  
      self::$memcache->flush();
    }
   
}
?>
