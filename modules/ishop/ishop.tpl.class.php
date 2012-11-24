<?php
class ishop_module_tpl
{

   public function _constructor()
    { 
        global $TMS;
        $TMS->registerHandlerObj($this->_module_name, $this);   
    }

    /**
    * $params[0] - id авторизованного пользователя
    * $params[1] - статус заказа, если не указаны(можно указывать несколько в виде массива), то выбираются все заказы
    */
    
    function get_client_orders($params)
    {
        global $TDB;

        if(is_array($params[1]))
        {
            $status='b.status in ('.implode(',',$params[1]).') AND ';
            
        }elseif(is_numeric($params[1]))
        {
            $status='b.status='.$params[1].' AND '; 
        }
        
        return $TDB->get_results('SELECT b. * FROM `ishop_orders_clients` a INNER JOIN ishop_orders b ON ( a.clientid = b.client ) WHERE '.$status.' a.uniq_id ='.$params[0].' order by b.date DESC');
        
    }
    
    /*
    * to_currency 3 params
    * 0 -value
    * 1 -from currency
    * 2- to currency
    * */
    
    function to_currency($params)
    {
            
            if($curs=$this->_common_obj->get_ishop_currency())
            {
                    if(!$curs[$params[1]])$params[1]=$_SESSION['currency']['name'];
                    $curs=XARRAY::arr_to_keyarr($curs,'name','rate');
                    return $params[0]*($curs[$params[2]]/$curs[$params[1]]);
            }
        
    }
   
    function  get_cart($params)
    {
        
        if($_SESSION['siteuser']['cart'])
        {
            $tunes = $this->_common_obj->get_tunes();
            foreach($_SESSION['siteuser']['cart'] as $id=> $obj)
            {
                if($_SESSION['currency']['id'])
                    {
                        $obj['details']['props'][$tunes['PriceProperty']]=$obj['details']['props'][$tunes['PriceProperty']]*$_SESSION['currency']['rate'];
                    }
                    
                    $objs[$id]=$obj;
            }
        }
        return $objs;  
    }
    
    
    function get_order_goods($params)
    {
        global $TDB;

        return $TDB->get_results('SELECT * FROM `ishop_orders_goods` WHERE order_id='.$params[0]);        
    }
    
    
    
    
    function calculate_order($params)
    {
      
      return $this->_calculate_order($params[0]);
      
    }

   
    function get_ishop_currency()
    {
        return  $this->_common_obj->get_ishop_currency();
    }
   
   
    public function incart($params)
    {
        if($_SESSION['siteuser']['cart'][$params[0]])return true;
        
    }
    
    
    function get_paysystem($params)
    {
        if($params[0])
        {
            $method_name='get_'.$params[0];
            if(method_exists ($this,$method_name))
            {
             return   $this->$method_name($params[1]);
            }
        }                    
    }
    
    /*paysystems*/

    function get_erip($params)
    {
        
        global $TMS;
        /*$TMS->AddFileSection(Common::get_site_tpl($this->_module_name, 'webmoney.paysystem.html'));
        $this->_common_obj->paysystems_tree->FindbyBasic(1, 'erip', true);
        $erip_data = $this->_common_obj->paysystems_tree->LastResult['params'];
        return $TMS->parseSection('erip');
       */ 
        
    }

    function get_webmoney($params)
    {   
        global $TMS;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, 'webmoney.paysystem.html'));
        $this->_common_obj->paysystems_tree->FindbyBasic(1, 'webmoney', true);
        $webmoney_data = $this->_common_obj->paysystems_tree->LastResult['params'];
        $webmoney_data['cart_items'] = $_SESSION['siteuser']['cart'];        
        $_SESSION['siteuser']['payment_num'] = time();
        
        $webmoney_data['payment_desc'] = array();
            while(list($k, $item) = each($_SESSION['siteuser']['cart']))
            {
                $webmoney_data['payment_desc'][$k]['Name'] = $item['details']['props']['Name'];
                $webmoney_data['payment_desc'][$k]['count'] = $item['count'];
            }
        
        $order = $this->_calculate_order();
        $webmoney_data['payment_amount'] = $order['sum'];
        $TMS->AddMassReplace('webmoney', $webmoney_data);
        
        return $TMS->parseSection('webmoney');
    }

    
    
    function get_webpay($params)
    {   
        global $TMS;
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, 'webpay.paysystem.html'));
        $this->_common_obj->paysystems_tree->FindbyBasic(1,'webpay',true);
        $webpay_data=$this->_common_obj->paysystems_tree->LastResult['params'];
        
        $webpay_data['cart_items']=$_SESSION['siteuser']['cart'];        
        $webpay_data['wsb_currency_id'] = "BYR";
        
        $webpay_data['wsb_seed']=time();        
        $_SESSION['siteuser']['webpay_order_num']=$webpay_data['wsb_order_num']=$webpay_data['wsb_seed'].'-'.substr(session_id(),0,6);
            //�������� ������������ ������: 124264917411111111ORDER-123456781BYR2195012345678901234567890
            // ��� ������ ��������� 2 (wsb_version = 2)
        $order=$this->_calculate_order();
        $webpay_data['wsb_total']=$order['sum'];
        $webpay_data['wsb_signature']=sha1($webpay_data['wsb_seed'].$webpay_data['wsb_storeid'].$webpay_data['wsb_order_num'].$webpay_data['wsb_test'].$webpay_data['wsb_currency_id'].$webpay_data['wsb_total'].$webpay_data['secret_key']); // 7a0142975bc660d219b793c650346af7ffce2473
        $TMS->AddMassReplace('webpay',$webpay_data);
        
        return $TMS->parseSection('webpay');
    }
    

}
?>