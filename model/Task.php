<?php
/**
 * Class Task 
 * 任务数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 22:27:55
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
                         
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Xpmse\Loader\App as App;


class Task extends Model {


	/**
	 * 公有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $media = null;

	/**
	 * 私有媒体文件对象
	 * @var \Xpmse\Meida
	 */
	protected $mediaPrivate = null;

	/**
	 * 任务数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('task'); // 数据表名称 xpmsns_user_task
		$this->media = new Media(['host'=>Utils::getHome()]);  // 公有媒体文件实例

	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 任务ID
		$this->putColumn( 'task_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 类型
		$this->putColumn( 'type', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>600, "null"=>true]));
		// 封面
		$this->putColumn( 'cover', $this->type("text", ["json"=>true, "null"=>true]));
		// 积分数量
		$this->putColumn( 'quantity', $this->type("integer", ["length"=>1, "null"=>true]));
		// 奖励公式
		$this->putColumn( 'formula', $this->type("text", ["json"=>true, "null"=>true]));
		// 时限额
		$this->putColumn( 'hourly_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 日限额
		$this->putColumn( 'daily_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 周限额
		$this->putColumn( 'weekly_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 月限额
		$this->putColumn( 'monthly_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 年限额
		$this->putColumn( 'yearly_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 完成时限
		$this->putColumn( 'time_limit', $this->type("integer", ["length"=>1, "null"=>true]));
		// 步骤
		$this->putColumn( 'process', $this->type("integer", ["length"=>1, "null"=>true]));
		// 接受条件
		$this->putColumn( 'accept', $this->type("text", ["json"=>true, "null"=>true]));
		// 达成条件
		$this->putColumn( 'complete', $this->type("text", ["json"=>true, "null"=>true]));
		// 事件
		$this->putColumn( 'events', $this->type("text", ["json"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "default"=>"online", "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {

		// 格式化: 封面
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('cover', $rs ) ) {
			$is_string = is_string($rs["cover"]);
			$rs["cover"] = $is_string ? [$rs["cover"]] : $rs["cover"];
			$rs["cover"] = !is_array($rs["cover"]) ? [] : $rs["cover"];
			foreach ($rs["cover"] as & $file ) {
				if ( is_array($file) && !empty($file['path']) ) {
					$fs = $this->media->get( $file['path'] );
					$file = array_merge( $file, $fs );
				} else if ( is_string($file) ) {
					$file =empty($file) ? [] : $this->media->get( $file );
				} else {
					$file = [];
				}
			}
			if ($is_string) {
				$rs["cover"] = current($rs["cover"]);
			}
		}


		// 格式化: 状态
		// 返回值: "_status_types" 所有状态表述, "_status_name" 状态名称,  "_status" 当前状态表述, "status" 当前状态数值
		if ( array_key_exists('status', $rs ) && !empty($rs['status']) ) {
			$rs["_status_types"] = [
		  		"online" => [
		  			"value" => "online",
		  			"name" => "上线",
		  			"style" => "success"
		  		],
		  		"offline" => [
		  			"value" => "offline",
		  			"name" => "下线",
		  			"style" => "danger"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

		// 格式化: 类型
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"repeatable" => [
		  			"value" => "repeatable",
		  			"name" => "可重复",
		  			"style" => "primary"
		  		],
		  		"once" => [
		  			"value" => "once",
		  			"name" => "一次性",
		  			"style" => "primary"
		  		],
			];
			$rs["_type_name"] = "type";
			$rs["_type"] = $rs["_type_types"][$rs["type"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按任务ID查询一条任务记录
	 * @param string $task_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["task_id"],  // 任务ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["quantity"],  // 积分数量 
	 *          	  $rs["formula"],  // 奖励公式 
	 *          	  $rs["hourly_limit"],  // 时限额 
	 *          	  $rs["daily_limit"],  // 日限额 
	 *          	  $rs["weekly_limit"],  // 周限额 
	 *          	  $rs["monthly_limit"],  // 月限额 
	 *          	  $rs["yearly_limit"],  // 年限额 
	 *          	  $rs["time_limit"],  // 完成时限 
	 *          	  $rs["process"],  // 步骤 
	 *          	  $rs["accept"],  // 接受条件 
	 *          	  $rs["complete"],  // 达成条件 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getByTaskId( $task_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
		$qb->where('task_id', '=', $task_id );
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
	 * 按任务ID查询一组任务记录
	 * @param array   $task_ids 唯一主键数组 ["$task_id1","$task_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务记录MAP {"task_id1":{"key":"value",...}...}
	 */
	public function getInByTaskId($task_ids, $select=["task.task_id","task.cover","task.slug","task.name","task.type","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('task_id', $task_ids);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['task_id']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按任务ID保存任务记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByTaskId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("task_id", $data, ["task_id", "slug"], ['_id', 'task_id']);
		return $this->getByTaskId( $rs['task_id'], $select );
	}
	
	/**
	 * 按别名查询一条任务记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["task_id"],  // 任务ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["quantity"],  // 积分数量 
	 *          	  $rs["formula"],  // 奖励公式 
	 *          	  $rs["hourly_limit"],  // 时限额 
	 *          	  $rs["daily_limit"],  // 日限额 
	 *          	  $rs["weekly_limit"],  // 周限额 
	 *          	  $rs["monthly_limit"],  // 月限额 
	 *          	  $rs["yearly_limit"],  // 年限额 
	 *          	  $rs["time_limit"],  // 完成时限 
	 *          	  $rs["process"],  // 步骤 
	 *          	  $rs["accept"],  // 接受条件 
	 *          	  $rs["complete"],  // 达成条件 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
		$qb->where('slug', '=', $slug );
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
	 * 按别名查询一组任务记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["task.task_id","task.cover","task.slug","task.name","task.type","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('slug', $slugs);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按别名保存任务记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["task_id", "slug"], ['_id', 'task_id']);
		return $this->getByTaskId( $rs['task_id'], $select );
	}

	/**
	 * 根据任务ID上传封面。
	 * @param string $task_id 任务ID
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverByTaskId($task_id, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('task_id', $task_id, ["cover"]);
		$paths = empty($rs["cover"]) ? [] : $rs["cover"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('task_id', ["task_id"=>$task_id, "cover"=>$paths] );
		}

		return $fs;
	}

	/**
	 * 根据别名上传封面。
	 * @param string $slug 别名
	 * @param string $file_path 文件路径
	 * @param mix $index 如果是数组，替换当前 index
	 * @return array 已上传文件信息 {"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }
	 */
	public function uploadCoverBySlug($slug, $file_path, $index=null, $upload_only=false ) {

		$rs = $this->getBy('slug', $slug, ["cover"]);
		$paths = empty($rs["cover"]) ? [] : $rs["cover"];
		$fs = $this->media->uploadFile( $file_path );
		if ( $index === null ) {
			array_push($paths, $fs['path']);
		} else {
			$paths[$index] = $fs['path'];
		}

		if ( $upload_only !== true ) {
			$this->updateBy('slug', ["slug"=>$slug, "cover"=>$paths] );
		}

		return $fs;
	}


	/**
	 * 添加任务记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["task_id"]) ) { 
			$data["task_id"] = $this->genId();
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
	public function top( $limit=100, $select=["task.task_id","task.cover","task.slug","task.name","task.type","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();


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
	 *         	      $query['select'] 选取字段，默认选择 ["task.task_id","task.cover","task.slug","task.name","task.type","task.process","task.quantity","task.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["task_id"] 按任务ID查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["name"] 按名称查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 任务记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["task.task_id","task.cover","task.slug","task.name","task.type","task.process","task.quantity","task.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("task.task_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("task.slug","like", "%{$query['keyword']}%");
				$qb->orWhere("task.name","like", "%{$query['keyword']}%");
			});
		}


		// 按任务ID查询 (=)  
		if ( array_key_exists("task_id", $query) &&!empty($query['task_id']) ) {
			$qb->where("task.task_id", '=', "{$query['task_id']}" );
		}
		  
		// 按别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("task.slug", '=', "{$query['slug']}" );
		}
		  
		// 按名称查询 (=)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("task.name", '=', "{$query['name']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("task.status", '=', "{$query['status']}" );
		}
		  
		// 按类型查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("task.type", '=', "{$query['type']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("task.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("task.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$tasks = $qb->select( $select )->pgArray($perpage, ['task._id'], 'page', $page);

		foreach ($tasks['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$tasks['_sql'] = $qb->getSql();
			$tasks['query'] = $query;
		}

		return $tasks;
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
				$select[$idx] = "task." .$select[$idx];
				continue;
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
			"task_id",  // 任务ID
			"slug",  // 别名
			"name",  // 名称
			"type",  // 类型
			"summary",  // 简介
			"cover",  // 封面
			"quantity",  // 积分数量
			"formula",  // 奖励公式
			"hourly_limit",  // 时限额
			"daily_limit",  // 日限额
			"weekly_limit",  // 周限额
			"monthly_limit",  // 月限额
			"yearly_limit",  // 年限额
			"time_limit",  // 完成时限
			"process",  // 步骤
			"accept",  // 接受条件
			"complete",  // 达成条件
			"events",  // 事件
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>