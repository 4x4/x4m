<?php
class pages_module_back
    {
    var $lct;
    var $result;
    //privates
    var $_module_name;
    //class linker
    var $_tree;
    var $_common_obj;

    function pages_module_back() { $this->_module_name='pages'; }

    function common_call()
        {
            
        $this->_common_obj=&pages_module_common::getInstance();
        //proxy for tree
        $this->_tree      =&$this->_common_obj->obj_tree;
        }

    function execute(&$action, $parameters = null)
        {
            
        $this->common_call();
        return $this->_common_obj->execute($this, $action, $parameters);
        }
        
        
        function executex(&$action,$acontext)
        {
            
            $this->common_call();
            $this->_common_obj->execute($this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }

    function create_outer_link($isOuterLink,$destinationPage,$link,$external_link,$name)
     {     
      if ($isOuterLink)
            {
            if ($pnode=$this->_tree->GetNodeStruct($destinationPage))
                {
                    
              
                $link_data=array
                    (
                    'Name'           => $name,
                    'Link' => '/' . $this->_common_obj->create_page_path($destinationPage,true) .'/'. $link,
                    'ExternalLinkId' => $external_link
                    );
            
                if ($linkId=$this->_common_obj->findLinksByExternalId($external_link))
                    {
                    $this->reinit_link($linkId, $link_data);
                    }
                else
                    {
                    $this->init_link($pnode['ancestor'], $link_data);
                    }
                }
            }
        else
            {
            if ($linkId=$this->_common_obj->findLinksByExternalId($external_link))
                {
                $this->_tree->delNode($linkId);                
                }
            }
     
     }
     
    function change_module_slot($params)
    {        
        
        if($params['id']&&$params['anc']){$this->_tree->ChangeAncestor($params['id'],$params['anc']); $this->result['isChanged']=true;}else{$this->result['isChanged']=false;} 
               
    }
    
    function pg_initial_data($selected, $data_flows = 'page_data')
        { $this->result[$data_flows]['Template']=XHTML::arr_select_opt(
                                                     $this->get_templates(),
                                                     $selected,
                                                     true); }
     
    function show_new_pg($params)
        {       
        $pnode=$this->_common_obj->get_node($params['parent_id']);
        $this->pg_initial_data($pnode['params']['Template']);    
        if ($pnode['obj_type'] == '_GROUP')
            {
            $this->get_tpl_slotz_all();                
            $this->result['page_data']['initTemplate']=$pnode['params']['Template'];
            
            }
        }

    function check_uniq($parameters)
        {
        if ($this->_tree->FindbyBasic($parameters['anc'], $parameters['basic']))
            {
            $this->result['uniq']=false;
            }
        else
            {
            $this->result['uniq']=true;
            }
        }

    function save_new_page($parameters)
        {
        $id=$this->init_page($parameters['page_data']['showGroupId'], $parameters['page_data']['basic'],
                             $parameters['page_data']);

        if (is_array($parameters['modules']))
            {
            foreach ($parameters['modules'] as $slot_name => $slotz)
                {
                //изначально созданные слоты и модули активны 
                $slot_data['Active'] = 1;
                $slot_id             =$this->init_slot($id, $slot_name, $slot_data);

                foreach ($slotz['modules'] as $mod)
                    {
                    $mod['params']['Active'] = 1;
                    $this->init_module($slot_id, $mod['params']);
                    }
                }
            }
        }

      function save_slotz($id, $modules)
        {
        //удаляем старые слоты    

        if ($childs=$this->_tree->GetChilds($id, 'ASC', 0, 0, '_SLOT'))
            {
            while (list($_fake, $fake)=each($childs))
                {
                $this->_tree->DelNode($fake['id']);
                }
            }
        //

        if (is_array($modules))
            {

            //вставляем новые
            foreach ($modules as $slot_name => $slotz)
                {
                $slot_data['Active'] = 1;

                $slot_id             =$this->init_slot($id, $slot_name, $slot_data);

                foreach ($slotz['modules'] as $mod)
                    {
                        if($mod['params']['on_module_save'])
                        {                            
                            $mod['params']=Common::call_module_method($mod['params']['type'].'.back','on_module_save',array($mod['params'],$id));
                        }                                
                        
                        $this->init_module($slot_id, $mod['params']);
                        
                    }
                }
            }
        }


    function save_edited_root($parameters)
        {
        if ($id=$this->reinit_root($parameters['root_data']))
            {
            $this->save_slotz(1, $parameters['modules']);

            $this->result['saved']=true;
            }
        }

    function save_edited_page($parameters)
        {

        if ($id=$this->reinit_page($parameters['id'], $parameters['page_data']['basic'], $parameters['page_data']))
            {
            $this->save_slotz($parameters['id'], $parameters['modules']);

            $this->result['saved']=true;
            }
        }

    function save_edited_group($parameters)
        {
        if ($id=$this->reinit_group($parameters['id'], $parameters['group_data']['basic'], $parameters['group_data']))
            {
            $this->save_slotz($parameters['id'], $parameters['modules']);
            $this->result['saved']=true;
            }
        }

        
        function show_new_link($params)
        {

           $pnode=$this->_common_obj->get_node($params['parent_id']);
           

            if ($pnode['obj_type'] == '_GROUP')
                {
                $this->result['link_data']['showGroupId'] =$params['parent_id'];           
                $this->result['link_data']['showGroup']   =$pnode['params']['Name'];
                }
        
        }
        
        
         function save_edited_link($parameters)
        {  
        if ($id=$this->reinit_link($parameters['id'],$parameters['link_data']))
            {            
                $this->result['saved']=true;
            }else{
                $this->result['saved']=false;
            }
        }
        
        
        function load_link($parameters)
        {        
            $node=$this->_tree->getNodeInfo($parameters['link_id']);
            $this->result['link_data']=$node['params'];
        }
        
        
    function save_new_group($parameters)
        {
        if ($id=$this->init_group($parameters['group_data']['showGroupId'], $parameters['group_data']['basic'],
                                  $parameters['group_data']))
            {
            if (is_array($parameters['modules']))
                {
                foreach ($parameters['modules'] as $slot_name => $slotz)
                    {
                    $slot_data['Active'] = 1;
                    $slot_id             =$this->init_slot($id, $slot_name, $slot_data);

                    foreach ($slotz['modules'] as $mod)
                        {
                        $mod['params']['Active'] = 1;
                        $this->init_module($slot_id, $mod['params']);
                        }
                    }
                }

            $this->result['saved']=true;
            }
        }

        
    function changeAncestorGrid($parameters)
        {

            $this->_common_obj->changeAncestorGrid($parameters,$this);
        }
        
        
     function switch_page($params)
     {
         $this->_tree->WriteNodeParam($params['id'],'visible',$params['state']);
         
     }
        
    function changeAncestor($parameters) {

        
        
    //включена проверка на дублирующий basic
    $this->result['dragOK']=$this->_tree->ChangeAncestorUniqs($parameters['id'], $parameters['ancestor'],
                                                              $parameters['relative']); }

    function get_tree_inheritance() { $this->result['tree_inheritance']=$this->_tree->LOCK_OBJ_ANC; }

    

    function get_templates()
        {
           $tpl_tree=new Tree($_DB['DB_NAME'], 'template_container');
           
           if($tpl_list=$tpl_tree->Search(null,true,array('ancestor'=>1,'obj_type'=>'_TGROUP')))
           {           
           
            return $templates=XARRAY::arr_to_lev($tpl_list, 'basic', 'params','Name');
           
           }else{
               x3_error::push('no templates defined',$this->_module_name);
               
           }
        }


    //изменить шаблон страницы
    //function change_template($parameters) { $this->get_slotz($parameters['page_id'], $parameters['tpl']); }

        function  create_new_route($params)
    {
          global $TDB;

          $params['id']='NULL';
          
          $res=$TDB->get_results("SELECT id FROM `routes` WHERE `from`='".$params['from']."'");

          if(!$res){
            $this->result['routes']=$TDB->InsertIN('routes',$params);
          }else{
               $res=current($res);
               $params['id']=$res['id'];
               $this->result['routes']=$TDB->InsertIN('routes',$params);
          }
    }

    
    
    function save_route_part($params)
    {
      global $TDB;  
            
                $update_params[$params['part']]=$params['text'];
                
                if($TDB->UpdateIN('routes',(int)$params['id'],$update_params)!==false) 
                {
                  x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
                 
                }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }   
    }
    
    function delete_route($params)
    {
        global $TDB;   
        $TDB->query('delete from routes where id="'.$params['id'].'"');
    }
   
    function delete_obj(&$data)
        {
            $this->_common_obj->delete_obj($this,$data);          
            if ($this->result['deleted']) {
                $this->result['deleted'] = array($data['id']);
            }
        }

    
    function get_cslotz($params)
        {
        global $_DB;

    
        if (!$params['tpl_name'])
            {
                $tpl_name=$this->_tree->ReadNodeParam($params['node_id'], 'Template');
            }
        else
            {
                $tpl_name=$params['tpl_name'];
            }

        if (!$params['load_all_tpl_slotz'])
            {
            $tpl_slotz=$this->_common_obj->get_tpl_slotz($tpl_name);
            }
        else
            {
            $tpl_slotz=$this->get_tpl_slotz_all(null, true);
            }

           
            
            if($params['node_id'])
            {        
                //для уменьшения объема выборки по модулям
                $node_slots=$this->_common_obj->get_page_slotz($params['node_id']);
                $slotz_in_page=array();
                $slotz_in_page=array_intersect(XARRAY::askeyval($node_slots, 'basic'), XARRAY::askeyval($tpl_slotz, 'basic'));
            }

        
        

        if ($tpl_slotz)
            {
            
            $i=0;
            //cборка слотов
            reset ($tpl_slotz);
            while (list($id, $tpl_slot)=each($tpl_slotz))
                {
                $i++;
                
                if(!$params['get_modules_only'])
                {
                    $this->result['slotz'][$i]['basic']=$tpl_slot['basic'];
                    $this->result['slotz'][$i]['alias']=$tpl_slot['params']['SlotAlias'];
                }

                if (($fkey=array_search($tpl_slot['basic'], $slotz_in_page)) !== false)
                    {
                        
                    //проверка на модули            
                 
                    //if ((!$params['tpl_name'])&&(!$params['get_slotz_only']))
                      //  {
                        //$modules=$this->_tree->GetChildsParam($fkey, '%', true);
                            
                             $mk[$fkey]=$tpl_slot['basic'];
                         //   $this->result['modules'][$tpl_slot['basic']]=$this->_tree->GetChildsParam($fkey, '%', true);
                       // }
                    }
                }
                
      
                
                if (($mk)&&(!$params['tpl_name'])&&(!$params['get_slotz_only']))
                        {
                            if ($modules_crotch=$this->_tree->get_anc_multiply_childs(array_keys($mk), array('_MODULE'), true))
                            {
                            
                                
                               foreach  ($modules_crotch as $id=>$module)
                               {

                                   $mi=Common::module_factory($module['params']['type'].'.front');                                   
                                   $module['params']['RAction']=$mi->_common_obj->front_action_list[$module['params']['Action']]['front_name'];                         
                                   
                                   if(method_exists($mi->_common_obj,$method=$module['params']['Action'].'_extra'))
                                   {

                                        $module['params']=$mi->_common_obj->$method($module['params']);
                                       
                                   }
   
                                   
                                   
                                   $this->result['modules'][$mk[$module['ancestor']]][$id]=$module;
                               }
                            
                            }
                          
                          
                        }
                    
            return true;
            }
        }
        
    function get_access($params)
    {    


        Common::call_common_instance('fusers');  
                $fusers=fusers_module_common::getInstance();         
              
             $this->result['access']['DisableAccess']=$this->_tree->ReadNodeParam($params['id'],'DisableAccess');
            
             if($this->result['access']['LinkId']=$this->_tree->ReadNodeParam($params['id'],'AuthRedirId'))
             {
                $this->result['access']['Link']=$this->_common_obj->create_page_path($this->result['access']['LinkId'],true,'',true);
             }
             $usercp = $this->_tree->ReadNodeParam($params['id'],'NoAuthRedirId');
             
             $this->result['access']['NoAuthRedirId']=XHTML::arr_select_opt( XARRAY::arr_to_lev( $this->_common_obj->get_page_module_servers( 'user_panel'), 'id', 'params', 'Name'), $usercp, true);
             
             if($groups=$fusers->obj_tree->GetChildsParam(1,array('Name')))
               {                   
                    if( $results=$fusers->get_node_rights($params['id'],'pages'))
                    {                    
                        foreach($results as $r)
                        {
                            //read
                            if($r['params']['Rights']==1)
                             {                    
                                 $groups[$r['ancestor']]['r']=1;
                             }                        
                        }                    
                    }                     
                    $this->result['access_groups']=$groups;                    
                   
                    
               }
    }     

    
    
    function set_access($params)
    {
                Common::call_common_instance('fusers');  
                $fusers=fusers_module_common::getInstance();       
                
                if($params['access'])
                {
                    unset($params['access']['Name']);
                    $params['access']['AuthRedirId']=$params['access']['LinkId'];                    
                    $this->reinit_page($params['id'], '%SAME%', $params['access']);
                
                }
                
                if($params['groups'])
                {   
            
                    if( $results=$fusers->get_node_rights($params['id'],$this->_module_name)) 
                    {
                       foreach($results as $k=>$v)
                       {
                         $fusers->obj_tree->DelNode($k);
                       }
                    }
                    
                                  
                    foreach($params['groups'] as $gr_key=>$gr_)
                    {
                        if($gr_)$fusers->init_scheme_item(str_replace('_','',$gr_key),array('Module'=>'pages','Node'=>$params['id'],'Rights'=>1));                    
                    }
                }    
    }

    function get_tpl_slotz_all($_fake = null, $return_slotz = false)
        {
        $all_slotz=array();

        if ($templates=$this->get_templates())
            {
            foreach ($templates as $tplink => $tpl)
                {
                $tpl_slotz = $this->_common_obj->get_tpl_slotz($tplink);

                foreach ($tpl_slotz as $a => $b)
                    {
                    $ok = true;

                    foreach ($all_slotz as $key => $param)
                        {
                        if ($b['basic'] == $param['basic'])
                            {
                            $ok=false;
                            }
                        }

                    if ($ok)
                        {
                        $all_slotz[$a]=$b;
                        }
                    }
                }
            }

        //некрасивое решение
        if ($return_slotz)
            {
            return $all_slotz;
            }

        //reset($all_slotz);
        while (list($id, $tpl_slot)=each($all_slotz))
            {
            $i++;
            $this->result['slotz'][$i]['basic']=$tpl_slot['basic'];
            $this->result['slotz'][$i]['alias']=$tpl_slot['params']['SlotAlias'];
            }
        }
        
     function get_page_url($parameters)
     {
            $this->result['page_data']['link']=HOST;
            $this->result['link']=$this->_get_page_url($parameters['id']);
     }
     
     function _get_page_url($id)
     {
     
          $this->_tree->GetFullBonesUp($id);
        
            if ($this->_tree->FullBonesMas)
                {
                    return  implode(XARRAY::askeyval($this->_tree->FullBonesMas,'basic'),'/');
                } 
         
     }

    function load_page_data($parameters)
        {

        
        $page                              =$this->_common_obj->get_node($parameters['page_id']);
        $this->result['page_data']         =$page['params'];
        $this->result['page_data']['basic']=$page['basic'];
        $this->result['page_data']['VTemplate']         =$page['params']['Template'];
        
        //все корневые шаблоны    
        $this->pg_initial_data($page['params']['Template'], 'page_data');

        $this->_tree->GetFullBonesUp($parameters['page_id']);

        $this->result['page_data']['LinkTo']=HOST;

        if ($this->_tree->FullBonesMas)
            {
            foreach ($this->_tree->FullBonesMas as $bone)
                {
                $this->result['page_data']['LinkTo'].=$bone['basic'] . '/';
                }
            }


        //XHTML::arr_select_opt($this->get_templates());
        //$this->get_slotz($parameters['page_id']);
        
        }

    function load_group_data($parameters)
        {
        $group                              =$this->_common_obj->get_node($parameters['group_id']);
        $this->result['group_data']         =$group['params'];
        $this->result['group_data']['basic']=$group['basic'];
        //
        $this->pg_initial_data($group['params']['Template'], 'group_data');

        $this->result['group_data']['StartPage']
            =XHTML::arr_select_opt(
                 XARRAY::arr_to_lev($this->_tree->GetChildsParam($parameters['group_id'], array('Name'), true,
                 array('obj_type'=>array('_PAGE','_GROUP'))), 'id','params','Name'),$group['params']['StartPage'],
                 true);
        
        
        //$this->get_slotz($parameters['group_id'], null, true);
        }

    function load_root_data()
        {
        $root                                  =$this->_common_obj->get_node(1);
        $this->result['root_data']             =$root['params'];
        $this->result['root_data']['basic']    =$root['basic'];
        $this->result['root_data']['Gacode']    =$root['params']['Gacode'];

        $this->result['root_data']['StartPage']=XHTML::arr_select_opt(
                                                    XARRAY::arr_to_lev(
                                                        $this->_tree->GetChildsParam(1, array('Name'), true), 'id',
                                                        'params',                                             'Name'),
                                                    $root['params']['StartPage']);
        //$this->get_slotz(1, null, true);
        }

    //специальная функция сервер данных для xlist  для выборки данных с дерева
    function load_xlist_data($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_GROUP',
            '_ROOT'
            );

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['columnsAsStructs']=array('image' => 'obj_type');

        $options['transformResults']['image']=array
            (
            '_GROUP' => 'group',
            '_ROOT'  => 'group'
            );

        $options['selectable']=array('image' => array
            (
            '_GROUP',
            '_ROOT'
            ));

        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

        
        
        function load_xlist_link_data($parameters)
        {
        $TD                  =Common::inc_module_factory('TTreeSource');
        $options['startNode']=$parameters['anc_id'];

        $options['shownodesWithObjType']=array
            (
            '_PAGE',
            '_GROUP',
            '_ROOT'
            );

        $options['columnsAsParameters']=array('name' => 'Name');

        $options['columnsAsStructs']=array('image' => 'obj_type');

        $options['transformResults']['image']=array
            (
            '_GROUP' => 'group',
            '_PAGE'  => 'page',
            '_ROOT'  => 'group'
            );

        $options['endLeafs']=array('_PAGE');

        $options['selectable']=array('image' => array
            (
            '_PAGE' ,
            '_GROUP',
            '_ROOT'
            ));

        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['anc_id']);
        $this->result=array_merge_recursive($TD->result, $this->result);
        }

        


    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_actions($parameters) { $this->result['tune_actions']['Action']=XHTML::arr_select_opt(

                                                                                     XARRAY::askeyval(
                                                                                         $this->_common_obj->get_actions(),
                                                                                         'front_name'),
                                                                                     $parameters['selected'],
                                                                                     true); }

      /*fed*/                                                                               
      function  get_module($params)
      {
          
          $this->result['module']=  $this->_tree->getNodeInfo($params['id']);
       
      }
      
      function get_action_properties($parameters)
        {
        global $TMS,$Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
                        
                $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
                switch ($parameters['Action'])
                {
                case 'show_level_menu':
                    $this->result['action_properties']=true;
                    $files=Common::get_module_template_list('menu',array('.'.$parameters['Action'].'.html'));
                    $this->result['xlist']                             =true;
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    $this->lct['action_properties']                    =$TMS->ParseSection($parameters['Action']);
                    break;
                    
                    
                    
                    
                case 'show_level_menu2':
                    $this->result['action_properties']=true;
                    $files=Common::get_module_template_list('menu',array('.'.$parameters['Action'].'.html'));
                    $this->result['xlist']                             =true;
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    $this->lct['action_properties']                    =$TMS->ParseSection($parameters['Action']);
                break;


                
                case 'show_map':
                    $this->result['action_properties']=true;
                    $files                                             =Common::get_module_template_list('menu',array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    $this->lct['action_properties']                    =$TMS->ParseSection($parameters['Action']);
                    break;

                case 'show_user_menu':
               
               $this->result['action_properties'] =true;               
               $files=Common::get_module_template_list('menu',array('.show_level_menu.html','.'.$parameters['Action'].'.html'));
               $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),$se, true); 
               $this->lct['action_properties']=$TMS->ParseSection($parameters['Action']);
               
               $this->result['action_properties_form']['menu']= $this->generate_select_umenus();
                
               
               break;

               case 'show_path':
                    $this->result['action_properties']=true;

                    $TMS->AddFileSection(Common::get_module_tpl($this->_module_name, 'ainterface.html'));
                    $files                                             =Common::get_module_template_list('menu',array('.'.$parameters['Action'].'.html'));

                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(
                                                                            XARRAY::combine($files, $files), $se, true);
                    $this->lct['action_properties']                    =$TMS->ParseSection($parameters['Action']);
                }
            }
        }



        
        
                
        function pages_table($params)
    {
        
        $TD = Common::inc_module_factory('TTreeSource');
        $options['startNode'] = $params['id'];
        $options['shownodesWithObjType'] = array('_PAGE','_GROUP','_ROOT','_LINK');
        $options['groups'] = array('_GROUP','_ROOT');
        $options['columnsAsParameters'] = array('name' => 'Name','visible'=>'Visible');
        $options['preventDots'] = true;
        $options['columnsAsStructs'] = array('basic'=>'basic');
        $options['sequence'] = array('name','basic','visible');
        
        $this->result['data_set'] = array();
        $options['gridFormat']=1;
        
        $TD->init_from_source($this->_tree);

        $TD->setOptions($options);

        $TD->CreateView($params['id']);
        
        $this->result=$TD->result;
    }


                            
    function _copy($params)
        {
            $this->_common_obj->_copy($this,$params,array
                            ( '_GROUP',
                            '_PAGE',
                            '_SLOT',
                            '_MODULE'
                            ));
        }

        
        
        
        
        
        function  save_new_link($parameters)
    {     
     $this->init_link($parameters['link_data']['showGroupId'],$parameters['link_data']);
        
    }

