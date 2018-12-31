<?php
/**
 * Class Subscriber 
 * 订阅数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 12:41:33
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Subscriber extends Api {

	/**
	 * 订阅数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条订阅记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["subscriber.subscriber_id","subscriber.ourter_id","subscriber.origin","subscriber.status","subscriber.created_at","subscriber.updated_at","behavior.slug","behavior.name"]
	 * 				 $query['subscriber_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["subscriber.subscriber_id","subscriber.ourter_id","subscriber.origin","subscriber.status","subscriber.created_at","subscriber.updated_at","behavior.slug","behavior.name"]
	 * 				 $data['subscriber_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 订阅记录 Key Value 结构数据 
	 *               	["subscriber_id"],  // 订阅者ID 
	 *               	["behavior_slug"],  // 行为别名 
	*               	["behavior_slug"], // behavior.slug
	 *               	["ourter_id"],  // 来源ID 
	 *               	["origin"],  // 来源 
	 *               	["origin_ourter_id"],  // 唯一来源ID 
	 *               	["handler"],  // 处理器 
	 *               	["timeout"],  // 超时时长 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["behavior_created_at"], // behavior.created_at
	*               	["behavior_updated_at"], // behavior.updated_at
	*               	["behavior_behavior_id"], // behavior.behavior_id
	*               	["behavior_name"], // behavior.name
	*               	["behavior_intro"], // behavior.intro
	*               	["behavior_status"], // behavior.status
	*               	["behavior_params"], // behavior.params
	*               	["behavior_before"], // behavior.before
	*               	["behavior_after"], // behavior.after
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["subscriber.subscriber_id","subscriber.ourter_id","subscriber.origin","subscriber.status","subscriber.created_at","subscriber.updated_at","behavior.slug","behavior.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按订阅者ID
		if ( !empty($data["subscriber_id"]) ) {
			
			$keys = explode(',', $data["subscriber_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Subscriber;
				return $inst->getInBySubscriberId($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Subscriber;
			return $inst->getBySubscriberId($data["subscriber_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索订阅记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["subscriber.subscriber_id","subscriber.ourter_id","subscriber.origin","subscriber.status","subscriber.created_at","subscriber.updated_at","behavior.slug","behavior.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["subscriber_id"] 按订阅者ID查询 ( AND = )
	 *			      $query["behavior_slug"] 按行为别名查询 ( AND = )
	 *			      $query["ourter_id"] 按来源ID查询 ( AND = )
	 *			      $query["origin"] 按来源查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=subscriber_id","name=ourter_id","name=origin","name=status","name=created_at","name=updated_at","model=%5CXpmsns%5CUser%5CModel%5CBehavior&name=slug&table=behavior&prefix=xpmsns_user_&alias=behavior&type=join","model=%5CXpmsns%5CUser%5CModel%5CBehavior&name=name&table=behavior&prefix=xpmsns_user_&alias=behavior&type=join"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["subscriber_id"] 按订阅者ID查询 ( AND = )
	 *			      $data["behavior_slug"] 按行为别名查询 ( AND = )
	 *			      $data["ourter_id"] 按来源ID查询 ( AND = )
	 *			      $data["origin"] 按来源查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *
	 * @return array 订阅记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["subscriber_id"],  // 订阅者ID 
	 *               	["behavior_slug"],  // 行为别名 
	*               	["behavior_slug"], // behavior.slug
	 *               	["ourter_id"],  // 来源ID 
	 *               	["origin"],  // 来源 
	 *               	["origin_ourter_id"],  // 唯一来源ID 
	 *               	["handler"],  // 处理器 
	 *               	["timeout"],  // 超时时长 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["behavior_created_at"], // behavior.created_at
	*               	["behavior_updated_at"], // behavior.updated_at
	*               	["behavior_behavior_id"], // behavior.behavior_id
	*               	["behavior_name"], // behavior.name
	*               	["behavior_intro"], // behavior.intro
	*               	["behavior_status"], // behavior.status
	*               	["behavior_params"], // behavior.params
	*               	["behavior_before"], // behavior.before
	*               	["behavior_after"], // behavior.after
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["subscriber.subscriber_id","subscriber.ourter_id","subscriber.origin","subscriber.status","subscriber.created_at","subscriber.updated_at","behavior.slug","behavior.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\User\Model\Subscriber;
		return $inst->search( $data );
	}


}