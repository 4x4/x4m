<?php
require_once("conf/init.php");
require_once($_PATH['PATH_XOAD'].'classes/ConvertCharset.class.php');   
    function translit($text)
    {    global $_COMMON_SITE_CONF;
        
        $Encoding = new ConvertCharset("utf-8","windows-1251", $Ent); 
        $text=$Encoding->Convert($text);
        if(!$_COMMON_SITE_CONF['do_not_translit_in_file_manager'])
        {         
            return  $e= strtr($text,array("А"=>"A", "а"=>"a", "Б"=>"B", "б"=>"b", "В"=>"W", "в"=>"w", "Г"=>"G", "г"=>"g", "Д"=>"D", "д"=>"d", "Е"=>"E", "е"=>"e", "Ё"=>"Jo", "ё"=>"jo", "Ж"=>"J", "ж"=>"j", "З"=>"Z", "з"=>"z", "И"=>"I", "и"=>"i", "Й"=>"I", "й"=>"i", "К"=>"K", "к"=>"k", "Л"=>"L", "л"=>"l", "М"=>"M", "м"=>"m", "Н"=>"N", "н"=>"n", "О"=>"O", "о"=>"o", "П"=>"P", "п"=>"p", "Р"=>"R", "р"=>"r", "С"=>"S", "с"=>"s", "Т"=>"T", "т"=>"t", "У"=>"U", "у"=>"u", "Ф"=>"F", "ф"=>"f", "Х"=>"h", "х"=>"h", "Ц"=>"Ch", "ц"=>"ch", "Ч"=>"Tsch", "ч"=>"tsch", "Ш"=>"Sch", "ш"=>"sch", "Щ"=>"Sch", "щ"=>"sch", "Э"=>"E", "э"=>"e", "Ю"=>"Yu", "ю"=>"uy", "Я"=>"Ya", "я"=>"ya", "Ь"=>"", "ь"=>"", "Ъ"=>"", "ъ"=>"", "Ы"=>"I", "ы"=>"i", " "=>"_"));
        }else{
            return $text;
        }

    }


if($_POST['PHPSESSID'])
{	
	if (!isset($_FILES["Filedata"]) || !is_uploaded_file($_FILES["Filedata"]["tmp_name"]) || $_FILES["Filedata"]["error"] != 0) {
		exit(0);
	} else{    
           
        $fileName=translit($_FILES["Filedata"]["name"]);
        $ext = substr($fileName, strrpos($fileName, '.') + 1);
        
                copy($_FILES["Filedata"]["tmp_name"], $_PATH['PATH_MEDIA'].$_POST['path'].'/'.$fileName);
      
        }
        return  true;
    }
}
	
?>