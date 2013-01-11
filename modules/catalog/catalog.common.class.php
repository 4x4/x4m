<?php
class catalog_module_common extends x3_module_common
{
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new catalog_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public $property_sets = array();
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        }                            
        
    function catalog_module_common()
    {
        global $_DB;
        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
        $this->set_obj_tree('catalog_container', true,true);
        //$this->obj_tree->UniqueBasics(1);
        $this->property_tree=new TTreeOBJ($_DB['DB_NAME'], 'catalog_properties', true);
        $this->search_forms_tree=new TTreeOBJ($_DB['DB_NAME'], 'catalog_search_forms', true);
        
        //obj_tree
        $this->obj_tree->setObject('_ROOT', array('Name'));
        // фильтра нет!  
        $this->obj_tree->setObject('_CATGROUP', null, '_ROOT,_CATGROUP');
        // фильтра нет!
        $this->obj_tree->setObject('_CATOBJ', null, '_CATGROUP');
        //связанные объекты вложенны в _CATOBJ
        //с 0.997 версии connected имеют свойства привязанные непостредственно к объекту присоединения
        $this->obj_tree->setObject('_CATCONNOBJ', null, '_CATOBJ,_CATGROUP');
        // property_tree        
        $this->property_tree->setObject('_ROOT', array('Name'));
        $this->property_tree->setObject('_PROPERTYSET', array('Name'), '_ROOT');
        //basic является eng alias'ом и  динамическим именем в шаблоне
        //Name имя свойство является eng alias'ом
        // TYPE : IMAGE,FILE,INPUT,LONGTEXT,BOOL
        $this->property_tree->setObject('_PROPERTY', array('Name', 'Default', 'Type', 'Prop_subgroup', 'Alias', 'isComparse'), '_PROPERTYSET');
        $this->property_tree->setObject('_SUBGROUP', array('SubName', 'SubNameEng'), '_PROPERTYSET');
        $this->property_tree->setObject('_PROPERTYVALS', null, '_PROPERTY');
        
        
        $this->search_forms_tree->setObject('_ROOT', array('Name'));
        $this->search_forms_tree->setObject('_SFORM', array('Name'),'_ROOT');
        $this->search_forms_tree->setObject('_SFIELD', array('sname','criteria','property_set','property'),'_SFORM');
        
        $this->define_front_actions();
    }

    function get_subgroups($set_id)
    {
        $subgroups = $this->property_tree->GetChildsParam($set_id, array('SubName', 'SubNameEng'), true, array('obj_type' => array('_SUBGROUP')));

        if($subgroups)
        {
            while(list($k, $v) = each($subgroups))
            {
                $_subgroups[$v['id']] = array('SubNameEng' => $v['params']['SubNameEng'], 'SubName' => $v['params']['SubName']);
            }
        }

        $_subgroups['_main_'] = array('SubNameEng' => '_main_', 'SubName' => '');
        return $_subgroups;
    }
    
    function property_set_to_properties($pset, $properties, $use_prefix = '', $by_subgroups = false,$outer_obj=null,$discount=0,$dont_recalculate_price=false)
    {
        static $sets, $pvalues,$subgroups;
        global $_PATH;
        if(!(is_array($sets[$pset]))&&$pset)
        {
            $sets[$pset] = $this->get_properties($pset, null, $f);            
            if(!$subgroups[$pset]&& $by_subgroups){$subgroups[$pset]=$this->get_subgroups($pset);}
        }

        if($sets[$pset])
        {
            if($outer_obj)
            {
                $outer_obj->pset_link=&$sets;
            }
            //разделение переменных

            
            foreach($sets[$pset] as $name => $pt)
            {   
                                      
                $outer_obj->aliasSets[$pset][$pt['params']['Name']]=$pt['params']['Alias'];
                
                if($properties['params'][$pt['params']['Name']] !== '')
                {             

                    switch($pt['params']['Type'])
                    {
                        case 'SELECTOR':
                            if($pt['params']['Prop_subgroup']=='_main_')
                            {
                                $ext['props'][$use_prefix . $pt['params']['Name']] = $pt['params']['catselector'][$properties['params'][$pt['params']['Name']]];
                            }
                            else
                            {
                                $ext['props'][$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$use_prefix . $pt['params']['Name']] = $pt['params']['catselector'][$properties['params'][$pt['params']['Name']]];
                            }
                            break;
                            
                        case  'ICURRENCY':
                        if($pt['params']['Prop_subgroup']=='_main_')
                            {
                                $cval=$properties['params'][$pt['params']['Name']];
                                
                                if($_SESSION['currency']&&!$dont_recalculate_price)
                                {
                                    $cval=(float)$_SESSION['currency']['rate']*$cval;
                                }
                                
                                $ext['props'][$use_prefix . $pt['params']['Name']] = $cval;                                    
                                $ext['props'][$use_prefix . $pt['params']['Name'].'.discount'] = (float)$discount*(float)$cval/100;
                                $ext['props'][$use_prefix . $pt['params']['Name'].'.discounted']= -((float)$discount*(float)($cval)/100)+$cval;
                            }
                            else
                            {
                                $ext['props'][$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$use_prefix . $pt['params']['Name']] = $properties['params'][$pt['params']['Name']];

                            }

                        
                        break;
                        
                        case 'CURRENCY':
                            if($pt['params']['Prop_subgroup']=='_main_')
                            {
                               
                            $ext['props'][$use_prefix . $pt['params']['Name']] = $properties['params'][$pt['params']['Name']];                                
                            $ext['props'][$use_prefix . $pt['params']['Name'].'.discount'] = (float)$discount*(float)$ext['props'][$use_prefix . $pt['params']['Name']]/100;
                            $ext['props'][$use_prefix . $pt['params']['Name'].'.discounted']= -((float)$discount*(float)($ext['props'][$use_prefix . $pt['params']['Name']])/100)+$ext['props'][$use_prefix . $pt['params']['Name']];
                            
                            }
                            else
                            {
                                $ext['props'][$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$use_prefix . $pt['params']['Name']] = $properties['params'][$pt['params']['Name']];

                            }

                            
                            if($pt['params']['catselector'])
                            {
                                foreach($pt['params']['catselector'] as $val => $cur)
                                {
                                    $_set = $use_prefix . $pt['params']['Name'] . '.' . $cur;
                                    //float только через точку
                                     $properties['params'][$pt['params']['Name']]=str_replace(',','.',$properties['params'][$pt['params']['Name']]);

                                    if($pt['params']['Prop_subgroup']=='_main_')
                                    {
                                        $ext['props'][$_set]=(float)($val) * (float)$properties['params'][$pt['params']['Name']];
                                        $ext['props'][$_set.'.discount'] = (float)$discount*(float)$ext['props'][$_set]/100;
                                        $ext['props'][$_set.'.discounted'] = -((float)$discount*(float)($ext['props'][$_set])/100)+$ext['props'][$_set];
                                    }
                                    else
                                    {                                        
                                        
                                     
                                        $ext['props'][$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$_set]=(float)($val) * (float)$properties['params'][$pt['params']['Name']];
                                        $ext[$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$_set.'.discount'] = (float)$discount*(float)$ext['props'][$pt['params']['Prop_subgroup']][$_set]/100;
                                        $ext[$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$_set.'.discounted'] = ((float)$discount*(float)($ext['props'][$pt['params']['Prop_subgroup']][$_set]) / 100) + $ext['props'][$pt['params']['Prop_subgroup']][$_set];
                                    }
                                }
                            }
                            break;

                        default:
                            if($pt['params']['Prop_subgroup']=='_main_')
                            {
                                
                                $ext['props'][$use_prefix . $pt['params']['Name']] = $properties['params'][$pt['params']['Name']];
                            }
                            else
                            {
                                $ext['props'][$subgroups[$pset][$pt['params']['Prop_subgroup']]['SubNameEng']][$use_prefix . $pt['params']['Name']] = $properties['params'][$pt['params']['Name']];
                            }
                    }
                }
            }


                $ext['props'][$use_prefix . 'Name'] = $properties['params']['Name'];
                $ext['props'][$use_prefix . 'Property_set'] = $properties['params']['Property_set'];
                $ext['id'] = $properties['id'];
                $ext['obj_type'] = $properties['obj_type'];
                $ext['basic'] = $properties['basic'];
                return $ext;
        }
    }        

    function get_properties($set_id, $insert_fake_value = false, &$subgroups=null, $by_subgroups = false)
    {
        $prps = $this->property_tree->GetChildsParam($set_id, array('Type', 'Name', 'Alias', 'SubName', 'SubNameEng', 'Prop_subgroup', 'isComparse'), true, array('obj_type' => array('_PROPERTY', '_SUBGROUP'))
        );

        if($prps)
        {
            while(list($key, $val) = each($prps))            
            {
                if($val['obj_type'] == '_PROPERTY')
                {
                    switch($val['params']['Type'])
                    {
                        case 'SELECTOR':
                        case 'CURRENCY':
                            
                            
                            $prps_vls = $this->property_tree->GetChildsParam($key, '%', true, array('obj_type' => array('_PROPERTYVALS')));

                            if(is_array($prps_vls))
                            {
                                $prps_value=array_pop($prps_vls);
                            }

                            if($insert_fake_value) $prps_value['params']['-'] = ' ';
                            $prps[$key]['params']['catselector'] = $prps_value['params'];
                            break;
                    }

                    if($by_subgroups)
                    {
                        $prps[$prps[$key]['params']['Prop_subgroup']][$key] = $prps[$key];
                        unset($prps[$key]);
                    }
                }
                elseif($val['obj_type'] == '_SUBGROUP')
                {
                    $subgroups[$key] = $val['params'];
                    unset($prps[$key]);
                }
            }
        }

        if($by_subgroups)
        {
            $subgroups['_main_'] = array('SubName' => ' ', 'SubNameEng' => '_main_');
        }
        
        return $prps;
    }

    function getConnectedProps($id)
    {
        return $this->obj_tree->GetChildsParam($id, '%', true, array('obj_type' => array('_CATCONNOBJ')));
    }    

    function getConnectedObjs($id,$only_params=false)
    {
        static $con_cache;
        
        if(!$con_cache[$id])
        {
            $con_cache[$id]= $this->obj_tree->GetChildsParam($id, '%',true,array('obj_type' => array('_CATCONNOBJ')));        
        }
        
        $c = $con_cache[$id];  
            
        if((!$only_params) && ($c))
        {

           $con_ids = XARRAY::arr_to_lev($c, 'id', 'params', 'id');
            
            
             if($con_ids =$this->obj_tree->GetNodesByIdArray($con_ids,null,true))
             {
                     $c=array();
                     while(list($k,$v)=each($con_ids))
                        {
                            $c[$k]=$this->property_set_to_properties($v['params']['Property_set'], $v,$prefix,true,$this);
                        }
                        
             }
            
            
        }
        return $c;
    }

    function define_back_actions() {}

    
    
      public function get_selector_property($propset_id,$property)
  {
        if($node = $this->property_tree->Search(array('Name' => $property),false, array('ancestor'=>$propset_id)))
        {
            $node = current($node);

            if($pv = $this->property_tree->GetChildsParam($node, '%', false, array('obj_type' => array('_PROPERTYVALS'))))
            {
                return current($pv);
            }
        }
      
      }
    
    function define_front_actions()
    {
        
        $l = Common::get_module_lang('catalog',$_SESSION['lang'],'define_front_actions');
        $this->def_action('show_search_form', $l['{show_search_form}'], 'show_search_form');
        $this->def_action('show_smart_search_form', $l['{show_smart_search_form}'], 'show_smart_search_form');
        $this->def_action('show_branch_info', $l['{show_branch_info}'], 'show_branch_info');
        //по умолчанию отображает фильтр с учетом настроек фильтра
        $this->def_action('catalog_filter', $l['{catalog_filter}'], 'catalog_filter');
        $this->def_action('catalog_filter_results', $l['{catalog_filter_results}'], array('catfilter'));
        $this->def_action('catalog_comparsion', $l['{catalog_comparsion}'], array('comparsion', 'delcomparse'));
        $this->def_action('show_search_results', $l['{show_search_results}'], array('catsearch'));
        $this->def_action('show_level_catmenu', $l['{show_level_catmenu}'], 'show_level_catmenu');
        $this->def_action('show_react_menu',$l['{show_react_menu}'], 'show_react_menu');
        $this->def_action('show_category', $l['{show_category}'], 'show_category');
        $this->def_action('show_catalog_server',$l['{show_catalog_server}'], array('showcat', 'showobj','show'));
        $this->set_action_priority('show_react_menu', array('show_level_catmenu' => 1));
        $this->set_action_priority('show_catalog_server', array('catsearch' => 0,'catfilter' => 0));
        $this->set_action_priority('showcat', array('catalog_filter' => 0));
    }
}
?>