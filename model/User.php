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
			 ->putColumn( 'email', $this->type('string',  ['length'=>128, 'unique'=>true]) )  // 电邮地址
			 
			 ->putColumn( 'zip', $this->type('string',  ['length'=>40]) )  // 邮编
			 ->putColumn( 'address', $this->type('string',  ['length'=>256]) )  // 收货地址
			 ->putColumn( 'remark', $this->type('string',  ['length'=>256]) )  // 用户备注
			 ->putColumn( 'tag', $this->type('text',  ['json'=>true]) )  // 用户标签

			// 身份校验
			 ->putColumn( 'mobile_verified', $this->type('boolean',  ['default'=>"0"]) )  // 手机号是否通过校验
			 ->putColumn( 'email_verified', $this->type('boolean',  ['default'=>"0"]) )   // 电邮地址是否通过校验
			
			// 用户状态 on/off/lock
			 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>true, 'default'=>'on']) )
		;
	}



	function create( $data ) {
		$data['user_id'] = $this->genUserId();
		return parent::create( $data );
	}

	function genUserId() {
		return time() . rand(10000,99999);
	}


	function __clear() {
		$this->dropTable();
	}

}