/*fed*/
    
    function create_module($params)
    {
        
        $id=$this->init_module($params['slot_id'], $params['params']);        
        $module=$this->_common_obj->render_module($id);
        $this->result['moduleHtml']='<map alias="'.$params['params']['alias'].'" mtype="'.$params['params']['type'].'" class="__module" id="_m'.$id.'">'.$module.'</map>';
        
        
    }

    function save_module($params)
    {
        if($this->reinit_module($params['id'],$params['params']))
        {
            $this->result['moduleHtml']=$this->_common_obj->render_module($params['id']);
        }
    }
    

    //генерация объектов дерева

    function set_root_data($site_name, $root_data)
        {
        $uniq_param['uniquetype']='unique_in_tree';
        $this->_tree->ReInitTreeOBJ(1, $site_name, $root_data, $uniq_param);
        }

    function init_group($id_anc, $group_link, $group_data)
        {
        $id=$this->_tree->InitTreeOBJ($id_anc, $group_link, '_GROUP', $group_data, true);
        return $id;
        }
        
        
    function init_link($id_anc,$data)
        {
        $id=$this->_tree->InitTreeOBJ($id_anc,'%SAMEASID%' , '_LINK', $data, true);
        return $id;
        }

        function reinit_link($id_anc,$data)
        {
        $id=$this->_tree->ReInitTreeOBJ($id_anc, '%SAME%', $data);
        return $id;
        }
        
    
    
    function reinit_group($id, $group_link, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_tree->ReInitTreeOBJ($id, $group_link, $data, $uniq_param);
        return $id;
        }

    function reinit_root($data)
        {
        $id=$this->_tree->ReInitTreeOBJ(1, '%SAME%', $data);
        return $id;
        }

    function init_page($id_anc, $page_link, $page_data)
        {
        $id=$this->_tree->InitTreeOBJ($id_anc, $page_link, '_PAGE', $page_data, true);
        return $id;
        }

    function reinit_page($id, $basic, $data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_tree->ReInitTreeOBJ($id, $basic, $data, $uniq_param);
        return $id;
        }

    function init_module($slot_id, $module_data)
        {
        $id=$this->_tree->InitTreeOBJ($slot_id, '%SAMEASID%', '_MODULE', $module_data, true);
        return $id;
        }
        
        
    function reinit_module($id,$data)
        {
        $uniq_param['uniquetype']='unique_in_anc';
        $id                      =$this->_tree->ReInitTreeOBJ($id,'%SAME%', $data, $uniq_param);
        return $id;
        }

    function init_slot($page_id, $slot_name, $slot_data)
        {
        $id=$this->_tree->InitTreeOBJ($page_id, $slot_name, '_SLOT', $slot_data, true);
        return $id;
        }

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree'
            ));
        }

