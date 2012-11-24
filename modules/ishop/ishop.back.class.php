<?php
class ishop_module_back
{
    var $lct;
    var $result;
    var $_tree;
    var $_module_name;
    var $_common_obj;
    

    
    function search_module_back() {$this->_module_name='ishop';}

    function request_action_set($action) {$this->_common_obj->request_action_set($action);}

    
    function common_call($front_call = null)
    {
        $this->_module_name = 'ishop';
        $this->_common_obj = ishop_module_common::getInstance();
        $this->_common_obj->module_name=$this->_module_name;
        $this->_tree =$this->_common_obj->obj_tree;

    }

    function executex($action,$acontext)
    {
        $this->common_call();
        $this->_common_obj->execute(&$this, $action);
        $acontext->lct = $this->lct;
        $acontext->result=$this->result;
    }

    function execute($action, $parameters = null)
    {
        $this->common_call();
        return $this->_common_obj->execute(&$this, $action, $parameters);
    }

    function get_module_options()
    {
        global $_CONFIG;
        $this->result['options']['rows_per_page']=$_CONFIG[$this->_module_name]['admin_rows_per_page'];
    }

    function delete_order($parameters) 
    {
        global $TDB;
        
        
        if (is_array($parameters['id']))
            {
                $id=implode($parameters['id'],"','");
                $w='in (\''. $id . '\')';
            }
            
        $TDB->query('DELETE FROM ishop_orders_goods WHERE ishop_orders_goods.order_id '.$w);
        $this->result['isDel'] = $TDB->query('DELETE FROM ishop_orders WHERE  ishop_orders.id '.$w);
    }
        

    function show_order() {}

