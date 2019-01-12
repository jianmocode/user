<?php
/**
 * Class Checkin 
 * 签到数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 17:47:37
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Job;
use \Xpmse\Loader\App as App;


class Checkin extends Model {




	/**
	 * 签到数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('checkin'); // 数据表名称 xpmsns_user_checkin

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 签到初始化( 注册行为/注册任务/设置默认值等... )
     */
    public function __defaults() {

        // 注册任务
        $tasks = [
            [
                "name"=>"签到任务", "slug"=>"checkin", "type"=>"repeatable",
                "daily_limit"=>1, "process"=>7, 
                "quantity" => [100,200,300,400,500,600,700],
                "auto_accept" => 1,
                "accept" => ["class"=>"\\xpmsns\\user\\model\\checkin", "method"=>"onCheckinAccpet"],
                "status" => "online",
            ]
        ];

        // 注册行为
        $behaviors =[
            [
                "name" => "用户签到", "slug"=>"xpmsns/user/checkin/create",
                "intro" =>  "本行为当用户签到成功后触发",
                "params" => ["checkin_id"=>"签到ID", "lng"=>"经度", "lat"=>"维度", "alt"=>"海拔", "time"=>"签到时刻", "device"=>"签到设备", "location"=>"位置", "history"=>"最近7条签到记录"],
                "status" => "online",
            ]
        ];

        // 订阅行为( 响应任务处理 )
        $subscribers =[
            [
                "name" => "签到任务",
                "behavior_slug"=>"xpmsns/user/checkin/create",
                "outer_id" => "checkin",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\user\\model\\checkin", "method"=>"onCheckinChange"],
                "status" => "on",
            ]
        ];

        $t = new \Xpmsns\User\Model\Task();
        $b = new \Xpmsns\User\Model\Behavior();
        $s = new \Xpmsns\User\Model\Subscriber();

        foreach( $tasks as $task ){
            try { $t->create($task); } catch( Excp $e) { $e->log(); }
        }

        foreach( $behaviors as $behavior ){
            try { $b->create($behavior); } catch( Excp $e) { $e->log(); }
        }
        foreach( $subscribers as $subscriber ){
            try { $s->create($subscriber); } catch( Excp $e) { $e->log(); }
        }
        
    }

    
    /**
     * 任务接受响应: 签到任务接受响应 (验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onCheckinAccpet(){
        return true;
    }

    /**
     * 订阅器:签到任务(签到行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(用户签到)数据结构
     * @param array $subscriber  订阅者(签到任务订阅) 数据结构  ["outer_id"=>"任务SLUG", "origin"=>"task" ... ]
     * @param array $data  行为数据 ["checkin_id"=>"签到ID", "lng"=>"经度", "lat"=>"维度", "alt"=>"海拔", "time"=>"签到时刻", "device"=>"签到设备", "location"=>"位置", "history"=>"最近7条签到记录"]
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function onCheckinChange( $behavior, $subscriber, $data, $env ) {

        // echo "onCheckinChange: {$task_slug} -> {$user_id} \n";
        $task_slug = $subscriber["outer_id"];
        $user_id = $env["user_id"];

        // 验证数据清单
        if ( empty($data["history"]) || !is_array($data["history"] ) ) {
            throw new Excp("签到数据异常", 402, ["task_slug"=>$task_slug, "user_id"=>$user_id, "history"=>$history]);
        }

        // 读取任务
        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 连续签到算法
        $last_ci = current($data["history"]);
        if ( empty($last_ci) ) {
            return;
        }

        $job = new Job(["name"=>"XpmsnsUserBehavior"]);
        
        // 最近7天日期
        $last_7days = [];
        $last_time = strtotime(date("Y-m-d 00:00:00", strtotime($last_ci["time"])));
        for( $i=0; $i<7; $i++) {

            $last_7days[$i] = $last_time - 86400 * $i;

            // DEBUG
            $job->info( "last_7days[{$i}]:" . $last_7days[$i] . ' ' . date('Y-m-d H:i:s', $last_7days[$i]) );
        }

        // 判断连续签到
        for( $i=0; $i<7; $i++) { 
            
            $ci = $data["history"][$i];
            if ( empty($ci) ) { 
                $job->info("? empty($ci): process=" . $process );
                break; 
            }

            $process = $i + 1;

            // 历史时间对比
            $curr = strtotime(date("Y-m-d 00:00:00", strtotime($ci["time"])));
            if ( $last_7days[$i]  != $curr ) {
                $process = $process -1;
                $job->info("? $last_7days[$i] != {$curr} :" .  ' ' . date('Y-m-d H:i:s', $last_7days[$i]) .  ' != ' . date('Y-m-d H:i:s', $curr) . "  process=" . $process  );
                break;
            }

            // DEBUG
            $job->info("? $last_7days[$i] == {$curr}  :" .  ' ' . date('Y-m-d H:i:s', $last_7days[$i]) .  ' == ' . date('Y-m-d H:i:s', $curr)  . "  process=" . $process  );
        }

        

        // 超过7天重置到第一天
        $force = false;  // 是否强制重置
        if ( $process >= 7 ) {
            $process = 1;
            $force = true;
        }

        // DEBUG
        $job->info( "result: process=" . $process . " force={$force}");


        // // 计算当前累计步骤
        // $process = 1; $next = null; 
        // foreach( $data["history"] as $ci ){
        //     $curr = strtotime(date("Y-m-d 00:00:00", strtotime($ci["time"])));
        //     $curr_shouldbe = strtotime(date("Y-m-d 00:00:00", strtotime("-1d",$next)));
        //     // echo  "\tcurr=".   date("Y-m-d H:i:s",$curr) .  "  curr_shouldbe=". date("Y-m-d H:i:s",$curr_shouldbe). " process={$process} \n";
        //     if ( $next == null ) {
        //         $next = $curr;
        //         continue;
        //     } else if ( intval($curr_shouldbe)  == intval($next) ) {
        //         $process++;
        //     } else {
        //         break;
        //     }
        // }

       

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        // 设定进展并发放奖励
        $t->processByUsertaskId( $usertask["usertask_id"], $process, $force );
        
        // print_r( $usertask );
        // print_r( $task );
        // print_r( $data );
        // print_r( $env );
        // echo "\t----- end -----\n";
    }


    /**
     * 触发用户行为(通知所有该行为订阅者)
     * @param string $slug 用户行为别名
     * @param array $data 行为数据
     * @return null
     */
    function triggerBehavior( $slug, $data=[] ) {

        // 许可行为
        $allowed = [
            // 用户签到 ["checkin_id"=>"签到ID", "lng"=>"经度", "lat"=>"维度", "alt"=>"海拔", "time"=>"签到时刻", "device"=>"签到设备", "location"=>"位置", "history"=>"最近7条签到记录"]
            "xpmsns/user/checkin/create",
        ];
        if ( !in_array($slug,$allowed) ){
            return;
        }

        // 创建用户对象
        try {
            $u = new \Xpmsns\User\Model\User;
        } catch( Excp $e) { return; }

        $uinfo = $u->getUserInfo();        
        if ( empty($uinfo["user_id"]) ) {
            return;
        }
        
        try {
            $behavior = new \Xpmsns\User\Model\Behavior;
        } catch( Excp $e) { return; }

        // 执行行为(通知所有该行为订阅者)
        try {
            $env = $behavior->getEnv();
            $behavior->runBySlug($slug, $data, $env );
        }catch(Excp $e) {}
    }

    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 签到ID
		$this->putColumn( 'checkin_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 签到时刻
		$this->putColumn( 'time', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 经度
		$this->putColumn( 'lng', $this->type("float", ["index"=>true, "null"=>true]));
		// 纬度
		$this->putColumn( 'lat', $this->type("float", ["index"=>true, "null"=>true]));
		// 海拔
		$this->putColumn( 'alt', $this->type("float", ["index"=>true, "null"=>true]));
		// 签到地点
		$this->putColumn( 'location', $this->type("string", ["length"=>600, "null"=>true]));
		// 设备
		$this->putColumn( 'device', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
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


 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按签到ID查询一条签到记录
	 * @param string $checkin_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["checkin_id"],  // 签到ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["time"],  // 签到时刻 
	 *          	  $rs["lng"],  // 经度 
	 *          	  $rs["lat"],  // 纬度 
	 *          	  $rs["alt"],  // 海拔 
	 *          	  $rs["location"],  // 签到地点 
	 *          	  $rs["device"],  // 设备 
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
	 */
	public function getByCheckinId( $checkin_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "checkin.checkin_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_checkin as checkin", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "checkin.user_id"); // 连接用户
		$qb->where('checkin_id', '=', $checkin_id );
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
	 * 按签到ID查询一组签到记录
	 * @param array   $checkin_ids 唯一主键数组 ["$checkin_id1","$checkin_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 签到记录MAP {"checkin_id1":{"key":"value",...}...}
	 */
	public function getInByCheckinId($checkin_ids, $select=["checkin.checkin_id","user.user_id","user.name","user.nickname","user.mobile","checkin.time","checkin.location","checkin.lng","checkin.lat","checkin.alt"], $order=["checkin.time"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "checkin.checkin_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_checkin as checkin", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "checkin.user_id"); // 连接用户
		$qb->whereIn('checkin.checkin_id', $checkin_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['checkin_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按签到ID保存签到记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByCheckinId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "checkin.checkin_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("checkin_id", $data, ["checkin_id"], ['_id', 'checkin_id']);
		return $this->getByCheckinId( $rs['checkin_id'], $select );
	}


	/**
	 * 添加签到记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["checkin_id"]) ) { 
			$data["checkin_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排签到记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 签到记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["checkin.checkin_id","user.user_id","user.name","user.nickname","user.mobile","checkin.time","checkin.location","checkin.lng","checkin.lat","checkin.alt"], $order=["checkin.time"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "checkin.checkin_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_checkin as checkin", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "checkin.user_id"); // 连接用户


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
	 * 按条件检索签到记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["checkin.checkin_id","user.user_id","user.name","user.nickname","user.mobile","checkin.time","checkin.location","checkin.lng","checkin.lat","checkin.alt"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["checkin_id"] 按签到ID查询 ( = )
	 *			      $query["user_user_id"] 按查询 ( = )
	 *			      $query["lng"] 按经度查询 ( = )
	 *			      $query["lat"] 按纬度查询 ( = )
	 *			      $query["time_after"] 按签到时刻查询 ( >= )
	 *			      $query["time_before"] 按签到时刻查询 ( <= )
	 *			      $query["orderby_time_desc"]  按签到时间倒序 DESC 排序
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *           
	 * @return array 签到记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["checkin_id"],  // 签到ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["time"],  // 签到时刻 
	 *               	["lng"],  // 经度 
	 *               	["lat"],  // 纬度 
	 *               	["alt"],  // 海拔 
	 *               	["location"],  // 签到地点 
	 *               	["device"],  // 设备 
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
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["checkin.checkin_id","user.user_id","user.name","user.nickname","user.mobile","checkin.time","checkin.location","checkin.lng","checkin.lat","checkin.alt"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "checkin.checkin_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_checkin as checkin", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "checkin.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("checkin.checkin_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("checkin.time","like", "%{$query['keyword']}%");
				$qb->orWhere("checkin.lng","like", "%{$query['keyword']}%");
				$qb->orWhere("checkin.lat","like", "%{$query['keyword']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keyword']}%");
				$qb->orWhere("user.email","like", "%{$query['keyword']}%");
				$qb->orWhere("user.name","like", "%{$query['keyword']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keyword']}%");
			});
		}


		// 按签到ID查询 (=)  
		if ( array_key_exists("checkin_id", $query) &&!empty($query['checkin_id']) ) {
			$qb->where("checkin.checkin_id", '=', "{$query['checkin_id']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_user_id", $query) &&!empty($query['user_user_id']) ) {
			$qb->where("user.user_id", '=', "{$query['user_user_id']}" );
		}
		  
		// 按经度查询 (=)  
		if ( array_key_exists("lng", $query) &&!empty($query['lng']) ) {
			$qb->where("checkin.lng", '=', "{$query['lng']}" );
		}
		  
		// 按纬度查询 (=)  
		if ( array_key_exists("lat", $query) &&!empty($query['lat']) ) {
			$qb->where("checkin.lat", '=', "{$query['lat']}" );
		}
		  
		// 按签到时刻查询 (>=)  
		if ( array_key_exists("time_after", $query) &&!empty($query['time_after']) ) {
			$qb->where("checkin.time", '>=', "{$query['time_after']}" );
		}
		  
		// 按签到时刻查询 (<=)  
		if ( array_key_exists("time_before", $query) &&!empty($query['time_before']) ) {
			$qb->where("checkin.time", '<=', "{$query['time_before']}" );
		}
		  

		// 按签到时间倒序 DESC 排序
		if ( array_key_exists("orderby_time_desc", $query) &&!empty($query['orderby_time_desc']) ) {
			$qb->orderBy("checkin.time", "desc");
		}

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("checkin.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("checkin.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$checkins = $qb->select( $select )->pgArray($perpage, ['checkin._id'], 'page', $page);

 		foreach ($checkins['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$checkins['_sql'] = $qb->getSql();
			$checkins['query'] = $query;
		}

		return $checkins;
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
				$select[$idx] = "checkin." .$select[$idx];
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
						array_push($linkSelect, "checkin.*");
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
			"checkin_id",  // 签到ID
			"user_id",  // 用户ID
			"time",  // 签到时刻
			"lng",  // 经度
			"lat",  // 纬度
			"alt",  // 海拔
			"location",  // 签到地点
			"device",  // 设备
			"data",  // 数据
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>