<?php
class banners_module_front  extends banners_module_tpl
    {
    var $_module_name;
    var $_tree;
    var $_common_obj;
    var $result;
    var $menu_ancestor;
    var $skip_stats=false;
    var $showed_banners = Array();

    function banners_module_front()
        {
        $this->common_call();
        $this->context     =null;
        parent::__construct();
        }

        
    function common_call($front_call = null)
        {      
            $this->_module_name='banners'; 
            $this->_common_obj=&banners_module_common::getInstance();
            $this->_tree      =&$this->_common_obj->obj_tree;
        }
        
    function execute($action_data)
        {
        global $TMS;
        if (is_array($action_data))
            {
            if ($action=$this->_common_obj->is_action($action_data['Action']))
                {
                    $TMS->startLogging($action_data['Action']);
                    $q=&$this->$action($action_data);
                    $TMS->clearLogged($action_data['Action']);
                    return $q;

                }
            }
        }        
        
    function show_random_banner($params)
        {
        global $TDB,$TMS;
        static $items;
        
        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $params['Template']));
        
        if (empty($items[$params['category']]))
            {
            $items[$params['category']]=$this->_tree->GetChilds($params['category']);
            }

        if (empty($items[$params['category']]))
            return;

        $rseed=rand(0, sizeof($items[$params['category']]) - 1);

        $item =$items[$params['category']][$rseed];
        $vb   =$this->validate_banner($item['id']);

        if (($vb !== false) or ($vb != 'n_rand'))
            {
            unset($items[$params['category']][$rseed]);

            //перенумерация
            $items[$params['category']]=array_merge($items[$params['category']], array());

            return $this->show_banner($item['id'], $params);
            }
        elseif ($vb == 'n_rand')
            {
            $this->show_random_banner($params);
            }
        }            
        



    function validate_banner($id)
        {
        $bool =true;
        $item =$this->_tree->getNodeInfo($id);
        $stats=$this->get_banner_stats($id);
                       
        if (in_array($id, $this->showed_banners))
            {
            return 'n_rand';
            }

        if ((int)$stats['shown'] > (int)$item['params']['times_to_show']
            && ($item['params']['times_to_show'] != '' || $item['params']['times_to_show'] != 0))
            {
            return false;
            }

        if (($item['params']['date1'] != '') || ($item['params']['date2'] != ''))
            {
            if (!$this->check_date($item['params']['date1'], $item['params']['date2']))
                {
                return false;
                }
            }

        return true;
        }

    function check_date($start, $end)
        {
                          
        $s =explode('-', $start);
        $e =explode('-', $end);

        $ds=mktime(0, 0, 0, $s[1], $s[0], $s[2]);
        $de=mktime(0, 0, 0, $e[1], $e[0], $e[2]);
        $dt=time();

        if ($b1=($dt >= $ds) && $b2=($dt <= $de))
            {
            return true;
            }
        else
            {
            return false;
            }
        }

    function show_selected_banner($params) { return $this->show_banner($params['bannersId'], $params); }

    function show_banner($id, $params = null)
        {
        global $TMS, $TDB, $_PATH, $TPA; 
        
        if (!$this->skip_stats) $this->showed_banners[]=$id;
        $info=$this->_tree->GetNodeInfo($id);
        if ($params['Template']) {
            $template=Common::get_site_tpl($this->_module_name, $params['Template']);            
            $TMS->AddFileSection($template);
        }
        
        if($info['params']['banner_type']){
            
        
        switch ($info['params']['banner_type'])
            {
            case 'html':        
                $TMS->AddReplace('html', 'html_code', $info['params']['flash_text']);
                break;

            case 'gif':            
                $fname=$_SERVER["DOCUMENT_ROOT"] . $info['params']['file_name'];
                if (file_exists($fname))
                    {
                    $size=getimagesize($fname);
                    }

                
                $a['fname'] = $info['params']['file_name'];
                $a['alt'] = $info['params']['alt'];
                $a['width'] = $size[0];
                $a['height']= $size[1];
                $a['_num']=$params['_num'];
                
                if ($info["params"]["use_page"] && $info["params"]["pageId"]) {
                    
                    $pages=&pages_module_common::getInstance();
                    $page=$pages->get_node($info["params"]["pageId"]);
                    
                    if ($page['obj_type'] == '_LINK') $a['link']=$page['params']['Link'];
                    else $a['link']  = $pages->create_page_path($info["params"]["pageId"],true);
                    
                    $a['page.Icon']=$page["params"]["Icon"];
                    $a['page.Comment']=$page["params"]["Comment"];
                    $a['page.Name']=$page["params"]["Name"];
                    $a['use_page']=1;
                } else {
                    $a['link']  = $info['params']['link'];
                }
                $TMS->AddMassReplace($info['params']['banner_type'], $a);
                break;

            case 'flash':
                if ($info['params']['flash_text'] != '') {
                    $banner=$info['params']['flash_text'];
                }
                else {
                    $a['fname'] = $info['params']['file_name'];
                    $a['width'] = $info['params']['width'];
                    $a['height']= $info['params']['height'];
                    $TMS->AddMassReplace($info['params']['banner_type'], $a);
                }
        
                break;
            }
            
            $banner=$TMS->parseSection($info['params']['banner_type']);
            if (!$this->skip_stats) { $this->set_banner_stats(array('id'=>$id)); }
                
            return $banner;
        }
    }
        

    function get_banner_stats($id)
        {
            global $TDB;
            return $TDB->get_results('select * from banners_server where banner_id=' . $id);
        }

    //$params['action']=0 -show
    //$params['action']=1 -click
        function set_banner_stats($params)
        {
        global $TDB;
        $ipv4address=sprintf("%u", ip2long($_SERVER["REMOTE_ADDR"]));

        if (!$params['action'])
            $params['action']=0;


        $TDB->InsertIN('banners_server', array
            (
                'id'        => 'null',
                'banner_id' => $params['id'],
                'action'    => $params['action'],
                'ip'        => $ipv4address,
                'date'      => time(),
                'url'       => $_SERVER['REQUEST_URI']
            ));
        }


    function get_banner_info($fname, $type)
        {
        $fname=Common::media_path($this->_module_name) . $params['fname'];

        switch ($type)
            {
            case 'gif':
                $size=getimagesize($fname);

                $banner_info=Array
                    (
                    'width'  => $size[0],
                    'height' => $size[1]
                    );

                break;

            case 'flash':
                $SWF=Common::inc_module_factory('swfheader');

                $SWF->loadswf($fname);

                $banner_info=Array
                    (
                    'width'  => $SWF->width,
                    'height' => $SWF->height
                    );

                break;
            }

        return $banner_info;
        }
        
        
    function show_banners_from_group($params){
        global $TMS, $TDB, $_PATH;
        
        
        $template=Common::get_site_tpl($this->_module_name, $params['Template']);
        $TMS->AddFileSection($template);        
        unset($params["Template"]);
        $banners=$this->_tree->GetChilds($params["category"]);
        if ((count($banners) > $params["OnPage"]) && $params["OnPage"] != 0) {
            
            if ($params["OnPage"]==1) {
                $keys=array(array_rand($banners, $params["OnPage"]));
            } else {
                $keys = array_rand($banners, $params["OnPage"]);
            }
        } else $keys=array_keys($banners);
        $buff = '';
        $i=1;
        foreach ($keys as $key) {
            $params['_num']=$i++;
            $buff .= $this->show_banner($banners[$key]['id'], $params);            
        }
        $TMS->AddReplace('_banner_group','buff', $buff);
        $TMS->AddReplace('_banner_group','_ARES', $_PATH['WEB_PATH_ARES']);
        $this->skip_stats=false;
        return $TMS->parseSection('_banner_group');
    }
    
    }
?>