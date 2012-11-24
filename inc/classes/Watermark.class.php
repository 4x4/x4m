<?php

class watermark {
	
	var $positionX;
	var $positionY;
    var $coeff;
    var $watermark_img_obj;
	
	function __construct($xpos, $ypos, $coeff){
		if(is_string($xpos) && is_string($ypos)){
			$this->positionX = $xpos;
			$this->positionY = $ypos;
            $this->coeff = (int) ($coeff) ? $coeff : 1;
		}
	}
    
    function create_watermark2($main_img_obj, $watermark_image){
        
        $main_img_obj_w = imagesx($main_img_obj);
        $main_img_obj_h = imagesy($main_img_obj);
        $Wsizes = getimagesize($watermark_image);
        $watermark_img_obj_w = $Wsizes[0];
        $watermark_img_obj_h = $Wsizes[1];
        
            if($watermark_img_obj_w > ($main_img_obj_w / $this->coeff) || $watermark_img_obj_h > ($main_img_obj_h / $this->coeff)){
                $msizes = array('width'=>$main_img_obj_w, 'height'=>$main_img_obj_h);
                watermark::resize_watermark($watermark_image, $msizes, $watermark_img_obj_w, $watermark_img_obj_h);
            } else {
                $this->watermark_img_obj = imagecreatefrompng($watermark_image);
            }
            
        $main_img_obj_min_x = watermark::xposition($main_img_obj_w, $watermark_img_obj_w, $this->positionX);
        $main_img_obj_min_y = watermark::yposition($main_img_obj_h, $watermark_img_obj_h, $this->positionY);
        
        $return_img = @imagecreatetruecolor($main_img_obj_w, $main_img_obj_h);
        imagesavealpha($return_img, true);
        $trans_colour = imagecolorallocatealpha($return_img, 0, 0, 0, 127);
        imagefill($return_img, 0, 0, $trans_colour);  
        imagecopyresampled($return_img, $main_img_obj, 0, 0, 0, 0, $main_img_obj_w, $main_img_obj_h, $main_img_obj_w, $main_img_obj_h);
        imagecopy($return_img, $this->watermark_img_obj, $main_img_obj_min_x, $main_img_obj_min_y, 0, 0, $watermark_img_obj_w, $watermark_img_obj_h);
            
            return $return_img;
    }
    

    function create_watermark($main_img_obj, $watermark_image, $alpha_level = 100){
        $alpha_level /= (int) $alpha_level;

        $main_img_obj_w = imagesx($main_img_obj);
        $main_img_obj_h = imagesy($main_img_obj);
        $Wsizes = getimagesize($watermark_image);
        $watermark_img_obj_w = $Wsizes[0];
        $watermark_img_obj_h = $Wsizes[1];
        
            if($watermark_img_obj_w > ($main_img_obj_w / $this->coeff) || $watermark_img_obj_h > ($main_img_obj_h / $this->coeff)){
                $msizes = array('width'=>$main_img_obj_w, 'height'=>$main_img_obj_h);
                watermark::resize_watermark($watermark_image, $msizes, $watermark_img_obj_w, $watermark_img_obj_h);
            } else {
                $this->watermark_img_obj = imagecreatefrompng($watermark_image);
            }
 
        $main_img_obj_min_x = watermark::xposition($main_img_obj_w, $watermark_img_obj_w, $this->positionX);
        $main_img_obj_min_y = watermark::yposition($main_img_obj_h, $watermark_img_obj_h, $this->positionY);

        $return_img = @imagecreatetruecolor($main_img_obj_w, $main_img_obj_h);
 
           for($y = 0; $y < $main_img_obj_h; $y++){
             for ($x = 0; $x < $main_img_obj_w; $x++){
                $return_color = NULL;
                $watermark_x = $x - $main_img_obj_min_x;
                $watermark_y = $y - $main_img_obj_min_y;
                $main_rgb = imagecolorsforindex($main_img_obj, imagecolorat($main_img_obj, $x, $y));
                if ($watermark_x >= 0 && $watermark_x < $watermark_img_obj_w && $watermark_y >= 0 && $watermark_y < $watermark_img_obj_h ){
                      $watermark_rbg = imagecolorsforindex( $this->watermark_img_obj, imagecolorat( $this->watermark_img_obj, $watermark_x, $watermark_y ) );
                      $watermark_alpha = round(((127 - $watermark_rbg['alpha']) / 127), 2);
                      $watermark_alpha = $watermark_alpha * $alpha_level;
                      $avg_red = $this->_get_ave_color($main_rgb['red'], $watermark_rbg['red'], $watermark_alpha);
                      $avg_green = $this->_get_ave_color($main_rgb['green'], $watermark_rbg['green'], $watermark_alpha);
                      $avg_blue = $this->_get_ave_color($main_rgb['blue'], $watermark_rbg['blue'], $watermark_alpha);
                      $return_color = $this->_get_image_color($return_img, $avg_red, $avg_green, $avg_blue);
                } else {
                      $rbg = imagecolorsforindex($main_img_obj, imagecolorat($main_img_obj, $x, $y));
                      $return_color = $this->_get_image_color($return_img, $rbg['red'], $rbg['green'], $rbg['blue']);
                }
             imagesetpixel($return_img, $x, $y, $return_color);
             }
        }

        return $return_img;
    }
 
