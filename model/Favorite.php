<?php
/**
 * Class Favorite 
 * 收藏数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 18:55:47
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
             
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Favorite extends Model {




	/**
	 * 收藏数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('favorite'); // 数据表名称 xpmsns_user_favorite

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    

    /**
     * 读取收藏内容
     * @param array &$favs 收藏数据集合
     * @param array $selects 选中字段清单
     *                 [
     *                    "origin"=>["field1","field2"..]
     *                    ...
     *                 ]
     */
    public function getSource( & $favs, $selects=[] ) {

        // 处理图文
        $article_ids = [];

        foreach( $favs  as $idx=>$fav ){
            if (  $fav["origin"] == "article"){
                array_push($article_ids, $fav["outer_id"]);
            }
        }

        // 处理图文
        if ( !empty($article_ids) ) {

            $select = is_array($selects["article"]) ? $selects["article"] : [
                "article.article_id",
                "article.title","article.cover","article.summary", "article.author", 
                "article.view_cnt","article.like_cnt", "article.dislike_cnt", "article.comment_cnt",
            ];

            $art = new \Xpmsns\Pages\Model\Article;
            $articles = $art->getInByArticleId($article_ids, $select);

            foreach( $favs as &$fav  ){
                $article_id = $fav["outer_id"];
                $article = $articles[$article_id];
                if( !is_array($article) ) {
                    $article = [];
                }

                $fav = array_merge( $fav, $article );
            }
        }
    }


    /**
     * 收藏初始化( 注册行为/注册任务/设置默认值等... )
     */
    public function __defaults() {

        // 注册任务
        $tasks = [
            [
                "name"=>"收藏任务", "slug"=>"favorite", "type"=>"repeatable",
                "daily_limit"=>1, "process"=>3, 
                "quantity" => [0,0,300],
                "params" => [
                    "count"=>3
                ],
                "auto_accept" => 0,
                "accept" => ["class"=>"\\xpmsns\\user\\model\\favorite", "method"=>"onFavoriteAccpet"],
                "status" => "online",
            ]
        ];

        // 注册行为
        $behaviors =[
            [
                "name" => "用户收藏", "slug"=>"xpmsns/user/favorite/create",
                "intro" =>  "本行为当用户收藏成功后触发",
                "params" => ["favorite_id"=>"收藏ID", "user_id"=>"用户ID", "outer_id"=>"资源ID", "origin"=>"来源", "url"=>"地址", "title"=>"标题", "summary"=>"摘要", "origin_outer_id"=>"收藏唯一ID"],
                "status" => "online",
            ]
        ];

        // 订阅行为( 响应任务处理 )
        $subscribers =[
            [
                "name" => "收藏任务",
                "behavior_slug"=>"xpmsns/user/favorite/create",
                "outer_id" => "favorite",
                "origin" => "task",
                "timeout" => 30,
                "handler" => ["class"=>"\\xpmsns\\user\\model\\favorite", "method"=>"onFavoriteChange"],
                "status" => "on",
            ]
        ];

        $t = new \Xpmsns\User\Model\Task();
        $b = new \Xpmsns\User\Model\Behavior();
        $s = new \Xpmsns\User\Model\Subscriber();

        foreach( $tasks as $task ){
            try { $t->create($task); } catch( Excp $e) { $e->log(); }
        }

        foreach( $behaviors as $behavior ){
            try { $b->create($behavior); } catch( Excp $e) { $e->log(); }
        }
        foreach( $subscribers as $subscriber ){
            try { $s->create($subscriber); } catch( Excp $e) { $e->log(); }
        }
    }

    /**
     * 任务接受响应: 收藏任务 (验证是否符合接受条件)
     * @return 符合返回 true, 不符合返回 false
     */
    public function onFavoriteAccpet(){
        return true;
    }

    /**
     * 订阅器: 收藏任务 ( 收藏行为发生时, 触发此函数, 可在后台暂停或关闭)
     * @param array $behavior  行为(用户签到)数据结构
     * @param array $subscriber  订阅者(签到任务订阅) 数据结构  ["outer_id"=>"任务SLUG", "origin"=>"task" ... ]
     * @param array $data  行为数据 ["favorite_id"=>"收藏ID", "user_id"=>"用户ID", "outer_id"=>"资源ID", "origin"=>"来源", "url"=>"地址", "title"=>"标题", "summary"=>"摘要", "origin_outer_id"=>"收藏唯一ID"],
     * @param array $env 环境数据 (session_id, user_id, client_ip, time, user, cookies...)
     */
    public function onFavoriteChange( $behavior, $subscriber, $data, $env ) {
    
        $task_slug = $subscriber["outer_id"];
        $user_id = $env["user_id"];

        $t = new \Xpmsns\User\Model\Usertask;
        $task = $t->getByTaskSlugAndUserId( $task_slug, $user_id );
        if ( empty($task) ) {
            throw new Excp("未找到任务信息({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]);
        }

        // 自动接受任务
        $usertask = $task["usertask"];
        if( 
            $task["auto_accept"] == 1 &&
            ( empty($usertask) || ( $usertask["status"] != "accepted" &&  $task["type"] == "repeatable" ) )
        ) {
            $task["usertask"] = $usertask = $t->acceptBySlug( $task_slug, $user_id );
        }

        if ( empty($task["usertask"]) ) {
            throw new Excp("用户尚未接受该任务({$task_slug})", 404, ["task_slug"=>$task_slug, "user_id"=>$user_id]); 
        }

        // 扩展数量
        $params = is_array($task["params"]) ? $task["params"] : [];
        $params["count"] = empty($params["count"]) ?  intval($task["process"]) : intval($params["count"]);
        if ( $params["count"] != intval($task["process"]) ) {
            $tt = new  \Xpmsns\User\Model\Task;
            $quantity = []; 
            for( $i=0;$i<$params["count"]; $i++) {
                $quantity[$i] = 0;
            }
            $quantity[$params["count"]-1] = end($task["quantity"]);

            $tt->updateBy("task_id", [
                "task_id"=>$task["task_id"],
                "process" => $params["count"],
                "quantity" => $quantity,
            ]);
        }


        // 任务副本创建时间
        if ( strtotime($data["created_at"]) < strtotime($usertask["created_at"]) ) {
            $created_at = $data["created_at"];
        } else {
            $created_at = $usertask["created_at"];
        }

        // 检索自任务副本创建到当前时刻的收藏数量
        $process = $this->query()
                   ->where("user_id", "=",$user_id)
                   ->where("created_at", ">=",$created_at)
                   ->limit(3)
                   ->count("favorite_id")
                ;
                
        if ( $process > 0 ) {
            $t->processByUsertaskId( $usertask["usertask_id"], $process );
        }

    }

    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        if ( !empty($data["origin"]) &&  !empty($data["outer_id"]) ) {
            $data["origin_outer_id"] = "DB::RAW(CONCAT(`origin`,'_',`outer_id`))";
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
				"origin_outer_id"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `origin_outer_id`))"
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

		// 收藏ID
		$this->putColumn( 'favorite_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 资源ID
		$this->putColumn( 'outer_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 来源
		$this->putColumn( 'origin', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 地址
		$this->putColumn( 'url', $this->type("string", ["length"=>600, "null"=>true]));
		// 收藏唯一ID
		$this->putColumn( 'origin_outer_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 标题
		$this->putColumn( 'title', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 摘要
		$this->putColumn( 'summary', $this->type("string", ["length"=>600, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按收藏ID查询一条收藏记录
	 * @param string $favorite_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["favorite_id"],  // 收藏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["outer_id"],  // 资源ID 
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["url"],  // 地址 
	 *          	  $rs["origin_outer_id"],  // 收藏唯一ID 
	 *          	  $rs["title"],  // 标题 
	 *          	  $rs["summary"],  // 摘要 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
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
	 */
	public function getByFavoriteId( $favorite_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户
		$qb->where('favorite_id', '=', $favorite_id );
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
	 * 按收藏ID查询一组收藏记录
	 * @param array   $favorite_ids 唯一主键数组 ["$favorite_id1","$favorite_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 收藏记录MAP {"favorite_id1":{"key":"value",...}...}
	 */
	public function getInByFavoriteId($favorite_ids, $select=["favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"], $order=["favorite.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户
		$qb->whereIn('favorite.favorite_id', $favorite_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['favorite_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按收藏ID保存收藏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByFavoriteId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("favorite_id", $data, ["favorite_id", "origin_outer_id"], ['_id', 'favorite_id']);
		return $this->getByFavoriteId( $rs['favorite_id'], $select );
	}
	
	/**
	 * 按收藏唯一ID查询一条收藏记录
	 * @param string $origin_outer_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["favorite_id"],  // 收藏ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["outer_id"],  // 资源ID 
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["url"],  // 地址 
	 *          	  $rs["origin_outer_id"],  // 收藏唯一ID 
	 *          	  $rs["title"],  // 标题 
	 *          	  $rs["summary"],  // 摘要 
	 *          	  $rs["created_at"],  // 创建时间 
	 *          	  $rs["updated_at"],  // 更新时间 
	 *                $rs["user_created_at"], // user.created_at
	 *                $rs["user_updated_at"], // user.updated_at
	 *                $rs["user_group_id"], // user.group_id
	 *                $rs["user_name"], // user.name
	 *                $rs["user_idno"], // user.idno
	 *                $rs["user_idtype"], // user.idtype
	 *                $rs["user_iddoc"], // user.iddoc
	 *                $rs["user_nickname"], // user.nickname
	 *                $rs["user_sex"], // user.sex
	 *                $rs["user_city"], // user.city
	 *                $rs["user_province"], // user.province
	 *                $rs["user_country"], // user.country
	 *                $rs["user_headimgurl"], // user.headimgurl
	 *                $rs["user_language"], // user.language
	 *                $rs["user_birthday"], // user.birthday
	 *                $rs["user_bio"], // user.bio
	 *                $rs["user_bgimgurl"], // user.bgimgurl
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
	 */
	public function getByOriginOuterId( $origin_outer_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户
		$qb->where('origin_outer_id', '=', $origin_outer_id );
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
	 * 按收藏唯一ID查询一组收藏记录
	 * @param array   $origin_outer_ids 唯一主键数组 ["$origin_outer_id1","$origin_outer_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 收藏记录MAP {"origin_outer_id1":{"key":"value",...}...}
	 */
	public function getInByOriginOuterId($origin_outer_ids, $select=["favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"], $order=["favorite.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户
		$qb->whereIn('favorite.origin_outer_id', $origin_outer_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['origin_outer_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按收藏唯一ID保存收藏记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByOriginOuterId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("origin_outer_id", $data, ["favorite_id", "origin_outer_id"], ['_id', 'favorite_id']);
		return $this->getByFavoriteId( $rs['favorite_id'], $select );
	}


	/**
	 * 添加收藏记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["favorite_id"]) ) { 
			$data["favorite_id"] = $this->genId();
        }

        // @KEEP BEGIN
        if ( !empty($data["origin"]) &&  !empty($data["outer_id"]) ) {
            $data["origin_outer_id"] = "DB::RAW(CONCAT(`origin`,'_',`outer_id`))";
        }
        // @KEEP END

		return parent::create( $data );
	}


	/**
	 * 查询前排收藏记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 收藏记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"], $order=["favorite.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户


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
	 * 按条件检索收藏记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["favorite_id"] 按收藏ID查询 ( = )
	 *			      $query["outer_id"] 按资源ID查询 ( = )
	 *			      $query["user_user_id"] 按查询 ( = )
	 *			      $query["user_mobile_full"] 按查询 ( LIKE )
	 *			      $query["origin"] 按来源查询 ( = )
	 *			      $query["title"] 按标题查询 ( LIKE )
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *           
	 * @return array 收藏记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["favorite_id"],  // 收藏ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["outer_id"],  // 资源ID 
	 *               	["origin"],  // 来源 
	 *               	["url"],  // 地址 
	 *               	["origin_outer_id"],  // 收藏唯一ID 
	 *               	["title"],  // 标题 
	 *               	["summary"],  // 摘要 
	 *               	["created_at"],  // 创建时间 
	 *               	["updated_at"],  // 更新时间 
	 *               	["user_created_at"], // user.created_at
	 *               	["user_updated_at"], // user.updated_at
	 *               	["user_group_id"], // user.group_id
	 *               	["user_name"], // user.name
	 *               	["user_idno"], // user.idno
	 *               	["user_idtype"], // user.idtype
	 *               	["user_iddoc"], // user.iddoc
	 *               	["user_nickname"], // user.nickname
	 *               	["user_sex"], // user.sex
	 *               	["user_city"], // user.city
	 *               	["user_province"], // user.province
	 *               	["user_country"], // user.country
	 *               	["user_headimgurl"], // user.headimgurl
	 *               	["user_language"], // user.language
	 *               	["user_birthday"], // user.birthday
	 *               	["user_bio"], // user.bio
	 *               	["user_bgimgurl"], // user.bgimgurl
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
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["favorite.favorite_id","user.user_id","user.name","user.nickname","user.mobile","favorite.origin","favorite.outer_id","favorite.created_at","favorite.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "favorite.favorite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_favorite as favorite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "favorite.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("favorite.favorite_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("favorite.outer_id","like", "%{$query['keyword']}%");
				$qb->orWhere("favorite.origin","like", "%{$query['keyword']}%");
				$qb->orWhere("favorite.title","like", "%{$query['keyword']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keyword']}%");
				$qb->orWhere("user.name","like", "%{$query['keyword']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keyword']}%");
			});
		}


		// 按收藏ID查询 (=)  
		if ( array_key_exists("favorite_id", $query) &&!empty($query['favorite_id']) ) {
			$qb->where("favorite.favorite_id", '=', "{$query['favorite_id']}" );
		}
		  
		// 按资源ID查询 (=)  
		if ( array_key_exists("outer_id", $query) &&!empty($query['outer_id']) ) {
			$qb->where("favorite.outer_id", '=', "{$query['outer_id']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_user_id", $query) &&!empty($query['user_user_id']) ) {
			$qb->where("user.user_id", '=', "{$query['user_user_id']}" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("user_mobile_full", $query) &&!empty($query['user_mobile_full']) ) {
			$qb->where("user.mobile_full", 'like', "%{$query['user_mobile_full']}%" );
		}
		  
		// 按来源查询 (=)  
		if ( array_key_exists("origin", $query) &&!empty($query['origin']) ) {
			$qb->where("favorite.origin", '=', "{$query['origin']}" );
		}
		  
		// 按标题查询 (LIKE)  
		if ( array_key_exists("title", $query) &&!empty($query['title']) ) {
			$qb->where("favorite.title", 'like', "%{$query['title']}%" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("favorite.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("favorite.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$favorites = $qb->select( $select )->pgArray($perpage, ['favorite._id'], 'page', $page);

 		foreach ($favorites['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$favorites['_sql'] = $qb->getSql();
			$favorites['query'] = $query;
		}

		return $favorites;
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
				$select[$idx] = "favorite." .$select[$idx];
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
						array_push($linkSelect, "favorite.*");
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
			"favorite_id",  // 收藏ID
			"user_id",  // 用户ID
			"outer_id",  // 资源ID
			"origin",  // 来源
			"url",  // 地址
			"origin_outer_id",  // 收藏唯一ID
			"title",  // 标题
			"summary",  // 摘要
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>