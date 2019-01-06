<?php
/**
 * Class CoinController
 * 积分控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-28 13:00:33
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Job;
use \Xpmse\Option;

class RunController extends \Xpmse\Loader\Controller {


	function __construct() {
    }

    /**
     * Behavior 队列服务器
     * 
     * @server : 
     *     xpm app run Run.php BehaviorServer --vv
     * 
     * @client :
     *   $slug = "helloworld";
     *   $job = new Job(["name"=>"Behavior"]);
     *   if ( $job->isRunning($slug) ) {
     *       // job is running
     *       return true;
     *   }
     *   $job_id = $job->call( $slug, "SomeModel", "SomeMethod", $args... );
     */
    function BehaviorServer() {
        
        Utils::cliOnly();
        $opt = new Option('xpmsns/user');

        // 默认值:
        // {
        //     "host": "127.0.0.1",
        //     "home": "<your home url>",
        //     "port": 7749,
        //     "user": 0,
        //     "worker_num": 1
        // }
        $config = $opt->get("user/server/behavior");
        if ( empty( $config ) ) {
            throw Excp("未找到服务器有效配置", 500, ["config"=>$config]);
        }
        $job = new Job(["name" => "Behavior"]);
        // $config["daemonize"] = 1;
        $job->server($config);

    }


    function BehaviorShutdown() {
        Utils::cliOnly();
        $job = new Job(["name" => "Behavior"]);
        $job->shutdown();
    }

    function BehaviorReload() {
        
        Utils::cliOnly();
        $job = new Job(["name" => "Behavior"]);
        $worker_only = false;
        if ( intval($_GET["worker_only"]) == 1 ) {
            $worker_only  = true;
        }
        var_dump($worker_only);
        $job->reload($worker_only);
    }
    
}