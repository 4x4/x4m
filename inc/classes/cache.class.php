<?php
class Cache
    {
    var $status    = true; // True to switch on cache and false to switch it off   
    var $cacheFile = '';   // The content of the actual cached file
    var $timeOut   = 1000; // The time how long the cached file remains reusable   
    var $cache     = true; // Shows if chaching actual content is needed
    var $cacheDir='';

    function Cache($url, $cacheDir, $timeout, $status)
        {
            $this->cacheDir=$cacheDir;
            if(is_writable($this->cacheDir))
            {
                $this->cacheFile=$cacheDir . md5($url).'.xtx';
                $this->status   =$status;
                $this->timeout  =$timeout;
                $this->cacheDir  =$cacheDir;
            }else{
                
                $this->status=false;
            }
        }

     function setCacheFile($cf)
     {
         $this->cacheFile=$this->cacheDir.$cf;
     }   
    
     
     function getCache()
     {
            if ((file_exists($this->cacheFile)) && ((filemtime($this->cacheFile) + $this->timeOut) > time()))
                {
                    //Read file from the cache
                    return  implode(file($this->cacheFile),'');
                }
         
         
     }
     
     function setCache($content)
     {
               if($this->status)
               {

                    if ((!file_exists($this->cacheFile)) or ((filemtime($this->cacheFile) + $this->timeOut) < time()))
                    {
                        $handle=fopen($this->cacheFile, 'w');
                        fwrite($handle, $content);
                        fclose($handle);
                   }
               }
     }
     
    function fromCache()
        {
        if ($this->status)
            {
                return $this->getCache();
            }
        else
            {
            $this->cache=true;
            }
        }

        
    function toCache($content)
        {
        if ($this->status)
            {
            if ($this->cache)
                {
                    $this->setCache($content);
                }
            }
        }

        
        
    function cleanCache()
        {
        if ($handle=opendir($this->cacheDir))
            {
            while (false !== ($file=readdir($handle)))
                {
                if (is_file($this->cacheDir . $file))
                    unlink($this->cacheDir . $file);
                }
                closedir($handle);
            }
        }
    }
?>