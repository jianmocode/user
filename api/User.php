<?php

namespace Mina\User\Api;

use \Tuanduimao\Loader\App;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
use \Tuanduimao\Api;
use \Tuanduimao\Option;
use \Tuanduimao\Wechat;
use \Mina\User\Model\User as UserModel;
use \Mina\User\Model\Group as GroupModel;


/**
 * 用户API接口
 */
class User extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {

		parent::__construct();
		$this->forbidden(['wechatRouter']);
	}


	/**
	 * 微信推送-消息接收器 (禁止直接调用)
	 * @param $query['query']    微信 GET参数
	 *        $query['message']  解密后消息正文
	 * @return 
	 */
	protected function wechatRouter( $query ) {

		$log = new \Tuanduimao\Log("Wechat");

		$message = $query['message'];
		$param =  $query['query'];
		$appid = $param['appid'];

		if ( empty($appid) ){
			throw new Excp("未知请求来源 ($appid)", 404, ["appid"=>$appid, "param"=>$param, "message"=>$message]);
		}

		if ( $message['MsgType'] != 'event' ) {
			return "忽略";
		}

		// 扫描带参二维码
		if ( $message['Event'] == 'SCAN' || $message['Event'] == 'subscribe' ) {

			$u = new UserModel();
			$ss = $u->extractWechatScanEvent($message);

			if ( $ss === null ) {
				return '忽略';
			}

			$u->loginByOpenId($appid, $ss['openid'], $ss['sid']);
			return "成功";
		}

		// $log->info("微信推送消息 {$param['nonce']}{$param['timestamp']}" , [$message, $param] );
		return "忽略";
	}


	protected function test() {

		$u = new UserModel();
		// $user_id = $u->updateWechatUser("wxf427d2cb6ac66d2c","o2ylUw_SyKDaSW3OE71JkEJ7N36g");
		// return $user_id;

		$resp = $u->loginByOpenId("wxf427d2cb6ac66d2c","o2ylUw_SyKDaSW3OE71JkEJ7N36g", "j0d2vq39eh86hdjjcvud43t3g4");

// 		$option =  new Option("mina/user");
// 		$appid = $option->get("user/wechat/login/appid");
// 		$conf = Utils::getConf();

// 		if ( $appid !== null ){
// 			$cfg = $conf['_map'][$appid]; 
// 		} else if ( is_array($conf['_type']['1'])) {
			
// 			$cfg = current($conf['_type']['1']);
// 		}

// 		$wechat = new Wechat( $cfg );


// 		$xml = '<xml><ToUserName><![CDATA[gh_da2c392b7342]]></ToUserName>
// <FromUserName><![CDATA[o2ylUw_SyKDaSW3OE71JkEJ7N36g]]></FromUserName>
// <CreateTime>1507023717</CreateTime>
// <MsgType><![CDATA[event]]></MsgType>
// <Event><![CDATA[SCAN]]></Event>
// <EventKey><![CDATA[signin]]></EventKey>
// <Ticket><![CDATA[gQH87jwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAySmVZNmN3ckZkazMxU3FyajFwY2UAAgReW9NZAwQ8AAAA]]></Ticket>
// </xml>';

// 		$resp = $wechat->messageToArray( $xml );

		return $resp;
	}




	/**
	 * 用户登录二维码
	 * @return [type] [description]
	 */
	protected function wechatSigninQrcode() {

		$option =  new Option("mina/user");
		$appid = $option->get("user/wechat/login/appid");
		$conf = Utils::getConf();

		if ( $appid !== null ){
			$cfg = $conf['_map'][$appid]; 
		} else if ( is_array($conf['_type']['1'])) {
			
			$cfg = current($conf['_type']['1']);
		}

		if ( empty($cfg) ) {
			throw new Excp("未找到有效的微信公众号配置", 404);
		}


		$session_id = session_id();
		$wechat = new Wechat( $cfg );

		$resp = $wechat->getQrcodeURL([
			'action_info'=>[
				"scene"=>[
					"scene_str"=>"<s>".session_id()."</s>"
				]
			]
		]);

		return [
			"name" => $cfg['name'],
			"url" => $resp['showqrcode'],
			"expire_seconds" => $resp['expire_seconds']
		];
	}


	/**
	 * 读取用户配置信息
	 * @return
	 */
	protected function option( $query=[] ) {

		$opt =  new Option("mina/user");
		$options = $opt->getAll();

		$map = $options['map'];
		unset($map["user/sms/vcode"]);

		$newmap = [];
		foreach ($map as $key => $val) {
			$newkey = str_replace("/", "_", $key);
			$newmap[$newkey] = $val;
		}

		return $newmap;
	}



	/**
	 * 用户登录 
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function login( $query=[], $data=[] ) {
		$this->authVcode();
		$u = new UserModel();
		$mobile = $data['mobile'];

		if ( empty($mobile) ) {
			throw new Excp("未知手机号码", 404, ['data'=>$data, 'query'=>$query]);
		}

		$u->login($mobile, $data['password']);
		return ['code'=>0, "message"=>"登录成功"];

	}


	/**
	 * 退出登录 (注销)
	 * @return [type] [description]
	 */
	protected function logout() {
		$u = new UserModel();
		$u->logout();
		return ['code'=>0, "message"=>"注销成功"];
	}


	/**
	 * 读取用户会话信息
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function getUserInfo( $query=[], $data=[] ) {
		$u = new UserModel();
		return $u->getUserInfo();
	}




	/**
	 * 创建一个新用户
	 * @param  array  $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function create( $query=[], $data=[] ) {

		$this->authVcode();

		$opt =  new Option("mina/user");
		$options = $opt->getAll();
		$map = $options['map'];		

		// 校验手机号码
		if ( empty($data['mobile']) ) {
			throw new Excp("手机号码格式不正确", 402, ['data'=>$data]);
		}

		// 校验短信验证码
		if( $map['user/sms/on'] == 1) {
			$data['mobile_verified'] = true;
			$data['mobile_nation'] = !empty($data['nation']) ? $data['nation'] : '86';
			if ( $this->verifySMSCode($query, $data) === false) {
				throw new Excp("短信验证码不正确", 402, ['data'=>$data]);
			}
		}


		// 检查密码
		if ( isset($data['repassword']) ) { 
			if ( $data['password'] != $data['repassword'] ) {
				throw new Excp("两次输入的密码不一致", 402, ['data'=>$data]);
			}
		}


		// Group
		$slug = $map['user/default/group'];
		$g = new GroupModel();
		$rs = $g->getBySlug($slug);
		$data['group_id'] = $rs['group_id'];


		// 数据入库
		$u = new UserModel();
		try {
			$u->create($data);
		} catch(Excp $e ){
			if ( $e->getCode() == '1062') {
				throw new Excp("手机号 {$data['mobile']} 已被注册", 402, ['data'=>$data]);	
			}
			throw $e;
		}

		return ["message"=>"注册成功", "code"=>0 ];
	}



	/**
	 * 校验手机短信验证码
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function verifySMSCode( $query=[], $data=[] ) {
		$u = new UserModel();
		$mobile = $data['mobile'];
		$nation  = !empty($data['nation']) ? $data['nation'] : '86';
		$smscode = $data['smscode'];
		return $u->verifySMSCode($mobile, $smscode, $nation);
	}


	/**
	 * 读取手机短信验证码
	 * @param  array $query [description]
	 * @return [type]        [description]
	 */
	protected function getSMSCode( $query = [] ) {

		$this->authVcodeOnly();

		// 是否开启验证码
		$opt =  new Option("mina/user");
		$options = $opt->getAll();
		$map = $options['map'];

		if( $map['user/sms/on'] != 1) {
			throw new Excp("非法请求 未开启短信验证码", 401, ['query'=>$query]);
		}

		// 提交信息校验
		if ( empty($query['mobile']) ) {
			throw new Excp("非法请求 未知手机号码", 401, ['query'=>$query]);
		}

		$now = time();
		$lock_time = 60;
		$locked_at = intval($_SESSION['SMSCODE:locked_at']);

		if ( ( $now - $locked_at ) < $lock_time ) {
			throw new Excp("非法请求 请求过于频繁", 403, ['locked_at'=>$locked_at]);
		}


		// 发送短信验证码
		$u = new UserModel();
		$nation = !empty($query['nation']) ? $query['nation'] : '86';
		$u->SMSCode( $query['mobile'], $nation );

		// 锁定
		$_SESSION['SMSCODE:locked_at'] = $now;
		return ["message"=>"发送成功", "code"=>0 ];

	}






	/**
	 * 查询标签列表
	 *
	 * 读取字段 select 默认 name
	 *
	 *    示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *    许可值: "*","tag_id","name","param"
	 * 
	 * 
	 * 查询条件
	 * 	  1. 按名称查询  name | orName | inName
	 * 	  3. 按标签ID查询  tagId | orTagId | inTagId 
	 * 	  8. 按参数标记查询  param | orParam
	 * 	  
	 * 排序方式 order 默认 tag_id  updated_at asc, tag_id desc
	 * 
	 *    1. 按标签更新顺序  updated_at
	 *    2. 按标签创建顺序  tag_id  
	 *    
	 *
	 * 当前页码 page    默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query 
	 * @return array 文章结果集列表
	 */
	protected function search( $query=[] ) {

		$this->authVcode();
		return $query;

	}



	/**
	 * 读取标签详情信息
	 * @param  array  $query Query 查询
	 *                   int ["name"]  标签详情
	 *                   
	 *          string|array ["select"] 读取字段  
	 *          			 示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *          		     许可值: "*","tag_id","name","param"
	 *                    
	 * @return Array 文章数据
	 * 
	 */
	protected function get( $query=[] ) {

		// 验证数值
		if ( empty($query['name']) ) {
			throw new Excp(" name 参数错误", 400, ['query'=>$query]);
		}

		$name = $query['name'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$allowFields = ["*","tag_id","name","param"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}
		}
		
		$cate = new \Mina\Pages\Model\Tag;
		$rs = $cate->getLine("WHERE name=:name LIMIT 1", $select, ["name"=>$name]);
		if ( empty($rs) ) {
			throw new Excp("标签不存在", 404,  ['query'=>$query]);
		}

		return $rs;
	}

}