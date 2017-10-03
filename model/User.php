<?php
namespace Mina\User\Model; 
define('__NS__', 'Mina\User\Model'); // 兼容旧版 App::M 方法调用

use \Tuanduimao\Mem as Mem;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Err as Err;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Model as Model;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Wechat as Wechat;
use \Tuanduimao\Option as Option;


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
		parent::__construct(['prefix'=>'mina_user_']);
		$this->table('user');

		// 微信公众号授权表
		$this->user_wechat = Utils::getTab('user_wechat', "mina_user_");  
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
		
		$this->putColumn( 'user_id', $this->type('string', ['length'=>128, 'unique'=>true]) )    // 用户ID 
			 ->putColumn( 'group_id', $this->type('string',  ['length'=>128, 'index'=>true]) )   // 用户组

			
			 ->putColumn( 'nickname', $this->type('string',  ['length'=>256]) )  // 用户名称
			 ->putColumn( 'sex', $this->type('integer',  ['length'=>1,  "index"=>true]) )  // 用户性别
			 ->putColumn( 'city', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在城市
			 ->putColumn( 'province', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在省份
			 ->putColumn( 'country', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在国家
			 ->putColumn( 'headimgurl', $this->type('string',  ['length'=>256]) )  // 用户头像地址
			 ->putColumn( 'language', $this->type('string',  ['length'=>20]) )  // 系统语言

			 // 常用字段
			 ->putColumn( 'mobile', $this->type('string',  ['length'=>40, 'unique'=>true]) )  // 手机号
			 ->putColumn( 'mobile_nation', $this->type('string',  ['length'=>40, 'unique'=>true]) )  // 手机号
			 ->putColumn( 'email', $this->type('string',  ['length'=>128, 'unique'=>true]) )  // 电邮地址
			 
			 ->putColumn( 'zip', $this->type('string',  ['length'=>40]) )  // 邮编
			 ->putColumn( 'address', $this->type('string',  ['length'=>256]) )  // 收货地址
			 ->putColumn( 'remark', $this->type('string',  ['length'=>256]) )  // 用户备注
			 ->putColumn( 'tag', $this->type('text',  ['json'=>true]) )  // 用户标签

			// 身份校验
			 ->putColumn( 'mobile_verified', $this->type('boolean',  ['default'=>"0"]) )  // 手机号是否通过校验
			 ->putColumn( 'email_verified', $this->type('boolean',  ['default'=>"0"]) )   // 电邮地址是否通过校验
			
			// 登录密码
			->putColumn( 'password', $this->type('string', ['length'=>128] ) )

			// 支付密码
			->putColumn( 'pay_password', $this->type('string', ['length'=>128] ) )

			// 用户状态 on/off/lock
			 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>true, 'default'=>'on']) )
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
	 * 读取用户信息
	 * @return [type] [description]
	 */
	function getUserInfo() {

		@session_start();
		$rs = $_SESSION['USER:info'];
		if ( empty($rs) ) {
			return [];
		}

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
			$opt =  new Option("mina/user");
			$options = $opt->getAll();
			$map = $options['map'];	

			$slug = $map['user/default/group'];
			$g = new Group();
			$rs = $g->getBySlug($slug);
			$u['group_id'] = $rs['group_id'];
			$this->create( $u );

		} else {
			$this->updateBy("user_id", $u);
		}

		$this->appid = $appid;
		$this->openid = $openid;
		$this->unionid = $u['unionid'];
		$this->cfg = $cfg;
		return $user_id;
	}



	/**
	 * 用户登录
	 */
	function login( $mobile, $password = null ) {

		$rows = $this->query()
				->where('mobile', '=', $mobile)
				->limit(1)
				->select(
					"user_id",
					"mobile", "mobile_nation", "mobile_verified",
					"password", "pay_password"
				)
				->get()
				->toArray();

		if ( empty($rows) ) {
			throw new Excp( "用户不存在", 404, ['data'=>$data, 'query'=>$query]);
		}

		$rs = current($rows);

		if ( $password != null ) {
			if ( $this->checkPassword($password, $rs['password']) === false ) {
				throw new Excp( "密码错误", 404, ['data'=>$data, 'query'=>$query]);
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
					"nickname", "sex", "city", "province", "country","headimgurl", "language",
					"mobile", "mobile_nation", "mobile_verified",
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
			throw new Excp( "用户不存在", 404, ['data'=>$data, 'query'=>$query]);
		}

		$rs = current($rows);

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

		$opt =  new \Tuanduimao\Option("mina/user");
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
		
		return parent::create( $data );
	}




	function genUserId() {
		return time() . rand(10000,99999);
	}


	function __clear() {
		$this->dropTable();
		$this->user_wechat->dropTable();
	}

}