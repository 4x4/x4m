<?php


/*        
    function resize_image( $file, $new_file, $width = 0, $height = 0, $quality=100, $proportional = false, $output = 'file', $delete_original = false, $use_linux_commands = false )
    {
        if ( $height <= 0 && $width <= 0 ) {
            return false;
        }
       
       $info = getimagesize($file);
        $image = '';

       
       $final_width = 0;
        $final_height = 0;
        list($width_old, $height_old) = $info;

        if ($proportional) {
            if ($width == 0) $factor = $height/$height_old;
            elseif ($height == 0) $factor = $width/$width_old;
            else $factor = min ( $width / $width_old, $height / $height_old);  

           
           $final_width = round ($width_old * $factor);
            $final_height = round ($height_old * $factor);

        }
        else {
           
        $final_width = ( $width <= 0 ) ? $width_old : $width;
            $final_height = ( $height <= 0 ) ? $height_old : $height;
        }

        switch ($info[2] ) {
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($file);
            break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($file);
            break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($file);
            break;
            default:
                return false;
        }
       
        $image_resized = imagecreatetruecolor( $final_width, $final_height );
               
        if ( ($info[2] == IMAGETYPE_GIF) || ($info[2] == IMAGETYPE_PNG) ) {
            $trnprt_indx = imagecolortransparent($image);
  
            // If we have a specific transparent color
            if ($trnprt_indx >= 0) {
  
                // Get the original image's transparent color's RGB values
                $trnprt_color    = imagecolorsforindex($image, $trnprt_indx);
  
                // Allocate the same color in the new image resource
                $trnprt_indx    = imagecolorallocate($image_resized, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
  
                // Completely fill the background of the new image with allocated color.
                imagefill($image_resized, 0, 0, $trnprt_indx);
  
                // Set the background color for new image to transparent
                imagecolortransparent($image_resized, $trnprt_indx);
  
           
            }
            // Always make a transparent background color for PNGs that don't have one allocated already
            elseif ($info[2] == IMAGETYPE_PNG) {
  
                // Turn off transparency blending (temporarily)
                imagealphablending($image_resized, false);
  
                // Create a new transparent color for image
                $color = imagecolorallocatealpha($image_resized, 0, 0, 0, 127);
  
                // Completely fill the background of the new image with allocated color.
                imagefill($image_resized, 0, 0, $color);
  
                // Restore transparency blending
                imagesavealpha($image_resized, true);
            }
        }

       
       imagecopyresampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
   
        if ( $delete_original ) {
            if ( $use_linux_commands )
                exec('rm '.$file);
            else
                @unlink($file);
        }
       
        switch ( strtolower($output) ) {
            case 'browser':
                $mime = image_type_to_mime_type($info[2]);
                header("Content-type: $mime");
                $output = NULL;
            break;
            case 'file':
                $output = $new_file;
            break;
            case 'return':
                return $image_resized;
            break;
            default:
            break;
        }

        switch ($info[2] ) {
            case IMAGETYPE_GIF:
                imagegif($image_resized, $output);
            break;
            case IMAGETYPE_JPEG:
                imagejpeg($image_resized, $output,$quality);
            break;
            case IMAGETYPE_PNG:
                imagepng($image_resized, $output);
            break;
            default:
                return false;
        }

        return true;
    }
   
    function check_chmod($params){
        $rigts = fileperms($params['fname']);
        $this->result['ok'] = 'x';
    }
    
    function getPhotos($params){
        global $_CONFIG;
        $per_page = $_CONFIG['gallery']['photos_per_page'] ? $_CONFIG['gallery']['photos_per_page'] : 200;
        $photoz = $this->_tree->GetChildsParam($params['group'],'%');
        // нужен нормальный фильтр!! пока такой
        //$photos2 = $this->_tree->getChildsStructs($params['group'], array('_PHOTO'));
        $photos = array();
        foreach( $photoz as $key => $val) {
            if ($val["image"]) $photos[$key]=$val;
        }
        
        
        $this->result['photos'] = $photos;
        $this->result['per_page'] = $per_page;
        $this->result['count'] = sizeof($photos);
    }
   */ 

    /*
    function resizeimg($params)
        {
        global $_PATH, $_WEBPATH;
        
        $cat=$this->_tree->getNodeInfo($params['id']);
 
        $arr  =explode('/', $params['img_big']);
        $fname=$arr[sizeof($arr) - 1];
        array_pop($arr);
        array_shift($arr);
        $file = PATH_.implode('/', $arr).'/'.$fname;
        array_push($arr, 'thumb');

        if (!is_dir($thumbdir=PATH_ . implode('/', $arr)))
            {
            mkdir($thumbdir);
            chmod($thumbdir, 0777);
            }

        array_push($arr, $fname);
        $width=$cat['params']['thumb_width'];
        $height = $cat['params']['thumb_height'];
        
        $quality = $cat['params']['compress'];
        $new_file=PATH_ . implode('/', $arr);      
        if ($this->resize_image($file,$new_file,$width,$height,$quality,true))
            {
            $this->result['resized']  =true;
            //array_shift($arr);
            $this->result['img_small']='/'.implode($arr, '/');
            }
        else
            {
            $this->result['resized']=false;
            }
        }

    function gallery_resize($params)
        {
        
            $this->result['completed']=true;
        
        }

    function resize_by_id($id,$cat)
        {
        global $_PATH, $_WEBPATH;
        $img  =$this->_tree->getNodeInfo($id);

        $width  =   $cat['params']['thumb_width'];
        $height =   $cat['params']['thumb_height'];
        
        $quality=$cat['params']['compress'];
        $array             =explode('/', $img['params']['img_big']);
        
        $fname             =$array[sizeof($array) - 1];
        array_pop($array);
        array_shift($array);
        array_push($array, 'thumb');
        array_push($array, $fname);
        $file     = PATH_ . implode('/', $array);
        $oldfile = $_SERVER['DOCUMENT_ROOT'] . $img['params']['img_small'];
        $file       = $_SERVER['DOCUMENT_ROOT'] . $img['params']['img_big'];
        $new_file   = $_SERVER['DOCUMENT_ROOT'] .'/'. implode('/',$array) ;

        if ($this->resize_image($file,$new_file,$width,$height,$quality,true))
            {
            $this->result['resized']=true;

            if ($new_file != $oldfile)
                {
                $r                         =@unlink($oldfile);
                $idi                       =$img['id'];
                $img['params']['img_small']=$img['params']['img_big'];
                $this->reinit_photo($img['id'], $img['basic'], $img['params']);
                }
            }
        else
            {
            $this->result['resized']=false;
            }

        return $this->result['resized'];
        }

        */
        
        
  /*
    function create_album($params, $category)
        {
        $files=XFILES::files_list('./media/gallery/' . $params['data']['folders'], 'files', Array
            (
            '.gif',
            '.jpeg',
            '.jpg'
            ),                    0,                                               true);

        foreach ($files as $file)
            {
            if (!preg_match('/[а-яА-Я ]/', $file, $arr))
                {
                $filesA[]=$file;
                }
            }

        $tpath='./media/gallery/' . $params['data']['folders'] . '/thumb';

        if (!is_dir($tpath))
            {
            mkdir($tpath);
            chmod($tpath, 0777);
            }

        foreach ($filesA as $file)
            {
            $data['LastModified'] = time();

            $f                    =explode('.', $file);
            array_pop($f);
            $f                =implode('', $f);
            $data['basic']    =$f;
            $data['img_big']  ='/media/gallery/' . $params['data']['folders'] . '/' . $file;
            $data['img_small']='/media/gallery/' . $params['data']['folders'] . '/thumb/' . $file;
            $data['category'] =$category;
            $data['info']     ='';
            $data['changed']  =filemtime(
                                   $_SERVER['DOCUMENT_ROOT'] . '/media/gallery/' . $params['data']['folders'] . '/'
                                       . $file);
            //'LastModified','basic','img_big','img_small','category','info','changed'
            //$data['']
            $id               =$this->_tree->InitTreeOBJ($category, $data['basic'], '_PHOTO', $data, true);
            $this->resize_by_id($id);
            }
        }
        

    function upload_album($params)
        {
        function microtime_float()
            {
            list($usec, $sec)=explode(" ", microtime());
            return ((float)$usec + (float)$sec);
            }

        session_start();
        unset($_SESSION['files'], $_SESSION['id']);
        $files=XFILES::files_list('./media/gallery/' . $params['folder'], 'files', Array
            (
            '.gif',
            '.jpeg',
            '.jpg',
            '.png',
            '.JPG'
            ),                    0,                                      true);

        foreach ($files as $file)
            {
            if (!preg_match('/[а-яА-Я ]/', $file, $arr))
                {
                $filesA[]=$file;

                if (!in_array($_SESSION['files'], $file))
                    {
                    $_SESSION['files'][]=$file;
                    }
                }
            }

        $tpath='./media/gallery/' . $params['folder'] . '/thumb';

         
        $a    =fileperms($tpath);

        if (!is_dir($tpath))
            {
            mkdir($tpath);
            chmod($tpath, 0777);
            }
        

        $start_time=microtime_float();

        if (sizeof($files) != 0)
            {
            foreach ($filesA as $file)
                {
                $cur_time = microtime_float();

                if (($delta=$cur_time - $start_time) < 20)
                    {
                    $data['LastModified']=time();
                    
                    $f                   =explode('.', $file);
                    array_pop($f);
                    $f                =implode('', $f);
                    $data['basic']    =$f;
                    $data['img_big']  ='/media/gallery/' . $params['folder'] . '/' . $file;
                    $data['img_small']='/media/gallery/' . $params['folder'] . '/thumb/' . $file;
                    $data['category'] =$params['category'];
                    $data['info']     ='';
                    $data['changed']  =filemtime($_SERVER['DOCUMENT_ROOT'] . '/media/gallery/' . $params['folder'] .'/'.$file);
                    $id =$this->_tree->InitTreeOBJ($params['category'], $data['basic'], '_PHOTO', $data, true);
                    
                    $data['id'] = $params['category'];
                    $this->resizeimg($data);
                    $_SESSION['id'][]=$id;
                    }
                }

            $this->result['progress']=ceil(sizeof($_SESSION['id']) * 100 / sizeof($_SESSION['files']));

            if ($this->result['progress'] >= 100)
                {
                unset($_SESSION['id']);
                }
            }
        else
            $this->result['progress']=100;
            $this->result['is_saved']=true;   
        }

        */           
   
  
?>
