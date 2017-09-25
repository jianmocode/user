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

		$select = empty($query['select']) ? 'name' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$allowFields = ["*","tag_id","name","param"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}
		}

	
		// Order 默认参数
		$query['order'] = !empty($query['order']) ? $query['order'] : 'tag_id';
		$allowOrder = ["updated_at", "tag_id"];
		$orderList = explode(',', $query['order']);


		// 分页参数
		$query['page'] = !empty($query['page']) ? intval($query['page']) : 1;
		$query['perpage'] = !empty($query['perpage']) ? intval($query['perpage']) : 50;



		// 查询数据表
		$t = new \Mina\Pages\Model\Tag;
		$qb = $t->query();

		// 设定查询条件
		$this->qb( $qb, 'name', 'name', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'tag_id', 'tagId', $query, ["and", "or", "in"] );
		$this->qb( $qb, 'param', 'param', $query, ['and', 'or'], 'like');

		// 处理排序
		foreach ($orderList as $order) {
			$order = trim($order);
			$orderArr = preg_split('/[ ]+/', $order );
			$orderArr[1] = !empty($orderArr[1]) ? $orderArr[1] : 'desc';

			if ( !in_array($orderArr[0], $allowOrder)) {
				throw new Excp(" order 参数错误 ({$orderArr[0]} 非法字段)", 400, ['query'=>$query]);
			}

			$qb->orderBy($orderArr[0],$orderArr[1]);
		}
		
		// 查询数据
		$qb->select( $select );
		$result = $qb ->paginate($query['perpage'],['tag_id'], 'page', $query['page'] );
		$resultData = $result->toArray();
		

		// 处理结果集
		$data = $resultData['data'];

		$resp['curr'] = $resultData['current_page'];
		$resp['perpage'] = $resultData['per_page'];
		
		$resp['next'] = ( $resultData['next_page_url'] === null ) ? false : intval( str_replace('/?page=', '',$resultData['next_page_url']));
		$resp['prev'] = ( $resultData['prev_page_url'] === null ) ? false : intval( str_replace('/?page=', '',$resultData['prev_page_url']));

		$resp['from'] = $resultData['from'];
		$resp['to'] = $resultData['to'];
		
		$resp['last'] = $resultData['last_page'];
		$resp['total'] = $resultData['total'];
		$resp['data'] = $data;

		if ( empty($data) ) {
			return $resp;
		}

		if ( count(end($data)) == 1) {
			foreach ($data as $idx=>$rs ) {
				$resp['data'][$idx] = current($rs);
			}
		}
		
		return $resp;

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