    function  edit_order($parameters)
    {
        global $TDB, $TMS,$Adm;
        $q = "SELECT b.*, a.date,a.id,a.status,a.currency,a.address,a.phone,a.deliv_time, a.comments,a.delivery_type,a.total_sum as tsum FROM `ishop_orders` a,`ishop_orders_clients` b  where a.client=b.clientid and a.id=".$parameters['id'];
           
        if($result=$TDB->get_results($q))
        {

            $l = $this->_common_obj->get_translation('back_interface');
            $this->order_status = array(1 => $l['{new_order}'],2=>$l['{submited}'],3=>$l['{payed}'],4=>$l['{submitted_by_paysystem}']);
            
            $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'edit_order')),true),true);
            $TMS->AddMassReplace('order',$result[1]);
            $this->result['formdata']['changeOrderStatus'] = XHTML::arr_select_opt($this->order_status,$result[1]['status']);
            $this->result['order']=$TMS->parseSection('order');
        }
    }

    function load_tunes()
    {
        Common::call_common_instance('catalog');
        $catalog =& catalog_module_common::getInstance();
        $this->result['tunes']=$this->_common_obj->get_tunes();
        
        
        if($cur = $catalog->property_tree->Search(array('Type'=>'FIELD'),true))
        {
            
            $this->result['tunes']['NameProperty'] = XHTML::arr_select_opt(XARRAY::combine($x = array_merge(array_unique(XARRAY::askeyval($cur, 'basic'))),$x),$this->result['tunes']['NameProperty'],true);
        }

        if($cur = $catalog->property_tree->Search(array('Type'=>'ICURRENCY'),true))
        {
            $this->result['tunes']['PriceProperty'] = XHTML::arr_select_opt(
            XARRAY::combine($x = array_merge(array_unique(XARRAY::askeyval($cur, 'basic'))),$x),$this->result['tunes']['PriceProperty']);
        }
    }

    function del_discount_scheme($params)
    {
        if($params['id'])
        {
            $this->_common_obj->discount_scheme_tree->DelNode($params['id']);
            $this->result['isDel']=1;
        }else{
            $this->result['isDel']=0;
        }
    }

    function  save_dscheme($params)
    {
        if($params['id'])
        {
            $this->_common_obj->discount_scheme_tree->DelNode($params['id'], 1);
            $this->reinit_dscheme($params['id'], $params['dscheme']);
            $id = $params['id'];
        }
        else
        {
            $id = $this->init_dscheme($params['dscheme']);
        }

         foreach($params['ditems']  as $item)
         {
            $this->init_dscheme_item($id,$item);
         }
    }

    function load_scheme($params)
    {
        $this->result['dscheme'] = $this->_common_obj->discount_scheme_tree->GetNodeParam($params['id']);

        if($pv = XARRAY::askeyval($this->_common_obj->discount_scheme_tree->GetChildsParam($params['id'],'%',true),'params'))
        {
            Common::call_common_instance('catalog');
            $catalog =& catalog_module_common::getInstance();

            while(list($k,$p)=each($pv))
            {
                if($catalog->obj_tree->IsNodeExist($pv[$k]['catid']))
                {
                    $catalog->obj_tree->GetFullBonesUp($pv[$k]['catid'],'id','params','Name');
                    $d = XARRAY::arr_to_lev($catalog->obj_tree->FullBonesMas,'id','params','Name');
                    $catalog->obj_tree->FullBonesMas=null;
                    $pv[$k]['Name']=implode("/",$d);
                }
            }
            $this->result['schemeitems'] = $pv;
        }
    }

    function save_order($params)
    {
        global $TDB;

        if($s = $params['data']['form']['changeOrderStatus'])
        {
            $q = 'update `ishop_orders` set status='.$s.' where id='.$params['data']['id'];         
            if($TDB->query($q))
            {
                x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
            }else{
                x3_error::push( $this->_common_obj->translate('save_error'),$this->_module_name);
            }
        }
    }

    
    function del_currency($params)
    {
        
        global $TDB;
        
         if (is_array($params['id']))
            {
                $id=implode($params['id'],"','");
                $w='id in (\''. $id . '\')';
                $q = 'delete from ishop_currency  where '.$w;         
                 if($TDB->query($q))
                {
                    $this->result['deleted']=true;
                
                }
            }
    }
    
    
    function save_tunes($data)
    {
        if(!$n = $this->_tree->get_nodes_by_obj_type(array('_TUNES')))
        {
            $this->init_node($data, '_TUNES');
        }
        else
        {
            $n = current($n);
            $this->reinit_node($n['id'], $data, '_TUNES');
        }
    }
    
    
    function load_currency($params)
    {
            global $TDB;
            $result=$TDB->get_results('select * from `ishop_currency` where id='.$params['id']);
            if($result[1]['ismain']=='0')
            {
                unset($result[1]['ismain']);
            }
                $result[1]['rate']=round($result[1]['rate'],8);
            $this->result['currency']=$result[1];
    }
    
    function save_currency($params)
    {
        global $TDB;
        
        if(!$params['data']['ismain'])
        {
            $params['data']['ismain']=0;
            
            $r=$TDB->get_results('select count(id) as idc from `ishop_currency`');
            
            
            if($r[1]['idc']=="0")
            {
                $params['data']['ismain']=1;
            }
            
        }
        else
        {
            $TDB->query('UPDATE `ishop_currency` SET `ismain`=0');
        }
        
        
        if(!$params['data']['id'])
        {
            $TDB->InsertIN('ishop_currency',$params['data']);
            
        }else{
                    
            $id=$params['data']['id'];
            unset($params['data']['id']);
            $TDB->UpdateIN('ishop_currency',(int)$id,$params['data']);
        }
        
        x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
    }

     function convertStatus($val)
     {      static $order_status;
            if(!$order_status)
            {
                $l = $this->_common_obj->get_translation('back_interface');
                $order_status = array(1 => $l['{new_order}'],2=>$l['{submited}'],3=>$l['{payed}'],4=>$l['{submitted_by_paysystem}']);
            }
            return $order_status[$val];
            
     }
      
      function change_main_currency($params)
      {       
            global $TDB; 
            $this->recount_currency($params['id']);
            $TDB->query('UPDATE `ishop_currency` SET `ismain`=0');
            $TDB->UpdateIN('ishop_currency',(int)$params['id'],array('ismain'=>1,'rate'=>1));
            x3_message::push( $this->_common_obj->translate('saved'),$this->_module_name);
            
      }
      
     function convertRate($rate)
     {
         return   round($rate,8);
     }
      
     function load_rates_data($parameters)
     {
     
         global $_CONFIG;

        $TTS = Common::inc_module_factory('TTableSource');
        $options['startRow'] = $parameters['startRow'];
        $options['table']    = 'ishop_currency';
        $options['rows_per_page'] = 100;
        $options['customSqlQuery'] = 'select * from ishop_currency order by id';
        $this->result['data_set'] = null;
        $options['sequence'] = array('id', 'name', 'alias', 'rate','ismain');
        $options['gridFormat'] =1; 

        
        $options['callfunc']=array('rate' => array
            (
            $this,
            'convertRate'
            ));
            
        $TTS->setOptions($options);
        $this->result['data_set']  = $TTS->CreateView();
        $this->result['pages_num'] = $TTS->pages_num;

     }
     
