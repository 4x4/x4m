<?php

//content class
class news_module_front
    extends news_module_tpl
    {
    var $_module_name;
    var $_common_obj;
    var $_tree;
    //только для callback
    var $current = array();

    function news_module_front()
        {
        global $TMS;
        $this->_module_name='news';
        //true так как front_call
        $this->_common_obj =news_module_common::getInstance(true);
        $this->_tree       =$this->_common_obj->obj_tree;
        parent::__construct();
        }

    function request_action_set($action) { $this->_common_obj->request_action_set($action); }

    function rss()
        {
        global $REQUEST_ASSOC, $TPA;

        if ($node=$this->_tree->getNodeInfo($REQUEST_ASSOC['id']))
            {
            Common::inc_module_factory('feedWriter', true);
            //Creating an instance of FeedWriter class. 
            //The constant RSS2 is passed to mention the version

            $feed=new FeedWriter();

            //Setting the channel elements
            //Use wrapper functions for common channel elements
            $feed->setTitle(CHOST . ' - ' . $node['basic']);
            $feed->setLink(CHOST);
            $feed->setDescription(CHOST);

            //Image title and link must match with the 'title' and 'link' channel elements for RSS 2.0
            //$TestFeed->setImage('Testing the RSS writer class','http://www.ajaxray.com/projects/rss','http://www.rightbrainsolution.com/images/logo.gif');

            //Use core setChannelElement() function for other optional channels
            $node['params']['lang']='ru';

            $feed->setChannelElement('language', $node['params']['lang']);

            if ($interval=$this->_common_obj->select_news_interval($node_id, 0, 10, '', '', false, $active=1))
                {
                foreach ($interval as $news)
                    {
                    //Create an empty FeedItem
                    $newItem = $feed->createNewItem();

                    //Add elements to the feed item
                    //Use wrapper functions to add common feed elements
                    $newItem->setTitle($news['header']);
                    $newItem->setLink($TPA->page_link . '/~shownews/' . $news['id']);
                    //The parameter is a timestamp for setDate() function
                    $date=strtotime($news['sortdate']);
                    $newItem->setDate($date);
                    $newItem->setDescription($news['news_short']);
                    $newItem->addElement('author', 'admin@admi.ru');
                    //Attributes have to passed as array in 3rd parameter
                    $newItem->addElement('guid', 'http://www.ajaxray.com', array('isPermaLink' => 'true'));

                    //Now add the feed item
                    $feed->addItem($newItem);
                    }

                $feed->genarateFeed();
                die();
                }
            }
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

    function show_news_archive($parameters) { return $this->show_news_interval($parameters); }

    function show_news_categories($parameters)
        {
        global $REQUEST_VARS, $TMS;

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));

        if ($categories=$this->_tree->GetChilds(1))
            {
            foreach ($categories as $category)
                {
                $pages = &pages_module_common::getInstance(true);

                if ($parameters['Destination_page'])
                    {
                    $news_server_page=$pages->create_page_path($parameters['Destination_page']);
                    }

                $link=$news_server_page . '/~showncat/id/' . $category['id'];
                $TMS->AddReplace('_news_category', 'Link', $link);
                $TMS->AddReplace('_news_category', 'Category', $category['basic']);
                $TMS->parseSection('_news_category', true);
                }

            return $TMS->parseSection('_news_categories');
            }
        }

    function show_news_by_author($parameters)
        {
        
        global $TMS;
        
        $sl   =isset($REQUEST_ASSOC['sl']) ? (int)$REQUEST_ASSOC['sl'] : 0;
        $count=isset($parameters['OnPage']) ? (int)$parameters['OnPage'] : $_CONFIG['news']['show_news_per_page'];

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['Template']));
        
        $pages = &pages_module_common::getInstance(true);
        
        if ($parameters['Destination_page'])
            {
            $news_server_page=$pages->create_page_path($parameters['Destination_page']);
            }
        else
            {
            $news_server_page=$TPA->page_link;
            }

        $news_list=$this->_common_obj->select_news_by_author($parameters['author_id'], $sl, $count);
      
        $obj_count    =$this->_common_obj->count_news(null, 1, 'and author_id='.$parameters['author_id']);
        
         return $this->render_news($news_list, $news_server_page, null, $obj_count, $count, $sl);
        }

    function shownews($parameters)
        {
        global $REQUEST_VARS, $TMS, $TPA;

      
        if (($nid=$REQUEST_VARS[1])&& ($news=$this->_common_obj->select_news($nid, '%d-%m-%Y %H:%i:%s', true)))
            {
            $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $parameters['MTemplate']));

            
            if ($news['Title'])
                {
                $TPA->externalMeta['Title']=$news['Title'];
                }
   
            if ($news['Description'])
                {
                $TPA->externalMeta['Description']=$news['Description'];
                }

            if ($news['Keywords'])
                {
                $TPA->externalMeta['Keywords']=$news['Keywords'];
                }

            //$cnode=$this->_tree->GetNodeParam($news['ctg_id'],'Tread');
            
            $news_node=array('params' => array('Name' => $news['header']));

            $this->bones_path[news_node]=$news_node;

            $news['tags']               =$this->get_tags($news['tags'], $TPA->page_link, $news['id']);
            
            
            $TMS->AddMassReplace('_news_single', $news);

            return $TMS->parseSection('_news_single');
            }
        else
            {

            //действие по умлочанию
            $parameters['Action']=$parameters['Default_action'];
            unset ($parameters['Default_action']);
            return $this->execute($parameters);
            }
        }

    function showncat($parameters)
        {
        global $REQUEST_ASSOC;

        if ($REQUEST_ASSOC['id'])
            {
            $parameters['Category']=$REQUEST_ASSOC['id'];
            }

        return $this->show_news_interval($parameters);
        }

    function render_news($news_list, $news_server_page, $cat_info, $news_count, $news_on_page, $startpage = 0)
        {
        global $TMS, $REQUEST_ASSOC;
        //DebugBreak();
        // тут правил    
        isset ($REQUEST_ASSOC['s']) ? $s=($REQUEST_ASSOC['s']) : $s=0;
        isset ($REQUEST_ASSOC['e']) ? $e=($REQUEST_ASSOC['e']) : $e=0;

        if ($news_count > 0 && $news_on_page > 0)
            {
            if ($news_count / $news_on_page > 1)
                {
                if ($s && $e)
                    Common::parse_nav_pages($news_count,
                                            $news_on_page,
                                            $startpage,
                                            $news_server_page . '/~newsinterval/id/' . $cat_info['id'] . '/s/' . $s
                                                . '/e/' . $e);
                else
                    Common::parse_nav_pages($news_count, $news_on_page, $startpage,
                                            $news_server_page . '/~showncat/id/' . $cat_info['id']);
                }
            }

        // конец правки             
        $TMS->AddReplace('_news_interval', 'archive_link', $news_server_page);

        $last=sizeof($news_list);
        $i   =1;

        if ($news_list)
            {
            foreach ($news_list as $news)
                {
                $news['link'] = $news_server_page . '/~shownews/' . $news['Basic'];
                $news['tags'] =$this->get_tags($news['tags'], $news_server_page);

                if ($i == $last)
                    {
                    $TMS->AddMassReplace('_news_node', array('islast' => 1));
                    }

                $TMS->AddMassReplace('_news_node', $news);
                $TMS->parseSection('_news_node', true);

                $i++;
                }
            }
        else
            {
            $TMS->parseSection('_news_fail', true);
            }

        $TMS->AddMassReplace('_news_interval', $cat_info);
        return $TMS->parseSection('_news_interval');
        }

    function newsinterval($parameters) { return $this->show_news_interval($parameters); }

    function show_news_interval($parameters)
        {
        global $TMS, $TPA, $REQUEST_ASSOC, $_CONFIG;

        $template         =($parameters['TemplateInterval'])
            ? $parameters['TemplateInterval'] : $parameters['Template'];
        $sl               =isset($REQUEST_ASSOC['sl']) ? (int)$REQUEST_ASSOC['sl'] : 0;
        $count            =isset($parameters['OnPage'])
            ? (int)$parameters['OnPage'] : $_CONFIG['news']['show_news_per_page'];
        $cat_id           =($parameters['Category']) ? $parameters['Category'] : (int)$REQUEST_ASSOC['id'];

        $catInfo          =$this->_tree->getNodeInfo($cat_id);
        $catInfo['header']=$catInfo['basic'];

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $template));

        $where='';

        if (($s=XDATE::convertFromDatePicker(
                    $REQUEST_ASSOC['e'])) && ($e=XDATE::convertFromDatePicker(
                                                     $REQUEST_ASSOC['s'])) && ($TPA->request_action == 'newsinterval'))
            {
            $where                        =" AND date between '$e' and '$s'";
            $parameters['DateIndependent']=1;
            }

        $pages=&pages_module_common::getInstance(true);

        if ($parameters['Destination_page'])
            {
            $news_server_page=$pages->create_page_path($parameters['Destination_page']);
            }
        else
            {
            $news_server_page=$TPA->page_link;
            }

        $this->current['news_server_page']=$news_server_page;
        $this->current['cat_id']          =$cat_id;
        $catInfo['link']                  =$pages->create_page_path($parameters['Destination_page']);

        if ($news_list=$this->_common_obj->select_news_interval($cat_id,
                                                                $sl,
                                                                $count,
                                                                $where,
                                                                $_CONFIG['news']['date_format'],
                                                                $parameters['DateIndependent']))
            {
            $obj_count    =$this->_common_obj->count_news($parameters['Category'], 1, $where); // здесь правка была
            $catInfo['id']=isset($parameters['Category']) ? $parameters['Category'] : (int)$REQUEST_ASSOC['id'];
            }

        return $this->render_news($news_list, $news_server_page, $catInfo, $obj_count, $count, $sl);
        }

    function get_tags($tagsline, $news_server_page, $this_news_id = false)
        {
        global $TMS, $_CONFIG;

        if ($tagsline)
            {
            if ($tags=explode(',', $tagsline))
                {
                foreach ($tags as $tag)
                    {
                    $tag_collection[]=array
                        (
                        'link' => $news_server_page . '/~bytag/tag/' . trim(urlencode(trim($tag))),
                        'tag'  => $tag
                        );
                    }

                return $tag_collection;
                }
            }
        }


    //$intervalback=  интервал в месяцах
    function calendar_interval($news_server_page,$category = '', $intervalback = 12, $show_from_year = null, $show_from_month = null)
        {
        global $TMS, $_CONFIG;
        $yearnow=date('Y');

        if ($show_from_year)
            {
            $yearpast=(int)$show_from_year;
            }
        else
            {
            $yearpast=date('Y', mktime(0, 0, 0, date('n') - $intervalback, 1, $yearnow));
            }

        if ($show_from_month)
            {
            $month_past=(int)$show_from_month;
            }
        else
            {
            $month_past=date('n', mktime(0, 0, 0, date('n') - $intervalback, 1, $yearnow));
            }

        for ($i=$yearpast; $i <= $yearnow; $i++)
            {
            if ($i == $yearnow)
                {
                $e=date('n');
                }
            else
                {
                $e=12;
                }

            for ($j=$month_past; $j <= $e; $j++)
                {
                $monthes[]=array
                    (
                    'link'            => Common::create_action_link($news_server_page, 'newsinterval', array
                        (
                        'id' => $category,
                        's'  => date('d-m-Y', mktime(0, 0, 0, $j, 1, $i)),
                        'e'  => date('d-m-Y', mktime(0, 0, 0, $j + 1, 1, $i))
                        )),
                    'month_date'      => date('d-m-Y', mktime(0, 0, 0, $j, 1, $i)),
                    'month_timestamp' => mktime(0, 0, 0, $j, 1, $i)
                    );
                }

            $years[date("Y", mktime(0, 0, 0, 1, 1, $i))]=$monthes;

            $monthes=array();

            $month_past=1;
            }

        return $years;
        }

    /*
    *$params[0] = тип вывода interval |  from-date
    *$params[1] = интервал в месяцях от текущего
    *$params[2] = отображать от определенного года
    * */
    function call_calendar($params)
        {
        if ($params[0] == 'interval')
            {
            $this->calendar_interval($this->current['news_server_page'], $this->current['cat_id'], $params[1]);
            }
        elseif ($params[0] == 'from-date')
            {
            $this->calendar_interval($this->current['news_server_page'], $this->current['cat_id'], 0, $params[2],
                                     $params[1]);
            }
        }

    function bytag($parameters)
        {
        global $TMS, $REQUEST_ASSOC, $_CONFIG;

        $template=($parameters['TemplateInterval']) ? $parameters['TemplateInterval'] : $parameters['Template'];

        if ((int)$parameters['OnPage'] === 0)
            {
            $parameters['OnPage']=$_CONFIG['news']['show_news_per_page'];
            }

        if (!$REQUEST_ASSOC['tag'])
            {
            $tag=$parameters['tag'];
            }
        else
            {
            $tag=$REQUEST_ASSOC['tag'];
            }

        $TMS->AddFileSection(Common::get_site_tpl($this->_module_name, $template));

        if (!$tag=mysql_real_escape_string(urldecode($tag)))
            {
            return $TMS->parseSection('news_by_tag_fail');
            }

        $count=$this->get_similar_news($tag, $parameters['OnPage'], 0, false, true);

        if ($count[1]['ncount'] > 0)
            {
            $news_list=$this->get_similar_news($tag, $parameters['OnPage'], 0);
            $pages    =&pages_module_common::getInstance(true);

            if ($parameters['Destination_page'])
                {
                $news_server_page=$pages->create_page_path($parameters['Destination_page']);
                }
            else
                {
                $news_server_page=$TPA->page_link;
                }

                
            return $this->render_news($news_list, $news_server_page, null, $count[1]['ncount'], $parameters['OnPage']);
            }
        else
            {
            return $TMS->parseSection('news_by_tag_fail');
            }
        }

    function get_similar_news($tags, $rows_num, $start_row = 0, $this_news_id = false, $count_only = false)
        {
        global $TDB, $_CONFIG;

        if ($tags)
            {
            if (is_array($tags))
                {
                foreach ($tags as $tag)
                    {
                    $tag_str.='tags LIKE "%' . trim($tag) . '%" OR ';
                    }

                $tag_str=substr($tag_str, 0, -3);
                }
            else
                {
                $tag_str='tags LIKE "%' . trim($tags) . '%" ';
                }

            if ($this_news_id !== false)
                {
                $this_news_id=' AND id!=' . $this_news_id;
                }

            if ($count_only)
                {
                $query="SELECT count(id) as ncount FROM news WHERE $tag_str AND active = 1;";
                }
            else
                {
                $query="SELECT id, header,news_short, news_long,author_type,author_id, img_small, tags, NOW( ) AS date_now,  DATE_FORMAT(date,'" . $_CONFIG['news']['date_format'] . "') as news_date,date as sortdate,Basic FROM news WHERE 
                 $tag_str AND active = 1 $this_news_id ORDER BY sortdate DESC LIMIT $start_row,$rows_num";
                }

            return $TDB->get_results($query);
            }
        }

    function show_news_server($parameters)
        {
        global $REQUEST_ASSOC, $TPA;

        if (!in_array($TPA->request_action,$this->_common_obj->front_action_list['show_news_server']['request_actions']))
            {
            //действие по умлочанию
            $parameters['Action']=$parameters['Default_action'];
            unset ($parameters['Default_action']);
            return $this->execute($parameters);
            }
        }
    } #endclass
?>