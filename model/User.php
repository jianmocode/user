<?php
namespace Mina\User\Model; 
define('__NS__', 'Mina\User\Model'); // 兼容旧版 App::M 方法调用

use \Tuanduimao\Mem as Mem;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Err as Err;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Model as Model;
use \Tuanduimao\Utils as Utils;


/**
 * 用户数据模型
 */
class User extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'mina_user_']);
		$this->table('user');
	}

	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
		
		$this->putColumn( 'user_id', $this->type('string', ['length'=>128, 'unique'=>true]) )    // 用户ID 
			 ->putColumn( 'group_id', $this->type('string',  ['length'=>128, 'index'=>true]) )   // 用户组

			 ->putColumn( 'openId',  $this->type('string', ['length'=>128, 'unique'=>true] ) )	  // 微信 openId
			 ->putColumn( 'unionId',  $this->type('string', ['length'=>128, 'unique'=>true]) )   // 微信 unionId
			 ->putColumn( 'nickName', $this->type('string',  ['length'=>256]) )  // 用户名称
			 ->putColumn( 'gender', $this->type('integer',  ['length'=>1,  "index"=>true]) )  // 用户性别
			 ->putColumn( 'city', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在城市
			 ->putColumn( 'province', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在省份
			 ->putColumn( 'country', $this->type('string',  ['length'=>100,  "index"=>true]) )  // 所在国家
			 ->putColumn( 'avatarUrl', $this->type('string',  ['length'=>256]) )  // 用户头像地址
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
			->putColumn( 'payPassword', $this->type('string', ['length'=>128] ) )

			// 用户状态 on/off/lock
			 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>true, 'default'=>'on']) )
		;
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



	function create( $data ) {

		$data['user_id'] = $this->genUserId();
		if (array_key_exists('password', $data) ) {
			$data['password'] = $this->hashPassowrd($data['password']);
		}

		if (array_key_exists('payPassword', $data) ) {
			$data['payPassword'] = $this->hashPassowrd($data['payPassword']);
		}
		
		return parent::create( $data );
	}




	function genUserId() {
		return time() . rand(10000,99999);
	}


	function __clear() {
		$this->dropTable();
	}

}