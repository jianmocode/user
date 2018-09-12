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
			 ->putColumn( 'iddoc', $this->type('string',  ['length'=>256]) )  // 身份证件类型 1 身份证 2 军人身份证 3 警察身份证  4 港澳通行证  5 台胞证  6 护照  7 其他
			 ->putColumn( 'nickname', $this->type('string',  ['length'=>128, 'index'=>true]) )  // 用户名称
			 ->putColumn( 'sex', $this->type('integer',  ['length'=>1,  "index"=>true]) )  // 用户性别
			 ->putColumn( 'city', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在城市
			 ->putColumn( 'province', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在省份
			 ->putColumn( 'country', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在国家
			 ->putColumn( 'headimgurl', $this->type('string',  ['length'=>256]) )  // 用户头像地址
			 ->putColumn( 'language', $this->type('string',  ['length'=>20]) )  // 系统语言
			 ->putColumn( 'birthday', $this->type('timestamp',  []) )  // 出生日期


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
			 ->putColumn( 'user_verified', $this->type('boolean',  ['default'=>"0"]) )    // 用户是否通过身份认证
			 ->putColumn( 'name_verified', $this->type('boolean',  ['default'=>"0"]) )    // 用户是否通过实名认证
			 ->putColumn( 'verify', $this->type('string',  ['length'=>128]) )    	      // 用户身份信息
			 ->putColumn( 'verify_data', $this->type('text',  ['json'=>true]) )  		  // 用户认证证明材料
			 ->putColumn( 'mobile_verified', $this->type('boolean',  ['default'=>"0"]) )  // 手机号是否通过校验
			 ->putColumn( 'email_verified', $this->type('boolean',  ['default'=>"0"]) )   // 电邮地址是否通过校验
			
			// 扩展属性字段
			->putColumn( 'extra', $this->type('text',  ['json'=>true]) )  // 扩展属性 JSON

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
		$rs = !empty($_SESSION['USER:info']) ? $_SESSION['USER:info'] : $_SESSION['_uinfo'] ;
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
			$this->updateBy("user_id", $u);
		}

		$this->appid = $appid;
		$this->openid = $openid;
		$this->unionid = $u['unionid'];
		$this->cfg = $cfg;
		return $user_id;
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
			throw new Excp( "用户不存在", 404, ['data'=>$data, 'query'=>$query, 'errorlist'=>[['mobile'=>'用户不存在']]]);
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
	function getByUid( $user_id ) {

		$user = $this->query()
					 ->where("user_id", "=", $user_id)
					 ->limit(1)
					 ->select('*')
					 ->get()
					 ->toArray()
				;

		$this->formatUsers($user);

		if ( empty($user) ) {
			return [
				"user_id" => $this->genUserId()
			];
		}

		return current($user);
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
					$users[$idx]['headimg_url'] = $user['headimgurl'];
					$users[$idx]['headimg_path'] = '';
				}  else {
					$img =  $media->get($user['headimgurl']);
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
			}  else {
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