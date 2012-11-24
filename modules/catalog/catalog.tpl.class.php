<?php

/**
* catalog.tpl
* 
* Функции данного  класса  доступны  из шаблонов модуля каталог 
* 
* @author Бобров Д.В
* @version 1.0.7
*/

class catalog_module_tpl
    extends x3_module
    {
    //aliasSets

    public function __construct()
        {
        global $TMS;

        $TMS->registerHandlerObj($this->_module_name, $this);
        }


    /**
     * @method: get_onpage_list
     * 
     * данная функция позволяет получить список возможных 
     * значений параметра onpage(количество объектов на странице)  в виде массива 
     * элемент массива {количество объектов}=>{ссылка} 
     * {ссылка}- не передается если на данный момент в сессии записано именно 
     * это количество выводимых объектов
     * <code> 
     * {%F:#onpage(catalog:get_onpage_list())%} 
     * {%each({F:onpage},on_num,on_link)%}  
     * <a href="{%F:on_link%}">{%F:on_num%}</a>  
     * {%endeach%}
     * </code>
     * @return array     
     */

    public function get_onpage_list()
        {
        global $TPA;

        if ($this->onpage)
            {
            foreach ($this->onpage as $onpage)
                {
                if (strpos($_SESSION['pages']['current_page_full_path'], '@onpage'))
                    {
                    $onpage_list[$onpage]=preg_replace('/@onpage\/(\d+)/', '@onpage/' . $onpage,
                                                       $_SESSION['pages']['current_page_full_path']);
                    }
                else
                    {
                    if ('/' == substr($_SESSION['pages']['current_page_full_path'], -1))
                        {
                        $delim='';
                        }
                    else
                        {
                        $delim='/';
                        }

                    $onpage_list[$onpage]=$_SESSION['pages']['current_page_full_path'] . $delim . '@onpage/' . $onpage;
                    }

                if ($onpage == $_SESSION['catalog']['Onpage'])
                    {
                    $onpage_list[$onpage]='';
                    }
                }

            return $onpage_list;
            }
        }


    /**
     * @method: get_objects_by_filter
     * 
     * данная функция позволяет получить список элементов согласно установленному фильтру, фильтр задается в json формате
     * @param json "filter" - указывается фильтр согласно которому будет произведена выборка         
     * @param json "options" - указываются опции "catalogurl":{ссылка на каталог}
     * <code> 
     *  {%F:#objects(catalog:get_objects_by_filter(|{"filter":{"equal":{"is_on_main_page":1}}}|))%}  //выбираем все товары у которых установлен флаг is_on_main_page
     *   <ul>
     *    {%each({F:objects},id,obj)%}  
     *      <li> id: {%F:id%} Имя: {%obj>params>Name%} </li>
     *    {%endeach%}
     *   </ul>
     * </code>
     * @return array     
     */

    public function get_objects_by_filter($parameters)
        {
        if ($parameters[0]['options'])
            {
            $params=$parameters[0]['options'];
            }
                
        $params['spack']=$parameters[0]['filter'];

        if ($objects=$this->select_objects($params))
            {
            while (list($k, $v)=each($objects['objects']))
                {
                $discount              = $this->getDiscountForCategory($v['ancestor']);
                $objects['objects'][$k]=$this->_common_obj->property_set_to_properties($v['params']['Property_set'],
                                                                                       $v,
                                                                                       $prefix,
                                                                                       true,
                                                                                       $this,
                                                                                       $discount);
                                                                                       
                                                                                       

                if ($params['catalogurl'])
                    {
                        $objects['objects'][$k]['Link']=$this->get_link_by_id($objects['objects'][$k]['id'],$params['catalogurl']);
                    }
                }
            }

        return $objects['objects'];
        }

        
    /**
     * @method: get_comparse_count
     * 
     * данная функция позволяет получить количество объектов находящихся в сравнении    
     * <code> 
     *  {%F:@comparse_count(catalog:get_comparse_count())%}
     * </code>
     * 
     */
         public function get_comparse_count($params)
        {
            
            if($_SESSION['catalog']['comparsedata'])
            {
                return count($_SESSION['catalog']['comparsedata']);
            }else{
                
                return 0;
            }
        }
     
     

    /**
     * @method: set_dynamic_filter
     * 
     * данная функция позволяет установить фильтр в контексте показа листинга 
     * @param 0 (json) - указывается фильтр согласно которому будет произведена выборка         
     * <code> 
     *  {%F:+(catalog:set_dynamic_filter(|{"equal":{"is_on_main_page":1}}|))%}
     * </code>
     * 
     */
                  
    public function set_dynamic_filter($params)
        {
        $this->dynamic_filter_data=$params[0];
        }


    /**
     * @method: in_current_filter
     * 
     * данная функция позволяет узнать присутсвует ли определенное поле в текущем фильтре
     * @param 0 (json) - тип фильтра (например equal)
     * @param 1 (json) - имя поля 
     * <code> 
     *  {%F:#inf(catalog:in_current_filter(equal,name))%}
     * </code>
     * 
     */
    public function in_current_filter($params)
        {
        $filter=$this->request_to_filter();
        return $filter[$params[0]][$params[1]];
        }

    public function get_property_alias($val, $params) { return $this->aliasSets[$params[0]][$val]; }


    /**
     * @method: get_link
     * 
     * получить ссылку на объект по id
     * @param (id) - индефикатор объекта
     * @param 1 (link) - имя поля 
     * <code> 
     *  {%F:#mylink(catalog:get_link({F:id},/catalog))%}
     * </code>
     * @return link
     */

    public function get_link($params) {return $this->get_link_by_id($params[0], $params[1]); }


    /**
   * @method: get_connected
   * 
   *  функция позволяет получить все связанные объекты
   * @param 0 (id) - индефикатор объекта
   * <code> 
   *  {%F:#connected(catalog:get_connected({F:id}))%}
   * </code>
   * @return array
   */

    function get_connected($params)
        {
        global $TPA;

        if ($d=$this->_common_obj->getConnectedObjs($params[0]))
            {
                
            while (list($k, $v)=each($d))
                {
                    if($v){
                    $ext[]=array
                    (
                    'id'       => $v['id'],
                    'obj_type' => $d['obj_type'],
                    'basic'    => $d['basic'],
                    'Link'     => $this->get_link_by_id($v['id'],$params[1])
                    )+ $v['props'];
                    }
                }
            }

        return $ext;
        }


    /**
     * @method: get_selector_property
     * 
     *  получить значение свойства-селектора
     * @param 0 (id) - индефикатор группы свойств
     * @param 1 (string) - имя свойства
     * @param 2 (flag) - флаг указывающий на то что свойство является валютой
     * 
     * <code> 
     *  {%F:#values(catalog:get_selector_property(271,field_name_sample))%}
     * </code>
     * @return array
     */

    public function get_selector_property($params)
        {
        $p=$this->_common_obj->get_selector_property($params[0], $params[1]);

        if ($params[2] && $p)
            {
            $p=array_flip($p);
            }

        return $p;
        }


       /**
     * @method: create_filter_link
     * 
     *  сгенерировать ссылку в виде фильтра
     * @param 0 (json) - фильтр в json формате
     * @param 1 (bool) - если true ссылаться на данную страницу
     * 
     * <code> 
     *  {%F:#values(catalog:create_filter_link(|{"equal":{"is_new":"1"}}|,'/catalog'))%}
     *  //вернет ссылку /catalog/@equal/is_new=1
     * </code>
     * @return link
     */

    public function create_filter_link($params)
        {
        global $TPA;
            
        if ($params[0])
            {
                $link=$this->filter_to_request($params[0]);

            if ($params[1])
                {
                 $link=$_SESSION['pages']['current_page_no_filter'] . $link;
                }

            return $link;
            }
        }


    /**
  * @method: in_comparse
  * 
  *  находиться ли объект в сравнении(сессия)
  * @param 0 (id) - индефикатор объекта
  *
  * 
  * <code> 
  *  {%F:#is_in_session(catalog:in_comparse({F:id}))%}
  * 
  * </code>
  * @return bool
  */

    public function in_comparse($params)
        {
        if ($_SESSION['catalog']['comparsedata'][$params[0]])
            {
            return true;
            }
        }


    /**
   * @method: get_obj_param
   * 
   *  получить значение параметра для заданного объекта
   * @param 0 (id) - индефикатор объекта
   * @param 1 (string) - имя параметра
   * 
   * <code> 
   *  {%F:#someparam(catalog:get_obj_param({F:id},myparam))%}
   * 
   * </code>
   * @return string
   */

    public function get_obj_param($id, $params)
        {
        if (is_array($id))
            {
            $params   =$id;
            $id       =$params[0];
            $params[0]=$params[1];
            }

        return $this->_tree->ReadNodeParam($id, $params[0]);
        }


    /**
   * @method: get_obj_params
   * 
   *  получить все параметры объекта в виде массива
   * @param 0 (id) - индефикатор объекта
   * 
   * <code> 
   *  {%F:#someparams(catalog:get_obj_params({F:id},myparam))%}
   * 
   * </code>
   * @return string
   */

    public function get_obj_params($params)
        {
        $cat_node=$this->_tree->getNodeInfo($params[0]);
        return $this->_common_obj->property_set_to_properties($cat_node['params']['Property_set'], $cat_node, '', true,
                                                              $this);
        }


    /**
   * @method: get_subproperties
   * 
   *  получить все параметры объекта в виде массива
   * @param 0 (id) - индефикатор объекта
   * @param 1 (string) - имя подгруппы параметров
   * 
   * <code> 
   *  {%F:#someparams(catalog:get_subproperties({F:id},mysubgroupname))%}    
   * </code>
   * @return array
   */
     function get_subproperties($params)
        {
            
        if ($params[0])
            {
            $node =$this->_tree->getNodeParam($params[0]);

            $props=$this->_common_obj->property_set_to_properties($node['Property_set'], array('params' => $node), '',
                                                                  true);
            $names=XARRAY::arr_to_lev2($this->pset_link[$node['Property_set']], 'params','Name', 'params', 'Alias');

            $types=XARRAY::arr_to_lev2($this->pset_link[$node['Property_set']], 'params','Name', 'params', 'Type');

            if ($props['props'][$params[1]])
                {
                    
                    foreach ($props['props'][$params[1]] as $key => $value)
                    {
                    $n[$key]=array
                        (
                        'alias' => $names[$key],
                        'type'  => $types[$key],
                        'value' => $value
                        );
                    }


                return $n;
                }
            }
        }

    /**
     * @method: get_ishop_link
     * 
     *  получить cсылку на корзину для данного объекта(по нажатию по данной ссылке в карзину добаляется +1 объект)
     * @param 0 (id) - индефикатор объекта
     * 
     * <code> 
     *  {%F:#link_to_basket(catalog:get_ishop_link({F:id}))%}
     * 
     * </code>
     * @return link
     */

    public function get_ishop_link($params)
        {
        if ($this->ishop && $this->BasketPage)
            {
            if ($_SESSION['siteuser']['cart'][$params[0]])
                {
                $h='/h/' . $_SESSION['siteuser']['cart'][$params[0]]['hash'];
                }

            return Common::create_action_link($this->BasketPage, 'addtocart', array('id' => $params[0] . $h));
            }
        }


    /* $params[0]=property
    *  [$params[1]=property_set]
    */

    public function get_property_uniq($params)
        {
        global $TDB;

        if ($params[0])
            {
            if ($params[1])
                {
                return $TDB->get_results('SELECT a.parameter, a.value
                            FROM `_tree_catalog_container_param` a
                            LEFT JOIN `_tree_catalog_container_param` b ON ( a.node_name = b.node_name )
                            WHERE a.parameter = "' . $params[0] . '"
                            AND b.parameter = "Property_set"
                            AND b.value = "' . $params[1] . '"
                            GROUP BY a.value');
                }
            else
                {
                return $TDB->get_results(
                           'SELECT value FROM `_tree_catalog_properties_param` WHERE `parameter` = "' . $params[0]
                               . '" GROUP BY `value`');
                }
            }
        }

        
        //            {%F:#afddprops(catalog:get_subgroup_elements(7437,addprops))%}
        
    function get_subgroup_elements($params)
     {
         
    if($subgr=$this->_common_obj->get_subgroups($params[0]))
    {
        
        $subgr=XARRAY::askeyval($subgr,'SubNameEng');
        $subgr=array_flip($subgr);
    return $this->_common_obj->property_tree->Search(array('Prop_subgroup'=>$subgr[$params[1]]),true,array('ancestor'=>$params[0],'obj_type'=>'_PROPERTY'));
    
     
         
     }   
     }
     
     
     
    /**
     * @method: get_prev
     * 
     *  получить предыдущий объект
     * @param 0 (id) - индефикатор объекта
     * @param 1 (n) - отступ (если пусто то отступ на 1 )     
     * <code> 
     *  {%F:#prev(catalog:get_prev({F:id},3))%}
     * 
     * </code>
     * @return id/obj
     */

     
     function get_prev($params)
     {
         if(!$params[1])$params[1]=1;
      return $this->_tree->getPrev($params[0],$params[1]);
     }


     function get_next($params)
     { 
         if(!$params[1])$params[1]=1;
         return  $this->_tree->getNext($params[0],$params[1]);
     }
     
     
     
     
    function get_childs($params)
        {
            
        if (!$params[0]['obj_type'])
            $obj_type=array('obj_type' => array
                (
                '_CATOBJ',
                '_CATGROUP'
                ));

        if ($nodes=$this->_tree->GetChildsParam($params[0]['id'], '%', true, $obj_type))
            {
            switch ($params[0]['as'])
                {
                case 'options':
                    return XHTML::as_select_opt(XARRAY::arr_to_lev($nodes, 'id', 'params', 'Name'),
                                                $params[0]['selected']);

                    break;

                default: return $nodes;
                }
            }
        }

    public function get_ancestor($params)
        {
        if (!is_array($p=$params))
            {
            $params=Array();

            $params[0]=$p;
            }

        $id=$this->_tree->GetAncestor($params[0]);
        return $id;
        }

    public function get_current_filter() { return $this->current_search_filter; }


    /*
    *  $params[0]== 'startpoint_id'
    *  $params[1]== 'endpoint_id'
    *  $params[2]== if parameter sended return full path
    */
    public function get_up_path($params)
        {
        $this->_tree->FullBonesMas=array();

        $this->_tree->GetFullBonesUp($params[0], $params[1]);

        if ($this->_tree->FullBonesMas)
            {
            if (!$params[2])
                {
                return array_pop($this->_tree->FullBonesMas);
                }
            else
                {
                return $this->_tree->FullBonesMas;
                }
            }
        }
        
    /*
     получает Алиас подгруппы свойств
    */
    public function get_property_alias_tpl($params) { 
        $alias = $this->aliasSets[$params[0]][$params[1]]; 
        if ($alias !== null) return $alias;
        $s = $this->_common_obj->get_subgroups($params[0]);
        foreach ($s as $sub) {
            if ($sub["SubNameEng"] == $params[1]) return $sub["SubName"];
        }
    }           
    
    /* проверяет наличие секции с карточкой товара и парсит её. Используется для вложенных обьектов. 
        $params[0] - Свойства обьекта
        $params[1] - Постфикс секции. По умолчанию - external
    */
    function render_properties_block($params){
        global $TMS;
        ($params[1]) ? $name = $params[1] : $name = 'external';
        $section = 'property_'.$name.'_'.$params[0]['Property_set'];
        
        if ($TMS->isSectionDefined($section)) {
            $TMS->AddMassReplace($section,$params[0]);
            return $TMS->parseSection($section);
        }
    }    
        
    }


/* ---------*/

?>