<?php
/**
 * Class Subscriber 
 * 订阅数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 12:41:33
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Subscriber extends Model {




	/**
	 * 订阅数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('subscriber'); // 数据表名称 xpmsns_user_subscriber

	}

	/**
	 * 自定义函数 
	 */


    // @KEEP BEGIN
    
    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        if ( !empty($data["origin"]) &&  !empty($data["ourter_id"]) ) {
            $data["origin_ourter_id"] = "DB::RAW(CONCAT(`origin`,'_',`ourter_id`))";
        }
        return parent::saveBy( $uniqueKey,  $data,  $keys , $select );
    }


	/**
	 * 重载Remove
	 * @return [type] [description]
	 */
	function remove( $data_key, $uni_key="_id", $mark_only=true ){ 
		
		
		if ( $mark_only === true ) {

			$time = date('Y-m-d H:i:s');
			$_id = $this->getVar("_id", "WHERE {$uni_key}=? LIMIT 1", [$data_key]);
			$row = $this->update( $_id, [
				"deleted_at"=>$time, 
				"origin_ourter_id"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `origin_ourter_id`))"
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}
			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
	}

    // @KEEP END
    

	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 订阅者ID
		$this->putColumn( 'subscriber_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 行为别名
		$this->putColumn( 'behavior_slug', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 来源ID
		$this->putColumn( 'ourter_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 来源
		$this->putColumn( 'origin', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 唯一来源ID
		$this->putColumn( 'origin_ourter_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 处理器
		$this->putColumn( 'handler', $this->type("text", ["json"=>true, "null"=>true]));
		// 超时时长
		$this->putColumn( 'timeout', $this->type("text", ["null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "default"=>"on", "null"=>true]));

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
		  		"on" => [
		  			"value" => "on",
		  			"name" => "开启",
		  			"style" => "primary"
		  		],
		  		"off" => [
		  			"value" => "off",
		  			"name" => "关闭",
		  			"style" => "danger"
		  		],
			];
			$rs["_status_name"] = "status";
			$rs["_status"] = $rs["_status_types"][$rs["status"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按订阅者ID查询一条订阅记录
	 * @param string $subscriber_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["subscriber_id"],  // 订阅者ID 
	 *          	  $rs["behavior_slug"],  // 行为别名 
	 *                $rs["behavior_slug"], // behavior.slug
	 *          	  $rs["ourter_id"],  // 来源ID 
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["origin_ourter_id"],  // 唯一来源ID 
	 *          	  $rs["handler"],  // 处理器 
	 *          	  $rs["timeout"],  // 超时时长 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["behavior_created_at"], // behavior.created_at
	 *                $rs["behavior_updated_at"], // behavior.updated_at
	 *                $rs["behavior_behavior_id"], // behavior.behavior_id
	 *                $rs["behavior_name"], // behavior.name
	 *                $rs["behavior_intro"], // behavior.intro
	 *                $rs["behavior_status"], // behavior.status
	 *                $rs["behavior_params"], // behavior.params
	 *                $rs["behavior_before"], // behavior.before
	 *                $rs["behavior_after"], // behavior.after
	 */
	public function getBySubscriberId( $subscriber_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为
		$qb->where('subscriber_id', '=', $subscriber_id );
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
	 * 按订阅者ID查询一组订阅记录
	 * @param array   $subscriber_ids 唯一主键数组 ["$subscriber_id1","$subscriber_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 订阅记录MAP {"subscriber_id1":{"key":"value",...}...}
	 */
	public function getInBySubscriberId($subscriber_ids, $select=["subscriber.subscriber_id","behavior.slug","behavior.name","subscriber.origin","subscriber.ourter_id","subscriber.status"], $order=["subscriber.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为
		$qb->whereIn('subscriber.subscriber_id', $subscriber_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['subscriber_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按订阅者ID保存订阅记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySubscriberId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("subscriber_id", $data, ["subscriber_id", "origin_ourter_id"], ['_id', 'subscriber_id']);
		return $this->getBySubscriberId( $rs['subscriber_id'], $select );
	}
	
	/**
	 * 按唯一来源ID查询一条订阅记录
	 * @param string $origin_ourter_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["subscriber_id"],  // 订阅者ID 
	 *          	  $rs["behavior_slug"],  // 行为别名 
	 *                $rs["behavior_slug"], // behavior.slug
	 *          	  $rs["ourter_id"],  // 来源ID 
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["origin_ourter_id"],  // 唯一来源ID 
	 *          	  $rs["handler"],  // 处理器 
	 *          	  $rs["timeout"],  // 超时时长 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["behavior_created_at"], // behavior.created_at
	 *                $rs["behavior_updated_at"], // behavior.updated_at
	 *                $rs["behavior_behavior_id"], // behavior.behavior_id
	 *                $rs["behavior_name"], // behavior.name
	 *                $rs["behavior_intro"], // behavior.intro
	 *                $rs["behavior_status"], // behavior.status
	 *                $rs["behavior_params"], // behavior.params
	 *                $rs["behavior_before"], // behavior.before
	 *                $rs["behavior_after"], // behavior.after
	 */
	public function getByOriginOurterId( $origin_ourter_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为
		$qb->where('origin_ourter_id', '=', $origin_ourter_id );
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
	 * 按唯一来源ID查询一组订阅记录
	 * @param array   $origin_ourter_ids 唯一主键数组 ["$origin_ourter_id1","$origin_ourter_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 订阅记录MAP {"origin_ourter_id1":{"key":"value",...}...}
	 */
	public function getInByOriginOurterId($origin_ourter_ids, $select=["subscriber.subscriber_id","behavior.slug","behavior.name","subscriber.origin","subscriber.ourter_id","subscriber.status"], $order=["subscriber.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为
		$qb->whereIn('subscriber.origin_ourter_id', $origin_ourter_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['origin_ourter_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按唯一来源ID保存订阅记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByOriginOurterId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("origin_ourter_id", $data, ["subscriber_id", "origin_ourter_id"], ['_id', 'subscriber_id']);
		return $this->getBySubscriberId( $rs['subscriber_id'], $select );
	}


	/**
	 * 添加订阅记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["subscriber_id"]) ) { 
			$data["subscriber_id"] = $this->genId();
        }
        
        // @KEEP BEGIN
        if ( !empty($data["origin"]) &&  !empty($data["ourter_id"]) ) {
            $data["origin_ourter_id"] = "DB::RAW(CONCAT(`origin`,'_',`ourter_id`))";
        }
        // @KEEP END
		return parent::create( $data );
    }



	/**
	 * 查询前排订阅记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 订阅记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["subscriber.subscriber_id","behavior.slug","behavior.name","subscriber.origin","subscriber.ourter_id","subscriber.status"], $order=["subscriber.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为


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
	 * 按条件检索订阅记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["subscriber.subscriber_id","behavior.slug","behavior.name","subscriber.origin","subscriber.ourter_id","subscriber.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["subscriber_id"] 按订阅者ID查询 ( = )
	 *			      $query["behavior_slug"] 按行为别名查询 ( = )
	 *			      $query["ourter_id"] 按来源ID查询 ( = )
	 *			      $query["origin"] 按来源查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *           
	 * @return array 订阅记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["subscriber.subscriber_id","behavior.slug","behavior.name","subscriber.origin","subscriber.ourter_id","subscriber.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "subscriber.subscriber_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_subscriber as subscriber", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_behavior as behavior", "behavior.slug", "=", "subscriber.behavior_slug"); // 连接行为

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("subscriber.subscriber_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("subscriber.behavior_slug","like", "%{$query['keyword']}%");
				$qb->orWhere("subscriber.ourter_id","like", "%{$query['keyword']}%");
				$qb->orWhere("subscriber.origin","like", "%{$query['keyword']}%");
				$qb->orWhere("behavior.name","like", "%{$query['keyword']}%");
			});
		}


		// 按订阅者ID查询 (=)  
		if ( array_key_exists("subscriber_id", $query) &&!empty($query['subscriber_id']) ) {
			$qb->where("subscriber.subscriber_id", '=', "{$query['subscriber_id']}" );
		}
		  
		// 按行为别名查询 (=)  
		if ( array_key_exists("behavior_slug", $query) &&!empty($query['behavior_slug']) ) {
			$qb->where("subscriber.behavior_slug", '=', "{$query['behavior_slug']}" );
		}
		  
		// 按来源ID查询 (=)  
		if ( array_key_exists("ourter_id", $query) &&!empty($query['ourter_id']) ) {
			$qb->where("subscriber.ourter_id", '=', "{$query['ourter_id']}" );
		}
		  
		// 按来源查询 (=)  
		if ( array_key_exists("origin", $query) &&!empty($query['origin']) ) {
			$qb->where("subscriber.origin", '=', "{$query['origin']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("subscriber.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("subscriber.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("subscriber.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$subscribers = $qb->select( $select )->pgArray($perpage, ['subscriber._id'], 'page', $page);

 		foreach ($subscribers['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$subscribers['_sql'] = $qb->getSql();
			$subscribers['query'] = $query;
		}

		return $subscribers;
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
				$select[$idx] = "subscriber." .$select[$idx];
				continue;
			}
			
			//  连接行为 (behavior as behavior )
			if ( trim($fd) == "behavior.*" || trim($fd) == "behavior.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\Behavior", 'getFields') ) {
					$fields = \Xpmsns\User\Model\Behavior::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "behavior.{$field} as behavior_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "subscriber.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "behavior." ) === 0 ) {
				$as = str_replace('behavior.', 'behavior_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "behavior.") === 0 ) {
				$as = str_replace('behavior.', 'behavior_', $select[$idx]);
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
			"subscriber_id",  // 订阅者ID
			"behavior_slug",  // 行为别名
			"ourter_id",  // 来源ID
			"origin",  // 来源
			"origin_ourter_id",  // 唯一来源ID
			"handler",  // 处理器
			"timeout",  // 超时时长
			"status",  // 状态
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>