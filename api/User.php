<?php

namespace Xpmsns\User\Api;

use \Xpmse\Loader\App;
use \Xpmse\Err;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;
use \Xpmse\Option;
use \Xpmse\Wechat;
use \Xpmsns\User\Model\User as UserModel;
use \Xpmsns\User\Model\Group as GroupModel;
use \Xpmsns\Pages\Model\Special; # pages 1.3.3 required 

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
	 * 上传图片文件
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function upload( $query, $data ) {

		$u = new UserModel();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		$resp = $this->__savefile([
			"host" => Utils::getHome(),
			"image"=>["image/png", "image/jpeg", "image/gif"]
		]);

		return $resp;
	}



	/**
	 * 更新资料 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function updateProfile( $query, $data ) {

		// 许可字段清单
		$allowed = ["extra", "mobile", "address", "bio", "sex", "nickname", "country", "city", "headimgurl", "bgimgurl", "birthday", "language"];

		// 用户身份验证
		$u = new UserModel();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		$data = array_filter(
			$data,
			function ($key) use ($allowed) {
				return in_array($key, $allowed);
			},
			ARRAY_FILTER_USE_KEY
		);

		// 只能更新自己
		$data['user_id'] = $uinfo['user_id'];

		// 用户头像
		if ( !empty($data['headimgurl']) ) {
			$data['headimgurl'] = json_decode($data['headimgurl'], true);
		}

		// 背景图片
		if ( !empty($data['bgimgurl']) ) {
			$data['bgimgurl'] = json_decode($data['bgimgurl'], true);
		}


		$u->save( $data );
		$u->loginSetSession($uinfo['user_id']);

		return ['code'=>0, 'message'=>'数据保存成功', 'user_info'=>$u->getUserInfo()];
	}


	/**
	 * 实名认证申请 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function nameVerifiy( $query, $data ) {
		
		// 许可字段清单
		$allowed = ["name", "idno", "idtype", "iddoc"];

		// 用户身份验证
		$u = new UserModel();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		$data = array_filter(
			$data,
			function ($key) use ($allowed) {
				return in_array($key, $allowed);
			},
			ARRAY_FILTER_USE_KEY
		);
		
		$data['user_id'] = $uinfo['user_id']; // 只能更新自己的数据
		$data["name_verified"] = 'verifying'; // 标记为认证申请中

		// 证件文档
		if ( !empty($data['iddoc']) ) {
			$data['iddoc'] = json_decode($data['iddoc'], true);
		}

		return $u->save( $data );
	}


	/**
	 * 实名认证重置 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function nameReset( $query, $data ) {
		
		// 用户身份验证
		$u = new UserModel();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		$then = $query['then'];
		$data['user_id'] = $uinfo['user_id']; // 只能更新自己的数据
		$data["name_verified"] = 'unverified'; // 标记为未认证
		$rs = $u->save( $data );

		if ( !empty($then)) {
			header("Location: {$then}");
		}
		return $rs;
	}


	/**
	 * 我的专栏查询 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function mySpecial( $query, $data ) {

	}


	/**
	 * 我的专栏信息更新/开通 API
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function mySpecialUpdate( $query, $data ) {

		// 用户身份验证
		$u = new UserModel();
		$uinfo = $u->getUserInfo();
		if ( empty($uinfo['user_id']) ) {
			throw new Excp("用户尚未登录", 403,  ['user'=>$uinfo]);
		}

		// 许可字段清单
		$allowed = ["type", "name", "path", "logo", "category_ids", "summary", "docs"];
		$data = array_filter(
			$data,
			function ($key) use ($allowed) {
				return in_array($key, $allowed);
			},
			ARRAY_FILTER_USE_KEY
		);

		// handle files field
		if ( array_key_exists('logo', $data) && is_string($data['logo']) ) {
			$data['logo'] = json_decode($data['logo'], true);
		}
		if ( array_key_exists('docs', $data) && is_string($data['docs']) ) {
			$data['docs'] = json_decode($data['docs'], true);
		}

		// User ID 
		$data['user_id'] = $uinfo['user_id'];

		
		// SaveData 检查专栏是否存在, 存在则更新，不存在则创建
		$sp = new Special();
		$us = $sp->query()->where('user_id', $data['user_id'])->limit(1)->select('special_id')->get()->toArray();
		if ( empty($us) ) { 
			$us = $sp->create($data);
		} else { 
			$data["special_id"] = current($us)["special_id"];
			$us = $sp->updateBy("special_id", $data);
		}

		return $us;
	}


	/**
	 * 读取用户自己相关信息(可公开的)
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function getData( $query, $data ) {

		// 许可字段清单
		$user_id = $query['user_id'];
		if ( empty($user_id) ) {
			return [];
		}

		$allowed = [
			"user_id","bio", "sex", "nickname", "country", "city", "headimgurl", "bgimgurl", "birthday", "language", 
			"name", "idno", "idtype", "iddoc", "name_verified"
		];
		$query['select'] = is_string($query['select']) ?  explode(',',$query['select']) : $query['select'];
		$select = empty($query['select']) ? $allowed : $query['select'];
		$select = array_intersect($select, $allowed);
		$u = new UserModel();
		return $u->getByUid( $user_id, $select );
	}



	/**
	 * 微信推送-消息接收器 (禁止直接调用)
	 * @param $query['query']	微信 GET参数
	 *		$query['message']  解密后消息正文
	 * @return 
	 */
	protected function wechatRouter( $query ) {

		$log = new \Xpmse\Log("Wechat");

		$message = $query['message'];
		$param =  $query['query'];
		$appid = $param['appid'];

		if ( empty($appid) ){
			throw new Excp("未知请求来源 ($appid)", 404, ["appid"=>$appid, "param"=>$param, "message"=>$message]);
		}

		if ( $message['MsgType'] != 'event' ) {
			return ["code"=>0, "result"=>"忽略", "message"=>null];
		}

		// 扫描带参二维码
		if ( $message['Event'] == 'SCAN' || $message['Event'] == 'subscribe' ) {

			$u = new UserModel();
			$ss = $u->extractWechatScanEvent($message);

			if ( $ss === null ) {
				return ["code"=>0, "result"=>"忽略", "message"=>null];
			}

			$message = $u->loginByOpenId($appid, $ss['openid'], $ss['sid'])
					 ->replyText("登录成功 \n@".date('Y年m月d日 h:i:s'), $message["ToUserName"]);
			return ["code"=>0, "result"=>"成功", "message"=>$message];
		}

		return ["code"=>0, "result"=>"忽略", "message"=>null];
	}


	/**
	 * 微信用户登录
	 * @param  [type] $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function wxappLogin( $query, $data ) {

		$this->authSecret($query['_secret']);
		$appid = !empty($query['_appid']) ? $query['_appid'] : null;
		$conf = Utils::getConf();

		if ( $appid !== null ){
			$cfg = $conf['_map'][$appid]; 
		} else if ( is_array($conf['_type']['3'])) {
			$cfg = current($conf['_type']['3']);
		}

		if ( empty($cfg) ) {
			throw new Excp("未找到有效的微信公众号配置", 404);
		}

		$u = new UserModel();
		$user_id = $u->updateWxappUser( $cfg['appid'], $data );
		$user = $u->getByUid($user_id);
        $user["_id"] = $user_id;
        
       
        try {  // 触发用户登录行为
            \Xpmsns\User\Model\Behavior::trigger("xpmsns/user/user/signin", $user );
        }catch(Excp $e) { $e->log(); }

		return $user;
	}



	/**
	 * 用户登录二维码
	 * @return [type] [description]
	 */
	protected function wechatSigninQrcode() {

		$option =  new Option("xpmsns/user");
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

		if ( Err::isError($resp) ) {
			$err = $resp->toArray();
			throw new Excp($err['message'], $err['code'], $err['extra']);
		}

		return [
			"name" => $cfg['name'],
			"url" => $resp['showqrcode'],
			"expire_seconds" => $resp['expire_seconds']
		];
	}




	/**
	 * 服务号二维码
	 * @return [type] [description]
	 */
	protected function wechatQrcode( $query ) {

		$option =  new Option("xpmsns/user");
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

		$query["scene_id"] = !empty($query["scene_id"]) ? $query["scene_id"] : 10086;
		$wechat = new Wechat( $cfg );

		$resp = $wechat->getQrcodeURL([
			"action_name" => "QR_LIMIT_SCENE",
			'action_info'=>[
				"scene"=>[
					"scene_id"=>$query["scene_id"]
				]
			]
		]);

		if ( Err::isError($resp) ) {
			$err = $resp->toArray();
			throw new Excp($err['message'], $err['code'], $err['extra']);
		}

		return [
			"name" => $cfg['name'],
			"url" => $resp['showqrcode'],
			"expire_seconds" => $resp['expire_seconds']
		];
	}


	/**
	 * 微信授权登录
	 */
	protected function wechatAuthUrl( $query ) {

		$option =  new Option("xpmsns/user");
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

		$back = !empty($query['back']) ? $query['back'] : "/";
		$back = urlencode($back);

		$authback = Utils::getHomeLink() . "/_api/xpmsns/user/user/wechatAuthBack?back={$back}";

		$wechat = new Wechat( $cfg );
		$url = $wechat->authUrl($authback);
		if (  $query['debug'] == 1 ) {
 			echo $url;
 			return;
 		}

 		// 转向微信授权页面
 		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' .  $url );
		
	}


	/**
	 * 微信授权登录成功
	 * @param  [type] $query [description]
	 * @return [type]		[description]
	 */
	protected function wechatAuthBack( $query ){

		$option =  new Option("xpmsns/user");
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

		$wechat = new Wechat( $cfg );
		$back = !empty($query['back']) ? $query['back'] : "/";
		$back = urldecode($back);
		$userInfo = $wechat->getAuthUser($_GET['code'], $_GET['state']);

		$u = new UserModel();
		$u->loginByOpenId($cfg['appid'], $userInfo['openid'], session_id() );

		// 转向微信授权页面
 		header('HTTP/1.1 301 Moved Permanently');
		header('Location: ' .  $back );

	}




	/**
	 * 读取用户配置信息
	 * @return
	 */
	protected function option( $query=[] ) {

		$opt =  new Option("xpmsns/user");
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
	 * @return [type]		[description]
	 */
	protected function login( $query=[], $data=[] ) {
		

		$this->authVcode();
		$u = new UserModel();
		$mobile = $data['mobile'];
		$data['mobile_nation'] = empty($data['mobile_nation']) ? '86' : $data['mobile_nation'];

		if ( empty($mobile) ) {
			throw new Excp("未知手机号码", 404, ['data'=>$data, 'query'=>$query, 'errorlist'=>[['mobile'=>'未知手机号码']]]);
		}
		
		$u->login($mobile, $data['password'], $data['mobile_nation'] );
		$uinfo = $u->getUserInfo();
		if (empty($uinfo) ){
			throw new Excp("系统错误, 读取用户信息失败", 500, ['data'=>$data, 'query'=>$query]);
		}

        
        // 触发用户登录行为
        try {  // 触发用户登录行为
            \Xpmsns\User\Model\Behavior::trigger("xpmsns/user/user/signin", $user );
        }catch(Excp $e) { $e->log(); }

		return $uinfo;
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
	 * @return [type]		[description]
	 */
	protected function getUserInfo( $query=[], $data=[] ) {
		$u = new UserModel();
		return $u->getUserInfo();
    }
    
    
    /**
     * 读取用户账户信息
     */
    protected function getUserAccountInfo( $query=[], $data=[] ) {
		$u = new UserModel();
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 503, [""]);
        }


        $balance = $u->getBalance( $user_id );
        $coin = $u->getCoin( $user_id );

        return [
            "user_id" => $user_id,
            "balance" => $balance,
            "coin" => $coin,
        ];
    }



	/**
	 * 创建一个新用户
	 * @param  array  $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function create( $query=[], $data=[] ) {

		// return ["message"=>"注册成功", "code"=>0 ]; // 快速测试
		
		$this->authVcode();

		$opt =  new Option("xpmsns/user");
		$options = $opt->getAll();
		$map = $options['map'];		

		// 校验手机号码
		if ( empty($data['mobile']) ) {
			throw new Excp("手机号码格式不正确", 402, ['data'=>$data, 'errorlist'=>[['mobile'=>'手机号码格式不正确']]]);
		}

		$data['mobile_nation'] = !empty($data['nation']) ? $data['nation'] : '86';

		// 校验短信验证码
		if( $map['user/sms/on'] == 1) {
			$data['mobile_verified'] = true;
			if ( $this->verifySMSCode($query, $data) === false) {
				throw new Excp("短信验证码不正确", 402, ['data'=>$data, 'errorlist'=>[['smscode'=>'短信验证码不正确']]]);
			}
		}

		// 检查密码
		if ( isset($data['repassword']) ) { 
			if ( $data['password'] != $data['repassword'] ) {
				throw new Excp("两次输入的密码不一致", 402, ['data'=>$data, 'errorlist'=>[['repassword'=>'两次输入的密码不一致']]]);
			}
		}
		if ( empty($data['password']) ) {
			throw new Excp("请输入登录密码", 402, ['data'=>$data, 'errorlist'=>[['password'=>'请输入登录密码']]]);
		}

		// Group
		$g = new GroupModel();
		if ( isset( $data['group_slug']) ) { 	
			$slug = $data['group_slug'];
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
		}  

		if ( empty($data['group_id']) ) {
			$slug = $map['user/default/group'];
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
        }

        $u = new UserModel();
        
        // 邀请注册
        $inviter = $u->getInviter();
        if ( !empty($inviter) ) {
            $data["inviter"] = $inviter["user_id"];
        }

		// 数据入库
		try {
			$resp = $u->create($data);
		} catch(Excp $e ){
			if ( $e->getCode() == '1062') {
				throw new Excp("手机号 +{$data['mobile_nation']} {$data['mobile']} 已被注册", 402, ['data'=>$data, 'errorlist'=>[['mobile'=>'手机号码已被注册']]]);
			}
			throw $e;
        }
        
        if ( !empty($resp) ) {
            try {  // 触发用户注册行为
                $resp["inviter"] = $inviter;
                \Xpmsns\User\Model\Behavior::trigger("xpmsns/user/user/signup", $resp);
            }catch(Excp $e) { $e->log(); }
        }

		return ["message"=>"注册成功", "code"=>0 ];
	}


	/**
	 * 微信登录后绑定手机号码
	 * @param  array  $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]		[description]
	 */
	protected function bindMobile( $query=[], $data=[] ) {

		$this->authVcode();

		$uinfo = $this->getUserInfo();
		$user_id = $uinfo['user_id'];
		$data['user_id'] = $user_id;

		if ( empty($user_id) ) {
			throw new Excp("尚未登录", 500, ['data'=>$data]);
		}

		$opt =  new Option("xpmsns/user");
		$options = $opt->getAll();
		$map = $options['map'];		

		// 校验手机号码
		if ( empty($data['mobile']) ) {
			throw new Excp("手机号码格式不正确", 402, ['data'=>$data]);
		}

		$data['mobile_nation'] = !empty($data['nation']) ? $data['nation'] : '86';


		// 校验短信验证码
		if( $map['user/sms/on'] == 1) {
			$data['mobile_verified'] = true;
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
		if ( isset( $data['group_slug']) ) { 
			$slug = $data['group_slug'];
			$g = new GroupModel();
			$rs = $g->getBySlug($slug);
			$data['group_id'] = $rs['group_id'];
		}

		// 数据入库
		$u = new UserModel();
		$u->updateBy('user_id', $data );
		$u->login($data['mobile'], $data['password'], $data['mobile_nation'] );

		return ["message"=>"绑定成功", "code"=>0 ];
	}



	/**
	 * 校验手机短信验证码
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]		[description]
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
	 * @return [type]		[description]
	 */
	protected function getSMSCode( $query = [] ) {

		$this->authVcodeOnly();

		// 是否开启验证码
		$opt =  new Option("xpmsns/user");
		$options = $opt->getAll();
		$map = $options['map'];

		if( $map['user/sms/on'] != 1) {
			throw new Excp("非法请求 未开启短信验证码", 401, ['query'=>$query]);
		}

		// 提交信息校验
		if ( empty($query['mobile']) ) {
			throw new Excp("未知手机号码", 401, ['query'=>$query, 'errorlist'=>[["mobile"=>"未知手机号码"]]]);
		}

		$now = time();
		$lock_time = 60;
		$locked_at = intval($_SESSION['SMSCODE:locked_at']);

		if ( ( $now - $locked_at ) < $lock_time ) {
			throw new Excp("请求过于频繁", 403, ['locked_at'=>$locked_at, 'errorlist'=>[["smscode"=>"请求过于频繁"]]]);
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
	 * 查询标签列表 ( 废弃)
	 *
	 * 读取字段 select 默认 name
	 *
	 *	示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *	许可值: "*","tag_id","name","param"
	 * 
	 * 
	 * 查询条件
	 * 	  1. 按名称查询  name | orName | inName
	 * 	  3. 按标签ID查询  tagId | orTagId | inTagId 
	 * 	  8. 按参数标记查询  param | orParam
	 * 	  
	 * 排序方式 order 默认 tag_id  updated_at asc, tag_id desc
	 * 
	 *	1. 按标签更新顺序  updated_at
	 *	2. 按标签创建顺序  tag_id  
	 *	
	 *
	 * 当前页码 page	默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query 
	 * @return array 文章结果集列表
	 */
	protected function ______search( $query=[] ) {

		$this->authVcode();
		return $query;

	}



	/**
	 * 读取标签详情信息( 废弃 )
	 * @param  array  $query Query 查询
	 *				   int ["name"]  标签详情
	 *				   
	 *		  string|array ["select"] 读取字段  
	 *		  			 示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *		  			 许可值: "*","tag_id","name","param"
	 *					
	 * @return Array 文章数据
	 * 
	 */
	protected function ___get( $query=[] ) {

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
		
		$cate = new \Xpmsns\Pages\Model\Tag;
		$rs = $cate->getLine("WHERE name=:name LIMIT 1", $select, ["name"=>$name]);
		if ( empty($rs) ) {
			throw new Excp("标签不存在", 404,  ['query'=>$query]);
		}

		return $rs;
	}

}