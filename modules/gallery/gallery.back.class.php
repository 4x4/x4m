<?php
class gallery_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function gallery_module_back()
        {
        $this->_module_name='gallery';
        }

    function common_call($front_call = null)
        {
        $this->_module_name='gallery';
        $this->_common_obj =&gallery_module_common::getInstance();
        $this->_tree       =&$this->_common_obj->obj_tree;
        }

    function execute($action, $parameters = null) {
        $this->common_call();
        return $this->_common_obj->execute(&$this, $action, $parameters);
    }
             
             
    function executex($action,$acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }

    function actions($action, $parameters)
        {
     
        switch ((string)$action)
            {
            default: return false; break;    
            case 'parse_gallery_tpl':
                $this->parse_gallery_tpl($parameters['tpl'], true);
                break;       
            }
        }

     
     
    function check_uniq($parameters) {
        $anc = $this->_tree->GetAncestor($parameters['album']);
        if ($this->_tree->FindbyBasic(1, $parameters['basic'])) {
            $this->result['uniq']=false;
        } else {
            $this->result['uniq']=true;
        }
    }     
     
    function album_table($params){
        global $_CONFIG;
        
        $TD =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$params['id'];

        $options['shownodesWithObjType']=array('_PHOTO');

        $options['columnsAsParameters']=array( 'Name' => 'Name', 'info' => 'info', 'image' => 'image' );

        $options['callfunc']=array('image'=>array($this,'image_convert')); 

        $options['preventDots']=true;

        $options['columnsAsStructs']=array('id' => 'id');

        $options['gridFormat']=1;
        
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($params['id']);

        $this->result=$TD->result;
        
        

    }
     
     function add_photos($params){
         if (!$params["album"]) {
             $this->result["saved"]=false;
             return;
         }
        $time=time();
        foreach ($params["files"] as $file) {
            $pos = strrpos($file,'/');
            if ($pos) $pos++;
            $name = substr($file, $pos);
            $ext=array(
                "image" => $file,
                "Name" => $name,
                "basic" => $name,
                "LastModified" => $time
            );
            $this->init_photo($params["album"], $name, $ext);
                
        }
        $this->reinit_album($params["album"], "%SAME%", array());
        $this->result["saved"]=true;
     }
     
     function image_convert($image)
     {
        return ENHANCE::image_transform($image,array(100,100),null,null); 
     }
       
     function gallery_table($params)
    {
        $TD = Common::inc_module_factory('TTreeSource');
        $options['startNode'] = $params['id'];
        $options['shownodesWithObjType'] = array('_ROOT', '_GALLERY', '_ALBUM');
        $options['groups'] = array( '_GALLERY','_ALBUM','_ROOT');
        $options['columnsAsParameters'] = array('name' => 'Name','visible'=>'Visible');
        $options['preventDots'] = true;
        $options['columnsAsStructs'] = array('name'=>'name');
        $options['sequence'] = array('name');
        
        $this->result['data_set'] = array();
        $options['gridFormat']=1;
        
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($params['id']);
        $this->result=$TD->result;
        
    }        
    
    function load_root_data()
        {
        $root                                  =$this->_common_obj->get_node(1);
        $this->result['root_data']             =$root['params'];
        }
    
    
    function save_edited_root($parameters) {
        if ($id=$this->reinit_root($parameters['root_data'])) $this->result['saved']=true;
    }    
        
    function reinit_root($data)
    {
      $data["basic"]=$data["Name"];
     
        $id=$this->_tree->ReInitTreeOBJ(1, $data["Name"], $data);
        return $id;
    }
        
    function delete_obj($data)
        {            
            if (is_array($data['id']))
            {
            foreach ($data['id'] as $id)
                {
                $this->delete_item($id, $data['del_files']);
                }
            }
        else
            {
            $this->delete_item($data['id'], $data['del_files']);
            }
        }

    function delete_item($id, $del_files = true)
        {
        $item=$this->_tree->getNodeInfo($id);

        if ($item['obj_type'] == "_PHOTO")
            {
            $this->delete_photo($id, $del_files);
            }

            
        if ($item['obj_type'] == "_ALBUM")
            {
            $this->delete_album($id, $del_files);
            }
            
        if ($item['obj_type'] == "_GALLERY")
            {
            $this->delete_gallery($id, $del_files);
            }
            
        }

        
    function delete_gallery($id, $del_files)
        {
        $albums=$this->_tree->getChilds($id);

        foreach ($albums as $album)
            {
            $this->delete_album($albums['id'], $del_files);
            }

        if ($this->_tree->DelNode($id))
            {
            $this->result['deleted'][]=$id;
            }
        }
        
        
    
    
    function delete_photo($id, $del_files)
        {
        $items=$this->_tree->getNodeInfo($id);
        

        if ($items['obj_type'] == "_PHOTO")
            {
            if ($this->_tree->DelNode($id))
                {
                if ($del_files)
                    {
                    
                    }

                $this->result['deleted'][]=$id;
                }
                $this->reinit_album($items['ancestor'], "%SAME%", array());                
            }
        }

    function delete_album($id, $del_files)
        {
        $photos=$this->_tree->getChilds($id);

        foreach ($photos as $items)
            {
            $this->delete_photo($items['id'], $del_files);
            }

        if ($this->_tree->DelNode($id))
            {
            $this->result['deleted'][]=$id;
            }
        }

        
   function changeAncestorGrid($parameters) { $this->_common_obj->changeAncestorGrid($parameters, $this);}                     
        

 /*       
    function changeAncestorGrid($parameters)
        {
            
            
       $AlbumSourceId =  $this->_tree->GetAncestor($parameters["id"]);
        
        if ($parameters["relative"] == "child") { $AlbumDestId = $parameters["ancestor"]; } else { $AlbumDestId = $this->_tree->GetAncestor($parameters["ancestor"]); }

        $ancestor=$this->_tree->getNodeInfo($AlbumSourceId);
        $ancestor2=$this->_tree->getNodeInfo($AlbumDestId);
        if ($ancestor["obj_type"] != $ancestor2["obj_type"]) {
            $this->result['dragOK']=false;
            return;
        }
        
        $this->result['dragOK'] =$this->_tree->ChangeAncestorUniqsGrid($parameters['id'], $parameters['ancestor'], $parameters['relative']);
            if ($this->result['dragOK'] && $AlbumSourceId != $AlbumDestId) {
                // пересчёт кол-ва фото в альбомах
                $this->reinit_album($AlbumSourceId, "%SAME%", array());                
                $this->reinit_album($AlbumDestId, "%SAME%", array());
            }
            
    
        }
   */     

    function load_tpl($parameters)
        {
        $this->result['default_tpl']=$this->_tree->ReadNodeParam($parameters['id'], 'Template');
        }

    function tpl_photo_edit()
        {
        global $TMS;
        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'edit_photo.html'));
        $this->lct['edit_photo']=$TMS->noparse('edit_photo');
        }

    function get_initial_category_data($tpl_selected = null)
        {
        global $_PATH;

        if ($files=Common::get_module_template_list($this->_module_name))
            {
            }
        }
        
    function get_initial_album_data($tpl_selected = null)
        {
        global $_PATH;

        if ($files=Common::get_module_template_list($this->_module_name))
            {
            }
        }        
        
      
        
    function get_initial_gallery_data($tpl_selected = null)
        {
        global $_PATH;

        if ($files=Common::get_module_template_list($this->_module_name))
            {
            }
        }         
        

    function add_album()
        {
        global $_PATH; 
        
        $this->get_initial_album_data();
        $this->get_intitial_outer_link();
        $folders = XFILES::directory_list($_PATH['PATH_MEDIA'].'gallery/');
        $this->result['category_data']['folders']=XHTML::arr_select_opt(XARRAY::combine($folders, $folders), $node['params']['folders'], true);
        }
   

    function add_gallery()
        {
        global $_PATH; 
        $this->get_initial_gallery_data();
        $folders = XFILES::directory_list($_PATH['PATH_MEDIA'].'gallery/');
        $this->result['category_data']['folders']=XHTML::arr_select_opt(XARRAY::combine($folders, $folders), $node['params']['folders'], true);
        }        
        

    function get_intitial_outer_link($dpage=false)
        {
        Common::call_common_instance('pages');
        $pages                                        =&pages_module_common::getInstance();
        $this->result['outerLink']['Destination_page'] = XHTML::arr_select_opt(
                                                           XARRAY::arr_to_lev(
                                                               $pages->get_page_module_servers('show_gallery_server'),
                                                               'id',
                                                               'params',
                                                               'Name'),
                                                           $dpage,
                                                           true);
        }

    function add_photo($parameters)
        {              
        global $_PATH;
        $this->load_initial_gallery_data($parameters['group_id']);
        }

    function load_initial_gallery_data($params)
        {
        global $_PATH;
        $this->load_albums($params['category']);
        }

    function load_albums($category_selected)
        {
        $this->result['gallery_data']['category']
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,
                                   true);
        }

    function load_photo($params)
        {
        global $TMS, $_PATH;
        
        $node = $this->_tree->getNodeInfo($params['gallery_id']);
        $this->result['gallery_data']=$node['params'];
        $this->result['gallery_data']['category'] =XHTML::arr_select_opt(
                                                                                XARRAY::arr_to_keyarr(
                                                                                    $this->_tree->GetChilds(1), 'id',
                                                                                    'basic'),
                                                                                $node['params']['category'],
                                                                                true);
        $this->result['gallery_data']['info']     =$node['params']['info'];
        }
        

        
    function load_gallery($parameters)
        {
        global $_PATH;                                                                  
        if ($node=$this->_tree->getNodeInfo($parameters['id']))
            {
            $this->result['category_data'] = $node['params'];     
            $this->result['category_data']['basic'] = $node['basic'];     
            }
        }        

    function load_album($parameters)
        {
        global $_PATH;                                                                  
        if ($node=$this->_tree->getNodeInfo($parameters['id']))
            {
                if ($node["obj_type"] != '_ALBUM') { $this->result["not_album"]=1; return; }
                $this->result['category_data'] = $node['params'];     
                $this->result['category_data']['basic'] = $node['basic'];     
                $this->result['outerLink']['isOuterLink']=$node['params']['isOuterLink'];            
                $this->get_intitial_outer_link($node['params']['Destination_page']);
                $folders = XFILES::directory_list($_PATH['PATH_MEDIA'].'gallery/');
                $this->result['category_data']['folders']=XHTML::arr_select_opt(XARRAY::combine($folders, $folders), $node['params']['folders'], $node['params']['folders'],true);
            }
        }

        
        
    function save_new_gallery($parameters)
        {
            if ($category=$this->init_gallery(1, $parameters['data']['Name'], $parameters['data']))
            {
            $this->result['is_saved']=true;
            $this->result['id']      =$category;
            }
        }
        
    function save_album($parameters)
        {
            if ($category=$this->init_album($parameters["data"]["ParentCategoryId"], $parameters['data']['Basic'], $parameters['data']))
            {
            if ($parameters['data']['folders'])
                {
                $this->refresh_album($parameters);
                }

            $this->result['is_saved']=true;
            $this->result['id']      =$category;
            }
        }        

    function refresh_gallery($params){
        
    }        
        
    function refresh_album($params)
        {
        function microtime_float()
            {
            list($usec, $sec)=explode(" ", microtime());
            return ((float)$usec + (float)$sec);
            }

        session_start();
        unset($_SESSION['files'], $_SESSION['dfiles']);
        $cat  =$this->_tree->GetNodeInfo($params['id']);
        
        $files=XFILES::files_list('./media/gallery/' . $cat['params']['folders'], 'files', Array
            (
            '.gif',
            '.jpeg',
            '.jpg',
            '.JPEG',
            '.JPG'
            ),                    0,                                              true);
        
        $path =$_SERVER['DOCUMENT_ROOT'].'/media/gallery/' . $cat['params']['folders'] . '/';
        $ch   =$this->_tree->GetChilds($params['id']);

        if (sizeof($ch) != 0)
            {
            foreach ($ch as $c)
                {
                $qqq                 = $this->_tree->GetNodeInfo($c['id']);

                $_SESSION['dfiles'][]=$qqq['params']['image'];
                }
            }
        else
            $_SESSION['dfiles']=null;

        foreach ($files as $file)
            {
            if (!preg_match('/[а-яА-Я ]/', $file, $arr))
                {
                
                if (!in_array($path . $file, $_SESSION['dfiles']))
                    {
                    $_SESSION['files'][]=$file;
                    }
                }
            }

        

        if (sizeof($_SESSION['files']) != 0)
            {
            $start_time=microtime_float();

            foreach ($_SESSION['files'] as $file)
                {
                
                $cur_time = microtime_float();

                if (($delta=$cur_time - $start_time) < 20)
                    {
                    $data['LastModified']=time();
                    $f                   =explode('.', $file);
                    array_pop($f);
                    $f                =implode('', $f);
                    $data['Name']    =$f;
                    $data['image']  ='/media/gallery/' . $cat['params']['folders'] . '/' . $file;                    $data['category'] =$params['id'];
                    $data['info']     ='';
                    $data['changed']  =filemtime(
                                           $_SERVER['DOCUMENT_ROOT'] . '/media/gallery/' . $cat['params']['folders']
                                               . '/' . $file);
                    if($id               =$this->_tree->InitTreeOBJ($cat['id'], $data['Name'], '_PHOTO', $data, true))                    {
                        $data['id'] = $cat['id'];
                    }
                    $_SESSION['id'][]=$id;
                    }

                
                $this->result['progress']=ceil(sizeof($_SESSION['id']) * 100 / sizeof($_SESSION['files']));

                if ($this->result['progress'] >= 100)
                    {
                    unset($_SESSION['id']);
                    }
                }
            }
        else
            {
            $this->result['progress']=100;
            $this->result['completed'] = true;
            }
        }

     function create_outer_link(&$parameters)
     {
     $pages=common::module_factory('pages.back');
     $pages->common_call();

     $pages->create_outer_link($parameters['outerLink']['isOuterLink'],$parameters['outerLink']['Destination_page'],
     '~show_gallery/'.$parameters['id'],$this->_module_name.$parameters['id'],$parameters['data']['basic']);
      
      if ($parameters['outerLink']['isOuterLink'])
            {
                $parameters['data']['Destination_page']=$parameters['outerLink']['Destination_page'];
                $parameters['data']['isOuterLink']=$parameters['outerLink']['isOuterLink'];

            }
        else
            {
                $parameters['data']['isOuterLink']='';
            }
     
     }


    function save_edited_folder($parameters)
        {
        
        $this->refresh_folder($parameters);
        
        if ($this->reinit_folder($parameters['id'], $parameters['data']['Name'], $parameters['data']))
            {
            $this->result['is_saved']=true;
            }
        }

     
     
     
    function save_edited_gallery($parameters)
        {
         
        $this->create_outer_link($parameters);
        $this->refresh_gallery($parameters);
        
        if ($this->reinit_gallery($parameters['id'], $parameters['data']['Name'], $parameters['data']))
            {
            $this->result['is_saved']=true;
            }
        }

     
    function save_edited_album($parameters) {
        $this->create_outer_link($parameters);
        $this->refresh_album($parameters);
        if ($this->reinit_album($parameters['id'], $parameters['data']['Basic'], $parameters['data'])) {
            $this->result['is_saved']=true;
        }
    }
        

    function save_edited_photo($parameters)
        {
        $parameters['main']=$parameters['main'];                                                 
        $this->result['is_saved']=
            $this->reinit_photo($parameters['id'], $parameters['main']['Name'], $parameters['main']);   
        }

    function save_photo($parameters)
        {
        $parameters['main']['LastModified']=time();
        $id=$this->init_photo($parameters['main']['ParentCategoryId'], $parameters['main']['Name'], $parameters['main']);
        $this->result['is_saved'] = $id;
        $this->reinit_album($parameters['main']['ParentCategoryId'], "%SAME%", array());
        }

    function delete_thumb($params)
        {
        $adr=Common::media_path($this->_module_name) . 'thumb/' . $params['thumb'];

        if (@unlink($adr))
            {
            $this->result['deleted']=true;
            }
        else
            $this->result['deleted']=false;
        }

    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_ainterface()
        {
        global $TMS;

        $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
        $this->lct['ainterface']=$TMS->parseSection('a_interface');
        }

    function load_actions($parameters)
        {
        $this->result['tune_actions']['Action']
            =XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'),
                                   $parameters['selected'],
                                   true);
        }

        
        
    function get_template_list($module_name, $actions) {
        $files = Common::get_module_template_list($module_name,$actions);
        $files_aliases = array();
        foreach ($files as $file) {
            for ($i=0; $i<count($actions);$i++) { $file = str_replace( $actions[$i], '', $file ); }
            $files_aliases[] = $file;
        }
        return XHTML::arr_select_opt(XARRAY::combine($files_aliases, $files),$se, true);
    }
        
        
        
    function get_action_properties($parameters)
        {
        
        global $TMS,$Adm;
        
        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);  
            
            switch ($parameters['Action'])
                {
                case 'show_gallery_list':
                    Common::call_common_instance('pages');
                    $pages=&pages_module_common::getInstance();
                    $this->result['action_properties']=true;                
                    $this->result['xlist']=false;
                    
                    $galleries = $this->_tree->GetChilds(1,'ASC',0,0, '_GALLERY');
                    $sel_galleries = array(array("value"=>"", "text"=>""));
                    foreach ($galleries as $gallery) {
                        
                        $node=$this->_tree->GetNodeParam($gallery["id"]);
                        $sel_galleries[]=array( "value" => $gallery["id"], "text" => $node["Name"] );
                    }
                    $this->result['action_properties_form']['GalleryStartId']=$sel_galleries;
                                       
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['page']    =XHTML::arr_select_opt( 
                        XARRAY::arr_to_lev($pages->get_page_module_servers(array('show_gallery_server', 'show_selected_gallery' )), 'id', 'params', 'Name'), false, true);

                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    break;

                case 'show_selected_album':
                    $this->result['action_properties']=true;
                    $this->result['xlist']=true;
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    Common::call_common_instance('pages');
                    $pages=&pages_module_common::getInstance();
                    $pppp=$pages->get_page_module_servers('show_search_results');
                    $this->result['action_properties_form']['page'] =XHTML::arr_select_opt(
                                                                                XARRAY::arr_to_lev(
                                                                                    $pages->get_page_module_servers(
                                                                                        'show_search_results'),
                                                                                    'id',
                                                                                    'params',
                                                                                    'Name'),
                                                                                false,
                                                                                true);

                    $albums = $this->_tree->GetChilds();

                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    break;
                    
                case 'show_from_folder':
                    $this->result['action_properties']=true;
                    $files = Common::get_module_template_list('gallery');
                    $this->result['xlist'] = false;

                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array('.'.$parameters['Action'].'.html'));
                    Common::call_common_instance('pages');
                    $pages = &pages_module_common::getInstance();
                    $pppp = $pages->get_page_module_servers('show_search_results');
                    $this->result['action_properties_form']['page']        =XHTML::arr_select_opt(
                                                                                XARRAY::arr_to_lev(
                                                                                    $pages->get_page_module_servers( 'show_search_results'),
                                                                                    'id',
                                                                                    'params',
                                                                                    'Name'),
                                                                                false,
                                                                                true);

                    $albums = $this->_tree->GetChilds();
                    $this->result['action_properties_form']['GalleryStart'] = XHTML::arr_select_opt(
                                                                                XARRAY::arr_to_keyarr(
                                                                                    $this->_tree->GetChilds(1), 'id',
                                                                                    'basic'),
                                                                                $category_selected,
                                                                                true);
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    break;
                    

                case 'show_gallery_server':
                    $this->result['action_properties']=true;
                    $files                                                   =Common::get_module_template_list(
                                                                                  'gallery');
                    $this->result['xlist']=null;
                    $this->result['action_properties_form']['MTemplate'] = $this->get_template_list($this->_module_name, array('.show_selected_album.html'));
                    $this->result['action_properties_form']['Default_action']=XHTML::arr_select_opt(
                                                                                  XARRAY::askeyval(
                                                                                      $this->_common_obj->get_non_server_actions(),
                                                                                      'front_name'),
                                                                                  null,
                                                                                  true);
                    $this->lct['action_properties']                          =$TMS->parseSection($parameters['Action']);
                    break;

                case 'show_search_results':
                    $this->result['action_properties']=true;
                    $this->result['xlist']                             =false;
                    $this->result['action_properties_form']['Template']=$this->get_template_list($this->_module_name, array(''));
                    $this->lct['action_properties']                    =$TMS->parseSection($parameters['Action']);
                    break;
                }
            }
        }

   function load_xlist_data_galleries($parameters)
        {
        $this->module_name   ='gallery';
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
                '_GALLERY' , '_ROOT'
            );

        $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name'=> 'Name');

        $options['columnsAsStructs']=array
            (
            //'name'  => 'basic',
            'image' => 'obj_type'
            );

        $options['transformResults']['image']=array
            (
                '_ROOT'  => 'group',
                '_GALLERY' => 'page'
                
            );

        //$options['endLeafs']=array('_PHOTO');
        $options['selectable']=array('image' => array( '_GALLERY' ));

        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);

        }
        


   function load_xlist_data_albums($parameters)
        {
        $this->module_name   ='gallery';
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
                '_ROOT', '_GALLERY', '_ALBUM'
            );

        $options['columnsAsParameters']=array('LastModified' => 'LastModified', 'name'=> 'Name');

        $options['columnsAsStructs']=array
            (
            //'name'  => 'basic',
            'image' => 'obj_type'
            );

        $options['transformResults']['image']=array
            (
                '_ROOT'  => 'group',
                '_GALLERY'  => 'group',
                '_ALBUM'  => 'page'
                
            );

        //$options['endLeafs']=array('_PHOTO');
        $options['selectable']=array('image' => array( '_ALBUM' ));

        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $TD->init_from_source($this->_common_obj->obj_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);

        }        
        
        
    function get_tree_inheritance()
        {
        $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC;
        }


    /*--------------------------------------------------------------------------------------------*/

    function init_photo($anc, $basic, $data)
        {
        $data['LastModified']=time();
        $id                  =$this->_tree->InitTreeOBJ($anc, '%SAMEASID%', '_PHOTO', $data, true);
        return $id;
        }

    function reinit_photo($id, $basic, $data)
        {
        $data['LastModified']    =time();
        $id=$this->_tree->ReInitTreeOBJ($id, '%SAME%', $data);
        return $id;
        }

    function init_album($anc, $basic, $data)
        {
        $data['LastModified']=time();
        $data['counter']=0;
        $id                  =$this->_tree->InitTreeOBJ($anc, $basic, '_ALBUM', $data, true);
        return $id;
        }

    function reinit_album($id, $basic, $data)
        {
        $data['LastModified']=time();
        $data["counter"] =$this->_tree->getChilds($id,'ASC',0,0,null,true);
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }
        
        
    function init_gallery($anc, $basic, $data)
        {
        $data['LastModified']=time();
        $id                  =$this->_tree->InitTreeOBJ($anc, $basic, '_GALLERY', $data, true);
        return $id;
        }

    function reinit_gallery($id, $basic, $data)
        {
        $data['LastModified']=time();
        $this->_tree->SetUniques(array('uniquetype' => 'unique_in_anc'));
        $id=$this->_tree->ReInitTreeOBJ($id, $basic, $data);
        return $id;
        }
        
        

    function get_num_photos($params)
        {
        
        $childs                    =$this->_tree->getChilds($params['cat_id']);
        $this->result['num_photos']=sizeof($childs);
        }

    function get_cat_info($params)
        {
        global $_PATH;
        
        $cat =$this->_tree->getNodeInfo($params['cat_id']);
        $this->result['cat_info']['thumb_width']=$cat['params']['thumb_width'];
        $this->result['cat_info']['compress']   =$cat['params']['compress'];
        }

    function check_gdlib()
        {
        $this->result['gdlib']=extension_loaded('gd');
        }



    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree',
            ));
        }
    }
?>