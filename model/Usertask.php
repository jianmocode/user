<?php
/**
 * Class Usertask 
 * 任务副本数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-03 12:54:22
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
          
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Log;
use \Xpmse\Loader\App as App;


class Usertask extends Model {




	/**
	 * 任务副本数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('usertask'); // 数据表名称 xpmsns_user_usertask

        $this->log = new Log("UserTask");
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

    /**
     * 接受指定任务(创建任务副本)
     * @param string $task  任务结构体
     *                  task_id         required
     *                  type            required
     *                  slug            required
     *                  auto_accept     required 
     *                  hourly_limit    ( type == repeatable) ? required
     *                  daily_limit     ( type == repeatable) ? required
     *                  weekly_limit    ( type == repeatable) ? required
     *                  monthly_limit   ( type == repeatable) ? required
     *                  yearly_limit    ( type == repeatable) ? required
     *                  time_limit      ( type == repeatable) ? required
     *                  accept          required
     *                  complete        required
     *                  events          required
     * 
     * @param string $user_id  用户ID
     * @return array 任务副本结构体
     */
    private function accept( $task, $user_id, $force=false ) {

        // 自动接受任务 （忽略处理）
        if ( $task["auto_accept"] == 1 && $force === false) {
            throw new Excp("该任务自动接受，无需调用accpet方法", 404, ["task"=>$task, "user_id"=>$user_id]);
        }

        // 校验任务是否已被接受
        if ( $this->hasAccepted($task, $user_id) ) {
            throw new Excp("用户已接受此项任务", 404, ["task"=>$task, "user_id"=>$user_id]);
        }
        
        // 校验冷却时间
        $err = $this->validateLimit($task, $user_id);
        if ( $err !== true ){
            throw new Excp("达到限额({$err["message"]})", 404, ["task"=>$task, "user_id"=>$user_id, "error"=>$err]);
        }

        // 校验接受条件
        $err = $this->runAcceptScript($task, $user_id);
        if( $err !== true ) {
            throw new Excp("未达到接受条件({$err["message"]})", 404, ["task"=>$task, "user_id"=>$user_id, "error"=>$err]);
        }

         // 如果是每天刷新，则历史任务副本失效
         if( $task["refresh"] == 'daily' ) {
             
            // 将历史副本标记为已完成
            $cnt = $this->statusByUserIdAndTaskId($user_id, $task["task_id"]);
            if ( $cnt != 0 ) {
                throw new Excp("有仍未完成的任务副本(count={$cnt})", 404, ["task"=>$task, "user_id"=>$user_id, "count"=>$cnt]);
            }          
        }

        // 创建任务副本
        return $this->create([
            "user_id"=>$user_id,
            "task_id" => $task["task_id"],
            "process" => 0,
            "status" => "accepted"
        ]);
    }

    /**
     * 批量修改任务状态
     * @param string $user_id  用户ID
     * @param string $task_id  任务ID
     * @return int 等于accepted的副本数量
     */
    public function statusByUserIdAndTaskId( $user_id, $task_id ) {
        // 取消任务副本
        $this->runsql("UPDATE {{table}} SET `status`='completed' WHERE `user_id`=? AND `task_id`=? AND `status`='accepted' ", fasle, [$user_id, $task_id] );
        return $this->query()
                   ->where("user_id", "=", $user_id) 
                   ->where("task_id","=",$task_id)
                   ->where("status", "=", "accepted")
                   ->count("_id")
            ;
    }

    /**
     * 取消指定任务(任务副本)
     * @param string $task_id  任务ID
     * @param string $user_id  用户ID
     * @return array 任务副本结构体
     */
    public function cancel( $usertask_id ) {
        // 取消任务副本
        return $this->updateBy("usertask_id",[
            "usertask_id"=>$usertask_id,
            "status" => "canceled"
        ]);
    }


    /**
     * 运行接受任务脚本, 校验是否达成接受条件
     * @param string $task  任务结构体
     *                  task_id         required
     *                  type            required
     *                  hourly_limit    ( type == repeatable) ? required
     *                  daily_limit     ( type == repeatable) ? required
     *                  weekly_limit    ( type == repeatable) ? required
     *                  monthly_limit   ( type == repeatable) ? required
     *                  yearly_limit    ( type == repeatable) ? required
     *                  time_limit      ( type == repeatable) ? required
     *                  accept          required
     *                  complete        required
     *                  events          required
     * 
     * @param string $user_id  用户ID
     * @return bool 符合条件返回true, 不符合条件返回错误结描述 ["code"=>1024, "message"=>"用户等级不符合要求", "extra"=>[...]]
     */
    private function runAcceptScript( $task, $user_id ) {
        return true;
    }


    /**
     * 校验任务冷却时间
     * @param string $task  任务结构体
     *                  task_id         required
     *                  type            required
     *                  hourly_limit    ( type == repeatable) ? required 
     *                  daily_limit     ( type == repeatable) ? required
     *                  weekly_limit    ( type == repeatable) ? required
     *                  monthly_limit   ( type == repeatable) ? required
     *                  yearly_limit    ( type == repeatable) ? required
     *                  time_limit      ( type == repeatable) ? required
     * 
     * @param string $user_id  用户ID
     * @return mix 未触发配额 true 触发配额 ["limit"=>"hourly", "count"=5, "message"=>"1小时内超过5次"];
     */
    private function validateLimit( $task, $user_id ) {

        if ( $task["type"] == "once") {
            return true;
        }

         // 校验一小时内已接受的任务数量 
         if( $task["hourly_limit"] > 0 ) {
            $cnt = $this->query()
                        ->where("task_id", "=", $task["task_id"])
                        ->where("user_id", "=", $user_id )
                        ->where("created_at" ,">" ,date('Y-m-d H:i:s', time()-3600) )
                        ->where("status" , "=", "accepted" )
                        ->count("usertask_id");
            if ($cnt >= $task["hourly_limit"]) {
                return ["limit"=>"hourly", "count"=>$cnt, "message"=>"一小时内不能超过{$task["hourly_limit"]}次" ];
            }
            return true;
        }

         // 校验一天内已接受的任务数量 
         if( $task["daily_limit"] > 0 ) {
            $cnt = $this->query()
                        ->where("task_id", "=",$task["task_id"])
                        ->where("user_id", "=",$user_id )
                        ->where("created_at", ">", date('Y-m-d 00:00:00') )
                        ->where("created_at", "<", date('Y-m-d 23:59:59') )
                        ->where("status" , "=", "accepted" )
                        ->count("usertask_id");
            
            if ($cnt >= $task["daily_limit"]) {
                return ["limit"=>"daily", "count"=>$cnt, "message"=>"一天内不能超过{$task["daily_limit"]}次"];
            }
            return true;
        }

        // 校验一周内已接受的任务数量 
        if( $task["weekly_limit"] > 0 ) {
            $cnt = $this->query()
                        ->where("task_id", "=",$task["task_id"])
                        ->where("user_id", "=",$user_id )
                        ->where("created_at", ">", date('Y-m-d 00:00:00', strtotime('monday this week')) )
                        ->where("created_at", "<", date('Y-m-d 23:59:59', strtotime('sunday this week')) )
                        ->where("status" , "=", "accepted" )
                        ->count("usertask_id");
            if ($cnt >= $task["weekly_limit"]) {
                return ["limit"=>"weekly", "count"=>$cnt, "message"=>"一周内不能超过{$task["weekly_limit"]}次"];
            }
            return true;
        }

        // 校验一月内已接受的任务数量 
        if( $task["monthly_limit"] > 0 ) {
            $cnt = $this->query()
                        ->where("task_id", "=",$task["task_id"])
                        ->where("user_id", "=",$user_id )
                        ->where("created_at", ">", date('Y-m-01 00:00:00') )
                        ->where("created_at", "<", date('Y-m-t 23:59:59') )
                        ->where("status" , "=", "accepted" )
                        ->count("usertask_id");
            if ($cnt >= $task["monthly_limit"]) {
                return ["limit"=>"monthly", "count"=>$cnt, "message"=>"一月内不能超过{$task["monthly_limit"]}次"];
            }
            return true;
        }

        // 校验一年内已接受的任务数量 
        if( $task["yearly_limit"] > 0 ) {
            $cnt = $this->query()
                        ->where("task_id", "=",$task["task_id"])
                        ->where("user_id", "=",$user_id )
                        ->where("created_at", ">", date('Y-01-01 00:00:00') )
                        ->where("created_at", "<", date('Y-12-31 23:59:59') )
                        ->where("status" , "=", "accepted" )
                        ->count("usertask_id");
            if ($cnt >= $task["yearly_limit"]) {
                return ["limit"=>"yearly", "count"=>$cnt, "message"=>"一年内不能超过{$task["yearly_limit"]}次"];
            }
        }

        return true;
    }



    /**
     * 是否已经接受某项任务
     * @param string $task  任务结构体
     *                  task_id         required
     *                  type            required
     * 
     * @param string $user_id  用户ID
     * @return bool 已接受返回true,  未接受返回false
     */
    private function hasAccepted( $task, $user_id ) {
        // 根据任务类型校验任务是否可接受

        // 单次任务 
        if ( $task["type"] == "once") {
            $rows = $this->query()
                         ->where("task_id", "=", $task["task_id"])
                         ->where("user_id", "=", $user_id )
                         ->where("status", "=", "accepted")
                         ->limit(1)
                         ->select("usertask_id")
                         ->get()->toArray();
            return !empty($rows);
        }

        // 可重复任务
        $qb = $this->query()
                     ->where("task_id", "=", $task["task_id"])
                     ->where("user_id", "=", $user_id )
                     ->where("status", "=", "accepted")
                     ->orderBy("created_at","desc")
                    ;
        
        // 如果是每天刷新，则历史任务副本失效
        if( $task["refresh"] == 'daily' ) {
            $today = date("Y-m-d 00:00:00");
            $qb->where("created_at", ">", $today );
        }

        $rows = $qb->limit(1)
                   ->select("usertask_id", "created_at", "updated_at", "status")
                   ->get()->toArray();
        
        // 从未接受任务
        if ( empty($rows) ) {
            return false;
        }

        $usertask = current( $rows );
        $timeago = time() - strtotime($usertask["created_at"]);

        // 已接受任务，且未完成
        if ($usertask["status"] == "accepted") {

            // 未设定任务完成时限
            if ( $task["time_limit"] <= 0 ) {
                return true;
            }

            // 设定完成实现, 但尚未到达任务时限
            if ( $timeago < $task["time_limit"] ) {
                return true;
            }
        }

        return false;
    }

    /**
     * 接受指定ID任务(创建任务副本)
     * @param string $task_id  任务ID
     * @param string $user_id  用户ID
     * @return array 任务副本结构体
     */
    public function acceptByTaskId( $task_id, $user_id , $force=false) {

        $t = new Task;
        $task = $t->getByTaskId( $task_id );
        if ( empty($task) ) {
            throw new Excp("任务不存在", 404, ["task_id"=>$task_id]);
        }

        if ( $task["status"] != "online" ) {
            throw new Excp("任务已下线", 403, ["task"=>$task]);
        }

        return $this->accept( $task, $user_id, $force );

    }


    /**
     * 接受指定SLUG的任务(创建任务副本)
     * @param string $slug 任务别名
     * @param string $user_id  用户ID
     */
    public function acceptBySlug( $slug, $user_id , $force=false) {
        $t = new Task;
        $task = $t->getBySlug( $slug );
        if ( empty($task) ) {
            throw new Excp("任务不存在", 404, ["slug"=>$slug]);
        }

        if ( $task["status"] != "online" ) {
            throw new Excp("任务已下线", 403, ["task"=>$task]);
        }

        return $this->accept( $task, $user_id, $force );
    }


    /**
     * 读取任务清单
     * @param array $query 查询条件( @see \Xpmsns\User\Model\Task )
     * @param string $user_id  用户ID
     */
    public function getTasks( $query, $user_id ) {

        $select = empty($query['select']) ? ["task.task_id", "task.daily_limit", "task.cover","task.slug", "task.params", "task.name","category.name","task.type","task.process","task.quantity","task.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
        }
        $query['select'] = $select;
        
        $t = new Task();
        $tasks = $t->search( $query );

        if ( $tasks["total"] == 0 ) {
            return $tasks;
        }

        $task_ids = array_column( $tasks["data"], "task_id");

        // 读取任务副本信息
        $qb = $this->query()
                   ->where( "user_id", $user_id )
                   ->whereIn("task_id", $task_ids)
                   ->orderBy("created_at", "desc")     
                ;
         
        $usertasks = $qb->get()
                        ->unique("task_id")
                        ->toArray();
        $map = [];
        array_walk($usertasks, function($task) use( &$map ) {
            $map["{$task['task_id']}"] = $task;
        });

        // 添加任务副本信息 
        foreach( $tasks["data"] as &$task ){
            $usertask = $map["{$task['task_id']}"];

            // 过滤过期任务 (第二天清零)
            $task["usertask"] = null;
            $params = $task["params"];
            if ( empty($params) ) {
                $params = [];
            }

            if ( !isset($params["daily_refresh"]) ) {
                $params["daily_refresh"] = true;
            }

            if ( !empty($usertask) &&  $task["type"] == "repeatable" && $task["daily_limit"] > 0 && $params["daily_refresh"] == true ) {

                $time = strtotime( date("Y-m-d 00:00:00", strtotime($usertask["created_at"])) );
                $today = strtotime(date("Y-m-d 00:00:00"));
                
                // $this->log->info( "usertask={$usertask['usertask_id']} created_at={$usertask["created_at"]} today=".date("Y-m-d H:i:s", $today)." time=".date("Y-m-d H:i:s", $time)." (today - time = ".($today - $time).") ", $params );

                // 超过1天
                if ( ($today - $time) >= 86400 ) { 
                    continue;
                }
            }

            $task["usertask"] = $usertask;
        }

        return $tasks;
    }


    /**
     * 读取任务信息 (按任务别名和用户ID读取最后一条任务副本)
     * @param string $task_slug  任务别名
     * @param string $user_id  用户ID 
     */
    public function getByTaskSlugAndUserId( $task_slug, $user_id ) {

        // 读取任务信息
        $t = new Task();
        $task = $t->getBySlug( $task_slug );
        if ( empty($task) ) {
            return [];
        }

        // 读取任务副本信息
        $task_id = $task["task_id"];
        $qb = $this->query()
                    ->where( "user_id", "=", $user_id )
                    ->where("task_id", "=", $task_id)
                    ->orderBy("created_at", "desc") 
                    ->limit(1)
        ;

        $usertasks = $qb->get()
                ->unique("task_id")
                ->toArray();
            
        $task["usertask"] = [];
        if ( !empty($usertasks) ) {

            $usertask = current($usertasks);
            $task["usertask"] = $usertask;
            $params = $task["params"];
            if ( empty($params) ) {
                $params = [];
            }

            if ( !isset($params["daily_refresh"]) ) {
                $params["daily_refresh"] = true;
            }

            if ( !empty($usertask) &&  $task["type"] == "repeatable" && $task["daily_limit"] > 0 && $params["daily_refresh"] == true ) {

                $time = strtotime( date("Y-m-d 00:00:00", strtotime($usertask["created_at"])) );
                $today = strtotime(date("Y-m-d 00:00:00"));
                
                // $this->log->info( "usertask={$usertask['usertask_id']} created_at={$usertask["created_at"]} today=".date("Y-m-d H:i:s", $today)." time=".date("Y-m-d H:i:s", $time)." (today - time = ".($today - $time).") ", $params );

                // echo "{$usertask["created_at"]} \n";
                // echo date("Y-m-d H:i:s", $time)." \n";
                // echo date("Y-m-d H:i:s", $today)." \n\n";
                // 超过1天
                if ( ($today - $time) >= 86400 ) { 
                    $task["usertask"] = null;
                }
            }
        }
       
       return $task;
   }

    /**
     * 读取任务信息 (按任务ID和用户ID读取最后一条任务副本)
     * @param string $task_slug  任务别名
     * @param string $user_id  用户ID 
     */
    public function getByTaskIdAndUserId( $task_id, $user_id ) {

        // 读取任务信息
        $t = new Task();
        $task = $t->getByTaskId( $task_id );
        if ( empty($task) ) {
            return [];
        }

        // 读取任务副本信息
        $task_id = $task["task_id"];
        $qb = $this->query()
                    ->where( "user_id", "=", $user_id )
                    ->where("task_id", "=", $task_id)
                    ->orderBy("created_at", "desc") 
                    ->limit(1)
        ;

        $usertasks = $qb->get()
                ->unique("task_id")
                ->toArray();
            
        $task["usertask"] = [];
        if ( !empty($usertasks) ) {
            $task["usertask"] = current($usertasks);
        }
       
       return $task;
   }


    /**
     * 设定任务副本进度并发放奖励 (按任务副本ID读取任务副本)
     * @param string $usertask_id  任务副本ID
     * @param int $process 新进度
     * @param bool $force 强制更新(不验证进度数值)
     * @return array 任务副本结构体
     */
    public function processByUsertaskId( $usertask_id, $process, $force=false ) {

        $usertask = $this->getByUsertaskId( $usertask_id );
        if ( empty($usertask) ) {
            throw new Excp("未找到任务副本信息", 404, ["process"=>$process,"usertask_id"=>$usertask_id]);
        }
        $this->process( $usertask, $process, $force );
    }

    /**
     * 设定任务副本进度并发放奖励 (按任务ID和用户ID读取最后一条任务副本)
     * @param string $task_id  任务ID
     * @param string $user_id  用户ID 
     * @param int $process 新进度
     * @return array 任务副本结构体
     */
    public function processByTaskIdAndUserId( $task_id, $user_id, $process ) {

         // 读取任务副本信息
         $qb = $this->query()
                    ->where( "user_id", "=", $user_id )
                    ->where("task_id", "=",$task_id)
                    ->orderBy("created_at", "desc")   
                    ->limit(1)  
        ;

        $usertasks = $qb->get()
                        ->unique("task_id")
                        ->toArray();

        if ( empty($usertasks) ) {
            throw new Excp("未找到任务副本信息", 404, ["process"=>$process,"usertask_id"=>$usertask_id]);
        }

        $this->process( current($usertasks), $process );
    }

    /**
     * 设定任务副本进度并发放奖励 (按任务别名和用户ID读取最后一条任务副本)
     * @param string $task_slug  任务别名
     * @param string $user_id  用户ID 
     * @param int $process 新进度
     * @return array 任务副本结构体
     */
    public function processByTaskSlugAndUserId( $task_slug, $user_id, $process ) {

        // 读取任务副本信息
        $qb = $this->query()
                   ->leftJoin("task as task", "task.task_id", "=", "usertask.task_id")
                   ->where( "user_id", "=", $user_id )
                   ->where("task.slug", "=", $task_slug)
                   ->orderBy("usertask.created_at", "desc")     
                   ->limit(1)
                   ->select( "usertask.*")
       ;
       $usertasks = $qb->get()
                       ->unique("usertask.task_id")
                       ->toArray();

       if ( empty($usertasks) ) {
           throw new Excp("未找到任务副本信息", 404, ["process"=>$process,"usertask_id"=>$usertask_id]);
       }
       
       $this->process( current($usertasks), $process );
   }



    /**
     * 设定任务副本进度并发放奖励
     * @param array $usertask 任务副本结构体
     *                  usertask_id required 任务副本ID
     *                  task_id     required 任务ID
     *                  user_id     required 用户ID 
     *                  process     required 当前进度
     * @param int $process 新进度
     * @param bool $force 强制更新(不验证进度数值)
     * @return array 任务副本结构体
     */
    private function process( $usertask, $process, $force=false ) {

        $usertask_id = $usertask["usertask_id"];
        $user_id = $usertask["user_id"];
        $task_id = $usertask["task_id"];

        if ( empty($usertask_id) ){
            throw new Excp("未提供任务副本ID", 402, ["process"=>$process,"usertask"=>$usertask]);
        }

        if ( empty($user_id) ){
            throw new Excp("未提供用户ID", 402, ["process"=>$process,"usertask"=>$usertask]);
        }

        if ( empty($task_id) ){
            throw new Excp("未提供任务ID", 402, ["process"=>$process,"usertask"=>$usertask]);
        }

        $t = new Task;
        $task = $t->getByTaskId( $task_id );

        // 格式化数据
        $process = intval($process);
        $task["process"] = intval($task["process"]);
        $usertask["process"] = intval($usertask["process"]);

        // 任务已完成
        if ( "completed" == $usertask["status"] ){
            throw new Excp("任务已经完成 ( usertask_id = {$usertask["usertask_id"]} )", 402, ["status"=>$usertask["status"], "usertask"=>$usertask]);
        }
        
        // 非法步骤
        if ( ($process > $task["process"] || $process  < 1 ) && !$force ) {
            throw new Excp("步骤信息不合法 ( process = {$process}  process > {$task['process']} 或 process < 1 )", 402, ["process"=>$process, "max"=>$task["process"], "current"=>$usertask["process"]]);
        }

        // 不可以后退
        if (( $process <= $usertask["process"]) && !$force ) {
            throw new Excp("步骤信息不合法  ( process = {$process}  process <= {$usertask['process']} ) ", 402, ["process"=>$process,"max"=>$task["process"], "current"=>$usertask["process"]]);
        }

        


        // 发放当前步骤奖励积分
        $current = $process - 1;
        $quantity = intval($task["quantity"][$current]);
        if ( $quantity  > 0 ) { // 增加积分
            $pay = new \Xpmsns\User\Model\Coin();
            $coin = $pay->create([
                "user_id" => $user_id,
                "quantity" => $quantity,
                "type" => "increase",
                "outer_id" => $usertask_id,
                "origin" => "usertask",
                "snapshot" => ["type"=>"task", "usertask_id"=>$usertask_id, "data"=>$usertask],
            ]);
        }

        // 更新进度
        $data = [
            "usertask_id" => $usertask_id,
            "process" => $process,
        ];

        // 标记为完成
        if ( $process == $task["process"] ){
            $data["status"] = "completed";
        }

        return $this->updateBy("usertask_id", $data );
    }



    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 用户任务ID
		$this->putColumn( 'usertask_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 任务ID
		$this->putColumn( 'task_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 进度
		$this->putColumn( 'process', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 数据
		$this->putColumn( 'data', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"accepted" => [
		  			"value" => "accepted",
		  			"name" => "已接受",
		  			"style" => "primary"
		  		],
		  		"completed" => [
		  			"value" => "completed",
		  			"name" => "已完成",
		  			"style" => "success"
		  		],
		  		"canceled" => [
		  			"value" => "canceled",
		  			"name" => "已取消",
		  			"style" => "muted"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按用户任务ID查询一条任务副本记录
	 * @param string $usertask_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["usertask_id"],  // 用户任务ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["task_id"],  // 任务ID 
	 *                $rs["task_task_id"], // task.task_id
	 *          	  $rs["process"],  // 进度 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["data"],  // 数据 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["task_created_at"], // task.created_at
	 *                $rs["task_updated_at"], // task.updated_at
	 *                $rs["task_slug"], // task.slug
	 *                $rs["task_name"], // task.name
	 *                $rs["task_type"], // task.type
	 *                $rs["task_summary"], // task.summary
	 *                $rs["task_cover"], // task.cover
	 *                $rs["task_quantity"], // task.quantity
	 *                $rs["task_formula"], // task.formula
	 *                $rs["task_hourly_limit"], // task.hourly_limit
	 *                $rs["task_daily_limit"], // task.daily_limit
	 *                $rs["task_weekly_limit"], // task.weekly_limit
	 *                $rs["task_monthly_limit"], // task.monthly_limit
	 *                $rs["task_yearly_limit"], // task.yearly_limit
	 *                $rs["task_time_limit"], // task.time_limit
	 *                $rs["task_process"], // task.process
	 *                $rs["task_accept"], // task.accept
	 *                $rs["task_complete"], // task.complete
	 *                $rs["task_events"], // task.events
	 *                $rs["task_status"], // task.status
	 *                $rs["task_auto_accept"], // task.auto_accept
	 *                $rs["task_categories"], // task.categories
	 */
	public function getByUsertaskId( $usertask_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户
		$qb->where('usertask_id', '=', $usertask_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

  
  
		return $rs;
	}

		

	/**
	 * 按用户任务ID查询一组任务副本记录
	 * @param array   $usertask_ids 唯一主键数组 ["$usertask_id1","$usertask_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务副本记录MAP {"usertask_id1":{"key":"value",...}...}
	 */
	public function getInByUsertaskId($usertask_ids, $select=["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.process","task.process","usertask.status","usertask.created_at","usertask.updated_at"], $order=["usertask.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户
		$qb->whereIn('usertask.usertask_id', $usertask_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['usertask_id']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按用户任务ID保存任务副本记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUsertaskId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("usertask_id", $data, ["usertask_id"], ['_id', 'usertask_id']);
		return $this->getByUsertaskId( $rs['usertask_id'], $select );
	}


	/**
	 * 添加任务副本记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["usertask_id"]) ) { 
			$data["usertask_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排任务副本记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 任务副本记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.process","task.process","usertask.status","usertask.created_at","usertask.updated_at"], $order=["usertask.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


  		foreach ($data as & $rs ) {
			$this->format($rs);
			
  		}

  
		return $data;
	
	}


	/**
	 * 按条件检索任务副本记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.process","task.process","usertask.status","usertask.created_at","usertask.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["usertask_id"] 按用户任务ID查询 ( = )
	 *			      $query["user_id"] 按用户ID查询 ( = )
	 *			      $query["task_id"] 按任务ID查询 ( = )
	 *			      $query["process"] 按进度查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 任务副本记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["usertask_id"],  // 用户任务ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["task_id"],  // 任务ID 
	 *               	["task_task_id"], // task.task_id
	 *               	["process"],  // 进度 
	 *               	["status"],  // 状态 
	 *               	["data"],  // 数据 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["user_created_at"], // user.created_at
	 *               	["user_updated_at"], // user.updated_at
	 *               	["user_group_id"], // user.group_id
	 *               	["user_name"], // user.name
	 *               	["user_idno"], // user.idno
	 *               	["user_idtype"], // user.idtype
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
	 *               	["user_mobile"], // user.mobile
	 *               	["user_mobile_nation"], // user.mobile_nation
	 *               	["user_mobile_full"], // user.mobile_full
	 *               	["user_email"], // user.email
	 *               	["user_contact_name"], // user.contact_name
	 *               	["user_contact_tel"], // user.contact_tel
	 *               	["user_title"], // user.title
	 *               	["user_company"], // user.company
	 *               	["user_zip"], // user.zip
	 *               	["user_address"], // user.address
	 *               	["user_remark"], // user.remark
	 *               	["user_tag"], // user.tag
	 *               	["user_user_verified"], // user.user_verified
	 *               	["user_name_verified"], // user.name_verified
	 *               	["user_verify"], // user.verify
	 *               	["user_verify_data"], // user.verify_data
	 *               	["user_mobile_verified"], // user.mobile_verified
	 *               	["user_email_verified"], // user.email_verified
	 *               	["user_extra"], // user.extra
	 *               	["user_password"], // user.password
	 *               	["user_pay_password"], // user.pay_password
	 *               	["user_status"], // user.status
	 *               	["task_created_at"], // task.created_at
	 *               	["task_updated_at"], // task.updated_at
	 *               	["task_slug"], // task.slug
	 *               	["task_name"], // task.name
	 *               	["task_type"], // task.type
	 *               	["task_summary"], // task.summary
	 *               	["task_cover"], // task.cover
	 *               	["task_quantity"], // task.quantity
	 *               	["task_formula"], // task.formula
	 *               	["task_hourly_limit"], // task.hourly_limit
	 *               	["task_daily_limit"], // task.daily_limit
	 *               	["task_weekly_limit"], // task.weekly_limit
	 *               	["task_monthly_limit"], // task.monthly_limit
	 *               	["task_yearly_limit"], // task.yearly_limit
	 *               	["task_time_limit"], // task.time_limit
	 *               	["task_process"], // task.process
	 *               	["task_accept"], // task.accept
	 *               	["task_complete"], // task.complete
	 *               	["task_events"], // task.events
	 *               	["task_status"], // task.status
	 *               	["task_auto_accept"], // task.auto_accept
	 *               	["task_categories"], // task.categories
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.process","task.process","usertask.status","usertask.created_at","usertask.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("usertask.usertask_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keyword']}%");
				$qb->orWhere("user.name","like", "%{$query['keyword']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keyword']}%");
				$qb->orWhere("task.task_id","like", "%{$query['keyword']}%");
				$qb->orWhere("task.slug","like", "%{$query['keyword']}%");
				$qb->orWhere("task.name","like", "%{$query['keyword']}%");
			});
		}


		// 按用户任务ID查询 (=)  
		if ( array_key_exists("usertask_id", $query) &&!empty($query['usertask_id']) ) {
			$qb->where("usertask.usertask_id", '=', "{$query['usertask_id']}" );
		}
		  
		// 按用户ID查询 (=)  
		if ( array_key_exists("user_id", $query) &&!empty($query['user_id']) ) {
			$qb->where("usertask.user_id", '=', "{$query['user_id']}" );
		}
		  
		// 按任务ID查询 (=)  
		if ( array_key_exists("task_id", $query) &&!empty($query['task_id']) ) {
			$qb->where("usertask.task_id", '=', "{$query['task_id']}" );
		}
		  
		// 按进度查询 (=)  
		if ( array_key_exists("process", $query) &&!empty($query['process']) ) {
			$qb->where("usertask.process", '=', "{$query['process']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("usertask.status", '=', "{$query['status']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("usertask.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("usertask.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$usertasks = $qb->select( $select )->pgArray($perpage, ['usertask._id'], 'page', $page);

  		foreach ($usertasks['data'] as & $rs ) {
			$this->format($rs);
			
  		}

  	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$usertasks['_sql'] = $qb->getSql();
			$usertasks['query'] = $query;
		}

		return $usertasks;
	}

	/**
	 * 格式化读取字段
	 * @param  array $select 选中字段
	 * @return array $inWhere 读取字段
	 */
	public function formatSelect( & $select ) {
		// 过滤 inWhere 查询字段
		$inwhereSelect = []; $linkSelect = [];
		foreach ($select as $idx=>$fd ) {
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "usertask." .$select[$idx];
				continue;
			}
			
			//  连接用户 (user as user )
			if ( trim($fd) == "user.*" || trim($fd) == "user.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "user.{$field} as user_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "usertask.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "user.") === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			
			//  连接用户 (task as task )
			if ( trim($fd) == "task.*" || trim($fd) == "task.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\Task", 'getFields') ) {
					$fields = \Xpmsns\User\Model\Task::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "task.{$field} as task_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "usertask.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "task." ) === 0 ) {
				$as = str_replace('task.', 'task_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "task.") === 0 ) {
				$as = str_replace('task.', 'task_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

		}

		// filter 查询字段
		foreach ($inwhereSelect as & $iws ) {
			if ( is_array($iws) ) {
				$iws = array_unique(array_filter($iws));
			}
		}

		$select = array_unique(array_merge($linkSelect, $select));
		return $inwhereSelect;
	}

	/**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"usertask_id",  // 用户任务ID
			"user_id",  // 用户ID
			"task_id",  // 任务ID
			"process",  // 进度
			"status",  // 状态
			"data",  // 数据
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>