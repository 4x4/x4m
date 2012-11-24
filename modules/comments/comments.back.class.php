<?php

class comments_module_back
    {
    var $lct;
    var $result;
    var $_module_name;
    var $_common_obj;
    var $_tree;

    function comments_module_back()
        {
        $this->_module_name='comments';
        }


    /*внимание здесь исполняются только массив action результаты в свойстве класса $result*/

    function common_call($front_call = null)
        {
            $this->_common_obj=&comments_module_common::getInstance();
            $this->_tree      =$this->_common_obj->obj_tree;
        }
    

    function executex($action,$acontext)
        {
            $this->common_call();
            $this->_common_obj->execute(&$this, $action);
            $acontext->lct=$this->lct;   
            $acontext->result=$this->result;
        }



    function get_categories($flows, $category_selected, $ext = true,$sec_flow='ctg_id')
        {
        $this->result[$flows][$sec_flow]
            =XHTML::arr_select_opt(XARRAY::arr_to_keyarr($this->_tree->GetChilds(1), 'id', 'basic'), $category_selected,
                                   $ext);
        }



    function save_tread($params)
        {
         
            if($params['id'])
            {
                
                if($this->reinit_tread($params['id'],$params['data']['basic'],$params['data']))
                {
             
                  x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
                 
                 }else{
                 x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            
                }
            
                return;
            }
            
            if ($id=$this->init_tread($params['data']['Name'],$params['data']))
            {
                $this->result['saved']=1;
            }else{
                $this->result['saved']=0;
            }
        }

        
        function delete_comments($params)
        {
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

            $query='delete from comments where ' . $w;

            if ($TDB->query($query))
                {
                $this->result['deleted']=true;
                }else{
                $this->result['deleted']=false;
                }
        }
     
    function switch_cobject($data)
    {
       if($this->_tree->WriteNodeParam($data['id'],'Active',$data['state']))
       {
                x3_message::push($this->_common_obj->translate('saved'),$this->_module_name);    
           }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }
    }   
    
    function switch_comment($params)
    {
            global $TDB; 
    
            
            if($TDB->UpdateIN('comments',(int)$params['id'],array('Active'=>(int)$params['state'])))
            {
                  x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
            }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }
            
        
    }
    
    function show_edit_tread($params)
    {
        
            $node=$this->_tree->getNodeInfo($params['id']);   
            $this->result['tread_data']=$node['params'];
            $this->result['tread_data']['basic']=$node['basic'];
    }
    
    
     
    function delete_obj($data)
        {
            global $TDB;
  
            if(!is_array($data['id']))
            {
                    $data['id']=array($data['id']);
            }
            if($data['id'])
            {
                $cid=array();
                foreach($data['id'] as $cdel)
                {
                   $node=$this->_tree->getNodeInfo($cdel);
                   
                   if($node['obj_type']=='_COBJECT')
                   {
                       $cid[]=$node['id'];
                   
                   }elseif($node['obj_type']=='_TREAD')
                   {
                        if($childs=$this->_tree->GetChilds($node['id']))
                        {
                            $cid=array_merge($cid,XARRAY::askeyval($childs,'id'));
                        }
                   }
                   
                }
                
                $cid='(\''. implode($cid,"','") . '\')';
                $TDB->query('delete from comments where cid in '.$cid);
            }
            
            $this->_common_obj->delete_obj(&$this,$data);          
        }

     function get_comment_by_module($params)
     {

                        if($cobj=$this->_common_obj->get_cobject_by_module($params['id'],$params['module']))
                        {
                            $this->result['id']=$cobj['id'];           
                            
                        }else
                        {
                            x3_error::push( $this->_common_obj->translate('cobj_not_exists'),$this->_module_name);    
                            $this->result['id']=false;
                        }
     }
       
    function save_comment_part($params)
        {
            global $TDB;  
            
                $update_params[$params['part']]=$params['text'];
                
                if($TDB->UpdateIN('comments',(int)$params['id'],$update_params)!==false) 
                {
                  x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
                 
                }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }
            
        }
        
        
    function get_module_options()
        {        
            global $_CONFIG; 
            $this->result['options']['rows_per_page']=$_CONFIG['news']['admin_rows_per_page'];   
        }
    

    function cidconvert($val)
    {
        static $mbase;
        if(!$mbase[$val])
        {
           return $mbase[$val]= $this->_tree->ReadNodeParam($val,'Marker');
            
        }else{
            
           return $mbase; 
        }
    }
    
    function new_comments_table($parameters)
    {
            global $_CONFIG;
            $TTS                     =Common::inc_module_factory('TTableSource');
            $options['startRow']     =$parameters['startRow'];
            $options['table']        ='comments';
            $ftime=time()-2520000;
            
            $options['where']        ='date>' . $ftime . ' order by Date DESC';
            
            $options['page_num_where']='cid=' . $parameters['id'];
            $options['filter']['Date']=array('name'=>'fromtimestamp','format'=>'d-m-y h:i:s');
                                        
            $options['gridFormat']=1;
            $options['callfunc']['cid']=array($this,'cidconvert');
            $options['columns']=array
                (
                'id',
                'Date',
                'cid',
                'UserName',
                'Header',
                'Message',
                'Active'
                );
            
            $options['sequence']=array('id','Date','cid','UserName','Header','Message','Active');
            
            $this->result['data_set']=null;
        
            $TTS->setOptions($options);
              
            $this->result['data_set']=$TTS->CreateView();        
            $this->result['pages_num']=$TTS->pages_num;               
        
    }

    
    function comments_table($parameters)
    {
            global $_CONFIG;
            $TTS                     =Common::inc_module_factory('TTableSource');
            $options['startRow']     =$parameters['startRow'];
            $options['table']        ='comments';
            $options['where']        ='cid=' . $parameters['id'] . ' order by Date DESC';
            
            $options['page_num_where']='cid=' . $parameters['id'];
            $options['filter']['Date']=array('name'=>'fromtimestamp','format'=>'d-m-y h:i:s');
                                        
            $options['gridFormat']=1;
            
            $options['columns']=array
                (
                'id',
                'Date',
                'UserName',
                'Header',
                'Message',
                'Active'
                );
            
            $options['sequence']=array('id','Date','UserName','Header','Message','Active');
            
            $this->result['data_set']=null;
        
            $TTS->setOptions($options);
              
            $this->result['data_set']=$TTS->CreateView();        
            $this->result['pages_num']=$TTS->pages_num;               
        
    }

    
    function cobject_table($parameters)
    {

        $TD = Common::inc_module_factory('TTreeSource');
        $options['startNode'] = $parameters['id'];
        $options['shownodesWithObjType'] = array('_COBJECT');
        $options['columnsAsParameters'] = array('name' => 'Marker','module'=>'Module','active'=>'Active');
        $options['preventDots'] = true;
        $options['columnsAsStructs'] = array('id' => 'id');
        $this->result['data_set'] = array();
        $options['gridFormat']=1;      

        $TD->init_from_source($this->_tree);
        $TD->setOptions($options);
        $TD->CreateView($parameters['id']);
        $this->result = $TD->result;
    
    }
    

    function xoadGetMeta()
        {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array
            (
            '_common_obj',
            '_tree',
            '_module_name'
            ));
        }

    function check_uniq($params)
    {        
        if($this->_tree->FindbyBasic(1,$params['basic']))
        {
            $this->result['uniq']=0;
        }else{$this->result['uniq']=1;}
    } 
    
    function init_tread($basic,$data)
        {
            $data['LastModified']=time();
            $id                  =$this->_tree->InitTreeOBJ(1, $basic, '_TREAD', $data, true);
            return $id;
        }

        
    function reinit_tread($id, $basic, $data)
        {
            $uniq_param['uniquetype']='unique_in_anc';
            $id                      =$this->_tree->ReInitTreeOBJ($id, $basic, $data, $uniq_param);
            return $id;
        }
    

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

    function get_action_properties($parameters)
        {
      
        global $TMS,$Adm;

        if (array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
            {
                
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
            switch ($parameters['Action'])
                {
                case 'show_last_comments':
               
                    $this->result['action_properties'] =true;
                    $files=Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html')); 
                        
                        
                    $childs=$this->_tree->GetChildsParam(1,array('Alias'));

                    $childs=XARRAY::askeyval($childs,'Alias');
                    
                    $this->result['action_properties_form']['Template']=XHTML::arr_select_opt(XARRAY::combine($files, $files),null,true);      
                    $this->result['action_properties_form']['treads']=XHTML::arr_select_opt($childs,null);      
                    
                    $this->lct['action_properties']=$TMS->parseSection($parameters['Action']);
                    break;
                    
                }
            }
        }

                        

    }


    
?>
