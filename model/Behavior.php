<?php
/**
 * Class Behavior 
 * 行为数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 12:31:17
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Behavior extends Model {




	/**
	 * 行为数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
        $this->table('behavior'); // 数据表名称 xpmsns_user_behavior
        
        // @KEEP BEGIN
        $this->cache = new \Xpmse\Mem(true, "Behavior:");
        // @KEEP END

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

    /**
     * 收集环境信息(建议单独调用)
     */
    function getEnv() {
        return [
            "session_id" => session_id(),
            "client_ip" => Utils::getClientIP(),
            "time" => time()
        ];
    }
    
    /**
     * 执行指定SLUG行为(通知所有该行为订阅者)
     * @param string $slug 行为slug
     * @param string $user_id 用户ID 
     * @param array $data 行为数据
     * @return 成功返回 true ,  失败返回错误结构体 ["code"=>xxx, "message"=>"xxx", "extra"=>[...]]
     */
    function runBySlug( $slug, $data=[], $env=[], $user_id=null ) {
        $cache_name = "{$slug}:detail";
        $behavior = $this->cache->getJSON( $cache_name );
        if ( $behavior === false ) {
            $behavior = $this->getBySlug($slug);
            if ( empty($behavior) ) {
                throw new Excp("用户行为数据不存在", 404, ["slug"=>$slug, "user_id"=>$user_id, "data"=>$data]);
            }
            $this->cache->setJSON( $cache_name, $behavior );
        }

        return $this->run( $behavior, $data, $env, $user_id );
    }


    /**
     * 执行指定ID行为(通知所有该行为订阅者)
     * @param string $behavior_id 行为ID
     * @param string $user_id 用户ID 
     * @param array $data 行为数据
     * @return 成功返回 true ,  失败返回错误结构体 ["code"=>xxx, "message"=>"xxx", "extra"=>[...]]
     */
    function runByBehaviorID( $behavior_id,$data=[], $env=[], $user_id=null) {
        $cache_name = "{$behavior_id}:detail";
        $behavior = $this->cache->getJSON( $cache_name );
        if ( $behavior === false ) {
            $behavior = $this->getByBehaviorId($behavior_id);
            if ( empty($behavior) ) {
                throw new Excp("用户行为数据不存在", 404, ["slug"=>$slug, "user_id"=>$user_id, "data"=>$data]);
            }
            $this->cache->setJSON( $cache_name, $behavior );
        }

        return $this->run( $behavior, $data, $env, $user_id );
    }


    /**
     * 执行行为(通知所有该行为订阅者)
     * @param string $behavior 行为结构体
     * @param string $user_id 用户ID 
     * @param array $data 行为数据
     * @return 成功返回 true ,  失败返回错误结构体 ["code"=>xxx, "message"=>"xxx", "extra"=>[...]]
     */
    function run( $behavior,$data=[], $env=[], $user_id=null ) {
        print_r( $behavior );
        print_r( $data ) ;
        print_r( $env );
        print_r( $user_id );
    }

    /**
     * 清理行为数据缓存
     */
    function clearCache( $behavior = null ) {

    }

    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 行为ID
		$this->putColumn( 'behavior_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 名称
		$this->putColumn( 'name', $this->type("string", ["length"=>64, "index"=>true, "null"=>true]));
		// 介绍
		$this->putColumn( 'intro', $this->type("text", ["null"=>true]));
		// 状态
		$this->putColumn( 'status', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 参数表
		$this->putColumn( 'params', $this->type("text", ["json"=>true, "null"=>true]));
		// 发生前
		$this->putColumn( 'before', $this->type("text", ["json"=>true, "null"=>true]));
		// 发生后
		$this->putColumn( 'after', $this->type("text", ["json"=>true, "null"=>true]));

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

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按行为ID查询一条行为记录
	 * @param string $behavior_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["behavior_id"],  // 行为ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["intro"],  // 介绍 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["params"],  // 参数表 
	 *          	  $rs["before"],  // 发生前 
	 *          	  $rs["after"],  // 发生后 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getByBehaviorId( $behavior_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_user_behavior as behavior", "{none}")->query();
		$qb->where('behavior_id', '=', $behavior_id );
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
	 * 按行为ID查询一组行为记录
	 * @param array   $behavior_ids 唯一主键数组 ["$behavior_id1","$behavior_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 行为记录MAP {"behavior_id1":{"key":"value",...}...}
	 */
	public function getInByBehaviorId($behavior_ids, $select=["behavior.behavior_id","behavior.name","behavior.slug","behavior.params","behavior.status"], $order=["behavior.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query()->whereIn('behavior_id', $behavior_ids);;
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['behavior_id']] = $rs;
			
		}



		return $map;
	}


	/**
	 * 按行为ID保存行为记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByBehaviorId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("behavior_id", $data, ["behavior_id", "slug"], ['_id', 'behavior_id']);
		return $this->getByBehaviorId( $rs['behavior_id'], $select );
	}
	
	/**
	 * 按别名查询一条行为记录
	 * @param string $slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["behavior_id"],  // 行为ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["name"],  // 名称 
	 *          	  $rs["intro"],  // 介绍 
	 *          	  $rs["status"],  // 状态 
	 *          	  $rs["params"],  // 参数表 
	 *          	  $rs["before"],  // 发生前 
	 *          	  $rs["after"],  // 发生后 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 */
	public function getBySlug( $slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();
		// $qb = Utils::getTab("xpmsns_user_behavior as behavior", "{none}")->query();
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
	 * 按别名查询一组行为记录
	 * @param array   $slugs 唯一主键数组 ["$slug1","$slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 行为记录MAP {"slug1":{"key":"value",...}...}
	 */
	public function getInBySlug($slugs, $select=["behavior.behavior_id","behavior.name","behavior.slug","behavior.params","behavior.status"], $order=["behavior.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
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
	 * 按别名保存行为记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveBySlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("slug", $data, ["behavior_id", "slug"], ['_id', 'behavior_id']);
		return $this->getByBehaviorId( $rs['behavior_id'], $select );
	}


	/**
	 * 添加行为记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["behavior_id"]) ) { 
			$data["behavior_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排行为记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 行为记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["behavior.behavior_id","behavior.name","behavior.slug","behavior.params","behavior.status"], $order=["behavior.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
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
	 * 按条件检索行为记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["behavior.behavior_id","behavior.name","behavior.slug","behavior.params","behavior.status"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["behavior_id"] 按行为ID查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["name"] 按名称查询 ( = )
	 *			      $query["status"] 按状态查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按创建时间 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间 DESC 排序
	 *           
	 * @return array 行为记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
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
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["behavior.behavior_id","behavior.name","behavior.slug","behavior.params","behavior.status"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "behavior.behavior_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = $this->query();

		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("behavior.behavior_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("behavior.slug","like", "%{$query['keywords']}%");
				$qb->orWhere("behavior.name","like", "%{$query['keywords']}%");
			});
		}


		// 按行为ID查询 (=)  
		if ( array_key_exists("behavior_id", $query) &&!empty($query['behavior_id']) ) {
			$qb->where("behavior.behavior_id", '=', "{$query['behavior_id']}" );
		}
		  
		// 按别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("behavior.slug", '=', "{$query['slug']}" );
		}
		  
		// 按名称查询 (=)  
		if ( array_key_exists("name", $query) &&!empty($query['name']) ) {
			$qb->where("behavior.name", '=', "{$query['name']}" );
		}
		  
		// 按状态查询 (=)  
		if ( array_key_exists("status", $query) &&!empty($query['status']) ) {
			$qb->where("behavior.status", '=', "{$query['status']}" );
		}
		  

		// 按创建时间 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("behavior.created_at", "desc");
		}

		// 按更新时间 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("behavior.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$behaviors = $qb->select( $select )->pgArray($perpage, ['behavior._id'], 'page', $page);

		foreach ($behaviors['data'] as & $rs ) {
			$this->format($rs);
			
		}

	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$behaviors['_sql'] = $qb->getSql();
			$behaviors['query'] = $query;
		}

		return $behaviors;
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
				$select[$idx] = "behavior." .$select[$idx];
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
			"behavior_id",  // 行为ID
			"slug",  // 别名
			"name",  // 名称
			"intro",  // 介绍
			"status",  // 状态
			"params",  // 参数表
			"before",  // 发生前
			"after",  // 发生后
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>