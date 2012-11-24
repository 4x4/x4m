<?php




function iptocountry($ip) {

   
    $numbers = preg_split( "/\./", $ip);   
    include("/media/ip_files/".$numbers[0].".php");
    $code=($numbers[0] * 16777216) + ($numbers[1] * 65536) + ($numbers[2] * 256) + ($numbers[3]);   
    foreach($ranges as $key => $value){
        if($key<=$code){
            if($ranges[$key][0]>=$code){$country=$ranges[$key][1];break;}
            }
    }
    if ($country==""){$country="unkown";}
    return $country;
}


echo $two_letter_country_code=iptocountry($_SERVER['REMOTE_ADDR']);

?>