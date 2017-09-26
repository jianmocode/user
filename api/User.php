<?php

namespace Mina\User\Api;

use \Tuanduimao\Loader\App;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
use \Tuanduimao\Api;


/**
 * 标签API接口
 */
class User extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {

		parent::__construct();
		$this->allowMethod('get', ["PHP",'GET'])
		     ->allowQuery('get',  ['tagId', 'select'])
		     ->allowMethod('search', ["PHP",'GET'])
		     ->allowQuery('search',  [
		     	"select",
		     	'name','orName','inName',
		     	'fullname','orFullname','inFullname',
		     	'categoryId','orcategoryId','incategoryId',
		     	'parentId','orParentId','inParentId',
		     	'children',
		     	'hidden', 'orHidden',
		     	'status', 'orStatus',
		     	'praram','orParam',
		     	'order',
		     	'page','perpage'
		     ]);
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