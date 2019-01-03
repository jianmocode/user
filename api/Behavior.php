<?php
/**
 * Class Behavior 
 * 行为数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-03 12:22:36
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Behavior extends Api {

	/**
	 * 行为数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条行为记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["behavior.behavior_id","behavior.slug","behavior.name","behavior.intro","behavior.status","behavior.params","behavior.created_at","behavior.updated_at"]
	 * 				 $query['behavior_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["behavior.behavior_id","behavior.slug","behavior.name","behavior.intro","behavior.status","behavior.params","behavior.created_at","behavior.updated_at"]
	 * 				 $data['behavior_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 行为记录 Key Value 结构数据 
	 *               	["behavior_id"],  // 行为ID 
	 *               	["slug"],  // 别名 
	 *               	["name"],  // 名称 
	 *               	["intro"],  // 介绍 
	 *               	["status"],  // 状态 
	 *               	["params"],  // 参数表 
	 *               	["before"],  // 发生前 
	 *               	["after"],  // 发生后 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["behavior.behavior_id","behavior.slug","behavior.name","behavior.intro","behavior.status","behavior.params","behavior.created_at","behavior.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按行为ID
		if ( !empty($data["behavior_id"]) ) {
			
			$keys = explode(',', $data["behavior_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Behavior;
				return $inst->getInByBehaviorId($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Behavior;
			return $inst->getByBehaviorId($data["behavior_id"], $select);
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Behavior;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Behavior;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索行为记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["behavior.behavior_id","behavior.slug","behavior.name","behavior.intro","behavior.status","behavior.created_at","behavior.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["behavior_id"] 按行为ID查询 ( AND = )
	 *			      $query["slug"] 按别名查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按创建时间 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=behavior_id","name=slug","name=name","name=intro","name=status","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["behavior_id"] 按行为ID查询 ( AND = )
	 *			      $data["slug"] 按别名查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按创建时间 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按更新时间 DESC 排序
	 *
	 * @return array 行为记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["behavior_id"],  // 行为ID 
	 *               	["slug"],  // 别名 
	 *               	["name"],  // 名称 
	 *               	["intro"],  // 介绍 
	 *               	["status"],  // 状态 
	 *               	["params"],  // 参数表 
	 *               	["before"],  // 发生前 
	 *               	["after"],  // 发生后 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["behavior.behavior_id","behavior.slug","behavior.name","behavior.intro","behavior.status","behavior.created_at","behavior.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\User\Model\Behavior;
		return $inst->search( $data );
	}


}