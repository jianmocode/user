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
			
		$this->putColumn( 'user_id', $this->type('string', ['length'=>128, 'unique'=>1]) )    // 用户ID 
			 ->putColumn( 'name', $this->type('string',  ['length'=>128]) )  // 用户名称
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