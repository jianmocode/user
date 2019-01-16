<?php
namespace Xpmsns\User\Model; 
define('__NS__', 'Xpmsns\User\Model'); // 兼容旧版 App::M 方法调用

use \Xpmse\Mem as Mem;
use \Xpmse\Excp as Excp;
use \Xpmse\Err as Err;
use \Xpmse\Conf as Conf;
use \Xpmse\Model as Model;
use \Xpmse\Utils as Utils;
use \Xpmse\Wechat as Wechat;
use \Xpmse\Option as Option;
use \Xpmse\Job;
use \Xpmse\Log;

/**
 * 用户数据模型
 */
class User extends Model {

	private $user_wechat = null;
	private $cfg = null;
	public $userinfo = [];
	public $appid = null;
	public $openid = null;
	public $unionid = null;
	public $user_id = null;
	public $wechat_id = null;

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'xpmsns_user_']);
		$this->table('user');

		// 微信公众号授权表
        $this->user_wechat = Utils::getTab('user_wechat', "xpmsns_user_");  
        $this->log = new Log("User");
	}


	public static function getFields() {
		return [
			'user_id', 
			'group_id',
			'name',
			'idno',
			'iddoc',
			'nickname',
			'sex',
			'city',
			'province',
			'country', 
			'headimgurl',
			'language',
			'birthday',
			'bio',
			'bgimgurl',
			'mobile', 
			'mobile_nation',
			'mobile_full', 
			'email', 
			'contact_name',
			'contact_tel', 
			'title', 
			'company', 
			'zip',
			'address', 
			'remark', 
			'tag', 
			'user_verified', 
			'name_verified', 
			'verify', 
			'verify_data',
			'mobile_verified',
			'email_verified', 
			'extra',
			'password',
			'pay_password',
			'status'
		];
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
		
		$this->putColumn( 'user_id', $this->type('string', ['length'=>128, 'unique'=>true]) )    // 用户ID 
			 ->putColumn( 'group_id', $this->type('string',  ['length'=>128, 'index'=>true]) )   // 用户组

			 // 用户资料
			 ->putColumn( 'name', $this->type('string',  ['length'=>128, 'index'=>true]) )  // 真实姓名
			 ->putColumn( 'idno', $this->type('string',  ['length'=>256]) )  // 身份证件号码
			 ->putColumn( 'idtype', $this->type('string',  ['length'=>40, 'index'=>true]) )  // 身份证件类型 ID:身份证 MID:军人身份证  APID: 警察身份证  HMLP:港澳通行证 MTPS:台胞证  PASSPORT:护照  OTHER:其他
			 ->putColumn( 'iddoc', $this->type('text',  ["json"=>true]) )  // 身份证件文件地址
			 ->putColumn( 'nickname', $this->type('string',  ['length'=>128, 'index'=>true]) )  // 用户名称
			 ->putColumn( 'sex', $this->type('integer',  ['length'=>1,  "index"=>true]) )  // 用户性别
			 ->putColumn( 'city', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在城市
			 ->putColumn( 'province', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在省份
			 ->putColumn( 'country', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在国家
			 ->putColumn( 'headimgurl', $this->type('text',  ["json"=>true]) )  // 用户头像地址信息
			 ->putColumn( 'language', $this->type('string',  ['length'=>20]) )  // 系统语言
			 ->putColumn( 'birthday', $this->type('timestamp',  []) )  // 出生日期
			 ->putColumn( 'bio', $this->type('string',  ["length"=>600]) )  // 用户简介
			 ->putColumn( 'bgimgurl', $this->type('text',  ["json"=>true]) )  // 用户中心图片地址

			 // 常用字段
			 ->putColumn( 'mobile', $this->type('string',  ['length'=>40]) )  // 手机号
			 ->putColumn( 'mobile_nation', $this->type('string',  ['length'=>40, 'default'=>"86"]) )  // 手机号国别
			 ->putColumn( 'mobile_full', $this->type('string',  ['length'=>40, 'unique'=>true]) )  // 完整的手机号码
			 ->putColumn( 'email', $this->type('string',  ['length'=>128, 'unique'=>true]) )  // 电邮地址
			 ->putColumn( 'contact_name', $this->type('string',  ['length'=>256]) )  // 联系人
			 ->putColumn( 'contact_tel',  $this->type('string',  ['length'=>40]) )   // 联系电话
			 ->putColumn( 'title',  $this->type('string',  ['length'=>128]) )   // 联系人职务
			 ->putColumn( 'company',  $this->type('string',  ['length'=>256]) )   // 联系人所在公司
			 ->putColumn( 'zip', $this->type('string',  ['length'=>40]) )  // 邮编
			 ->putColumn( 'address', $this->type('string',  ['length'=>256]) )  // 收货地址
			 ->putColumn( 'remark', $this->type('string',  ['length'=>256]) )  // 用户备注
			 ->putColumn( 'tag', $this->type('text',  ['json'=>true]) )  // 用户标签

			// 身份校验
			 ->putColumn( 'user_verified', $this->type('string',  ["length"=>40, 'default'=>"unverified"]) )    // 身份认证 unverified 未通过认证  verified 已认证  verifying 认证中
             ->putColumn( 'name_verified', $this->type('string',  ["length"=>40, 'default'=>"unverified"]) )    // 实名认证 unverified 未通过认证  verified 已认证  verifying 认证中
             ->putColumn( 'name_message', $this->type('string',  ['length'=>600]) )    	      // 驳回实名认证申请通知
             ->putColumn( 'verify', $this->type('string',  ['length'=>128]) )    	      // 用户身份信息
             ->putColumn( 'verify_data', $this->type('text',  ['json'=>true]) )  		  // 用户认证证明材料
             ->putColumn( 'verify_message', $this->type('string',  ['length'=>600]) )    	      // 驳回身份认证申请通知
			 ->putColumn( 'mobile_verified', $this->type('boolean',  ['default'=>"0"]) )  // 手机号是否通过校验
			 ->putColumn( 'email_verified', $this->type('boolean',  ['default'=>"0"]) )   // 电邮地址是否通过校验
			
			// 扩展属性字段
			->putColumn( 'extra', $this->type('text',  ['json'=>true]) )  // 扩展属性 JSON

			// 登录密码
			->putColumn( 'password', $this->type('string', ['length'=>128] ) )

			// 支付密码 (二级密码)
            ->putColumn( 'pay_password', $this->type('string', ['length'=>128] ) )

			// 用户状态 on/off/lock
             ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>true, 'default'=>'on']) )
            
            // 社会化
            ->putColumn( 'inviter', $this->type('string', ['length'=>128] ) ) // 邀请者 (user_id)
            ->putColumn( 'follower_cnt', $this->type('integer', ['length'=>1] ) )  // 粉丝数量 (缓存数据)
            ->putColumn( 'following_cnt', $this->type('integer', ['length'=>1] ) )  // 关注数量 (缓存数据)
		;

		// 微信公众号授权表
		
		$this->user_wechat

			// 用户ID 
			->putColumn( 'user_id', $this->type('string', ['length'=>128, 'index'=>true]) )
			
			// 微信 GroupID
			->putColumn( 'groupid',  $this->type('string', ['length'=>128, 'index'=>true] ) )

			// 微信 subscribe
			->putColumn( 'subscribe',  $this->type('boolean', [ 'index'=>true] ) )

			// 微信 subscribe_time
			->putColumn( 'subscribe_time',  $this->type('timestamp', [] ) )

			// 微信 tagid_list
			->putColumn( 'tagid_list',  $this->type('text', ["json"=>true] ) )

			// 微信 openId
			->putColumn( 'openid',  $this->type('string', ['length'=>128, 'index'=>true] ) )
			
			// 微信 unionId
			->putColumn( 'unionid',  $this->type('string', ['length'=>128, 'index'=>true]) )

			// 微信应用 id
			->putColumn( 'appid',  $this->type('string', ['length'=>128, 'index'=>true]) )

			// 用户备注
			->putColumn( 'remark', $this->type('string',  ['length'=>256]) )  

			// 微信应用 SLUG
			->putColumn( 'appid_openid',  $this->type('string', ['length'=>128, 'unique'=>true]) )
		;
    }
    

    /**
     * 读取邀请者信息
     */
    public function getInviter(){
        $invite = new Invite();
        return $invite->getInviter();
    }

    /**
     * 读取当前邀请者信息
     */
    static public function inviter(){
        $invite = new Invite();
        return $invite->getInviter();
    }


    /**
     * 用户初始化( 注册行为/注册任务/设置默认值等... )
     */
    public function __defaults() {

        // 注册任务
        $tasks = [
            [
                "name"=>"完善个人资料任务", "slug"=>"profile", "type"=>"once",
                "process"=>5, 
                "quantity" => [0,0,0,0,500],
                "auto_accept" => 0,
                "params" => [
                    ["nickname"],
                    ["address"],
                    ["birthday"],
                    ["contact_name"],
                    ["company"]
                ],
                "accept" => ["class"=>"\\xpmsns\\user\\model\\user", "method"=>"onProfileAccpet"],
                "status" => "online",
            ],[
                "name"=>"邀请注册任务", "slug"=>"invite", "type"=>"repeatable",
                "process"=>4,
                "quantity" => [100,100,100,100],
                "params" => [
                    "count"=>4
                ],
                "auto_accept" => 0,
                "accept" => ["class"=>"\\xpmsns\\user\\model\\user", "method"=>"onInviteAccpet"],
                "status" => "online",
            ]
        ];

        // 注册行为
        $behaviors =[
            [
                "name" => "更新个人资料", "slug"=>"xpmsns/user/user/profile",
                "intro" =>  "本行为当更新用户资料后触发",
                "params" => ["name"=>"真实姓名", "nickname"=>"昵称", "sex"=>"性别", "address"=>"地址", "birthday"=>"生日"],
                "status" => "online",
            ],[
                "name" => "用户注册", "slug"=>"xpmsns/user/user/signup",
                "intro" =>  "本行为当更新用户资料后触发",
                "params" => ["user_id"=>"用户ID", "mobile"=>"手机号", "name"=>"真实姓名", "nickname"=>"昵称", "sex"=>"性别", "address"=>"地址", "birthday"=>"生日", "inviter"=>"邀请者ID"],
                "status" => "online",
            ],[
                "name" => "用户登录", "slug"=>"xpmsns/user/user/signin",
                "intro" =>  "本行为当更新用户资料后触发",
                "params" => ["user_id"=>"用户ID"],
                "status" => "online",
            ]
        ];

        // 订阅行为( 响应任务处理 )
        $subscribers =[
            [
                "name" => "完善个人资料任务",
                "behavior_slug"=>"xpmsns/user/user/profile",
                "outer_id" => "profile",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\user\\model\\user", "method"=>"onProfileChange"],
                "status" => "on",
            ],[
                "name" => "邀请注册任务",
                "behavior_slug"=>"xpmsns/user/user/signup",
                "outer_id" => "invite",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\user\\model\\user", "method"=>"onInviteChange"],
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


        // 注册服务器
        $services = [
            [
                "app" => "xpmsns/user",
                "name" => "Behavior",
                "type" => "queue",
                "cname" => "用户行为收集队列服务",
                "autostart" => 1,
                "status" => 'on',
                "priority" => 10,
                "setting" => [
					"host" => "127.0.0.1",
					"home" => Utils::getHome(),
                    "user" => 0,
                    "worker_num" =>1
				]
            ]
        ];

        $se = new \Xpmse\Service;
        foreach( $services as $service ) {
            try { $se->create($service); } catch( Excp $e) { $e->log(); }
        }
    }

    /**
     * 任务接受响应: 完善个人资料任务 (验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onProfileAccpet(){
        return true;
    }

    /**
     * 任务接受响应: 邀请注册任务 (验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onInviteAccpet(){
        return true;
    }

    /**
     * 订阅器: 完善个人资料任务 ( 更新个人资料行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(用户签到)数据结构
     * @param array $subscriber  订阅者(完善个人资料任务订阅) 数据结构  ["outer_id"=>"任务SLUG", "origin"=>"task" ... ]
     * @param array $data  行为数据 ["name"=>"真实姓名", "nickname"=>"昵称", "sex"=>"性别", "address"=>"地址", "birthday"=>"生日"],
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function onProfileChange( $behavior, $subscriber, $data, $env ) {

        $task_slug = $subscriber["outer_id"];
        $user_id = $env["user_id"];

        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        $job = new Job(["name"=>"XpmsnsUserBehavior"]);


        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        if ( empty($task["usertask"]) ) {
            throw new Excp("用户尚未接受该任务({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]); 
        }


        if ( $task["usertask"]["status"] != "accepted" ) {
            throw new Excp("该任务已经完成或取消({$task["usertask"]["status"]})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]); 
        }

        
        $params = is_array($task["params"]) ? $task["params"] : [];

        // 步骤清单
        $defaults = [
            is_array($params[0]) ? $params[0] : ["nickname"],
            is_array($params[1]) ? $params[1] : ["address"],
            is_array($params[2]) ? $params[2] : ["birthday"],
            is_array($params[3]) ? $params[3] : ["contact_name"],
            is_array($params[4]) ? $params[4] : ["company"],
        ];

        // print_r( $defaults );
        // print_r( $params );

        $user = $this->getByUid( $user_id );

        // 计算当前完成步骤
        $status = [true, true, true, true, true];
        foreach( $defaults as $step=>$fields ) {
            foreach( $fields  as $field ) {
               
                $var = $user["$field"];
                if ( is_numeric($var) && $var == 0 ) {
                    $var = "0:not-null";
                }

                if ( is_bool($var) && $var == false ) {
                    $var = "false:not-null";
                }

                // DEBUG
                $job->info("检查字段 field={$field} var={".var_export( $var, true )."} 是否为空？" . var_export( empty($var), true ) );
                if ( empty($var) ) {
                    $status[$step] = false;
                    continue;
                }
            }
        }

        $process = 0;
        foreach($status as $s) {
            if ( $s ) {
                $process ++;
            }
        }

        // DEBUG 
        $job->info("Result: process={$process}");
        if ( $process > 0 ) {
            // 设定进展并发放奖励
            $t->processByUsertaskId( $usertask["usertask_id"], $process );  
        }

    }


    /**
     * 订阅器: 邀请注册任务 (用户注册行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(用户注册)数据结构
     * @param array $subscriber  订阅者(邀请注册任务订阅) 数据结构  ["outer_id"=>"任务SLUG", "origin"=>"task" ... ]
     * @param array $data  行为数据 ["user_id"=>"用户ID", "mobile"=>"手机号", "name"=>"真实姓名", "nickname"=>"昵称", "sex"=>"性别", "address"=>"地址", "birthday"=>"生日", "inviter"=>"邀请者ID"],
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    
    public function onInviteChange( $behavior, $subscriber, $data, $env ) {
        
        $job = new Job(["name"=>"XpmsnsUserBehavior"]);

        if ( empty($data["inviter"]) ) {
            $job->info("没有邀请者信息 (user_id={$env['user_id']})", $data );
            return ;
        }

        // 读取任务
        $inviter = $data["inviter"];
        $task_slug = $subscriber["outer_id"];
        $user_id = $inviter["user_id"];
        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        // 扩展数量
        $params = is_array($task["params"]) ? $task["params"] : [];
        $params["count"] = empty($params["count"]) ?  intval($task["process"]) : intval($params["count"]);
        if ( $params["count"] != intval($task["process"]) ) {
            $tt = new  \Xpmsns\User\Model\Task;
            $quantity = []; 
            for( $i=0;$i<$params["count"]; $i++) {
                $quantity[$i] = 0;
            }
            $quantity[$params["count"]-1] = end($task["quantity"]);
 
            $tt->updateBy("task_id", [
                "task_id"=>$task["task_id"],
                "process" => $params["count"],
                "quantity" => $quantity,
            ]);
        }

        // 任务副本创建时间
        $today = date('Y-m-d 00:00:00');
        $created_at = $today;
        // if ( strtotime($today) > strtotime($usertask["created_at"]) ) {
        //     $created_at = $today;
        // } else {
        //     $created_at = $usertask["created_at"];
        // }


        $job->info("邀请记录: inviter={$user_id}, invitee={$env['user_id']}, today={$today}, created_at={$created_at}, count={$params['count']}");

        // 检索自任务副本创建到当前时刻的邀请成功的数量
        $process = $this->query()
                   ->where("inviter", "=", $user_id)
                   ->where("created_at", ">=", $created_at)
                   ->limit( $params["count"] )
                   ->count("user_id")
                ;
        
        $job->info("Result: process={$process}");

        if ( $process > 0 ) {
            $t->processByUsertaskId( $usertask["usertask_id"], $process );
        }

    }

    /**
     * 订阅器: 邀请注册任务 (用户注册行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(用户注册)数据结构
     * @param array $subscriber  订阅者(邀请注册任务订阅) 数据结构  ["outer_id"=>"任务SLUG", "origin"=>"task" ... ]
     * @param array $data  行为数据 ["user_id"=>"用户ID", "mobile"=>"手机号", "name"=>"真实姓名", "nickname"=>"昵称", "sex"=>"性别", "address"=>"地址", "birthday"=>"生日", "inviter"=>"邀请者ID"],
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    
    public function onInviteChangeAnother( $behavior, $subscriber, $data, $env ) {
       
        $job = new Job(["name"=>"XpmsnsUserBehavior"]);

        if ( empty($data["inviter"]) ) {
            $job->info("没有邀请者信息 (user_id={$env['user_id']})", $data );
            return ;
        }

        // 读取任务
        $inviter = $data["inviter"];
        $task_slug = $subscriber["outer_id"];
        $user_id = $inviter["user_id"];
        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }
        
        $job->info("邀请记录: inviter={$user_id}, invitee={$evn['user_id']}, usertask_id={$usertask['usertask_id']} process=1");

        // 设定进展并发放奖励
        $t->processByUsertaskId( $usertask["usertask_id"], 1 );
    }


    /**
     * 读取用户账户余额
     */
    function getBalance( $user_id ) {
        $blc = new Balance();
        return $blc->Sum( $user_id );
    }


    /**
     * 读取用户积分余额
     */
    function getCoin( $user_id ){
        $coin = new Coin();
        return $coin->Sum( $user_id );
    }

	/**
	 * 读取用户信息
	 * @return [type] [description]
	 */
	function getUserInfo() {

		@session_start();
		$rs = !empty($_SESSION['USER:info']) ? $_SESSION['USER:info'] : $_SESSION['_uinfo'] ;
		if ( !is_array($rs) ) {
			$rs = [];
		}
		$rs['session_id'] = session_id();
		return $rs;
    }
    
    /**
     * 读取用户信息
     */
    static public function info() {
        @session_start();
		$rs = !empty($_SESSION['USER:info']) ? $_SESSION['USER:info'] : $_SESSION['_uinfo'] ;
		if ( !is_array($rs) ) {
			$rs = [];
		}
		$rs['session_id'] = session_id();
		return $rs;
    }



	/**
	 * 解压扫描登录指令
	 */
	function extractWechatScanEvent( $message ) {
		
		$event_key = $message['EventKey'];
		$event_key = str_replace("qrscene_", "", $event_key);
		$node = new \DOMDocument();
		$node->loadXML($event_key);
		$node = $node->firstChild;
		if ( $node->nodeName != 's' ) {
			return null;
		}

		// session id
		$sid = trim($node->nodeValue);
		$openid = $message['FromUserName'];

		if ( empty($sid) || empty($openid) ) {
			return null;
		}

		return [
			"sid"=>$sid, 
			"openid" => $openid
		];

	}


	/**
	 * 向用户回复文本消息
	 * @param  [type] $message [description]
	 * @return [type]          [description]
	 */
	function replyText(  $message, $wechat_id=null, $openid=null,  $appid=null ) {
		
		$openid = !empty($openid) ? $openid : $this->openid;
		$appid = !empty($appid) ? $appid : $this->appid;
		$wechat_id = !empty($wechat_id) ? $wechat_id : $this->wechat_id;

		$cfg = $this->cfg;
		$this->wechat_id = $wechat_id;

		if ( $cfg == null || ($this->cfg['appid'] != $appid ) ) {
			$conf = Utils::getConf();
			$cfg = $conf["_map"][$appid];
			if ( empty($cfg) ) {
				throw new Excp( "未找到配置信息($appid)", 404, ['openid'=>$openid, 'appid'=>$appid]);
			}
			$this->cfg = $cfg;
		}

		
		$wechat = new Wechat( $cfg );
		return $wechat->replyText($wechat_id, $openid, $message );
	}


	/**
	 * 退出登录
	 * @return [type] [description]
	 */
	function logout() {
		@session_start();
		unset($_SESSION['USER:info']);
		return $this;
	}


	/**
	 * 使用公众号登录
	 * @param  [type] $appid      [description]
	 * @param  [type] $openid     [description]
	 * @param  [type] $session_id [description]
	 * @return [type]             [description]
	 */
	function loginByOpenId( $appid, $openid, $session_id ) {
		$user_id = $this->updateWechatUser($appid, $openid);
		$this->loginSetSession( $user_id, $session_id );
		return $this;
	}


	/**
	 * 保存用户信息 
	 * @param  [type] $data [description]
	 * @return 成功返回 用户ID, 失败抛出异常
	 */
	function save( $data ) {

		// 用户头像处理
		if ( array_key_exists('headimg_path', $data) ) {
			// headimg_path
			// headimg_url
			$data['headimgurl'] = $data['headimg_path'];
			unset( $data['headimg_path'] );
		}	

		if ( array_key_exists('tag', $data) && is_string($data['tag']) ) {
			$data['tag'] = str_replace("，", ",", $data['tag']);
			$data['tag'] = explode(',', $data['tag']);
		}


		$user_id = $data['user_id'];
		if ( empty($user_id) ) {
			$user_id = $this->genUserId();
		}


		$uinfo = $this->query()
					  ->where("user_id", '=', $user_id )
					  ->limit(1)
					  ->select("group_id", "user_id")
					  ->get()
					  ->toArray();

		if ( empty($uinfo) ) {

			// Group 信息
			$opt =  new Option("xpmsns/user");
			$options = $opt->getAll();
			$map = $options['map'];	
			$slug = $map['user/default/group'];
			$g = new Group();
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
			$this->create( $data );

		} else {
			$this->updateBy("user_id", $data);
		}

		return $user_id;
	}


	/**
	 * 微信用户 注册/登录信息
	 * @param  [type] $appid  [description]
	 * @param  [type] $openid [description]
	 * @return [type]         [description]
	 */
	function updateWxappUser( $appid, $wxappUinfo ) {

		$conf = Utils::getConf();
		$cfg = $conf["_map"][$appid];
		if ( empty($cfg) ) {
			throw new Excp( "未找到配置信息($appid)", 404, ['openid'=>$openid, 'appid'=>$appid]);
		}

		$wechat = new Wechat( $cfg );
		$u =$wxappUinfo;
		$openid = $u['openid'];
		$u['appid'] = $appid;
		$u['appid_openid'] = "{$appid}_{$u['openid']}";

	
		$user_id =  null;

		// 是否已注册 ( 检查 unionid )
		if( !empty($u['unionid']) ) {
			$user_id_unionid = $this->user_wechat->getVar('user_id', "WHERE unionid = ? LIMIT 1", [$u['unionid']]);
			if ( !empty($user_id_unionid) ) {
				$user_id = $user_id_unionid;
			}
		}

		//是否已注册 ( 检查 openid )
		$user_id_openid = $this->user_wechat->getVar('user_id', "WHERE openid = ? AND appid = ? LIMIT 1", [$openid, $appid]);

		if ( !empty($user_id_openid) ) {
			$user_id = $user_id_openid;
		} else if ( empty($user_id) ) {
			$user_id = $this->genUserId();
		}

		$u['user_id'] = $user_id;

		// 处理微信用户头像信息
		$u['headimgurl'] = $u['avatarUrl'];
		$u['headimgurl'] = str_replace("http:", "", $u['headimgurl']);
		$u['headimgurl'] = str_replace("https:", "", $u['headimgurl']);

		// 处理用户昵称
		if ( !empty($u['nickName']) ) {
			$u['nickname'] = $u['nickName'];
		}
		$u['sex'] = $u['gender'];

	
		// 从未注册 生成 UserID
		$this->user_wechat->createOrUpdate($u);


		$uinfo = $this->query()
					  ->where("user_id", '=', $user_id )
					  ->limit(1)
					  ->select("group_id", "user_id")
					  ->get()
					  ->toArray();
		
        unset($u['remark']);
        
        $method = "signin";
		if ( empty($uinfo) ) {

			// Group 信息
			$opt =  new Option("xpmsns/user");
			$options = $opt->getAll();
			$map = $options['map'];	

			$slug = $map['user/default/group'];
			$g = new Group();
			$rs = $g->getBySlug($slug);
			$u['group_id'] = $rs['group_id'];
            $this->create( $u );
            $method = "signup";

		} else {
            
            // 更新扩展数据
            if ( !empty($u["extra"]) ) {
                $extra = json_decode($u["extra"], true);
                if ( $extra == false ) {
                    $extra = [];
                }
                $u["extra"] = array_merge($u["extra"], $extra);
            }

            // 忽略更新列表
            $opt =  new \Xpmse\Option("xpmsns/user");
	        $options = $opt->getAll();
            $c = $options['map'];
            $notsync = is_array($c["user/wechat/notsync"]) ? $c["user/wechat/notsync"] : [];
            foreach( $u as $field => $value ) {
                if ( in_array($field, $notsync) ) {
                    unset( $u["$field"]);
                    $this->log->info("微信小程序登录: 忽略更新字段 {$field} notsync=", $notsync);
                }
            }
            $this->log->info("微信小程序登录: 更新用户资料 user=", $u);
			$this->updateBy("user_id", $u);
		}

		$this->appid = $appid;
		$this->openid = $openid;
		$this->unionid = $u['unionid'];
        $this->cfg = $cfg;
        
        // 更新 Session 
        $this->loginSetSession($user_id);

		return [
            "user_id"=>$user_id, 
            "method"=>$method
        ];
	}




	/**
	 * 微信用户 注册/登录信息
	 * @param  [type] $appid  [description]
	 * @param  [type] $openid [description]
	 * @return [type]         [description]
	 */
	function updateWechatUser( $appid, $openid ) {

		$conf = Utils::getConf();
		$cfg = $conf["_map"][$appid];
		if ( empty($cfg) ) {
			throw new Excp( "未找到配置信息($appid)", 404, ['openid'=>$openid, 'appid'=>$appid]);
		}

		$wechat = new Wechat( $cfg );
		$u = $wechat->getUser( $openid );
		$u['appid'] = $appid;
		$u['appid_openid'] = "{$appid}_{$u['openid']}";

		// 订阅时间
		$u['subscribe_time'] = date('Y-m-d H:i:s', $u['subscribe_time']);

		$user_id =  null;

		// 是否已注册 ( 检查 unionid )
		if( !empty($u['unionid']) ) {
			$user_id_unionid = $this->user_wechat->getVar('user_id', "WHERE unionid = ? LIMIT 1", [$u['unionid']]);

			if ( !empty($user_id_unionid) ) {
				$user_id = $user_id_unionid;
			}
		}

		//是否已注册 ( 检查 openid )
		$user_id_openid = $this->user_wechat->getVar('user_id', "WHERE openid = ? AND appid = ? LIMIT 1", [$openid, $appid]);

		if ( !empty($user_id_openid) ) {
			$user_id = $user_id_openid;
		} else if ( empty($user_id) ) {
			$user_id = $this->genUserId();
		}

		$u['user_id'] = $user_id;

		// 处理微信用户头像信息
		$u['headimgurl'] = str_replace("http:", "", $u['headimgurl']);
		$u['headimgurl'] = str_replace("https:", "", $u['headimgurl']);

		// 从未注册 生成 UserID
		$this->user_wechat->createOrUpdate($u);


		$uinfo = $this->query()
					  ->where("user_id", '=', $user_id )
					  ->limit(1)
					  ->select("group_id", "user_id")
					  ->get()
					  ->toArray();
		
		unset($u['remark']);

		if ( empty($uinfo) ) {

			// Group 信息
			$opt =  new Option("xpmsns/user");
			$options = $opt->getAll();
			$map = $options['map'];	

			$slug = $map['user/default/group'];
			$g = new Group();
			$rs = $g->getBySlug($slug);
			$u['group_id'] = $rs['group_id'];
			$this->create( $u );

		} else {

            // 更新扩展数据
            if ( !empty($u["extra"]) ) {
                $extra = json_decode($u["extra"], true);
                if ( $extra == false ) {
                    $extra = [];
                }

                $u["extra"] = array_merge($u["extra"], $extra);
            }

            // 忽略更新列表
            $opt =  new \Xpmse\Option("xpmsns/user");
	        $options = $opt->getAll();
            $c = $options['map'];
            $notsync = is_array($c["user/wechat/notsync"]) ? $c["user/wechat/notsync"] : [];
            foreach( $u as $field => $value ) {
                if ( in_array($field, $notsync) ) {
                    unset( $u["$field"]);
                }
            }

			$this->updateBy("user_id", $u);
		}

		$this->appid = $appid;
		$this->openid = $openid;
		$this->unionid = $u['unionid'];
        $this->cfg = $cfg;
        
        // 更新 Session 
        $this->loginSetSession($user_id);

		return $user_id;
	}



	/**
	 * 用户登录
	 */
	function login( $mobile,  $password = null, $mobile_nation="86" ) {

		$rows = $this->query()
				->where('mobile', '=', $mobile)
				->where('mobile_nation', '=', $mobile_nation)
				->limit(1)
				->select(
					"user_id",
					"mobile", "mobile_nation", "mobile_verified",
					"password", "pay_password"
				)
				->get()
				->toArray();

		if ( empty($rows) ) {
			throw new Excp( "用户不存在", 404, ['data'=>$data, 'query'=>$query, 'errorlist'=>[['mobile'=>'用户不存在']]]);
		}

		$rs = current($rows);

		if ( $password != null ) {
			if ( $this->checkPassword($password, $rs['password']) === false ) {
				throw new Excp( "登录密码错误", 404, ['data'=>$data, 'query'=>$query, 'errorlist'=>[['password'=>'登录密码错误']]]);
			}
		}

		$this->loginSetSession($rs['user_id']);
		return $rs;
	}



	/**
	 * 设定用户会话信息
	 * @param  [type] $user_id    [description]
	 * @param  [type] $session_id [description]
	 * @return [type]             [description]
	 */
	function loginSetSession( $user_id, $session_id=null ) {
		
		$rows = $this->query()
				->leftJoin("group", 'group.group_id', "=", "user.group_id" )
				->where('user.user_id', '=', $user_id)
				->limit(1)
				->select(
					"user_id", "user.group_id as group_id", 
					"user.name","nickname", "sex", "city", "province", "country","headimgurl", "language", "bio", "bgimgurl",
					"mobile", "mobile_nation", "mobile_verified","extra",
					"email", "email_verified",
					"zip", "address", "user.remark as remark", "user.tag as tag",
					"password", "pay_password",
					"user.status as status",
					"group.name as group_name",
					"group.slug as group_slug",
					"group.remark as group_remark",
					"group.tag as group_tag",
					"group.status as group_status"
				)
				->get()
				->toArray();

		if ( empty($rows) ) {
			throw new Excp( "用户不存在", 404, ['data'=>$data, 'query'=>$query, 'errorlist'=>[['mobile'=>'用户不存在']]]);
		}

        $rs = current($rows);
        
        // 读取用户账户信息
        $rs["balance"] = $this->getBalance( $user_id );
        $rs["coin"] = $this->getCoin( $user_id );

		if ( $session_id != null ) {
			@session_id($session_id);
		}

		@session_start();
		$userinfo = $rs;
		$userinfo['signin_at'] = time();
		unset($userinfo['password']);
		unset($userinfo['pay_password']);
		$_SESSION['USER:info'] = $userinfo;
		$this->userinfo = $userinfo;
		$this->user_id = $userinfo['user_id']; 
		return $this;
    }
    


	/**
	 * 发送短信验证码
	 * @param [type]  $mobile     [description]
	 * @param integer $nationcode [description]
	 */
	function SMSCode( $mobile, $nationcode=86 ) {

		$opt =  new \Xpmse\Option("xpmsns/user");
		$options = $opt->getAll();
		$c = $options['map'];

		if ( !is_array($c["user/sms/vcode"]) || !is_array($c["user/sms/vcode"]['option']) ) {
			throw new Excp("短信网关配置信息错误", 500, ["c"=>$c["user/sms/vcode"]]);
		}

		@session_start();
		$code = rand(1000,9999);
		$_SESSION['SMSCODE:code'] = $nationcode.$mobile.$code;

		$sms = $c["user/sms/vcode"];
		$sms['option']['mobile'] = $mobile;
		$sms['option']['nationcode'] = $nationcode;

		return Utils::SendSMS($sms, [$code] );
	}


	/**
	 * 校验短信验证码
	 * @param  [type]  $mobile     [description]
	 * @param  [type]  $smscode    [description]
	 * @param  integer $nationcode [description]
	 * @return [type]              [description]
	 */
	function verifySMSCode( $mobile,  $code, $nationcode = "86" ){
		$code = $nationcode.$mobile.$code;
		@session_start();
		// throw new Excp("短信验证码不正确", 402, ['code'=>$code, 'session'=>$_SESSION['SMSCODE:code']]);
		return ( $_SESSION['SMSCODE:code'] == $code );
	}


	/**
	 * 校验用户密码是否正确 
	 * @param  [type] $password [description]
	 * @param  [type] $hash     [description]
	 * @return [type]           [description]
	 */
	function checkPassword( $password, $hash ) {
		return password_verify($password, $hash);
	}


	/**
	 * Password Hash
	 * @param  [type] $password [description]
	 * @return [type]           [description]
	 */
	function hashPassowrd( $password ) {
		return password_hash( $password, PASSWORD_BCRYPT, ['cost'=>12] );
	}


	/**
	 * 添加用户
	 * @param  [type] $data [description]
	 * @return [type]       [description]
	 */
	function create( $data ) {

		if (!array_key_exists('user_id', $data) ) {
			$data['user_id'] = $this->genUserId();
		}

		if (array_key_exists('password', $data) ) {
			$data['password'] = $this->hashPassowrd($data['password']);
		}

		if (array_key_exists('pay_password', $data) ) {
			$data['pay_password'] = $this->hashPassowrd($data['pay_password']);
		}

		// 包含国别的手机号
		if (array_key_exists('mobile_nation', $data) ) {
			$data['mobile_nation'] = str_replace('00', '',$mobile_nation);
			$data['mobile_nation'] = str_replace('+', '', $mobile_nation);

			if (empty($data['mobile_nation']) ) {
				$data['mobile_nation'] = "86";
			}
		}

		if (array_key_exists('mobile', $data) ) {
			$data['mobile_full'] = "DB::RAW(CONCAT(`mobile_nation`, `mobile`))";
		}
		
		return parent::create( $data );
	}


	/**
	 * 重载Update BY 
	 * @param  [type] $uni_key [description]
	 * @param  [type] $data    [description]
	 * @return [type]          [description]
	 */
	function updateBy( $uni_key, $data ) {
		if (array_key_exists('mobile', $data) ) {
			$data['mobile_full'] = "DB::RAW(CONCAT(`mobile_nation`, `mobile`))";
		}

		if (array_key_exists('password', $data) ) {
			$data['password'] = $this->hashPassowrd($data['password']);
		}

		if (array_key_exists('pay_password', $data) ) {
			$data['pay_password'] = $this->hashPassowrd($data['pay_password']);
		}

		return parent::updateBy( $uni_key, $data );
	}


	/**
	 * 取消微信授权
	 * @param  [type] $user_id [description]
	 * @param  [type] $appid   [description]
	 * @return [type]          [description]
	 */
	function removeWechat( $user_id,  $appid = null, $mark_only = true ) {

		if ( $mark_only == true ) {
			$now = date("Y-m-d H:i:s");
			if ( empty($appid) ) {
				return $this->user_wechat->runsql("update {{table}} SET `deleted_at`= ?, `appid_openid`=null WHERE `user_id` = ?", false ,[$now, $user_id]);
			} else {
				return $this->user_wechat->runsql("update {{table}} SET `deleted_at`= ?, `appid_openid`=null WHERE `user_id` = ? AND `appid`=?", false ,[$now, $user_id, $appid]);
			}
		}

		if ( empty($appid) ) {
			return $this->user_wechat->runsql("delete {{table}} WHERE `user_id` = ?", false ,[$user_id]);
		}

		return $this->user_wechat->runsql("delete {{table}} WHERE `user_id` = ? AND `appid`=? ", false ,[$user_id, $appid]);

	}




	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		
		if ( $uni_key == 'user_id') {
			// 取消用户微信授权
			$resp = $this->removeWechat( $data_key );
			if ( $resp === false ) {
				return false;
			}
		}

		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"user_id"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `user_id`))", 
				"email"=>null,
				"mobile_full" => null,
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}

			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
	}



	/**
	 * 读取用户资料
	 * @param  [type] $user_id [description]
	 * @return [type]          [description]
	 */
	function getByUid( $user_id, $select = "*" ) {

		if ( is_array($select) ) {
			array_push($select, 'user_id');
			array_push($select, 'group_id');
		}

		$user = $this->query()
					 ->where("user_id", "=", $user_id)
					 ->limit(1)
					 ->select($select)
					 ->get()
					 ->toArray()
				;

		if ( empty($user) ) {
			return [
				"user_id" => $this->genUserId()
			];
		}
	
        $this->formatUsers($user);
        $rs = current($user);
        $rs["balance"] = $this->getBalance( $user_id );
        $rs["coin"] = $this->getCoin( $user_id );
		return $rs;
    }
    

    /**
     * 读取用户资料(通过手机号码)
     * @param  string $mobile 手机号码
     */
    function getByMobile( $mobile, $select="*", $nation = "86" ) {

		if ( is_array($select) ) {
			array_push($select, 'user_id');
			array_push($select, 'group_id');
		}

		$user = $this->query()
                     ->where("mobile", "=", $mobile)
                     ->where("mobile_nation","=", $nation )
					 ->limit(1)
					 ->select($select)
					 ->get()
					 ->toArray()
				;

		if ( empty($user) ) {
			return [];
		}
	
        $this->formatUsers($user);
        $rs = current($user);
        $rs["balance"] = $this->getBalance( $user_id );
        $rs["coin"] = $this->getCoin( $user_id );
		return $rs;
    }
    


	/**
	 * 用户检索
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	function search( $query=[] ) {

		$qb = $this->query();

		// 按关键词查找 (昵称/手机号/邮箱)
		if ( array_key_exists('keyword', $query) && !empty($query['keyword']) ) {
			$qb->where(function ( $qb ) use($query) {
			   	$qb->where("nickname", "like", "%{$query['keyword']}%");
			   	$qb->orWhere("name", "like", "%{$query['keyword']}%");
				$qb->orWhere("mobile","like", "%{$query['keyword']}%");
				$qb->orWhere('email', 'like', "%{$query['keyword']}%");
			})
			;
		}

		// 按性别查找
		if ( array_key_exists('sex', $query)  ) {
			$qb->where("sex", "=", "{$query['sex']}");
		}

		// 按状态查找
		if ( array_key_exists('status', $query)  ) {
			$qb->where("status", "=", "{$query['status']}");
		}

		// 按用户组查找
		if ( array_key_exists('group_id', $query)  ) {
			$qb->where("group_id", "=", "{$query['group_id']}");
		}

		// 按城市查找
		if ( array_key_exists('city', $query)  ) {
			$qb->where("city", "=", "{$query['city']}");
		}

		// 按省份
		if ( array_key_exists('province', $query)  ) {
			$qb->where("province", "=", "{$query['province']}");
		}

		// 按国家
		if ( array_key_exists('country', $query)  ) {
			$qb->where("country", "=", "{$query['country']}");
		}

		// 按ID列表
		if ( array_key_exists('user_ids', $query)  ) {
			$qb->whereIn('user_id', $query['user_ids'] );
		}

		// 排序: 最新注册
		if ( array_key_exists('order', $query)  ) {

			$order = explode(' ', $query['order']);
			$order[1] = !empty($order[1]) ? $order[1] : 'asc';
			$qb->orderBy($order[0], $order[1] );
		}
		
		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 查询用户信息
		$users = $qb->select("*")->pgArray($perpage, ['_id'], 'page', $page);
		$this->formatUsers($users['data']);


		// echo $qb->getSql();
		// Utils::out($query);

		return $users;

	}


	function getInByUserId( $ids, $select = ['*'] ) {

		$qb = $this->query();
		$qb->whereIn("user_id", $ids);
		$qb->select($select);
		$rows = $qb->get()->toArray();
		$map = [];

		foreach ($rows as & $rs ) {
			$this->format( $rs );
			$map[$rs['user_id']] = $rs;
		}
		return $map;
	}


	/**
	 * 处理用户呈现的字段信息
	 * @param  [type] $users [description]
	 * @return [type]        [description]
	 */
	function formatUsers( & $users, $remove_password = true  ) {

		$media = new \Xpmse\Media;

		if ( empty($users) ) {
			return;
		}

		$upad = Utils::pad( $users, 'user_id' );
		$uids = $upad['data'];

		$gpad = Utils::pad( $users, 'group_id');
		$gids = $gpad['data'];

		$userWechats = []; $userGroups = [];

		// 读取微信授权信息
		$conf = Utils::getConf();  
		$wemap = $conf['_map'];
		$wechats = $this->user_wechat->query()->whereIn("user_id", $uids)->get()->toArray();
		
		foreach ($wechats as $we ) {

			$appid = $we['appid'];
			$name = "{$appid}";

			if ( is_array($wemap[$appid]) ) {
				$name = $wemap[$appid]['name'];
			}

			$we['name'] = $name;
			$user_id = $we['user_id'];
			if ( !is_array($userWechats[$user_id])) {
				$userWechats[$user_id]  = [];
			}
			$userWechats[$user_id][] = $we;
		}

		// 读取分组信息
		$g = new Group;
		$groups = $g->query()->whereIn("group_id", $gids)->get()->toArray();
		foreach ($groups as $group ) {
			$group_id = $group['group_id'];

			// 下一版支持1个用户 多个分组
			$userGroups[$group_id] = [$group];
		}


		// 增添微信授权信息和分组信息 &  删除密码信息
		foreach ($users as $idx=>$user ) {

			$uid = $user['user_id'];
			$gid = $user['group_id'];


			if ( !is_array($userWechats[$uid]) ) {
				$userWechats[$uid] = [];
			}

			if ( !is_array($userGroups[$gid]) ) {
				$userGroups[$gid] = [];
			}

			// 默认分组名称
			$defaultGroup = current($userGroups[$gid]);
			if ( !is_array($defaultGroup) ) {
				$group_name = '未知分组';
			} else {
				$group_name = $defaultGroup['name'];
			}


			$users[$idx]['group_name'] = $group_name;
			$users[$idx]['groups']= $userGroups[$gid] ;
			$users[$idx]['wechats']=$userWechats[$uid] ;
			
			if ( $remove_password === true ) {
				unset($users[$idx]["pay_password"]);
				unset($users[$idx]["password"]);
			}

			// 用户头像处理
			if ( isset( $user['headimgurl']) ) {
				if ( Utils::isURL( $user['headimgurl']) ) {
					$url = $user['headimgurl'];
                    $users[$idx]['headimg_url'] = $url;
					$users[$idx]['headimg_path'] = '';
                    $users[$idx]['headimgurl'] = [
                        "url" => $url,
                        "path" => ''
                    ];
                    // $users[$idx]['headimgurl']['url'] = $url;
					// $users[$idx]['headimgurl']['path'] = '';

				} else if ( is_array( $user['headimgurl']) ){
					$users[$idx]['headimg_path'] = $user['headimgurl']['path'];
					$users[$idx]['headimg_url'] = $user['headimgurl']['url'];

				}  else if ( is_string($user['headimgurl']) ) {
					$img =  $media->get($user['headimgurl']);
					$users[$idx]['headimgurl'] = $img;
					$users[$idx]['headimg_path'] = $img['path'];
					$users[$idx]['headimg_url'] = $img['origin'];
					// $users[$idx]['headimg_path']  = $img['url'];
				}
			}

		}
		// Utils::out( $userGroups, $userWechats );
	}


	function format( & $rs ) {
		if ( array_key_exists("headimgurl", $rs)  ){
			if ( Utils::isURL( $user['headimgurl']) ) {
				$users[$idx]['headimg_url'] = $user['headimgurl'];
				$users[$idx]['headimg_path'] = '';

			} else if ( is_array( $user['headimgurl']) ){
				$users[$idx]['headimg_path'] = $users[$idx]['path'];
				$users[$idx]['headimg_url'] = $users[$idx]['url'];

			} else {
				$img =  $media->get($user['headimgurl']);
				$users[$idx]['headimg_path'] = $img['path'];
				$users[$idx]['headimg_url'] = $img['origin'];
				// $users[$idx]['headimg_path']  = $img['url'];
			}
		}
	}


	
	function genUserId() {
		return $this->genId();
		// return uniqid();
	}


	function __clear() {
		$this->dropTable();
		$this->user_wechat->dropTable();
	}

}