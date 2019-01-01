<?php
/**
 * Class Task 
 * 任务数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-28 16:22:25
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
                         

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Task extends Api {

	/**
	 * 任务数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 接受任务
     * @method POST 
     * @param string task_id  [选填]任务ID ( task_id/slug 必填一项 )
     * @param string slug [选填]任务别名( task_id/slug 必填一项 )
     * @return array 任务副本结构体
     */
    function accept( $query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $task_id = $data["task_id"];
        $slug = $data["slug"];

        if ( empty($slug) && empty( $task_id ) ) {
              throw new Excp("请提供任务ID或者任务别名", 402, ["query"=>$query, "data"=>$data]);
        }

        $utask = new \Xpmsns\User\Model\UserTask;
        if ( !empty($task_id) ) {
            return $utask->acceptByTaskId( $task_id, $user_id);
        } 
        
        return $utask->acceptBySlug($slug, $user_id);
    }


    /**
     * 取消任务
     */
    function cancel( $query, $data ) {
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $usertask_id = $data["usertask_id"];
        if ( empty($usertask_id) ) {
            throw new Excp("请提供任务副本ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $utask = new \Xpmsns\User\Model\UserTask;
        $usertask = $utask->getByUsertaskId($usertask_id, ["user_id"]);
        if ( empty($usertask) ) {
            throw new Excp("用户尚未接受此项任务", 402, ["query"=>$query, "data"=>$data]);
        }

        if( $user_id != $usertask["user_id"])  {
            throw new Excp("当前用户没有该项任务的管理权限", 402, ["query"=>$query, "data"=>$data]);
        } 

        $rs = $utask->cancel( $usertask_id );
        if ( $rs["status"] == "canceled" ){
            return ["code"=>0, "message"=>"操作成功,任务已取消", "rs"=>$rs];
        }
        
        throw new Excp("取消失败,未知错误", 500, ["query"=>$query, "data"=>$data, "response"=>$rs]);
    }

    

    // @KEEP END

	/**
	 * 查询一条任务记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["task.task_id","task.slug","task.name","task.type","task.summary","task.quantity","task.hourly_limit","task.daily_limit","task.weekly_limit","task.monthly_limit","task.yearly_limit","task.time_limit","task.process","task.accept","task.complete","task.events","task.status","task.created_at","task.updated_at"]
	 * 				 $query['task_id']  按查询 (多条用 "," 分割)
	 * 				 $query['slug']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["task.task_id","task.slug","task.name","task.type","task.summary","task.quantity","task.hourly_limit","task.daily_limit","task.weekly_limit","task.monthly_limit","task.yearly_limit","task.time_limit","task.process","task.accept","task.complete","task.events","task.status","task.created_at","task.updated_at"]
	 * 				 $data['task_id']  按查询 (多条用 "," 分割)
	 * 				 $data['slug']  按查询 (多条用 "," 分割)
	 *
	 * @return array 任务记录 Key Value 结构数据 
	 *               	["task_id"],  // 任务ID 
	 *               	["slug"],  // 别名 
	 *               	["name"],  // 名称 
	 *               	["type"],  // 类型 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["quantity"],  // 积分数量 
	 *               	["formula"],  // 奖励公式 
	 *               	["hourly_limit"],  // 时限额 
	 *               	["daily_limit"],  // 日限额 
	 *               	["weekly_limit"],  // 周限额 
	 *               	["monthly_limit"],  // 月限额 
	 *               	["yearly_limit"],  // 年限额 
	 *               	["time_limit"],  // 完成时限 
	 *               	["process"],  // 步骤 
	 *               	["accept"],  // 接受条件 
	 *               	["complete"],  // 达成条件 
	 *               	["events"],  // 事件 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["task.task_id","task.slug","task.name","task.type","task.summary","task.quantity","task.hourly_limit","task.daily_limit","task.weekly_limit","task.monthly_limit","task.yearly_limit","task.time_limit","task.process","task.accept","task.complete","task.events","task.status","task.created_at","task.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按任务ID
		if ( !empty($data["task_id"]) ) {
			
			$keys = explode(',', $data["task_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Task;
				return $inst->getInByTaskId($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Task;
			return $inst->getByTaskId($data["task_id"], $select);
		}

		// 按别名
		if ( !empty($data["slug"]) ) {
			
			$keys = explode(',', $data["slug"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Task;
				return $inst->getInBySlug($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Task;
			return $inst->getBySlug($data["slug"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索任务记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["task.task_id","task.slug","task.name","task.type","task.summary","task.quantity","task.hourly_limit","task.daily_limit","task.weekly_limit","task.monthly_limit","task.yearly_limit","task.time_limit","task.process","task.accept","task.complete","task.status","task.created_at","task.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["task_id"] 按任务ID查询 ( AND = )
	 *			      $query["slug"] 按别名查询 ( AND = )
	 *			      $query["name"] 按名称查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["type"] 按类型查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=task_id","name=slug","name=name","name=type","name=summary","name=quantity","name=hourly_limit","name=daily_limit","name=weekly_limit","name=monthly_limit","name=yearly_limit","name=time_limit","name=process","name=accept","name=complete","name=status","name=created_at","name=updated_at"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["task_id"] 按任务ID查询 ( AND = )
	 *			      $data["slug"] 按别名查询 ( AND = )
	 *			      $data["name"] 按名称查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["type"] 按类型查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 任务记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["task_id"],  // 任务ID 
	 *               	["slug"],  // 别名 
	 *               	["name"],  // 名称 
	 *               	["type"],  // 类型 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["quantity"],  // 积分数量 
	 *               	["formula"],  // 奖励公式 
	 *               	["hourly_limit"],  // 时限额 
	 *               	["daily_limit"],  // 日限额 
	 *               	["weekly_limit"],  // 周限额 
	 *               	["monthly_limit"],  // 月限额 
	 *               	["yearly_limit"],  // 年限额 
	 *               	["time_limit"],  // 完成时限 
	 *               	["process"],  // 步骤 
	 *               	["accept"],  // 接受条件 
	 *               	["complete"],  // 达成条件 
	 *               	["events"],  // 事件 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["task.task_id","task.slug","task.name","task.type","task.summary","task.quantity","task.hourly_limit","task.daily_limit","task.weekly_limit","task.monthly_limit","task.yearly_limit","task.time_limit","task.process","task.accept","task.complete","task.status","task.created_at","task.updated_at"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\User\Model\Task;
		return $inst->search( $data );
	}


}