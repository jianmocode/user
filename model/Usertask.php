<?php
/**
 * Class Usertask 
 * 任务数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 22:55:39
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
         
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Usertask extends Model {




	/**
	 * 任务数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('usertask'); // 数据表名称 xpmsns_user_usertask

	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 用户任务ID
		$this->putColumn( 'usertask_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 任务ID
		$this->putColumn( 'task_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 进度
		$this->putColumn( 'process', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"accepted" => [
		  			"value" => "accepted",
		  			"name" => "已接受",
		  			"style" => "primary"
		  		],
		  		"completed" => [
		  			"value" => "completed",
		  			"name" => "已完成",
		  			"style" => "success"
		  		],
		  		"canceled" => [
		  			"value" => "canceled",
		  			"name" => "已取消",
		  			"style" => "muted"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按用户任务ID查询一条任务记录
	 * @param string $usertask_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["usertask_id"],  // 用户任务ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["task_id"],  // 任务ID 
	 *                $rs["task_task_id"], // task.task_id
	 *          	  $rs["process"],  // 进度 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_mobile"], // user.mobile
	 *                $rs["user_mobile_nation"], // user.mobile_nation
	 *                $rs["user_mobile_full"], // user.mobile_full
	 *                $rs["user_email"], // user.email
	 *                $rs["user_contact_name"], // user.contact_name
	 *                $rs["user_contact_tel"], // user.contact_tel
	 *                $rs["user_title"], // user.title
	 *                $rs["user_company"], // user.company
	 *                $rs["user_zip"], // user.zip
	 *                $rs["user_address"], // user.address
	 *                $rs["user_remark"], // user.remark
	 *                $rs["user_tag"], // user.tag
	 *                $rs["user_user_verified"], // user.user_verified
	 *                $rs["user_name_verified"], // user.name_verified
	 *                $rs["user_verify"], // user.verify
	 *                $rs["user_verify_data"], // user.verify_data
	 *                $rs["user_mobile_verified"], // user.mobile_verified
	 *                $rs["user_email_verified"], // user.email_verified
	 *                $rs["user_extra"], // user.extra
	 *                $rs["user_password"], // user.password
	 *                $rs["user_pay_password"], // user.pay_password
	 *                $rs["user_status"], // user.status
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["task_created_at"], // task.created_at
	 *                $rs["task_updated_at"], // task.updated_at
	 *                $rs["task_slug"], // task.slug
	 *                $rs["task_name"], // task.name
	 *                $rs["task_quantity"], // task.quantity
	 *                $rs["task_accept"], // task.accept
	 *                $rs["task_complete"], // task.complete
	 *                $rs["task_events"], // task.events
	 *                $rs["task_status"], // task.status
	 *                $rs["task_formula"], // task.formula
	 *                $rs["task_summary"], // task.summary
	 *                $rs["task_type"], // task.type
	 *                $rs["task_cover"], // task.cover
	 *                $rs["task_hourly_limit"], // task.hourly_limit
	 *                $rs["task_daily_limit"], // task.daily_limit
	 *                $rs["task_weekly_limit"], // task.weekly_limit
	 *                $rs["task_monthly_limit"], // task.monthly_limit
	 *                $rs["task_yearly_limit"], // task.yearly_limit
	 *                $rs["task_time_limit"], // task.time_limit
	 *                $rs["task_process"], // task.process
	 */
	public function getByUsertaskId( $usertask_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户
		$qb->where('usertask_id', '=', $usertask_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

  
  
		return $rs;
	}

		

	/**
	 * 按用户任务ID查询一组任务记录
	 * @param array   $usertask_ids 唯一主键数组 ["$usertask_id1","$usertask_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务记录MAP {"usertask_id1":{"key":"value",...}...}
	 */
	public function getInByUsertaskId($usertask_ids, $select=["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.status"], $order=["usertask.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户
		$qb->whereIn('usertask.usertask_id', $usertask_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['usertask_id']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按用户任务ID保存任务记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUsertaskId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("usertask_id", $data, ["usertask_id"], ['_id', 'usertask_id']);
		return $this->getByUsertaskId( $rs['usertask_id'], $select );
	}


	/**
	 * 添加任务记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["usertask_id"]) ) { 
			$data["usertask_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排任务记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 任务记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.status"], $order=["usertask.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户


		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


  		foreach ($data as & $rs ) {
			$this->format($rs);
			
  		}

  
		return $data;
	
	}


	/**
	 * 按条件检索任务记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["usertask_id"] 按用户任务ID查询 ( = )
	 *			      $query["user_id"] 按用户ID查询 ( = )
	 *			      $query["task_id"] 按任务ID查询 ( = )
	 *			      $query["process"] 按进度查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 任务记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["usertask.usertask_id","user.name","user.nickname","task.name","user.mobile","usertask.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "usertask.usertask_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_usertask as usertask", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "usertask.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_task as task", "task.task_id", "=", "usertask.task_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("usertask.usertask_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keyword']}%");
				$qb->orWhere("user.name","like", "%{$query['keyword']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keyword']}%");
				$qb->orWhere("task.task_id","like", "%{$query['keyword']}%");
				$qb->orWhere("task.slug","like", "%{$query['keyword']}%");
				$qb->orWhere("task.name","like", "%{$query['keyword']}%");
			});
		}


		// 按用户任务ID查询 (=)  
		if ( array_key_exists("usertask_id", $query) &&!empty($query['usertask_id']) ) {
			$qb->where("usertask.usertask_id", '=', "{$query['usertask_id']}" );
		}
		  
		// 按用户ID查询 (=)  
		if ( array_key_exists("user_id", $query) &&!empty($query['user_id']) ) {
			$qb->where("usertask.user_id", '=', "{$query['user_id']}" );
		}
		  
		// 按任务ID查询 (=)  
		if ( array_key_exists("task_id", $query) &&!empty($query['task_id']) ) {
			$qb->where("usertask.task_id", '=', "{$query['task_id']}" );
		}
		  
		// 按进度查询 (=)  
		if ( array_key_exists("process", $query) &&!empty($query['process']) ) {
			$qb->where("usertask.process", '=', "{$query['process']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("usertask.status", '=', "{$query['status']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("usertask.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("usertask.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$usertasks = $qb->select( $select )->pgArray($perpage, ['usertask._id'], 'page', $page);

  		foreach ($usertasks['data'] as & $rs ) {
			$this->format($rs);
			
  		}

  	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$usertasks['_sql'] = $qb->getSql();
			$usertasks['query'] = $query;
		}

		return $usertasks;
	}

	/**
	 * 格式化读取字段
	 * @param  array $select 选中字段
	 * @return array $inWhere 读取字段
	 */
	public function formatSelect( & $select ) {
		// 过滤 inWhere 查询字段
		$inwhereSelect = []; $linkSelect = [];
		foreach ($select as $idx=>$fd ) {
			
			// 添加本表前缀
			if ( !strpos( $fd, ".")  ) {
				$select[$idx] = "usertask." .$select[$idx];
				continue;
			}
			
			//  连接用户 (user as user )
			if ( trim($fd) == "user.*" || trim($fd) == "user.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "user.{$field} as user_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "usertask.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "user.") === 0 ) {
				$as = str_replace('user.', 'user_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			
			//  连接用户 (task as task )
			if ( trim($fd) == "task.*" || trim($fd) == "task.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\Task", 'getFields') ) {
					$fields = \Xpmsns\User\Model\Task::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "task.{$field} as task_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "usertask.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "task." ) === 0 ) {
				$as = str_replace('task.', 'task_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "task.") === 0 ) {
				$as = str_replace('task.', 'task_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

		}

		// filter 查询字段
		foreach ($inwhereSelect as & $iws ) {
			if ( is_array($iws) ) {
				$iws = array_unique(array_filter($iws));
			}
		}

		$select = array_unique(array_merge($linkSelect, $select));
		return $inwhereSelect;
	}

	/**
	 * 返回所有字段
	 * @return array 字段清单
	 */
	public static function getFields() {
		return [
			"usertask_id",  // 用户任务ID
			"user_id",  // 用户ID
			"task_id",  // 任务ID
			"process",  // 进度
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>