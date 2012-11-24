<?php
ignore_user_abort(1); 


preg_match('@^(?:www.)?([^/]+)@i', $_SERVER['HTTP_HOST'], $m);
define('REAL_HTTP_HOST', $m[1]);

require_once ('inc/core.php');
require_once ('conf/init.php');

$TMS=new TMutiSection();
$TDB=new Tmysqllayer(DB_USER, DB_PASS, DB_NAME, DB_HOST);
$TPA=new TPageAgregator();

$TMS->registerHandlerObj('D', new DEBUG());
$TMS->registerHandlerObj('E', new ENHANCE());
$TMS->registerHandlerObj('MAIN', new MAIN());

/*
* x3_cron implements crons  
* cron status:
* #1-pending
* #2-inprogress
* #3-finished
*/

class x3_cron
    {
    private $tasks;

    static private $instance;
    private $chain;
    static function getInstance()
        {
        if (!self::$instance)
            {
            self::$instance=new x3_cron();
            }

        return self::$instance;
        }

    public final function __clone() { trigger_error("Cannot clone instance of Singleton pattern", E_USER_ERROR); }

    /*
    * $task=array('module'=>'modulename',
    *             'method'=>array('task_method',(array)$task_params),
    *             'status'=> 1
    *             'starttime'=> //this parameter assign's on add
    *    );
    * 
    * cron status:
    * 1-pending
    * 2-inprogress
    * 3-finished
    */

    function set_chain($chain)
    {
         $this->chain=$chain;        
    }
    
    function chain()
    {
        return $this->chain;
    }
    
    function set_tasks($t)
        {
            $this->tasks=$t;
        }

    function get_tasks() { return $this->tasks; }

    public function add_task($modulename, $method)
        {
        $taskid=md5(time());

        $this->tasks[$taskid]=array
            (
            'module' => $modulename,
            'method' => $method,
            'status' => 1
            );

        return $taskid;
        }

    function start()
        {
        if ($this->tasks)
            {
            foreach ($this->tasks as $taskid => $module_task)
                {
                if ($m=Common::module_factory($module_task['module'] . '.cron'))
                    {
                    if ($module_task['status'] != 3)
                        {
                        $method_name=key($module_task['method']);
                        
                        $this->tasks[$taskid]['status']
                            =$m->$method_name($module_task['method'][$method_name], $this->tasks[$taskid]['status']);
                        }
                    }
                }
            }
        }
    }


$X3_SESSION  =new x3_file_session();
$X3_CHAINCALL=new x3_chaincall('cron.php');

$X3_CRON     =x3_cron::getInstance();
//---------------------

$X3_CRON->add_task('backup', array('restore_by_cron' => array
    (
      'file'=>'demo.sql'
    )));

    
$X3_CRON->start();
$X3_SESSION->serialize_session();




sleep(1);
if($X3_CRON->chain())
{
    $TDB->close();
    $X3_CHAINCALL->chain(array('x3sid' => $X3_SESSION->get_session_id()));
}else{
   $X3_SESSION->data=null; 
}
?>