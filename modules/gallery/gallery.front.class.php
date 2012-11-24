<?php

class gallery_module_front
    extends gallery_module_tpl
    {

    var $_module_name;
    var $_tree;
    var $_common_obj;
    var $menu_ancestor;

    function gallery_module_front()
        {
        $this->_module_name='gallery';
        //true так как front_call
        $this->_common_obj =&gallery_module_common::getInstance(true);
        $this->_tree       =&$this->_common_obj->obj_tree;
        $this->context     =null;
        $this->_tree->enable_cache(true);
        parent::__construct();
        
        }

    function execute($action_data)
        {
        global $TMS;
        if (is_array($action_data))
            {
            if ($action=$this->_common_obj->is_action($action_data['Action']))
                {
                    $TMS->startLogging($action_data['Action']);
                    $q=&$this->$action($action_data);
                    $TMS->clearLogged($action_data['Action']);
                    return $q;

                }
            }
        }

    function request_action_set($action) { $this->_common_obj->request_action_set($action); }


    //Выводит список фотоальбомов
    function show_gallery_list($params)
        {
            global $TMS,$REQUEST_ASSOC, $_WEBPATH;
            Common::call_common_instance('pages');
            $pages      =&pages_module_common::getInstance();
            $destination=$pages->create_page_path($params['page']);
            $TMS->AddFileSection(Common::get_fmodule_tpl_path('gallery', $params['Template']));        
            
            $albums=$this->_tree->GetChilds($params["GalleryStartId"]);
            $album_info = $this->_tree->GetChildsParam($params["GalleryStartId"],array( 'Name', 'gallery_short','thumb_width','Avatar','isOuterLink', 'info', 'counter', 'basic'));
            
            if (!$albums) return $TMS->parseSection('gallery_empty');
            
            $sl = ($REQUEST_ASSOC['sl']) ? $REQUEST_ASSOC['sl'] : 0;
            Common::parse_nav_pages(count($albums), $params['count'], $sl,$destination.'/~show_gallery_list' );
            $i=0;
            
            foreach ($albums as $album) {
                $id=$album["id"];
                $a=$album_info[$id];
                $a['basic']=$album['basic'];
                ($album['basic']) ? $a['link'] = $destination.'/~show_album/'.$album['basic'] : $a['link'] = $destination.'/~show_album/'.$id;
                $a['inRow'] = (int)$params["Levels"];
                $a['_num']=$i++;
                $TMS->AddMassReplace('item', $a);
                $TMS->parseSection('item', true);
            }
            $TMS->AddMassReplace('galleries', $this->_tree->GetNodeParam($params["GalleryStartId"]));
            return $TMS->parseSection('galleries', true);
        
        }

    function show_album($params)
        {
        global $TMS, $HTTP_HOST, $REQUEST_VARS, $_WEBPATH, $_PATH, $TDB,$TPA;
        if ($REQUEST_VARS[1]) {
            
            $TDB->query("SELECT `id` FROM `_tree_gallery_container_struct` WHERE `basic`='".mysql_real_escape_string($REQUEST_VARS[1])."';");
            $r=$TDB->get_results();
            $GalleryStart=$r[1]['id'];
            
            /*
            ($params['GalleryStartId']) ? $anc = $this->_tree->GetAncestor($params['GalleryStartId']) : $anc=1;
            $nodes=$this->_tree->FindbyBasic($anc,$REQUEST_VARS[1]);
            $GalleryStart=$nodes[0];
            */
            
        } else {
            $GalleryStart=$params['GalleryStartId'];
            
        }
        if (!$GalleryStart) return;
        
        if ($REQUEST_VARS[2]==null){$sl = 0;}
        else $sl = $REQUEST_VARS[2];
        
        
        if (!$GalleryStart) return;
        $album = $this->_tree->GetNodeInfo($GalleryStart);
        $album["params"]["id"]=$album["id"];
        $album["params"]["_ARES"] = $_PATH['WEB_PATH_ARES'];
        $album["params"]["start"] = $sl+1;
        
        if($params['MTemplate'] ){$params['Template']=$params['MTemplate'];}   
        
        if (!$params["Levels"]) $params["Levels"]=$album["params"]["counter"];
        if ($params["rows"]) $params["max_rows"]=$params["rows"];
        if (!$params["max_rows"]) $params["max_rows"]=ceil($album["params"]["counter"]/$params["Levels"]);
        
        $onPage = intval($params["max_rows"]*$params["Levels"]);
        $total = intval($album["params"]["counter"]);

        
        $template=Common::get_fmodule_tpl_path('gallery', $params['Template']);
        $TMS->AddFileSection($template);
        if ($total>$onPage && $onPage != 0) {
            Common::parse_nav_pages($total, $onPage, $sl, $TPA->page_link . '/~show_album/'.$album["basic"],'','');
            $limit = " LIMIT ".$sl.",".$onPage;
        } else {
            $limit='';
        }

        
        $photos =$this->_tree->GetChildsParam($GalleryStart, array('LastModified','Name','image','category','info','changed','img_big', ), true,null,'ASC',$limit);
        if ($params['generate_branch']) {
            $this->bones_path['gallery_node']=array('params'=>array('Name' =>$album['params']['Name'], 'Basic'=>$album['params']['Basic']));
        } 
        $preview =  $TMS->isSectionDefined('preview');
        
        if ($photos) {
            $album["params"]["shown"]=count($photos);
            $album["params"]["end"]=$sl+count($photos)+1;
            $TMS->AddMassReplace('album',$album["params"]);
            $TMS->AddReplace('album', '_ARES', $_PATH['WEB_PATH_ARES']);
            if ($preview) { $TMS->AddMassReplace('preview',$album["params"]); }
            ($params["Width"]) ? $width= $params["Width"] : $width = $album["thumb_width"];
            
            $i=0;
            foreach ($photos as $photo) {
                
                
                $tmp=array(
                    "_num"=>$i,
                    "Name"=>$photo["params"]["Name"],
                    "_num_abs" => ($i+1+(int)$sl),
                    "id" => $photo["id"],
                    "img_src" => $photo["params"]["image"],
                    "width" => $width,
                    "inRow" => $params["Levels"],
                    "info" => $photo["params"]["info"],
					"album_id" => $album["id"]
                );
                $TMS->AddMassReplace('photo', $tmp);
                $TMS->parseSection('photo', true);
                if ($preview){
                    $TMS->AddMassReplace('preview_photo', $tmp);
                    $TMS->parseSection('preview_photo', true);
                }
                $i++;
            }
            if ($preview){$TMS->parseSection('preview', true);}
            
            $TMS->AddMassReplace('album', $tab);
            return $TMS->parseSection('album');
                
        } else {
                    
            $TMS->AddMassReplace('album',$album["params"]);
            $TMS->AddMassReplace('gallery_empty', $gallery);
            return $TMS->parseSection('gallery_empty');
        }
        
    }


        
    function show_selected_album($params)
        {
        if ($params)
            {
            //действие по умлочанию
            if ($params['GalleryStartId'])
                {
             return   $this->show_album($params);
                }
            }
        }
        
    function show_from_folder($params) {
        global $TMS, $HTTP_HOST, $REQUEST_VARS, $_WEBPATH;
        $TMS->AddFileSection(Common::get_fmodule_tpl_path('gallery', $params['Template']));
        $TMS->AddReplace('_show_from_folder', 'image_folder', $params["ImageFolder"]);
        $TMS->AddReplace('_show_from_folder', 'rows', $params["rows"]);
        return $TMS->parseSection('_show_from_folder');
    }        
        
        
    function show_gallery_server($parameters) {       
        global $REQUEST_ASSOC;
            
            
        if ($REQUEST_VARS[1]){
            $parameters['generate_branch']=1;
            return $this->show_selected_album($parameters);
        }
        else {
            $parameters['Action']=$parameters['Default_action'];
            $parameters['MTemplate']=$parameters['Template'];
            unset ($parameters['Default_action']);
            return $this->execute($parameters);
            
    
        }            
                  
    }
        
        
    }
?>


