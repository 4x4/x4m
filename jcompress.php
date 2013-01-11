<?php
require_once('inc/core/helpers.php');
require_once ("conf/init.php");




function IE()
{
  $agent=getenv("HTTP_USER_AGENT");
  if(strstr($agent,'MSIE')){return true;}
  else return false;
}



function getExtension($file)
    {
        $pos=strrpos($file, '.');

        if (!$pos)
            {
                return false;
            }

        $str=substr($file, $pos, strlen($file));
        return $str;
    }

    

    
function compress_file($file, $cmp = true, $level = 9)
    {
    
        if ($all=file_get_contents($file))
        {
            
        if ($cmp&&!IE())
            {
                return $d=gzencode($all, $level);
            }
        else
            {
                return $all;
            }
        }
    }


    function compress_mass_js($compress_scheme)
    {

        if ($pathes=explode(',',$compress_scheme))
            {
                $cjs='';
        
                foreach ($pathes as $path)
                    {               
                        if(file_exists($p=PATH_ . $path.'.js'))
                        {
                           $cjs.=file_get_contents(PATH_ . $path.'.js',false)."\r\n";
                        }
                    }
                    
                compress_js_output ($cjs);
            }
    }

    function compress_js_output($input, $fileflag = false, $level = 9)
        {
        if ($fileflag)
            {
            if (in_array(getExtension($input),array('.js')))
                {
                $all=compress_file(PATH_ . $input, xConfig::get('GLOBAL','output_js_compress'));
                }
            else
                {       
                $all=gzencode($all, $level);
                }
            }
        elseif(xConfig::get('GLOBAL','output_js_compress'))
            {
            
             $all=gzencode($input, $level);   
            
            }else{
                
                echo $input;
                return;
            }
        
      
        if (!IE()&& xConfig::get('GLOBAL','output_js_compress'))
            {
            if (@$_SERVER["HTTP_ACCEPT_ENCODING"] && FALSE !== strpos($_SERVER["HTTP_ACCEPT_ENCODING"], 'gzip'))
                {
                    header ('Content-Encoding: gzip');
                    header ('Content-Length: ' . strlen($all));
                }
            }    
        echo $all;
        }

if (isset($_GET['m'])){compress_mass_js($_GET['m']);}elseif($_GET['q']){compress_js_output($_GET['q'],true);}