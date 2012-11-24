<?php

function iptocountry($ip) {

   
    $numbers = preg_split( "/\./", $ip);   
    include("media/ip_files/".$numbers[0].".php");
    $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);   
    foreach($ranges as $key => $value){
        if($key<=$code){
            if($ranges[$key][0]>=$code){$country=$ranges[$key][1];break;}
            }
    }
    if ($country==""){$country="unkown";}
    return $country;
}

function move301Permanent($link)
        {
            
            
            Header( "HTTP/1.1 301 Moved Permanently" );                                       
            Header( "Location:".$link);
            die();
        }
        

 $two_letter_country_code=iptocountry($_SERVER['REMOTE_ADDR']);
     
 if($two_letter_country_code=='BY' OR $two_letter_country_code=='RU')
 {
     
         move301Permanent('http://kuhniitalii.by');
 }else{
     
         move301Permanent('http://scavolini.by/404');
 }
 

?>