    function _get_ave_color($color_a, $color_b, $alpha_level){
        return round((($color_a * (1 - $alpha_level)) + ($color_b * $alpha_level)));
    }
 
    function _get_image_color($im, $r, $g, $b){
        $color = imagecolorexact($im, $r, $g, $b);
        if ($color != -1) return $color;
        $color = imagecolorallocate($im, $r, $g, $b);
        if ($color != -1) return $color;
        return imagecolorclosest($im, $r, $g, $b);
    }
    
    function xposition($wA, $wB, $position = 'right'){
    	if($wA < $wB) return false;
    		if('left' == $position){ (int) $x = 10; }
            elseif('center' == $position){ (int) $x = floor(($wA / 2) - ($wB / 2)); }
    		else { (int) $x = floor($wA - ($wB + 10)); }
    	return $x;
    }
    
    function yposition($hA, $hB, $position = 'bottom'){
    	if($hA < $hB) return false;
    		if('top' == $position){ (int) $y = 10; }
            elseif('center' == $position){ (int) $y =  floor(($hA / 2) - ($hB / 2));}
    		else { (int) $y = floor($hA - ($hB + 10)); }
    	return $y;
    }
    
    function resize_watermark($watermark_image, $msizes = array(), &$watermark_img_obj_w, &$watermark_img_obj_h){
        $orig_width = $watermark_img_obj_w;
        $orig_height = $watermark_img_obj_h;
        $msizes['width'] = ceil($msizes['width'] / $this->coeff);
        $msizes['height'] = ceil($msizes['height'] / $this->coeff);
        $ratio = $msizes['width']/$msizes['height'];
        $sratio = $watermark_img_obj_w/$watermark_img_obj_h;
            if ($ratio<$sratio){
                $watermark_img_obj_h = ceil($msizes['width']/$sratio);
                $watermark_img_obj_w = $msizes['width'];
            } else {
                $watermark_img_obj_w = ceil($msizes['height']*$sratio);
                $watermark_img_obj_h = $msizes['height'];
            }
        $watermark_img_obj = @imagecreatefrompng($watermark_image);
        $this->watermark_img_obj = @imagecreatetruecolor($watermark_img_obj_w, $watermark_img_obj_h) or die("Cannot Initialize new GD image stream");
        imagealphablending($this->watermark_img_obj, false);
        imagesavealpha($this->watermark_img_obj, true);
        imagecopyresampled($this->watermark_img_obj, $watermark_img_obj, 0, 0, 0, 0, $watermark_img_obj_w, $watermark_img_obj_h, $orig_width, $orig_height);
        imagedestroy($watermark_img_obj);
    }
 
}

?>