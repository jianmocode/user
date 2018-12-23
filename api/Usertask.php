<?php
/**
 * Class Usertask 
 * 任务数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 22:55:37
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
         

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Usertask extends Api {

	/**
	 * 任务数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条任务记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["usertask.status","user.user_id","user.name","user.nickname","user.mobile","task.task_id","task.slug","task.name","task.quantity","task.type","task.cover","task.process"]
	 * 				 $query['usertask_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["usertask.status","user.user_id","user.name","user.nickname","user.mobile","task.task_id","task.slug","task.name","task.quantity","task.type","task.cover","task.process"]
	 * 				 $data['usertask_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 任务记录 Key Value 结构数据 
	 *               	["usertask_id"],  // 用户任务ID 
	 *               	["user_id"],  // 用户ID 
	*               	["user_user_id"], // user.user_id
	 *               	["task_id"],  // 任务ID 
	*               	["task_task_id"], // task.task_id
	 *               	["process"],  // 进度 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["user_created_at"], // user.created_at
	*               	["user_updated_at"], // user.updated_at
	*               	["user_group_id"], // user.group_id
	*               	["user_name"], // user.name
	*               	["user_idno"], // user.idno
	*               	["user_iddoc"], // user.iddoc
	*               	["user_nickname"], // user.nickname
	*               	["user_sex"], // user.sex
	*               	["user_city"], // user.city
	*               	["user_province"], // user.province
	*               	["user_country"], // user.country
	*               	["user_headimgurl"], // user.headimgurl
	*               	["user_language"], // user.language
	*               	["user_birthday"], // user.birthday
	*               	["user_mobile"], // user.mobile
	*               	["user_mobile_nation"], // user.mobile_nation
	*               	["user_mobile_full"], // user.mobile_full
	*               	["user_email"], // user.email
	*               	["user_contact_name"], // user.contact_name
	*               	["user_contact_tel"], // user.contact_tel
	*               	["user_title"], // user.title
	*               	["user_company"], // user.company
	*               	["user_zip"], // user.zip
	*               	["user_address"], // user.address
	*               	["user_remark"], // user.remark
	*               	["user_tag"], // user.tag
	*               	["user_user_verified"], // user.user_verified
	*               	["user_name_verified"], // user.name_verified
	*               	["user_verify"], // user.verify
	*               	["user_verify_data"], // user.verify_data
	*               	["user_mobile_verified"], // user.mobile_verified
	*               	["user_email_verified"], // user.email_verified
	*               	["user_extra"], // user.extra
	*               	["user_password"], // user.password
	*               	["user_pay_password"], // user.pay_password
	*               	["user_status"], // user.status
	*               	["user_bio"], // user.bio
	*               	["user_bgimgurl"], // user.bgimgurl
	*               	["user_idtype"], // user.idtype
	*               	["task_created_at"], // task.created_at
	*               	["task_updated_at"], // task.updated_at
	*               	["task_slug"], // task.slug
	*               	["task_name"], // task.name
	*               	["task_quantity"], // task.quantity
	*               	["task_accept"], // task.accept
	*               	["task_complete"], // task.complete
	*               	["task_events"], // task.events
	*               	["task_status"], // task.status
	*               	["task_formula"], // task.formula
	*               	["task_summary"], // task.summary
	*               	["task_type"], // task.type
	*               	["task_cover"], // task.cover
	*               	["task_hourly_limit"], // task.hourly_limit
	*               	["task_daily_limit"], // task.daily_limit
	*               	["task_weekly_limit"], // task.weekly_limit
	*               	["task_monthly_limit"], // task.monthly_limit
	*               	["task_yearly_limit"], // task.yearly_limit
	*               	["task_time_limit"], // task.time_limit
	*               	["task_process"], // task.process
	*/
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["usertask.status","user.user_id","user.name","user.nickname","user.mobile","task.task_id","task.slug","task.name","task.quantity","task.type","task.cover","task.process"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按用户任务ID
		if ( !empty($data["usertask_id"]) ) {
			
			$keys = explode(',', $data["usertask_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Usertask;
				return $inst->getInByUsertaskId($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Usertask;
			return $inst->getByUsertaskId($data["usertask_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}

	/**
	 * 添加一条任务记录
	 * @param  array $query GET 参数
	 * @param  array $data  POST 参数新增的字段记录 
	 *               $data['usertask_id'] 用户任务ID
	 *               $data['user_id'] 用户ID
	 *               $data['task_id'] 任务ID
	 *               $data['process'] 进度
	 *               $data['status'] 状态
	 *
	 * @return array 新增的任务记录  @see get()
	 */
	protected function create( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		if (empty($data['usertask_id'])) {
			throw new Excp("缺少必填字段用户任务ID (usertask_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['user_id'])) {
			throw new Excp("缺少必填字段用户ID (user_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['task_id'])) {
			throw new Excp("缺少必填字段任务ID (task_id)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['process'])) {
			throw new Excp("缺少必填字段进度 (process)", 402, ['query'=>$query, 'data'=>$data]);
		}
		if (empty($data['status'])) {
			throw new Excp("缺少必填字段状态 (status)", 402, ['query'=>$query, 'data'=>$data]);
		}

		$inst = new \Xpmsns\User\Model\Usertask;
		$rs = $inst->create( $data );
		return $inst->getByUsertaskId($rs["usertask_id"]);
	}


	/**
	 * 更新一条任务记录
	 * @param  array $query GET 参数
	 * 				 $query['name=usertask_id']  按更新
     *
	 * @param  array $data  POST 参数 更新字段记录 
	 *               $data['usertask_id'] 用户任务ID
	 *               $data['user_id'] 用户ID
	 *               $data['task_id'] 任务ID
	 *               $data['process'] 进度
	 *               $data['status'] 状态
	 *
	 * @return array 更新的任务记录 @see get()
	 * 
	 */
	protected function update( $query, $data ) {

		if ( !empty($query['_secret']) ) { 
			// secret校验，一般用于小程序 & 移动应用
			$this->authSecret($query['_secret']);
		} else {
			// 签名校验，一般用于后台程序调用
			$this->auth($query); 
		}

		// 按用户任务ID
		if ( !empty($query["usertask_id"]) ) {
			$data = array_merge( $data, ["usertask_id"=>$query["usertask_id"]] );
			$inst = new \Xpmsns\User\Model\Usertask;
			$rs = $inst->updateBy("usertask_id",$data);
			return $inst->getByUsertaskId($rs["usertask_id"]);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}




	/**
	 * 根据条件检索任务记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["usertask.usertask_id","usertask.user_id","usertask.task_id","usertask.process","usertask.status","user.name","user.nickname","user.mobile","task.name"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["usertask_id"] 按用户任务ID查询 ( AND = )
	 *			      $query["user_id"] 按用户ID查询 ( AND = )
	 *			      $query["task_id"] 按任务ID查询 ( AND = )
	 *			      $query["process"] 按进度查询 ( AND = )
	 *			      $query["status"] 按状态查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=usertask_id","name=user_id","name=task_id","name=process","name=status","model=%5CXpmsns%5CUser%5CModel%5CUser&name=name&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=nickname&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=mobile&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CTask&name=name&table=task&prefix=xpmsns_user_&alias=task&type=leftJoin"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keyword"] 按关键词查询
	 *			      $data["usertask_id"] 按用户任务ID查询 ( AND = )
	 *			      $data["user_id"] 按用户ID查询 ( AND = )
	 *			      $data["task_id"] 按任务ID查询 ( AND = )
	 *			      $data["process"] 按进度查询 ( AND = )
	 *			      $data["status"] 按状态查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 任务记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
	 *               	["usertask_id"],  // 用户任务ID 
	 *               	["user_id"],  // 用户ID 
	*               	["user_user_id"], // user.user_id
	 *               	["task_id"],  // 任务ID 
	*               	["task_task_id"], // task.task_id
	 *               	["process"],  // 进度 
	 *               	["status"],  // 状态 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	*               	["user_created_at"], // user.created_at
	*               	["user_updated_at"], // user.updated_at
	*               	["user_group_id"], // user.group_id
	*               	["user_name"], // user.name
	*               	["user_idno"], // user.idno
	*               	["user_iddoc"], // user.iddoc
	*               	["user_nickname"], // user.nickname
	*               	["user_sex"], // user.sex
	*               	["user_city"], // user.city
	*               	["user_province"], // user.province
	*               	["user_country"], // user.country
	*               	["user_headimgurl"], // user.headimgurl
	*               	["user_language"], // user.language
	*               	["user_birthday"], // user.birthday
	*               	["user_mobile"], // user.mobile
	*               	["user_mobile_nation"], // user.mobile_nation
	*               	["user_mobile_full"], // user.mobile_full
	*               	["user_email"], // user.email
	*               	["user_contact_name"], // user.contact_name
	*               	["user_contact_tel"], // user.contact_tel
	*               	["user_title"], // user.title
	*               	["user_company"], // user.company
	*               	["user_zip"], // user.zip
	*               	["user_address"], // user.address
	*               	["user_remark"], // user.remark
	*               	["user_tag"], // user.tag
	*               	["user_user_verified"], // user.user_verified
	*               	["user_name_verified"], // user.name_verified
	*               	["user_verify"], // user.verify
	*               	["user_verify_data"], // user.verify_data
	*               	["user_mobile_verified"], // user.mobile_verified
	*               	["user_email_verified"], // user.email_verified
	*               	["user_extra"], // user.extra
	*               	["user_password"], // user.password
	*               	["user_pay_password"], // user.pay_password
	*               	["user_status"], // user.status
	*               	["user_bio"], // user.bio
	*               	["user_bgimgurl"], // user.bgimgurl
	*               	["user_idtype"], // user.idtype
	*               	["task_created_at"], // task.created_at
	*               	["task_updated_at"], // task.updated_at
	*               	["task_slug"], // task.slug
	*               	["task_name"], // task.name
	*               	["task_quantity"], // task.quantity
	*               	["task_accept"], // task.accept
	*               	["task_complete"], // task.complete
	*               	["task_events"], // task.events
	*               	["task_status"], // task.status
	*               	["task_formula"], // task.formula
	*               	["task_summary"], // task.summary
	*               	["task_type"], // task.type
	*               	["task_cover"], // task.cover
	*               	["task_hourly_limit"], // task.hourly_limit
	*               	["task_daily_limit"], // task.daily_limit
	*               	["task_weekly_limit"], // task.weekly_limit
	*               	["task_monthly_limit"], // task.monthly_limit
	*               	["task_yearly_limit"], // task.yearly_limit
	*               	["task_time_limit"], // task.time_limit
	*               	["task_process"], // task.process
	 */
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["usertask.usertask_id","usertask.user_id","usertask.task_id","usertask.process","usertask.status","user.name","user.nickname","user.mobile","task.name"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\User\Model\Usertask;
		return $inst->search( $data );
	}


}