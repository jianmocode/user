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
 * 用户组数据模型
 */
class Group extends Model {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct( $param=[] ) {
		parent::__construct(['prefix'=>'mina_user_']);
		$this->table('group');
	}
	
	/**
	 * 数据表结构
	 * @see https://laravel.com/docs/5.3/migrations#creating-columns
	 * @return [type] [description]
	 */
	function __schema() {
		
		$this->putColumn( 'group_id', $this->type('string', ['length'=>128, 'unique'=>1]) )    // 用户组ID 
			 ->putColumn( 'slug', $this->type('string',  ['length'=>128, 'unique'=>1]) )  // 用户组别名
			 ->putColumn( 'name', $this->type('string',  ['length'=>256]) )  // 用户组名称
			 ->putColumn( 'remark', $this->type('string',  ['length'=>256]) )  // 用户组备注
			 ->putColumn( 'tag', $this->type('text',  ['json'=>true]) )  // 用户组标签

			// 用户状态 on/off/lock
			 ->putColumn( 'status', $this->type('string', ['length'=>10,'index'=>1, 'default'=>'on']) )
		;
	}


	function getBySlug( $slug, $status=null ) {
		$qb = $this->query()
					->where("slug", "=", $slug);
		if ( $status !== null ) {
			$qb->where("status", "=", $status);
		}					

		$rs = $qb->limit(1)
		   ->select('group_id', 'slug', 'name', "remark", 'tag','status')
		   ->get();

		   
		if ( empty($rs) ) {
			return [];
		}

		return current($rs);
	}



	function create( $data ) {
		$data['group_id'] = $this->genGroupId();
		if (empty($data['slug']) ) {
			$data['slug'] = $data['group_id'];
		}
		return parent::create( $data );
	}


	function genGroupId() {
		return time() . rand(10000,99999);
	}


	function __clear() {
		$this->dropTable();
	}

}