/*paysystems*/     
     
     
    function show_webpay($params)
    {
        if($currencies=$this->_common_obj->get_ishop_currency())
        {
            $this->result['data']['webpay_ishop_currency']=XHTML::arr_select_opt(XARRAY::arr_to_keyarr($currencies,'name','alias'),$this->result['data']['webpay_ishop_currency']);
        }
        
    }
    
    
     function show_websystem($parameters)
     {
         global $TMS,$Adm;
         
         $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>$parameters['id']. '.paysystem')),true),true);

         $this->_common_obj->paysystems_tree->FindbyBasic(1, $parameters['id'],true);
         
         $this->result['data']=$this->_common_obj->paysystems_tree->LastResult['params'];

            $method_name='show_'.$parameters['id'];            
            if(method_exists ($this,$method_name)){$this->$method_name();}
         $this->result['template']= $TMS->parseSection('paysystem'); 
     }
     
     
     function save_websystem($parameters)
     {
            $ps=$parameters['data']['paysystem'];
            unset($parameters['data']['paysystem']);
            
            $this->init_paysystem($ps,$parameters['data']);
     }
     
     
     function load_paysystems($parameters)
     {
         global $_PATH,$TMS,$Adm; 

         if($files=XFILES::files_list($_PATH['PATH_MOD'] .'ishop/tpl/', 'files', array('.paysystem.html'), 0, true))
         {
              foreach($files as  $file)
              {  

                  $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>str_replace('.html','',$file))),true),true);                                                  
                  $file=strtok($file,'.');                  
                  $this->result['data_set']['rows'][$file]=array('data'=>array(0=>$file,1=>$TMS->parseSection('name'))); 
                  $TMS->delSection('name');
              }
          }
     }
     
