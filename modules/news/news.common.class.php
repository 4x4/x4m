<?php
class news_module_common
    extends x3_module_common
    {
    static private $instance;

    static function getInstance($front_call = null)
        {
        if (!self::$instance)
            {
            self::$instance=new news_module_common($front_call);
            }

        return self::$instance;
        }

    public final function __clone() { trigger_error("Cannot clone instance of Singleton pattern", E_USER_ERROR); }

    function news_module_common()
        {
        if (self::$instance)
            trigger_error("Cannot call instance of Singleton pattern", E_USER_ERROR);

        parent::x3_module_common();

        $this->set_obj_tree('news_container');
        //$this->obj_tree->UniqueBasics(1);

        $this->obj_tree->setObject('_ROOT', array
            (
            'LastModified',
            'bNewsChunkSize',
            'fNewsChunkSize'
            ));

        $this->obj_tree->setObject('_NEWSGROUP', array
            (
            'Tread',
            'LastModified',
            'bNewsChunkSize',
            'fNewsChunkSize'
            ),                     '_ROOT');

        $this->define_front_actions();
        }

    function select_news_by_author($author_id, $start_row, $rows_num, $date_format = '%d.%m.%Y %H:%i:%s')
        {
        global $TDB;

        $limit=($rows_num) ? " LIMIT $start_row, $rows_num" : '';
        $query=
            "SELECT id, header,Basic,news_short, news_long, img_small, image_folder, tags, NOW( ) AS date_now,  DATE_FORMAT(date,'"
            . $date_format . "') as news_date,date as sortdate,author_type,author_id FROM news WHERE author_id = '$author_id' and active=1 ORDER BY sortdate DESC"
            . $limit;
        return $TDB->get_results($query);
        }

    function select_news_interval($category_id, $start_row, $rows_num,
                                      $where = '', $date_format = '%d.%m.%Y %H:%i:%s', $today_only = true, $active = 1)
        {
        global $TDB;

        if ($today_only)
            {
            $today_only='';
            }
        else
            {
            $today_only=' AND date <= NOW()';
            }

        $category_id=isset($category_id) ? "AND ctg_id=$category_id" : '';

        $limit      =($rows_num) ? " LIMIT $start_row, $rows_num" : '';

        $query      =
            "SELECT id, header,Basic,news_short, news_long, img_small, image_folder, tags, NOW( ) AS date_now,  DATE_FORMAT(date,'"
            . $date_format . "') as news_date,date as sortdate,author_type,author_id FROM news WHERE active = $active $category_id $today_only  $where ORDER BY sortdate DESC"
            . $limit;

        return $TDB->get_results($query);
        }

    function count_news($category_id, $active = '1', $where = '')
        {
        global $TDB;
        $category_id=($category_id) ? "AND ctg_id=$category_id" : '';
        $query      ="SELECT count(id) as ncount FROM news WHERE active = $active $category_id  $where";
        $result     =$TDB->get_results($query);
        return $result[1]['ncount'];
        }

    function select_news($id, $date_format = '%d.%m.%Y %H:%i:%s', $is_basic = false)
        {
        global $TDB;

        if ($is_basic)
            {
            $where="Basic='$id'";
            }
        else
            {
            $where="id=$id";
            }

        $query=
            'SELECT id,ctg_id, header,Basic,news_short, news_long, img_small, image_folder,author_type,author_id, tags, DATE_FORMAT( date, "'
            . $date_format . '" )  AS news_date, Title,Keywords,Description FROM `news` WHERE ' . $where;
        $r    =$TDB->get_results($query);
        return $r[1];
        }

    function get_author($id, $type)
        {
        if (!$id)
            return;

        if ($type == 'fusers')
            {
            Common::call_common_instance('fusers');
            $ci=fusers_module_common::getInstance();
            }
        elseif ($type == 'users')
            {
            Common::call_common_instance('users');
            $ci=users_module_common::getInstance();
            }

        return $ci->obj_tree->getNodeInfo($id);
        }

    function get_categories($params_list)
        {
        if (is_array($params_list))
            {
            $ape['objType'] == array('_NEWSGROUP');
            return $this->source->GetChildsParam(1, $params_list, true, $ape);
            }
        }

    function show_news_interval_extra($params)
        {
        $node            =$this->obj_tree->GetNodeStruct($params['Category']);
        $params['_Extra']=$node['basic'];
        return $params;
        }

    function define_front_actions()
        {
        $l=Common::get_module_lang('news', $_SESSION['lang'], 'define_front_actions');
        $this->def_action('show_news_categories', $l['{show_news_categories}'], '');
        $this->def_action('show_news_interval', $l['{show_news_interval}'], '');
        $this->def_action('show_news_by_author', $l['{show_news_by_author}'], '');
        $this->def_action('show_news_server', $l['{show_news_server}'], array
            (
            'shownews',
            'showncat',
            'bytag',
            'newsinterval',
            'rss'
            ));
        }
    }
?>