<?php
class price_module_front
{
    var $_module_name;
    var $_tree;
    var $_common_obj;
    var $menu_ancestor;

    function price_module_front()
    {
        $this->_module_name = 'price';
        $this->_common_obj =& price_module_common::getInstance(true);
        $this->_tree =& $this->_common_obj->obj_tree;
        $this->context = null;
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

     
     function request_action_set($action)
     {
        $this->_common_obj->request_action_set($action);
     }
     
     
     function show_price_list($params)
     {
         global $TMS;
         
         $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
         return $this->price_objects_render($params['category'], 0, $params["hashed_links"], $params["show_counters"], (int) $params["max_level"]);
         
     
     }
     
     function price_objects_render($cat_id, $level = 0, $hashed_links = 0, $show_counters = 0, $max_level = 0)   {
        global $TMS,$_PATH, $TPA;
        
        $buff = '';
        $category = $this->_tree->getNodeInfo($cat_id);
        $items = $this->_tree->GetChildsParam($cat_id, array('file_name','basic','LastModified', 'description', 'image', 'hashed_link', 'hash' ,'hidden', 'DisableAccess', 'counter'), true);
        (Common::is_module_exists("fusers")) ? $fusers_exists = true : $fusers_exists = false;
        $session = ENHANCE::get_session('');
        $fusers = false;

        if ($category["params"]["DisableAccess"] && $fusers_exists) {
           
           Common::call_common_instance('fusers');
           $fusers=fusers_module_common::getInstance();       
           $found= false;
           if ($session["siteuser"]["usergroup"])
                $found = $fusers->get_node_rights($cat_id, $this->_module_name, $session["siteuser"]["usergroup"]);
                
           if (!$found) 
                return $TMS->parseSection('_doc_list_no_access');
        }
        
        $TMS->AddReplace('_doc_list_level_'.$level, 'cat_name', $category["basic"]);
        
        
        if ($items != false)
        {
            
            foreach ($items as $item)
            {
                
                if ($item["params"]["hidden"]) continue;
                
                
                if ($item["obj_type"] == '_PRICEGROUP') {
                    if ( $level < ($max_level-1) || !$max_level ) {
                        if ($hashed_links || $category["params"]["hashed_link"]) $buff .= $this->price_objects_render($item['id'], $level+1, 1, $show_counters);
                        else $buff .= $this->price_objects_render($item['id'], $level+1, 0, $show_counters);
                    }
                    continue;
                }
                
                if(! file_exists($f=PATH_ . $item['params']['file_name'])) {
                     $buff .= $TMS->parseSection('_file_not_found');
                     continue;    
                }
                
                if ($item['params']['DisableAccess'] && $fusers_exists ) {
                    if (!$fusers) {
                       Common::call_common_instance('fusers');
                       $fusers=fusers_module_common::getInstance();       
                    }
                   $found= false;
                   if ($session["siteuser"]["usergroup"])
                        $found = $fusers->get_node_rights($item['id'], $this->_module_name, $session["siteuser"]["usergroup"]);
                        
                   if (!$found) $buff .= $TMS->parseSection('_file_no_access');
                }
                
                $stat=stat($f);                
                if ($hashed_links || $item["params"]["hashed_link"] || $category["params"]["hashed_link"]) {
                    $alink = $TPA->page_link . '/~download/link/'.$item["params"]["hash"];
                    ($item['params']['counter']) ? $acounter = $item['params']['counter'] : $acounter = 0;
                }
                else {
                    $alink = $_PATH['WEBPATH_MEDIA'] . $item['params']['file_name'];
                    $acounter = '';
                }
                $TMS->AddMassReplace('_doc_list_item_level_'.$level, array(
                    "link" => $alink,
                    "counter" => $acounter,
                    "type" => pathinfo($item['params']['file_name'], PATHINFO_EXTENSION),
                    "caption" => $item['params']['basic'],
                    "image" => $a['image'] = $item['params']['image'],
                    "description" => $item['params']['description'],
                    "time" => date('d.m.Y', $item['params']['LastModified']),
                    "show_counters" => $show_counters,
                    "size" => XFILES::format_size($stat['size'])
                    
                ));
                $buff .= $TMS->parseSection('_doc_list_item_level_'.$level);
            }
        }
        $TMS->AddReplace("_doc_list_level_".$level, "buff", $buff);
        $TMS->AddReplace("_doc_list_level_".$level, "show_counters", $show_counters);
        $TMS->AddMassReplace('_doc_list_level_'.$level, $category['params']);
        $menu = $TMS->parseSection('_doc_list_level_'.$level);
        
        return $menu;
    }
       
       
       
    function show_folder($params) {
        global $TMS, $TPA;
        
        $params["Folder"]=substr($params["Folder"],1);
        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
        $TMS->AddReplace('_doc_list', 'folder', $params["Folder"]);
        
        $filesnames = XFILES::files_list($params["Folder"], 'files', $extensions, 0, true);
        $filecount = count($filesnames);
        $base = $TPA->page_link;
        $files = array();
        for ($i = 0; $i < $filecount; $i++) {
            $file = $params["Folder"].$filesnames[$i];
            $path_parts = pathinfo($file);
            $files[] = array(   "path" => $base.'/~download/link/'.md5(strrev("/".$file)), "name"=> $path_parts["filename"], "ext" => $path_parts["extension"], "modified" =>  Enhance::date_format(filemtime($file), array('m.d.y')), "size" => XFiles::format_size( filesize($file),2));
        }

        for ($i = 0; $i < $filecount; $i++) {        
            $TMS->AddMassReplace("_doc_list_item", $files[$i]);
            $TMS->parseSection("_doc_list_item",true);
        }
        
        
        return $TMS->parseSection('_doc_list');
       

    }
    
    function download($params) {
        global $TMS, $TPA, $REQUEST_ASSOC, $HTTP_SERVER_VARS; 
        $link = isset($REQUEST_ASSOC['link']) ? $REQUEST_ASSOC['link'] : 0;
        switch ($params["Action"]) {
            case 'show_folder' :
                $filesnames = XFILES::files_list(substr($params["Folder"],1), 'files', $extensions, 0, true);
                $pos = -1;
                
                for ($i = 0; $i<count($filesnames); $i++) {
                    if ( $link == md5(strrev($params["Folder"].$filesnames[$i])) ) {
                        $pos = $i;
                        break;
                    }
                }
                
                if ($pos != -1) {
                    $file = substr($params["Folder"],1) . $filesnames[$pos];
                    if (!$this->send_file($file)) {
                        $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
                        return $TMS->parseSection('_file_not_found');
                    }
                }                
                break;
            default :
                $found = $this->hashfile_search($params['category'], $link);
                 if ($found === false) {
                     //$found = $this->hashfile_recursive_search($params['category'], $link);  
                     /* медленный вариант поиска, если hash не был вычислен при добавлении (для совместимости со старыми версиями модуля), перебирает всех элементы и проверяет по имени файла */
                     //if ($found === false) 
                     $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
                     return $TMS->parseSection('_file_not_found');
                 }
                 
                 if (!$this->send_file($found)) {
                     $TMS->AddFileSection(Common::get_fmodule_tpl_path($this->_module_name, $params['Template']));
                     return $TMS->parseSection('_file_not_found');
                 }
                break;
        }
                
    }
    
    
    /* // рекурсивный поиск. устаревший вариант */
    function hashfile_recursive_search($cat_id, $link) {
        $category = $this->_tree->getNodeInfo($cat_id);
        $items = $this->_tree->GetChildsParam($cat_id, array('file_name', 'hashed_link', 'counter'), true);
                foreach ($items as $item) {
                    if ($item["obj_type"] == "_PRICEGROUP") {
                        $found = $this->hashfile_recursive_search($item["id"], $link);
                        if ($found === false) continue;
                        else return $found;                        
                    }
                    //if  ( !$item["params"]["hashed_link"] && !$params["hashed_links"] ) continue;
                    if ( md5(strrev($item["params"]["file_name"])) == $link ) { return $item; }
                    
                }
        return false;
    }
    /* _________________________________________*/
    
    function hashfile_search($cat_id, $link) {
        $tmp = $this->_tree->Search(array("hash" => $link));
        if (is_array($tmp)) return $this->_tree->getNodeInfo($tmp[0]);
        else return false;
    }
    
    function send_file($price) {
        global $HTTP_SERVER_VARS;
        //DebugBreak();
        $price["params"]["counter"]++;
        $file = substr($price["params"]["file_name"],1);
        
        $this->_tree->WriteNodeParam($price["id"],"counter", $price["params"]["counter"]);
        
        if (!strlen($file)) return;
        
                    $file_info = pathinfo($file);
                    $fsize = filesize($file);
                    $ftime = date("D, d M Y H:i:S T", filemtime($file));            
                    $fd = @fopen($file, "rb");
                    if(!$fd)  return false;
                    if ($HTTP_SERVER_VARS["HTTP_RANGE"]) {
                        $range = $HTTP_SERVER_VARS["HTTP_RANGE"];
                        $range = str_replace("bytes=", "", $range);
                        $range = str_replace("-", "", $range);
                        if($range){fseek($fd, $range);}
                    }
                    
                    $aFile = fread($fd, filesize($file));
                    fclose($fd);
                    
                    if($range){header("HTTP/1.1 206 Partial Content");}
                        else {header("HTTP/1.1 200 OK");}            
                        
                        header("Content-Disposition: attachment; filename=" . $file_info['basename']);
                        header("Last-Modified: $ftime");
                        header("Accept-Ranges: bytes");
                        header("Content-Length: " . ($fsize-$range));
                        header("Content-Range: bytes $range-" . ($fsize -1) ."/". $fsize);
                        header("Content-type: application/octet-stream");
                        print $aFile;
                        exit;        
    }

}
?>