/*/paysystems*/     
     
    function load_orders_data($parameters)
    {
        global $_CONFIG;

        $TTS = Common::inc_module_factory('TTableSource');
        $options['startRow'] = $parameters['startRow'];
        $options['table']    = 'ishop_orders';
        // $options['where']        ='ctg_id=' . $parameters['anc_id'] . ' order by sortdate DESC';

        $options['rows_per_page'] = (isset($parameters['rows_per_page'])) ? $parameters['rows_per_page'] : $_CONFIG['ishop']['admin_rows_per_page'];
        $options['customSqlQuery'] = 'SELECT a.id,b.fio,a.phone ,a.date,a.status, a.total_sum as tsum FROM `ishop_orders` a, `ishop_orders_clients` b  WHERE a.client=b.clientid ORDER BY date DESC';
        $this->result['data_set'] = null;
        $options['sequence'] = array('id', 'date', 'fio', 'phone','tsum','status');
        $options['gridFormat'] =1; 
        
        $options['callfunc']=array('status' => array
            (
            $this,
            'convertStatus'
            ));
        $TTS->setOptions($options);
         
        $this->result['data_set']  = $TTS->CreateView();
        $this->result['pages_num'] = $TTS->pages_num;
    }

    function load_goods_data($parameters)
    {
        global $_CONFIG;

        $TTS = Common::inc_module_factory('TTableSource');
        $options['startRow'] = $parameters['startRow'];
        $options['table']    = 'ishop_orders_goods';

        $options['rows_per_page'] = isset($parameters['rows_per_page']) ? (int) $parameters['rows_per_page'] : $_CONFIG['ishop']['admin_rows_per_page']; 

        $options['customSqlQuery'] = 'SELECT count * price AS summ, `cat_id` , `count` , `name` , `price`,`comments` FROM ishop_orders_goods WHERE order_id = ' . $parameters['id'];

        $this->result['data_set'] = null;
        $options['sequence'] = array('cat_id', 'name', 'price', 'count','summ','comments');
        $options['gridFormat'] =1;
        $TTS->setOptions($options);

        $this->result['data_set'] = $TTS->CreateView();
        $this->result['pages_num'] = $TTS->pages_num;
    }
    

    /*ainterface--------------------------------------------------------------------------------------------*/

    function load_actions($parameters)
    {
        $this->result['tune_actions']['Action'] = XHTML::arr_select_opt(XARRAY::askeyval($this->_common_obj->get_actions(), 'front_name'), $parameters['selected'], true);
    }

    function discount_schemes($parameters)
    {
        $TD = Common::inc_module_factory('TTreeSource');      
        $options['startNode'] = $parameters['anc_id'];
        $options['shownodesWithObjType'] = array('_DSCHEME');
        $options['columnsAsParameters'] = array('Name' => 'Name');
        $options['preventDots'] = true;
        $options['columnsAsStructs'] = array('id' => 'id');    
        $options['sequence'] = array('id', 'Name');
        $options['gridFormat'] =1; 
                
        $TD->init_from_source($this->_common_obj->discount_scheme_tree);
        $TD->setOptions($options);
        $TD->CreateView(1);    
        $this->result['data_set'] = $TD->result['data_set'];
    }

    function get_action_properties($parameters)
    {
        global $TMS,$Adm;

        if(array_key_exists($parameters['Action'], $this->_common_obj->get_actions()))
        {
        $TMS->AddFileSection($Adm->load_module_tpls($this->_module_name, array(array('tpl_name'=>'ainterface')),true),true);                
        
            switch ($parameters['Action'])
            {
                case 'show_currency':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list('ishop',array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);                    
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                break;
                    
                case 'show_ishop_basket':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list('ishop',array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);                    
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                    $this->result['action_properties_form']['Catalog_Server_Page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_catalog_server'), 'id', 'params', 'Name'), false, true);
                    $this->lct['action_properties'] = $TMS->parseSection($parameters['Action']);
                    break;

                case 'show_basket_status':
                    $this->result['action_properties'] = true;
                    $files = Common::get_module_template_list($this->_module_name,array('.'.$parameters['Action'].'.html'));
                    $this->result['action_properties_form']['Template'] = XHTML::arr_select_opt(XARRAY::combine($files, $files), null, true);
                    Common::call_common_instance('pages');
                    $pages =& pages_module_common::getInstance();
                    $this->result['action_properties_form']['Basket_Page'] = XHTML::arr_select_opt(XARRAY::arr_to_lev($pages->get_page_module_servers('show_ishop_basket'), 'id', 'params', 'Name'), false, true);                    
                    $this->lct['action_properties']=$TMS->parseSection('show_basket_status');
                    break;
            }
        }
    }
    
    
    function recount_currency($id)
    {
        global $TDB;
        if($id)
        {                        
            $newrate=$TDB->get_col('select rate from ishop_currency where id="'.$id.'"');            
            $TDB->query('update ishop_currency set rate=rate/'.$newrate[0]);            
        }
        
    }

    //Каталог    
    function load_xlist_data($parameters)
    {
        $catalog =& Common::module_factory('catalog.back');
        $catalog->common_call();
        $catalog->load_xlist_data($parameters);        
        $this->result['data_set'] = $catalog->result['data_set'];
    }

    /*--------------------------------------------------------------------------------------------*/

    
    function init_paysystem($pname,$data)
    {
            if($ids=$this->_common_obj->paysystems_tree->FindbyBasic(1,$pname))
            {
                $this->_common_obj->paysystems_tree->DelNode($ids[0]);
            }
            
            $this->_common_obj->paysystems_tree->InitTreeOBJ(1, $pname, '_PAYSYSTEM', $data, true);
            
    }
    
    function init_node($data, $obj_type)
    {
        return $this->_tree->InitTreeOBJ(1, '%SAMEASID%', $obj_type, $data, true);
    }

    function init_dscheme($data)
    {        
     return $this->_common_obj->discount_scheme_tree->InitTreeOBJ(1, '%SAMEASID%', '_DSCHEME', $data, true);
    }

    function reinit_dscheme($id,$data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        $this->_common_obj->discount_scheme_tree->ReInitTreeOBJ(1, '%SAMEASID%', $data, $uniq_param);
    }

    function init_dscheme_item($anc,$data)
    {
        return $this->_common_obj->discount_scheme_tree->InitTreeOBJ($anc, '%SAMEASID%', '_DSCHEMEITEM', $data, true);
    }

    function reinit_node($id, $data)
    {
        $uniq_param['uniquetype'] = 'unique_in_anc';
        return $this->_tree->ReInitTreeOBJ($id, '%SAME%', $data, $uniq_param);
    }

    function xoadGetMeta()
    {
        XOAD_Client::mapMethods($this, array('execute'));
        XOAD_Client::publicMethods($this, array('execute'));
        XOAD_Client::privateVariables($this, array('_common_obj', '_tree'));
    }
}
?>