//=============USER_MENU===============================//

    function save_umenu($params){
        global $TDB;
       
        $basic = $params['data']['basic'];
        $header = $params['data']['header'];
        $pages = implode(',',$params['items']);
        $TDB->get_results("insert into user_menu values (NULL,\"$basic\",\"$header\",\"$pages\")");
        $this->result['is_saved'] = $TDB->result;
    }
    
    function menu_table(){
        global $_CONFIG;
        
        $TTS                     =Common::inc_module_factory('TTableSource');
        $options['startRow']     =0;
        $options['table']        ='user_menu';
        $options['where']        =' 1';
        $options['rows_per_page']=30;
        $options['gridFormat']=1;
        
        $options['columns']=array('id','basic');


        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $options['sequence']=array('id','basic');
        $TTS->setOptions($options);
        $this->result['data_set']=$TTS->CreateView();

    }
    
    
    function route_301_switch($params)
       {
           global $TDB;
           
            if ($TDB->UpdateIN('routes', (int)$params['id'], array('is301' => (int)$params['state'])))
            {
              x3_message::push($this->_common_obj->translate('saved'), $this->_module_name);
            }
        else
            {
              x3_error::push($this->_common_obj->translate('save_error'), $this->_module_name);
            }
           
       }
       
    function routes_table()
    {
        global $_CONFIG;

        $TTS                     =Common::inc_module_factory('TTableSource');
        $options['startRow']     =0;
        $options['table']        ='routes';
        $options['where']        =' 1';
        $options['rows_per_page']=30;
        $options['gridFormat']=1;
      
        $options['columns']=array('*');

        
        //obj_tree является прямым потомком Tree поэтому его использование обосновано
        $this->result['data_set']=null;
        $options['sequence']=array('id','from','to','is301');
        $TTS->setOptions($options);
        $this->result['data_set']=$TTS->CreateView();

    }
    
    
    
    function delete_umenu($params){
        global $TDB;    

        if (is_array($params['id']))
            {
                $id=implode($params['id'],"','");
                $w='id in (\''. $id . '\')';
            }
        else
            {
            $w='id="' . $params['id'] . '"';
            }
            
        if($TDB->get_results('delete from user_menu where '.$w))
        {
            $this->result['isDel'] = $TDB->result;
        }else{
            x3_error::push('error delete',$this->_module_name);
        }
    }    
    
    function load_edit_umenu($params){
        global $TDB;
        
        $data = $TDB->get_results('select * from user_menu where id='.$params['id']);
        $pages = $data[1]['pages'];
        
        $pages1 = explode(',',$pages);
        $w = Array();
        $x = array_keys($pages1);
        foreach ($x as $x1){
            $w[$pages1[$x1]]=$x1;    
        }
        $this->result['umenu'] = null;
        $q = "SELECT node_name, value FROM `_tree_page_container_param` WHERE parameter = 'Name' AND node_name IN ( $pages)";
        $this->result['data'] = array('basic'=>$data[1]['basic'],'header'=>$data[1]['header'],'id'=>$params['id']);
        $items = $TDB->get_results($q);
        foreach($items as $i){
            $key = $w[$i['node_name']];
            $arr[$key]  = array('id'=>$i['node_name'],'text'=>$i['value']);
        }
        
        ksort($arr);
        foreach($arr as $a){
            $xx[] = $a;
        }
        $this->result['items'] = $xx;
    }
    
    function update_umenu($params){
        global $TDB;
        
        $basic = $params['data']['basic'];
        $header = $params['data']['header'];
        $id=$params['data']['id'];
        $pages = implode(',',$params['items']);
        $q = "update user_menu set basic=\"$basic\",header=\"$header\", pages=\"$pages\" where id=$id";
        $TDB->get_results($q);
        $this->result['is_saved'] = $TDB->result;
    
    }
    
    function onchangeancestor($params)  
        {
               if($modules=$this->_tree->DeepSearch2($params['id'],'_SLOT',0,null,true,array('obj_type'=>'_MODULE')))
               {
                  foreach($modules as $module)
                  {
                        Common::call_module_method($module['params']['type'].'.back','on_page_module_position_change',array($module,$params['id']));
                  } 
               }
        }
    
    
    function generate_select_umenus()
    {
        global $TDB;
        $menus = $TDB->get_results("select id,basic from user_menu");
        $arr[0] = Array('value'=>'','text'=>'');
        foreach($menus as $m){
            $arr[] = Array('value'=>$m['id'],'text'=>$m['basic']);
        }
        return $arr;
        
        
    }





    }
?>