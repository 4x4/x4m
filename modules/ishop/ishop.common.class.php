<?php
class ishop_module_common extends x3_module_common
{
     static private $instance;
  
     static function getInstance($front_call=null) 
     {
        if(!self::$instance) 
        {
          self::$instance = new ishop_module_common($front_call);
        } 
        return self::$instance;
     }
      
     public final function __clone()
        {
            trigger_error( "Cannot clone instance of Singleton pattern", E_USER_ERROR );
        } 
        
        
function ishop_module_common($front_call)
    {
        global $_DB;

        if(self::$instance)trigger_error( "Cannot call instance of Singleton pattern", E_USER_ERROR );
        parent::x3_module_common();
                
        $this->define_front_actions();
        $this->set_obj_tree('shop_container', true);
    //    $this->obj_tree->UniqueBasics(1);
        //obj_tree
        $this->obj_tree->setObject('_ROOT', array('Name'));
        $this->obj_tree->setObject('_TUNES', null, '_ROOT');
        //$this->obj_tree->setObject('_USERDATA', null, '_ROOT');
        
        $this->paysystems_tree=new TTreeOBJ($_DB['DB_NAME'], 'shop_paysystems', true);
        $this->paysystems_tree->setObject('_ROOT', array('Name'));
        $this->paysystems_tree->setObject('_PAYSYSTEM', null,'_ROOT');
        
        
        $this->discount_scheme_tree=new TTreeOBJ($_DB['DB_NAME'], 'shop_dscheme', true);
        $this->discount_scheme_tree->setObject('_ROOT', array('Name'));
        $this->discount_scheme_tree->setObject('_DSCHEME', array('Name'), '_ROOT');
        $this->discount_scheme_tree->setObject('_DSCHEMEITEM', array('catid','discount'), '_DSCHEME');
    }

        
        
    function get_ishop_currency()
    {
        global $TDB;
        static $currency;        
        if(!$currency){$currency=$TDB->get_results('SELECT * FROM `ishop_currency`');}
        return $currency;
        
    }
      
        
    function get_tunes()
    {
        if($n = $this->obj_tree->get_nodes_by_obj_type(array('_TUNES')))
        {
            $selected = current($n);
            return $selected['params'];
        }
    }

    
    
        
    function changecurrency()
    {
                global $REQUEST_ASSOC,$TDB;
                if(($id=$REQUEST_ASSOC['curid'])or($id=$_REQUEST['curid']))
                {
                    $cur=$TDB->selectIN('*','ishop_currency','id ='.$id);
                    $_SESSION['currency']=$cur[1];
                }                
    }
    
    
    function setcurrentcurrency()
    {
        global $TDB;        

        if($_REQUEST['changecurrency'])
        {
            return ishop_module_common::changecurrency();      
        }
        
        if(!($_SESSION['currency'])&&($cur=$TDB->selectIN('*','ishop_currency','ismain=1')))$_SESSION['currency']=$cur[1];
    }
    
    function getDiscoutsSchemes()
    {
        return XARRAY::arr_to_lev($this->discount_scheme_tree->GetChildsParam(1,'%',true),'id', 'params', 'Name');
    }

    function getDiscountScheme($id)
    {
        return XARRAY::arr_to_keyarr(XARRAY::askeyval($this->discount_scheme_tree->GetChildsParam($id,'%',true),'params'),'catid','discount');   
    }


    function define_front_actions()
    {
        $l = Common::get_module_lang('ishop', $_SESSION['lang'], 'define_front_actions'); 
        
        $this->def_action('show_basket_status', $l['{show_basket_status}'],'show_search_form');
        $this->def_action('show_currency', $l['{show_currency}'],'show_currency');
        $this->def_action('show_ishop_basket', $l['{show_ishop_basket}'],array('order','remove','removeall','cart','addtocart','submitorder'));
        $this->set_action_priority('show_basket_status', array('show_ishop_basket' => 0));
    }
}
?>