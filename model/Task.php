<?php
/**
 * Class Task 
 * 任务数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-25 14:47:13
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
                           
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Media;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Loader\App as App;
use \Xpmse\Job;


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

    // @KEEP BEGIN
    public function onBehaviorTrigger( $behavior, $subscriber, $data, $env ) {
        print_r( $data );
    }


    // @KEEP END


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
		// 参数
		$this->putColumn( 'params', $this->type("text", ["json"=>true, "null"=>true]));
		// 类目
		$this->putColumn( 'categories', $this->type("text", ["json"=>true, "null"=>true]));
		// 类型
		$this->putColumn( 'type', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 简介
		$this->putColumn( 'summary', $this->type("string", ["length"=>600, "null"=>true]));
		// 封面
		$this->putColumn( 'cover', $this->type("text", ["json"=>true, "null"=>true]));
		// 积分数量
		$this->putColumn( 'quantity', $this->type("text", ["json"=>true, "null"=>true]));
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
		// 刷新周期
		$this->putColumn( 'refresh', $this->type("string", ["length"=>32, "index"=>true, "default"=>"no", "null"=>true]));
		// 步骤
		$this->putColumn( 'process', $this->type("integer", ["length"=>1, "null"=>true]));
		// 自动接受
		$this->putColumn( 'auto_accept', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 接受条件
		$this->putColumn( 'accept', $this->type("text", ["json"=>true, "null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "default"=>"online", "null"=>true]));
		// 事件
		$this->putColumn( 'events', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 
		// 格式化: 封面
		// 返回值: [{"url":"访问地址...", "path":"文件路径...", "origin":"原始文件访问地址..." }]
		if ( array_key_exists('cover', $rs ) ) {
            array_push($fileFields, 'cover');
		}

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

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

		// 格式化: 刷新周期
		// 返回值: "_refresh_types" 所有状态表述, "_refresh_name" 状态名称,  "_refresh" 当前状态表述, "refresh" 当前状态数值
		if ( array_key_exists('refresh', $rs ) && !empty($rs['refresh']) ) {
			$rs["_refresh_types"] = [
		  		"no" => [
		  			"value" => "no",
		  			"name" => "不刷新",
		  			"style" => "muted"
		  		],
		  		"hourly" => [
		  			"value" => "hourly",
		  			"name" => "每小时",
		  			"style" => "primary"
		  		],
		  		"daily" => [
		  			"value" => "daily",
		  			"name" => "每天",
		  			"style" => "danger"
		  		],
		  		"weekly" => [
		  			"value" => "weekly",
		  			"name" => "每周",
		  			"style" => "primary"
		  		],
		  		"monthly" => [
		  			"value" => "monthly",
		  			"name" => "每月",
		  			"style" => "primary"
		  		],
		  		"quarterly" => [
		  			"value" => "quarterly",
		  			"name" => "每季度",
		  			"style" => "primary"
		  		],
		  		"" => [
		  			"value" => "",
		  			"name" => "",
		  			"style" => ""
		  		],
		  		"yearly" => [
		  			"value" => "yearly",
		  			"name" => "每年",
		  			"style" => "primary"
		  		],
			];
			$rs["_refresh_name"] = "refresh";
			$rs["_refresh"] = $rs["_refresh_types"][$rs["refresh"]];
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
	 *          	  $rs["params"],  // 参数 
	 *          	  $rs["categories"],  // 类目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["quantity"],  // 积分数量 
	 *          	  $rs["hourly_limit"],  // 时限额 
	 *          	  $rs["daily_limit"],  // 日限额 
	 *          	  $rs["weekly_limit"],  // 周限额 
	 *          	  $rs["monthly_limit"],  // 月限额 
	 *          	  $rs["yearly_limit"],  // 年限额 
	 *          	  $rs["time_limit"],  // 完成时限 
	 *          	  $rs["refresh"],  // 刷新周期 
	 *          	  $rs["process"],  // 步骤 
	 *          	  $rs["auto_accept"],  // 自动接受 
	 *          	  $rs["accept"],  // 接受条件 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["link"], // category.link
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 *                $rs["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$categories[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$categories[n]]["isblank"], // category.isblank
	 */
	public function getByTaskId( $task_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 		$qb->where('task.task_id', '=', $task_id );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $rs;
	}

		

	/**
	 * 按任务ID查询一组任务记录
	 * @param array   $task_ids 唯一主键数组 ["$task_id1","$task_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务记录MAP {"task_id1":{"key":"value",...}...}
	 */
	public function getInByTaskId($task_ids, $select=["task.task_id","task.cover","task.slug","task.name","category.name","task.type","task.refresh","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 		$qb->whereIn('task.task_id', $task_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['task_id']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	 *          	  $rs["params"],  // 参数 
	 *          	  $rs["categories"],  // 类目 
	 *                $rs["_map_category"][$categories[n]]["category_id"], // category.category_id
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["summary"],  // 简介 
	 *          	  $rs["cover"],  // 封面 
	 *          	  $rs["quantity"],  // 积分数量 
	 *          	  $rs["hourly_limit"],  // 时限额 
	 *          	  $rs["daily_limit"],  // 日限额 
	 *          	  $rs["weekly_limit"],  // 周限额 
	 *          	  $rs["monthly_limit"],  // 月限额 
	 *          	  $rs["yearly_limit"],  // 年限额 
	 *          	  $rs["time_limit"],  // 完成时限 
	 *          	  $rs["refresh"],  // 刷新周期 
	 *          	  $rs["process"],  // 步骤 
	 *          	  $rs["auto_accept"],  // 自动接受 
	 *          	  $rs["accept"],  // 接受条件 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["events"],  // 事件 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["_map_category"][$categories[n]]["created_at"], // category.created_at
	 *                $rs["_map_category"][$categories[n]]["updated_at"], // category.updated_at
	 *                $rs["_map_category"][$categories[n]]["slug"], // category.slug
	 *                $rs["_map_category"][$categories[n]]["project"], // category.project
	 *                $rs["_map_category"][$categories[n]]["page"], // category.page
	 *                $rs["_map_category"][$categories[n]]["wechat"], // category.wechat
	 *                $rs["_map_category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *                $rs["_map_category"][$categories[n]]["name"], // category.name
	 *                $rs["_map_category"][$categories[n]]["fullname"], // category.fullname
	 *                $rs["_map_category"][$categories[n]]["link"], // category.link
	 *                $rs["_map_category"][$categories[n]]["root_id"], // category.root_id
	 *                $rs["_map_category"][$categories[n]]["parent_id"], // category.parent_id
	 *                $rs["_map_category"][$categories[n]]["priority"], // category.priority
	 *                $rs["_map_category"][$categories[n]]["hidden"], // category.hidden
	 *                $rs["_map_category"][$categories[n]]["isnav"], // category.isnav
	 *                $rs["_map_category"][$categories[n]]["param"], // category.param
	 *                $rs["_map_category"][$categories[n]]["status"], // category.status
	 *                $rs["_map_category"][$categories[n]]["issubnav"], // category.issubnav
	 *                $rs["_map_category"][$categories[n]]["highlight"], // category.highlight
	 *                $rs["_map_category"][$categories[n]]["isfootnav"], // category.isfootnav
	 *                $rs["_map_category"][$categories[n]]["isblank"], // category.isblank
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 		$qb->where('task.slug', '=', $slug );
		$qb->limit( 1 );
		$qb->select($select);
		$rows = $qb->get()->toArray();
		if( empty($rows) ) {
			return [];
		}

		$rs = current( $rows );
		$this->format($rs);

 		$category_ids = []; // 读取 inWhere category 数据
		$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$rs["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $rs;
	}

	

	/**
	 * 按别名查询一组任务记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 任务记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["task.task_id","task.cover","task.slug","task.name","category.name","task.type","task.refresh","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 		$qb->whereIn('task.slug', $slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['slug']] = $rs;
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$map["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
	public function top( $limit=100, $select=["task.task_id","task.cover","task.slug","task.name","category.name","task.type","task.refresh","task.process","task.quantity","task.status"], $order=["task.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 

		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->limit($limit);
		$qb->select( $select );
		$data = $qb->get()->toArray();


 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($data as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$data["_map_category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
		}

		return $data;
	
	}


	/**
	 * 按条件检索任务记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["task.task_id","task.cover","task.slug","task.name","category.name","task.type","task.refresh","task.process","task.quantity","task.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["task_id"] 按任务ID查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["name"] 按名称查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["auto_accept"] 按自动接受查询 ( = )
	 *			      $query["category_category_id"] 按查询 ( IN )
	 *			      $query["category_slug"] 按查询 ( IN )
	 *			      $query["refresh"] 按刷新周期查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 任务记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["task_id"],  // 任务ID 
	 *               	["slug"],  // 别名 
	 *               	["name"],  // 名称 
	 *               	["params"],  // 参数 
	 *               	["categories"],  // 类目 
	 *               	["category"][$categories[n]]["category_id"], // category.category_id
	 *               	["type"],  // 类型 
	 *               	["summary"],  // 简介 
	 *               	["cover"],  // 封面 
	 *               	["quantity"],  // 积分数量 
	 *               	["hourly_limit"],  // 时限额 
	 *               	["daily_limit"],  // 日限额 
	 *               	["weekly_limit"],  // 周限额 
	 *               	["monthly_limit"],  // 月限额 
	 *               	["yearly_limit"],  // 年限额 
	 *               	["time_limit"],  // 完成时限 
	 *               	["refresh"],  // 刷新周期 
	 *               	["process"],  // 步骤 
	 *               	["auto_accept"],  // 自动接受 
	 *               	["accept"],  // 接受条件 
	 *               	["status"],  // 状态 
	 *               	["events"],  // 事件 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["category"][$categories[n]]["created_at"], // category.created_at
	 *               	["category"][$categories[n]]["updated_at"], // category.updated_at
	 *               	["category"][$categories[n]]["slug"], // category.slug
	 *               	["category"][$categories[n]]["project"], // category.project
	 *               	["category"][$categories[n]]["page"], // category.page
	 *               	["category"][$categories[n]]["wechat"], // category.wechat
	 *               	["category"][$categories[n]]["wechat_offset"], // category.wechat_offset
	 *               	["category"][$categories[n]]["name"], // category.name
	 *               	["category"][$categories[n]]["fullname"], // category.fullname
	 *               	["category"][$categories[n]]["link"], // category.link
	 *               	["category"][$categories[n]]["root_id"], // category.root_id
	 *               	["category"][$categories[n]]["parent_id"], // category.parent_id
	 *               	["category"][$categories[n]]["priority"], // category.priority
	 *               	["category"][$categories[n]]["hidden"], // category.hidden
	 *               	["category"][$categories[n]]["isnav"], // category.isnav
	 *               	["category"][$categories[n]]["param"], // category.param
	 *               	["category"][$categories[n]]["status"], // category.status
	 *               	["category"][$categories[n]]["issubnav"], // category.issubnav
	 *               	["category"][$categories[n]]["highlight"], // category.highlight
	 *               	["category"][$categories[n]]["isfootnav"], // category.isfootnav
	 *               	["category"][$categories[n]]["isblank"], // category.isblank
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["task.task_id","task.cover","task.slug","task.name","category.name","task.type","task.refresh","task.process","task.quantity","task.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "task.task_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_task as task", "{none}")->query();
 
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
		  
		// 按自动接受查询 (=)  
		if ( array_key_exists("auto_accept", $query) &&!empty($query['auto_accept']) ) {
			$qb->where("task.auto_accept", '=', "{$query['auto_accept']}" );
		}
		  
		// 按查询 (IN)  
		if ( array_key_exists("category_category_id", $query) &&!empty($query['category_category_id']) ) {
			if ( is_string($query['category_category_id']) ) {
				$query['category_category_id'] = explode(',', $query['category_category_id']);
			}
			$qb->whereIn("category.category_id",  $query['category_category_id'] );
		}
		  
		// 按查询 (IN)  
		if ( array_key_exists("category_slug", $query) &&!empty($query['category_slug']) ) {
			if ( is_string($query['category_slug']) ) {
				$query['category_slug'] = explode(',', $query['category_slug']);
			}
			$qb->whereIn("category.slug",  $query['category_slug'] );
		}
		  
		// 按刷新周期查询 (=)  
		if ( array_key_exists("refresh", $query) &&!empty($query['refresh']) ) {
			$qb->where("task.refresh", '=', "{$query['refresh']}" );
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

 		$category_ids = []; // 读取 inWhere category 数据
		foreach ($tasks['data'] as & $rs ) {
			$this->format($rs);
			
 			// for inWhere category
			$category_ids = array_merge($category_ids, is_array($rs["categories"]) ? $rs["categories"] : [$rs["categories"]]);
		}

 		// 读取 inWhere category 数据
		if ( !empty($inwhereSelect["category"]) && method_exists("\\Xpmsns\\Pages\\Model\\Category", 'getInByCategoryId') ) {
			$category_ids = array_unique($category_ids);
			$selectFields = $inwhereSelect["category"];
			$tasks["category"] = (new \Xpmsns\Pages\Model\Category)->getInByCategoryId($category_ids, $selectFields);
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
			
			// 连接类目 (category as category )
			if ( strpos( $fd, "category." ) === 0 || strpos("category.", $fd ) === 0  || trim($fd) == "*" ) {
				$arr = explode( ".", $fd );
				$arr[1]  = !empty($arr[1]) ? $arr[1] : "*";
				$inwhereSelect["category"][] = trim($arr[1]);
				$inwhereSelect["category"][] = "category_id";
				if ( trim($fd) != "*" ) {
					unset($select[$idx]);
					array_push($linkSelect, "task.categories");
				}
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
			"params",  // 参数
			"categories",  // 类目
			"type",  // 类型
			"summary",  // 简介
			"cover",  // 封面
			"quantity",  // 积分数量
			"hourly_limit",  // 时限额
			"daily_limit",  // 日限额
			"weekly_limit",  // 周限额
			"monthly_limit",  // 月限额
			"yearly_limit",  // 年限额
			"time_limit",  // 完成时限
			"refresh",  // 刷新周期
			"process",  // 步骤
			"auto_accept",  // 自动接受
			"accept",  // 接受条件
			"status",  // 状态
			"events",  // 事件
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>