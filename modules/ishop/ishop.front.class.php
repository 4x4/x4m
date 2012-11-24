<?php
class ishop_module_front
    extends ishop_module_tpl
    {
    var $_module_name;
    var $_common_obj;
    var $order_data;
    
    function ishop_module_front()
        {
        $this->_module_name='ishop';
        $this->_common_obj =&ishop_module_common::getInstance(true);
        parent::_constructor();
        $this->context=null;
        }

    function execute($action_data, $back_slot = null)
        {
        if (is_array($action_data))
            {
            if ($action=$this->_common_obj->is_action($action_data['Action'], $action_data, $back_slot))
                {
                $action_data['__back_tpa']=$back_slot;
                return $this->$action($action_data);
                }
            }
        }

    function request_action_set($action) { $this->_common_obj->request_action_set($action); }

    function order($parameters)
        {
        global $TMS, $TPA;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if ($parameters['v_error'])
            {
            $TMS->AddMassReplace($parameters['fields']);
            $TMS->parseSection('ishop_order_error', true);
            }

        if (is_array($_POST['order']))
            {
            foreach ($_POST['order'] as $id => $item)
                {
                if (is_numeric($item['count']))
                    $_SESSION['siteuser']['cart'][$id]['count']=$item['count'];

                if ($item['comments'])
                    $_SESSION['siteuser']['cart'][$id]['comments']=$item['comments'];
                }
            }

        if ($_SESSION['siteuser']['authorized'])
            {
            $TMS->AddMassReplace('ishop_order', array
                (
                'username'  => $_SESSION['siteuser']['userdata']['Name'],
                'useremail' => $_SESSION['siteuser']['userdata']['Email'],
                'company'   => $_SESSION['siteuser']['userdata']['Company'],
                'phone'     => $_SESSION['siteuser']['userdata']['Phone'],
                'site'      => $_SESSION['siteuser']['userdata']['Site']
                ));
            }

        $TMS->AddMassReplace('ishop_order', array('orderLink' => $TPA->page_link . '/~submitorder'));

        return $TMS->parseSection('ishop_order');
        }

    function goods_to_order($user_id, $template = null)
        {
        global $TDB, $TMS, $_COMMON_SITE_CONF;

        if (!empty($_SESSION['siteuser']['cart']))
            {
            $_POST['hours']  =isset($_POST['hours']) ? $_POST['hours'] : '00';
            $_POST['minutes']=isset($_POST['minutes']) ? $_POST['minutes'] : '00';
            $deliv_time      =$_POST['hours'] . ':' . $_POST['minutes'] . ':' . '00';

            $tunes           =$this->_common_obj->get_tunes();

            if(!$_SESSION['siteuser']['paysystem'])
            {
                $_SESSION['siteuser']['paysystem'] =$_POST['payment_method'];   
            }
            
            if ($_SESSION['siteuser']['userdata'])
                {
                    if (isset($_POST['address']) && $_POST['address']) {
                        $address=mysql_real_escape_string(isset($_POST['address']) ? $_POST['address'] : ' ');    
                    } else {
                        $address=mysql_real_escape_string($_SESSION['siteuser']['userdata']['address']);
                    }

                    
                    $phone  =$_SESSION['siteuser']['userdata']['Phone'];
                    $email  =$_SESSION['siteuser']['userdata']['Email'];
                    $name   =$_SESSION['siteuser']['userdata']['Name'];
                }
            else
                {
                    $address=mysql_real_escape_string(isset($_POST['address']) ? $_POST['address'] : ' ');
                    $phone  =isset($_POST['phone']) ? $_POST['phone'] : ' ';
                    $email  =mysql_real_escape_string(isset($_POST['email']) ? $_POST['email'] : ' ');
                    $name   =$_POST['fio'];
                }

                if($paysystem=$_SESSION['siteuser']['paysystem'])
                {
                    $paysystem_order_num=$_SESSION['siteuser']['cart'][$paysystem.'_order_num'];
                }

            $order_data=array
                (
                'id'            => 'null',
                'date'          => 'NOW()',
                'client'        => isset($user_id) ? (int)$user_id : 0,
                'currency'      => isset($_SESSION['currency']) ? $_SESSION['currency']['alias'] : ' ',
                'delivery_type' => isset($_POST['deliveryType']) ? $_POST['deliveryType'] : ' ',
                'Name'          => $name,
                'address'       => $address,
                'phone'         => $phone,
                'email'         => $email,
                'total_sum'     => 0,
                'status'        => 1,
                'comments'      => mysql_real_escape_string($_POST['comments']),
                'deliv_time'    => $deliv_time,
                'paysystem'     => $paysystem,
                'paysystem_order_num'=> $paysystem_order_num
                );
                
                if($paysystem)
                {
                        if(method_exists($this,$paysystem))
                        {
                            $order_data=$this->$paysystem($order_data);
                        }
                }
                
                

            $TMS->AddMassReplace('ishop_cart_email', $order_data);
  
            if ($TDB->InsertIN('ishop_orders', $order_data))
                {
                $lid      =$TDB->last_inserted;
                $total_sum=0;
                
                $order_data['id']=$lid;
                
                foreach ($_SESSION['siteuser']['cart'] as $id => $obj)
                    {
                    if ($obj['ext_data']['outer_price'])
                        {
                        $price=(float)str_replace(array
                            (
                            ',',
                            ' '
                            ),                    array
                            (
                            '.',
                            ''
                            ),$obj['ext_data']['outer_price']);
                        }
                    else
                        {
                            $price=$obj['details']['props'][$tunes['PriceProperty']];
                        }

                    if (!$tunes['NameProperty'])
                        $tunes['NameProperty']='Name';

                    $order_item=array
                        (
                        'id'       => 'null',
                        'order_id' => $lid,
                        'cat_id'   => $obj['details']['id'],
                        'count'    => $obj['count'],
                        'name'     => $obj['details']['props'][$tunes['NameProperty']],
                        'comments' => $obj['comments'],
                        'price'    => $price
                        );

                    $TMS->AddMassReplace('ishop_cart_object_email', $obj['details']['props']);
                    $TMS->AddMassReplace('ishop_cart_object_email', $order_item);
                    $TMS->parseSection('ishop_cart_object_email', true);
                    $TDB->InsertIN('ishop_orders_goods', $order_item);
                    $total_sum+=floatval($price) * $obj['count'];
                    }

                $ts=round($total_sum, 2);

                if ($_POST['extended'])
                    $TMS->AddMassReplace('ishop_cart_email', $_POST['extended']);

                $order_data['total_sum']=$ts;

                if ($_SESSION['siteuser']['userdata'])
                    {
                    $order_data['email']  =$_SESSION['siteuser']['userdata']['Email'];
                    $order_data['Name']   =$_SESSION['siteuser']['userdata']['Name'];
                    $order_data['company']=$_SESSION['siteuser']['userdata']['Company'];
                    }

                if($paysystem)
                {
                        if(method_exists($this,$pa=$paysystem.'_after'))
                        {
                            $order_data=$this->$pa($order_data);
                        }
                }
                
                $this->order_data=$order_data;
                
                $TMS->AddMassReplace('ishop_cart_email', $order_data);
                if ($tunes['Emails'] && ($emails=explode(',', $tunes['Emails'])))
                    {
                        $m=Common::inc_module_factory('Mail');
                        $m->From($_COMMON_SITE_CONF['admin_email']);
                        $m->To($emails);
                        $m->Content_type('text/html');
                        $m->Subject($tunes['EmailSubject']);
                        $m->Body($TMS->parseSection('ishop_cart_email'), $tunes['EmailEncoding']);
                        $m->Priority(2);
                        $m->Send();
                    }

                $TDB->UpdateIN('ishop_orders', $lid, array('total_sum' => $ts));
                
                
                
                
                
                return true;
                }
            }
        }

    function submitorder($parameters)
        {
        global $TMS, $TPA, $TDB, $REQUEST_ASSOC;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if (!empty($_SESSION['siteuser']['cart']))
            {
            if ($_SESSION['siteuser']['authorized'])
                {
                $result=$TDB->get_results("SELECT * FROM `ishop_orders_clients` WHERE `uniq_id` = '" . $_SESSION['siteuser']['id']. "'");

                if (!$result=$TDB->get_results($q))
                    {
                    $TDB->InsertIN('ishop_orders_clients', array
                        (
                            'clientid'    => 'null',
                            'fio'         => $_SESSION['siteuser']['userdata']['Name'],
                            'email'       => $_SESSION['siteuser']['userdata']['Email'],
                            'sendspecial' => isset($_POST['sendspecial']) ? (int)$_POST['sendspecial'] : 0,
                            'uniq_id'=>$_SESSION['siteuser']['id']
                        ));

                    $user_id=$TDB->last_inserted;
                    }
                else
                    {
                            $result =current($result);
                            $user_id=$result['clientid'];
                    }
                }
            else
                {
                if ($_POST['fio'] && $_POST['email'] && $_POST['phone'])
                    {
                        
                    $TDB->InsertIN('ishop_orders_clients', array
                        (
                            'clientid'    => 'null',
                            'fio'         => $_POST['fio'],
                            'email'       => $_POST['email'],
                            'sendspecial' => isset($_POST['sendspecial']) ? (int)$_POST['sendspecial'] : 0,
                            'uniq_id'=>0
                        ));

                            $user_id=$TDB->last_inserted;
                    }
                else
                    {
                        return $TMS->parseSection('ishop_order_submit_user_info_failed');
                    }
                }

            if ($this->goods_to_order($user_id))
                {          
                    unset($_SESSION['siteuser']['cart']);
                    $TMS->AddMassReplace('ishop_order_submit_ok',$this->order_data);
                    return $TMS->parseSection('ishop_order_submit_ok');
                }
            else
                {
                return $TMS->parseSection('ishop_order_submit_failed');
                }

            return $TMS->parseSection('ishop_order_submit_failed');
            }
        }

    function show_currency($parameters)
        {
        global $TMS;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if ($cur=$this->_common_obj->get_ishop_currency())
            {
            foreach ($cur as $currency)
                {
                if ($currency['id'] == $_SESSION['currency']['id'])
                    {
                    $currency['selected']=1;
                    }
                else
                    {
                    $currency['selected']='';
                    }

                $TMS->AddMassReplace('currency_item', $currency);
                $TMS->parseSection('currency_item', true);
                }

            $TMS->AddMassReplace('currency', array('action' => CHOST . $_SESSION['pages']['current_page_full_path']));
            return $TMS->parseSection('currency');
            }
        }

    function show_ishop_basket($parameters)
        {
        global $TMS, $TPA;
        $tunes=$this->_common_obj->get_tunes();
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if (count($co=$_SESSION['siteuser']['cart']) > 0)
            {
            Common::call_common_instance('pages');
            $pages=&pages_module_common::getInstance();

            Common::call_common_instance('catalog');
            $catalog                          =&catalog_module_common::getInstance();

            $parameters['Catalog_Server_Page']=$pages->create_page_path($parameters['Catalog_Server_Page']);
            $orderSum                         =0;
            $orderDiscountedSum               =0;

            foreach ($co as $key => $obj)
                {
                $i++;
                $real_id                               =$obj['details']['props']['__realid'];

                $obj['details']['props']['CatalogLink']=$parameters['Catalog_Server_Page'] . '/~showobj/id/' . $real_id;

                $h                                     ='';

                if ($_SESSION['siteuser']['cart'][$key])
                    {
                    $h='/h/' . $_SESSION['siteuser']['cart'][$key]['hash'];
                    }

                $obj['details']['props']['AddToCartLink']     =$TPA->page_link . '/~addtocart/id/' . $key . $h;
                $obj['details']['props']['id']                =$key;
                $obj['details']['props']['RemoveFromCartLink']=$TPA->page_link . '/~remove/id/' . $key . $h;
                $obj['details']['props']['Count']             =$_SESSION['siteuser']['cart'][$key]['count'];


                /*СѓС‡РёС‚С‹РІР°РµРј РєСѓСЂСЃ*/
                if ($_SESSION['currency']['id'])
                    {
                     $obj['details']['props'][$tunes['PriceProperty']]=$obj['details']['props'][$tunes['PriceProperty']]
                                                                          * $_SESSION['currency']['rate'];
                    }

                $obj['details']['props']['Price']   =$obj['details']['props'][$tunes['PriceProperty']];

                $obj['details']['props']['PriceSum']=
                    $obj['details']['props']['Price'] * $obj['details']['props']['Count'];

                if ($discounted_price=$_SESSION['siteuser']['cart'][$key]['ext_data']['discounted_price'])
                    {
                    $obj['details']['props']['DiscountedPriceSum']
                                       =$discounted_price * $obj['details']['props']['Count'];
                    $orderDiscountedSum=+$obj['details']['props']['DiscountedPriceSum'];
                    }

                $orderSum+=$obj['details']['props']['PriceSum'];

                if ($_SESSION['siteuser']['cart'][$key]['ext_data'])
                    {
                    $TMS->AddMassReplace('ishop_cart_object', $_SESSION['siteuser']['cart'][$key]['ext_data']);
                    }

                if ($obj['details']['props'])
                    {
                    $TMS->AddMassReplace('ishop_cart_object', $obj['details']['props']);
                    }

                $TMS->parseSection('ishop_cart_object', true);

                $TMS->clear_section_fields('ishop_cart_object');

                if (is_array($MField_to_kill))
                    {
                    foreach ($MField_to_kill as $MField)
                        $TMS->KillMFields($MField);
                    }
                }

            $ga=array
                (
                'orderSum'           => $orderSum,
                'orderDiscountedSum' => $orderDiscountedSum,
                'orderTypeCount'     => $i,
                'orderCount'         => count($co),
                'orderLink'          => $TPA->page_link . '/~order/',
                'removeAll'          => $TPA->page_link . '/~removeall/',
                'removeSelected'     => $TPA->page_link . '/~remove/',
                'catalogPageLink'    => $parameters['Catalog_Server_Page'],
                'submitLink'          => $TPA->page_link . '/~submitorder'

                );

            $TMS->AddMassReplace('ishop_cart', $ga);
            return $TMS->parseSection('ishop_cart');
            }
        else
            {
            //РєРѕСЂР·РёРЅР° РїСѓСЃС‚Р°
            return $TMS->parseSection('ishop_cart_empty');
            }
        }

    function cart() { }

    function show_basket_status($parameters)
        {
        global $TMS;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
        $pages=&pages_module_common::getInstance();
        $TMS->AddMassReplace('ishop_basket_status',array('cartPageLink' => $pages->create_page_path($parameters['Basket_Page'])));
        $TMS->AddMassReplace('ishop_basket_status', $this->_calculate_order());
        return $TMS->parseSection('ishop_basket_status');
        }

    function _add($id, $count = 1, $rq_hash = null, $ishop_ext_data = array(), $prevent_rq_hash = null)
        {
        $realid=$id;

        if ($ishop_ext_data['generate_id_from'])
            {
            if ($gi=explode(',', $ishop_ext_data['generate_id_from']))
                {
                foreach ($gi as $g)
                    {
                    $g_text.=$ishop_ext_data[$g];
                    }

                $id=md5($g_text);
                }
            }

        if ($prevent_rq_hash)
            {
                $rq_hash=$_SESSION['siteuser']['cart'][$id]['hash'];
            }

        if (isset($_SESSION['siteuser']['cart'][$id]) && $_SESSION['siteuser']['cart'][$id]['hash'] == $rq_hash)
            {
            $_SESSION['siteuser']['cart'][$id]['count']+=$count;
            //РјРµРЅСЏРµРј С…РµС€ РґР»СЏ РµРґРёРЅРёС‡РЅРѕРіРѕ Р·Р°РєР°Р·Р°
            $_SESSION['siteuser']['cart'][$id]['hash']=Common::GenerateHash();
            }
        elseif (!$_SESSION['siteuser']['cart'][$id])
            {
            $tunes=$this->_common_obj->get_tunes();
            Common::call_common_instance('catalog');
            $catalog=&catalog_module_common::getInstance();

            if ($cat_node=$catalog->obj_tree->getNodeInfo($realid))
                {
                    

                //$props=$catalog->property_set_to_properties($cat_node['params']['Property_set'],$cat_node,'',true,null,0,true);
                                $props                     =$catalog->property_set_to_properties($cat_node['params']['Property_set'],
                                                                                 $cat_node,
                                                                                 '',
                                                                                 true);

                
                $props['props']['__realid']=$realid;

                if ($ishop_ext_data['generate_name_from'])
                    {
                    if ($nm=explode(',', $ishop_ext_data['generate_name_from']))
                        {
                        foreach ($nm as $n)
                            {
                            $name.=$ishop_ext_data[$n] . ' ';
                            }
                        }

                    $props['props'][$tunes['NameProperty']]=$name;
                    }

                if ($ishop_ext_data['outer_price'])
                    {
                    $props['props'][$tunes['PriceProperty']]=$ishop_ext_data['outer_price'];
                    }

                if (strpos($props['props'][$tunes['PriceProperty']],
                           ',') or (strpos($props['props'][$tunes['PriceProperty']], ' ')))
                    {
                    $props['props'][$tunes['PriceProperty']]=(float)str_replace(array
                        (
                        ',',
                        ' '
                        ),                                                      array
                        (
                        '.',
                        ''
                        ),
                        $cat_node['params'][$tunes['PriceProperty']]);
                    } else{
                        
                        $props['props'][$tunes['PriceProperty']]=$cat_node['params'][$tunes['PriceProperty']];
                    }

                    
                    $_SESSION['siteuser']['cart'][$id]['count']   =$count;
                    $_SESSION['siteuser']['cart'][$id]['hash']    =Common::GenerateHash();
                    $_SESSION['siteuser']['cart'][$id]['ext_data']=$ishop_ext_data;
                    $_SESSION['siteuser']['cart'][$id]['details'] =$props;
         
                
                }
            }
        
        }

    function remove($parameters)
        {
        global $REQUEST_ASSOC;

        if (is_array($_POST['order']['remove']))
            {
            foreach ($_POST['order']['remove'] as $rem)
                {
                unset($_SESSION['siteuser']['cart'][$rem]);
                }
            }

        if (($_SESSION['siteuser']['cart'][$REQUEST_ASSOC['id']])
            && ($_SESSION['siteuser']['cart'][$REQUEST_ASSOC['id']]['hash'] == $REQUEST_ASSOC['h']))
            {
            unset($_SESSION['siteuser']['cart'][$REQUEST_ASSOC['id']]);
            }

        return $this->show_ishop_basket($parameters);
        }

    function removeall($parameters)
        {
        unset($_SESSION['siteuser']['cart']);
        return $this->show_ishop_basket($parameters);
        }

    function addtocart($parameters)
        {
        global $REQUEST_ASSOC, $TMS;

        $tunes=$this->_common_obj->get_tunes();

        if ($id=intval($REQUEST_ASSOC['id']))
            {
            if (!$c=intval($_POST['ishop']['quantity']))
                $c=1;

            $this->_add($id, $c, $REQUEST_ASSOC['h'], $_POST['ishop']);
            }

        $parameters['last_added']=$id;
        return $this->show_ishop_basket($parameters);
        }



    //export
    function _calculate_order()
        {
        static $order;
        
        if(!$order)
        {
            $tunes   =$this->_common_obj->get_tunes();
            $allcount=0;
            $count   =0;
            $orderSum=0;

            if (is_array($_SESSION['siteuser']['cart']))
                {
                foreach ($_SESSION['siteuser']['cart'] as $key => $obj)
                    {
        
                    $obj['details']['props']['PriceSum']= $obj['details']['props'][$tunes['PriceProperty']] * $_SESSION['siteuser']['cart'][$key]['count'];
                    $orderSum+=$obj['details']['props']['PriceSum'];
                    $allcount+=$obj['count'];
                    }

                $count=count($_SESSION['siteuser']['cart']);
                }

            if ($_SESSION['currency']['rate'])
                {
                $orderSum=$orderSum * $_SESSION['currency']['rate'];
                }
        
        $order= array
            (
            'sum'      => $orderSum,
            'allcount' => $allcount,
            'count'    => $count
            );
        }
        
        return $order;
        
        }
        
        
    function webpay($order_data)
    {
        $order_data['paysystem_order_num']=$_SESSION['siteuser']['webpay_order_num'];
        $order_data['paysystem']='webpay';
        return $order_data;
    }
     
    function date_to_lat_alp($d)
    {
        static $alp=array('01'=>'A','02'=>'B','03'=>'C','04'=>'D','05'=>'E','06'=>'F','07'=>'G','08'=>'H' ,'09'=>'I',10=>'J',
        'K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',27=>'BG',28=>'BH',29=>'BI',30=>'CA',31=>'CA');
        return str_replace(array_flip($alp),$alp,$d);
    }
   
     
     function erip_after($order_data)
    {
            
            $this->_common_obj->paysystems_tree->FindbyBasic(1, 'erip',true);
            $result['data']=$this->_common_obj->paysystems_tree->LastResult['params'];
            $ftp_server=$result['data']['erip_ftp_address'];
            $user_name=$result['data']['erip_login']; 
            $password=$result['data']['erip_password'];
            $order_data['erip_fullnum']=$filename=$this->date_to_lat_alp(date('d')).$this->date_to_lat_alp(date('m')).$this->date_to_lat_alp(date('y')).$order_data['id'].'.001';
            $currentdate=date('Ymdhis');
            $expdate=date('Ymdhis',time()+24*3600*7);
            $tunes           =$this->_common_obj->get_tunes();
            $id=$order_data['id'];
            $sum=$order_data['total_sum'];
            $description='';
            $order_data['paysystem_order_num']=$id;
            foreach ($_SESSION['siteuser']['cart'] as $key => $obj)
                    {
                        $description.='~'.$obj['details']['props']['Name'].' = '. $obj['details']['props'][$tunes['PriceProperty']].' '; 
                    }
                    

            $header=array('currentdate'=>date('Ymdhis'),'num'=>1);
            $senddata=array('currentdate'=>date('Ymdhis'),'expdate'=>date('Ymdhis',time()+24*3600*7),'id'=>$id,'sum'=>$sum,'description'=>$description);
            
            $senddata=str_replace('^','',$senddata);
            
            $contents = implode('^',$header)."\r\n".implode('^',$senddata);
            $conn_id = ftp_connect($ftp_server); 
            
            if (@ftp_login($conn_id, $user_name, $password)) 
            {
                if (ftp_chdir($conn_id, "IN")) 
                {
                            $tempHandle = fopen('php://temp', 'r+');
                            fwrite($tempHandle, $contents);
                            rewind($tempHandle);       
                            ftp_fput($conn_id, $filename, $tempHandle, FTP_ASCII);
                
                }    
               
            }

            // close the connection
            ftp_close($conn_id);  
        
        return $order_data;
    }   

    function execute_postoff() { $this->_common_obj->execute_postoff($this); }
    }
    



?>