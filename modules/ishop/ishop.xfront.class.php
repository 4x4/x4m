
<?php
class ishop_module_xfront extends ishop_module_front
{
    function executex($action,$acontext)
    {          
        $this->_common_obj->execute(&$this, $action);            
        $acontext->result = $this->result;
    }

    function get_basket_data($params)      
    {                
        $this->result=$this->_calculate_order();
        
    }
    
    /*
    * params[id]
    * params[count]
    * 
    * returns
    * result.sum - sum of one product
    * result.all_sum - all_cart_sum
    */

    function get_sum_count($params)
    {
        $tunes = $this->_common_obj->get_tunes();     
        if($_SESSION['siteuser']['cart'])
        {    
            $rsum=0;
            foreach($_SESSION['siteuser']['cart'] as $id=>$cart_item)
            {
                
                if($_SESSION['currency']['id'])
                    {
                        $price=$cart_item['details']['props'][$tunes['PriceProperty']]*$_SESSION['currency']['rate'];
                    }else
                    {
                        $price=$cart_item['details']['props'][$tunes['PriceProperty']];
                    }
                   
                    if($id==$params['id'])
                    {
                        $sum=$price*$params['count'];
                        $rsum+=$sum;

                    }else{
                        $rsum+=$price*$cart_item['count'] ;
                    }
            }
        
        }
           
        $this->result=array('sum'=>$sum,'allsum'=>$rsum);
        
        
        
        
    }

        function set_paysystem($params)
        {
            $_SESSION['siteuser']['paysystem'] = $params['paysystem'];
            $_SESSION['paysystem']['user_info'] = $params['userinfo'];
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
                        $obj['priceSum']=$obj['details']['props'][$tunes['PriceProperty']]*$obj['count'];
                    }

                    
                    $objs[$id]=$obj;
            }
        }
            $this->result['cart_items']=$objs;  
    }
    
    
    
    function add_to_cart($params)
    {        
        if(!$params['count'])$params['count']=1;
        $last=$this->_add($params['id'], $params['count'], null, $params['ext'], true);
        $this->result['cart']=$this->_calculate_order();
    }


}
?>