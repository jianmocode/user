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
use \Xpmse\Service;

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
    function BehaviorStart() {
        
        Utils::cliOnly();
        $se = new Service;
        $service = $se->getByName("Behavior", "xpmsns/user");

        // 默认值:
        // {
        //     "host": "127.0.0.1",
        //     "home": "<your home url>",
        //     "port": 7749,
        //     "user": 0,
        //     "worker_num": 1
        // }
        $config = $service["setting"];
        if ( empty( $config ) ) {
            throw Excp("未找到服务器有效配置", 500, ["config"=>$config]);
        }
        $job = new Job(["name" => "XpmsnsUserBehavior"]);
        $daemonize = false;
        if ( intval($_GET["daemonize"]) == 1 ) {
            $daemonize  = true;
        }
        $config["daemonize"] = $daemonize;
        $job->server($config);

    }

    // 重启
    function BehaviorRestart() {
        
        Utils::cliOnly();
        $job = new Job(["name" => "XpmsnsUserBehavior"]);
        $job->restart();
    }

    // 关闭
    function BehaviorShutdown() {
        Utils::cliOnly();
        $job = new Job(["name" => "XpmsnsUserBehavior"]);
        $job->shutdown();
    }
    

    // 平滑重启
    function BehaviorReload() {
        
        Utils::cliOnly();
        $job = new Job(["name" => "XpmsnsUserBehavior"]);
        $worker_only = false;
        if ( intval($_GET["worker_only"]) == 1 ) {
            $worker_only  = true;
        }
        $job->reload($worker_only);
    }

    // 检查服务器
    function BehaviorInspect(){
        Utils::cliOnly();
        $job = new Job(["name" => "XpmsnsUserBehavior"]);
        $detail = $job->inspect();
        Utils::out( $detail );
    }
    
}