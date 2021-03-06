<?php
/**
 * Class Follow 
 * 关注数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-28 11:56:45
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
           
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Mina\Cache\Redis as Cache;
use \Xpmse\Loader\App as App;
use \Xpmse\Job;


class Follow extends Model {




    /**
     * 数据缓存对象
     */
    protected $cache = null;

	/**
	 * 关注数据模型【3】
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
        $this->table('follow'); // 数据表名称 xpmsns_user_follow
         // + Redis缓存
        $this->cache = new Cache([
            "prefix" => "xpmsns_user_follow:",
            "host" => Conf::G("mem/redis/host"),
            "port" => Conf::G("mem/redis/port"),
            "passwd"=> Conf::G("mem/redis/password")
        ]);


       
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN


    /**
     * 关注某人
     */
    public function follow( $my_id, $user_id, $data=[] ) {

        $data["follower_id"] = $my_id;
        $data["user_id"]  = $user_id;

        if ( $data["user_id"] == $data["follower_id"] ) {
            throw new Excp("不可以关注自己", 403, ["query"=>$query, "data"=>$data]);
        }

        // 关注
        try {
            $resp =  $this->create( $data );
        } catch( Excp $e ) {
            if ( $e->getCode() == 1062 ) {
                throw new Excp("你已经关注了该用户", 403, ["query"=>$query, "data"=>$data]);  
            }
            throw $e;
        }


        $u = new User();
        // 更新被关注的人粉丝数量
        $u->runSql("update {{table}} SET `follower_cnt`=? WHERE `user_id`=? LIMIT 1", false, [ $this->countFollowers($user_id), $user_id ] );
        // 更新我的关注的人数量
        $u->runSql("update {{table}} SET `following_cnt`=? WHERE `user_id`=? LIMIT 1", false, [ $this->countFollowings($my_id), $my_id ] );

        // 清除缓存
        $this->clearRelationCache( $my_id, $user_id );
        $this->clearRelationCache( $user_id, $my_id );

        return $resp;

    }


    /**
     * 取关某人
     */
    public function unfollow( $my_id, $user_id ) {
        $fo = new \Xpmsns\User\Model\Follow;
        $id = "{$user_id}_{$my_id}";

        // 取关
        $resp = $this->remove( $id,"user_follower");
        if ( $resp == true ) {
            $this->clearRelationCache($user_id, $my_id);
            $this->clearRelationCache($my_id,$user_id);

            $u = new User();
            // 更新被关注的人粉丝数量
            $u->runSql("update {{table}} SET `follower_cnt`=? WHERE `user_id`=? LIMIT 1", false, [ $this->countFollowers($user_id), $user_id ] );
            // 更新我的关注的人数量
            $u->runSql("update {{table}} SET `following_cnt`=? WHERE `user_id`=? LIMIT 1", false, [ $this->countFollowings($my_id), $my_id ] );
            return ["code"=>0, "message"=>"取关成功"];
        }

        throw new Excp("取关失败", 403, ["my_id"=>$my_id, "user_id"=>$user_id, "response"=>$resp]);  
    }



    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        if ( !empty($data["user_id"]) &&  !empty($data["follower_id"]) ) {
            $data["user_follower"] = "DB::RAW(CONCAT(`user_id`,'_', `follower_id`))";           
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
				"user_follower"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `user_follower`))"
			]);

			if ( $row['deleted_at'] == $time ) {	
				return true;
			}
			return false;
		}

		return parent::remove($data_key, $uni_key, $mark_only);
    }
    

    /**
     * 清空用户关系缓存
     * @param string $my_id 用户ID 
     * @param array  $user_id 待检验的用户ID
     */
    function clearRelationCache( string $my_id = null, string $user_id = null ) {

        if ( empty($my_id) ) {
            return $this->cache->delete();
        } else if ( empty($user_id) ) {
            return $this->cache->delete($my_id);
        } else {
            $cache_name = "{$my_id}:{$user_id}";
            return $this->cache->del($cache_name);
        }
    }

    /**
     * 从缓存中读取用户关系
     * @param string $my_id 用户ID 
     * @param array  $user_id 待检验的用户ID
     * @return string|bool self 自己, follower 粉丝, following 关注的人, friend 互相关注,  no-relation 不是粉丝,也不是关注的人,  false 缓存中没有数据
     */
    function getRelationFromCache( string $my_id, string $user_id ) {
        $cache_name = "{$my_id}:{$user_id}";
        return $this->cache->get($cache_name);
    }


    /**
     * 批量读取用户关系
     * @param string $my_id 用户ID 
     * @param array  $user_ids 待检验的用户ID清单
     * @return 待检测用户与$my_id用户关系映射  {":user_id": "follower", .... }
     *         关系有效值: self 自己, follower 粉丝, following 关注的人, friend 互相关注,  no-relation 路人 不是粉丝,也不是关注的人,  false 缓存中没有数据
     */
    function getRelation(string $my_id, array $user_ids) {

        $relation = [];
        $followers = []; $followings = []; $friends = []; 

        // 读取粉丝
        $followerRows = $this->query()
                          ->where("user_id", $my_id)
                          ->whereIn("follower_id", $user_ids)
                          ->select("follower_id")
                          ->get()->toArray();
        $followers = array_column( $followerRows, "follower_id" );
        
        // 读取关注的人
        $followingRows = $this->query()
                          ->where("follower_id", $my_id)
                          ->whereIn("user_id", $user_ids)
                          ->select("user_id")
                          ->get()->toArray();
        
        $followings = array_column( $followingRows, "user_id" );

        // 计算互相关注关系
        $friends = array_intersect($followers, $followings);

        // 返回关系映射
        foreach( $user_ids as $user_id ) {

            // self
            if ( $user_id == $my_id) {
                $relation[$user_id] = "self";
            }

            // 互相关注
            elseif ( in_array($user_id, $friends) ) {
                $relation[$user_id] = "friend";
            }

            // 粉丝
            elseif ( in_array($user_id, $followers) ) {
                $relation[$user_id] = "follower";
            }

            // 关注的人
            elseif ( in_array($user_id, $followings) ) {
                $relation[$user_id] = "following";
            }

            // 路人 不是粉丝,也不是关注的人
            else {
                $relation[$user_id] = 'no-relation';
            }

            // 数据缓存1个小时
            $cache_name = "{$my_id}:{$user_id}";
            $this->cache->set($cache_name, $relation[$user_id], 3600);
        }
        
        return $relation;
    }


    /**
     * 查询粉丝列表
     * @param string $my_id 用户ID 
     * @param array $query 查询条件
     */
    function getFollowers( string $my_id, $query=[] ) {
        $select = $query["select"];
        if (empty($select) ) {
            $select = [
                "follow_id", "origin", 
                "follow.user_id", "user.user_id as user_user_id", "user.nickname as user_nickname", "user.name as user_name","user.headimgurl as user_headimgurl", "user.follower_cnt as user_follower_cnt","user.following_cnt as user_following_cnt","user.question_cnt as user_question_cnt", "user.answer_cnt as user_answer_cnt", "user.article_cnt as user_article_cnt", "user.favorite_cnt as user_favorite_cnt", "user.bio as user_bio",
                "follow.follower_id", "follower.user_id as follower_user_id",  "follower.nickname as follower_nickname", "follower.name as follower_name","follower.headimgurl as follower_headimgurl", "follower.follower_cnt as follower_follower_cnt","follower.following_cnt as follower_following_cnt","follower.question_cnt as follower_question_cnt", "follower.answer_cnt as follower_answer_cnt", "follower.article_cnt as follower_article_cnt", "follower.favorite_cnt as follower_favorite_cnt", "follower.bio as follower_bio",
            ];
        }

        if ( is_string($select) ) {
            $select = explode(',', $select);
        }
        
        $query["select"] = $select;
        $query["user_id"] = $my_id;
        return $this->search( $query );

    }

    /**
     * 查询粉丝数量
     * @param string $my_id 用户ID 
     * @return int 粉丝总数
     */
    function countFollowers( string $my_id ){
        $qb = $this->query();
        $cnt = $qb->where("user_id", "=", $my_id )->count("_id");
        return intval( $cnt );
    }


    /**
     * 查询关注者列表
     * @param string $my_id 用户ID 
     * @param array $query 查询条件
     */
    function getFollowings( string $my_id, $query=[] ) {

        $select = $query["select"];

        if (empty($select) ) {
            $select = [
                "follow_id", "origin", "follower.user_id as user_id",
                "follow.follower_id as user_id", "follower.user_id as user_user_id","follower.nickname as user_nickname", "follower.name as user_name","follower.headimgurl as user_headimgurl","follower.follower_cnt as user_follower_cnt","follower.following_cnt as user_following_cnt", "follower.question_cnt as user_question_cnt", "follower.answer_cnt as user_answer_cnt", "follower.article_cnt as user_article_cnt", "follower.favorite_cnt as user_favorite_cnt", "follower.bio as user_bio",
                "user.user_id as following_id", "user.nickname as following_nickname", "user.name as following_name","user.headimgurl as following_headimgurl", "user.follower_cnt as following_follower_cnt","user.following_cnt as following_following_cnt","user.question_cnt as following_question_cnt", "user.answer_cnt as following_answer_cnt", "user.article_cnt as following_article_cnt", "user.favorite_cnt as following_favorite_cnt", "user.bio as following_bio",
            ];
        }

        if ( is_string($select) ) {
            $select = explode(',', $select);
        }
        
        $query["select"] = $select;
        $query["follower_id"] = $my_id;
        return $this->search( $query );
    }

    /**
     * 查询关注的人数量
     * @param string $my_id 用户ID 
     * @return int 关注人总数
     */
    function countFollowings( string $my_id ){
        $qb = $this->query();
        $cnt = $qb->where("follower_id", "=", $my_id )->count("_id");
        return intval( $cnt );
    }


    /**
     * 查询好友列表
     * @param string $my_id 用户ID 
     * @param array|string  $select 查询字段清单
     */
    function getFriends( string $my_id, $query=[],  $select = [] ) {

        $select = $query["select"];
        
        if (empty($select) ) {
            $select = [
                "follow.follow_id", "follow.origin", 
                "friend.user_id as friend_user_id", "friend.follower_id as friend_follower_id",  
                "follow.user_id", "user.nickname as user_nickname", "user.name as user_name","user.headimgurl as user_headimgurl",
                "follow.follower_id", "follower.nickname as follower_nickname", "follower.name as follower_name","follower.headimgurl as follower_headimgurl",
            ];
        }

        if ( is_string($select) ) {
            $select = explode(',', $select);
        }

        return [];
    }


    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 关注ID
		$this->putColumn( 'follow_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 粉丝ID
		$this->putColumn( 'follower_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 来源
		$this->putColumn( 'origin', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 唯一ID
		$this->putColumn( 'user_follower', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 数据
		$this->putColumn( 'data', $this->type("string", ["length"=>400, "json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {
     
		$fileFields = []; 

        
        // @KEEP BEGIN
        $fileFields = ["user_headimgurl", "follower_headimgurl", "following_headimgurl"]; 
        foreach( $fileFields as $fd ) {
            if ( is_string($rs["{$fd}"])  && !empty($rs["{$fd}"])) {
                $rs["{$fd}"] = json_decode( $rs["{$fd}"], true);   
            }
        }
        // @KEEP END

        // 处理图片和文件字段 
        $this->__fileFields( $rs, $fileFields );

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按关注ID查询一条关注记录
	 * @param string $follow_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["follow_id"],  // 关注ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["follower_id"],  // 粉丝ID 
	 *                $rs["follower_user_id"], // user.user_id
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["user_follower"],  // 唯一ID 
	 *          	  $rs["data"],  // 数据 
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
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 *                $rs["user_name_message"], // user.name_message
	 *                $rs["user_verify_message"], // user.verify_message
	 *                $rs["user_client_token"], // user.client_token
	 *                $rs["user_user_name"], // user.user_name
	 *                $rs["follower_created_at"], // user.created_at
	 *                $rs["follower_updated_at"], // user.updated_at
	 *                $rs["follower_group_id"], // user.group_id
	 *                $rs["follower_name"], // user.name
	 *                $rs["follower_idno"], // user.idno
	 *                $rs["follower_idtype"], // user.idtype
	 *                $rs["follower_iddoc"], // user.iddoc
	 *                $rs["follower_nickname"], // user.nickname
	 *                $rs["follower_sex"], // user.sex
	 *                $rs["follower_city"], // user.city
	 *                $rs["follower_province"], // user.province
	 *                $rs["follower_country"], // user.country
	 *                $rs["follower_headimgurl"], // user.headimgurl
	 *                $rs["follower_language"], // user.language
	 *                $rs["follower_birthday"], // user.birthday
	 *                $rs["follower_bio"], // user.bio
	 *                $rs["follower_bgimgurl"], // user.bgimgurl
	 *                $rs["follower_mobile"], // user.mobile
	 *                $rs["follower_mobile_nation"], // user.mobile_nation
	 *                $rs["follower_mobile_full"], // user.mobile_full
	 *                $rs["follower_email"], // user.email
	 *                $rs["follower_contact_name"], // user.contact_name
	 *                $rs["follower_contact_tel"], // user.contact_tel
	 *                $rs["follower_title"], // user.title
	 *                $rs["follower_company"], // user.company
	 *                $rs["follower_zip"], // user.zip
	 *                $rs["follower_address"], // user.address
	 *                $rs["follower_remark"], // user.remark
	 *                $rs["follower_tag"], // user.tag
	 *                $rs["follower_user_verified"], // user.user_verified
	 *                $rs["follower_name_verified"], // user.name_verified
	 *                $rs["follower_verify"], // user.verify
	 *                $rs["follower_verify_data"], // user.verify_data
	 *                $rs["follower_mobile_verified"], // user.mobile_verified
	 *                $rs["follower_email_verified"], // user.email_verified
	 *                $rs["follower_extra"], // user.extra
	 *                $rs["follower_password"], // user.password
	 *                $rs["follower_pay_password"], // user.pay_password
	 *                $rs["follower_status"], // user.status
	 *                $rs["follower_inviter"], // user.inviter
	 *                $rs["follower_follower_cnt"], // user.follower_cnt
	 *                $rs["follower_following_cnt"], // user.following_cnt
	 *                $rs["follower_name_message"], // user.name_message
	 *                $rs["follower_verify_message"], // user.verify_message
	 *                $rs["follower_client_token"], // user.client_token
	 *                $rs["follower_user_name"], // user.user_name
	 */
	public function getByFollowId( $follow_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝
		$qb->where('follow.follow_id', '=', $follow_id );
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
	 * 按关注ID查询一组关注记录
	 * @param array   $follow_ids 唯一主键数组 ["$follow_id1","$follow_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 关注记录MAP {"follow_id1":{"key":"value",...}...}
	 */
	public function getInByFollowId($follow_ids, $select=["follow.follow_id","user.user_id","user.name","user.nickname","user.mobile","follower.user_id","follower.name","follower.nickname","follower.mobile","follow.origin","follow.created_at","follow.updated_at"], $order=["follow.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝
		$qb->whereIn('follow.follow_id', $follow_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['follow_id']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按关注ID保存关注记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByFollowId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("follow_id", $data, ["follow_id", "user_follower"], ['_id', 'follow_id']);
		return $this->getByFollowId( $rs['follow_id'], $select );
	}
	
	/**
	 * 按唯一ID查询一条关注记录
	 * @param string $user_follower 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["follow_id"],  // 关注ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["follower_id"],  // 粉丝ID 
	 *                $rs["follower_user_id"], // user.user_id
	 *          	  $rs["origin"],  // 来源 
	 *          	  $rs["user_follower"],  // 唯一ID 
	 *          	  $rs["data"],  // 数据 
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
	 *                $rs["user_inviter"], // user.inviter
	 *                $rs["user_follower_cnt"], // user.follower_cnt
	 *                $rs["user_following_cnt"], // user.following_cnt
	 *                $rs["user_name_message"], // user.name_message
	 *                $rs["user_verify_message"], // user.verify_message
	 *                $rs["user_client_token"], // user.client_token
	 *                $rs["user_user_name"], // user.user_name
	 *                $rs["follower_created_at"], // user.created_at
	 *                $rs["follower_updated_at"], // user.updated_at
	 *                $rs["follower_group_id"], // user.group_id
	 *                $rs["follower_name"], // user.name
	 *                $rs["follower_idno"], // user.idno
	 *                $rs["follower_idtype"], // user.idtype
	 *                $rs["follower_iddoc"], // user.iddoc
	 *                $rs["follower_nickname"], // user.nickname
	 *                $rs["follower_sex"], // user.sex
	 *                $rs["follower_city"], // user.city
	 *                $rs["follower_province"], // user.province
	 *                $rs["follower_country"], // user.country
	 *                $rs["follower_headimgurl"], // user.headimgurl
	 *                $rs["follower_language"], // user.language
	 *                $rs["follower_birthday"], // user.birthday
	 *                $rs["follower_bio"], // user.bio
	 *                $rs["follower_bgimgurl"], // user.bgimgurl
	 *                $rs["follower_mobile"], // user.mobile
	 *                $rs["follower_mobile_nation"], // user.mobile_nation
	 *                $rs["follower_mobile_full"], // user.mobile_full
	 *                $rs["follower_email"], // user.email
	 *                $rs["follower_contact_name"], // user.contact_name
	 *                $rs["follower_contact_tel"], // user.contact_tel
	 *                $rs["follower_title"], // user.title
	 *                $rs["follower_company"], // user.company
	 *                $rs["follower_zip"], // user.zip
	 *                $rs["follower_address"], // user.address
	 *                $rs["follower_remark"], // user.remark
	 *                $rs["follower_tag"], // user.tag
	 *                $rs["follower_user_verified"], // user.user_verified
	 *                $rs["follower_name_verified"], // user.name_verified
	 *                $rs["follower_verify"], // user.verify
	 *                $rs["follower_verify_data"], // user.verify_data
	 *                $rs["follower_mobile_verified"], // user.mobile_verified
	 *                $rs["follower_email_verified"], // user.email_verified
	 *                $rs["follower_extra"], // user.extra
	 *                $rs["follower_password"], // user.password
	 *                $rs["follower_pay_password"], // user.pay_password
	 *                $rs["follower_status"], // user.status
	 *                $rs["follower_inviter"], // user.inviter
	 *                $rs["follower_follower_cnt"], // user.follower_cnt
	 *                $rs["follower_following_cnt"], // user.following_cnt
	 *                $rs["follower_name_message"], // user.name_message
	 *                $rs["follower_verify_message"], // user.verify_message
	 *                $rs["follower_client_token"], // user.client_token
	 *                $rs["follower_user_name"], // user.user_name
	 */
	public function getByUserFollower( $user_follower, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝
		$qb->where('follow.user_follower', '=', $user_follower );
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
	 * 按唯一ID查询一组关注记录
	 * @param array   $user_followers 唯一主键数组 ["$user_follower1","$user_follower2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 关注记录MAP {"user_follower1":{"key":"value",...}...}
	 */
	public function getInByUserFollower($user_followers, $select=["follow.follow_id","user.user_id","user.name","user.nickname","user.mobile","follower.user_id","follower.name","follower.nickname","follower.mobile","follow.origin","follow.created_at","follow.updated_at"], $order=["follow.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝
		$qb->whereIn('follow.user_follower', $user_followers);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

  		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['user_follower']] = $rs;
			
  		}

  

		return $map;
	}


	/**
	 * 按唯一ID保存关注记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUserFollower( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("user_follower", $data, ["follow_id", "user_follower"], ['_id', 'follow_id']);
		return $this->getByFollowId( $rs['follow_id'], $select );
	}


	/**
	 * 添加关注记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["follow_id"]) ) { 
			$data["follow_id"] = $this->genId();
        }
        
        // @KEEP BEGIN
        if ( !empty($data["user_id"]) &&  !empty($data["follower_id"]) ) {
            $data["user_follower"] = "DB::RAW(CONCAT(`user_id`,'_', `follower_id`))";
        }
        // @KEEP END
        
		return parent::create( $data );
	}


	/**
	 * 查询前排关注记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 关注记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["follow.follow_id","user.user_id","user.name","user.nickname","user.mobile","follower.user_id","follower.name","follower.nickname","follower.mobile","follow.origin","follow.created_at","follow.updated_at"], $order=["follow.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝


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
	 * 按条件检索关注记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["follow.follow_id","user.user_id","user.name","user.nickname","user.mobile","follower.user_id","follower.name","follower.nickname","follower.mobile","follow.origin","follow.created_at","follow.updated_at"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keyword"] 按关键词查询
	 *			      $query["user_id"] 按用户ID查询 ( = )
	 *			      $query["follower_id"] 按粉丝ID查询 ( = )
	 *			      $query["user_user_id"] 按查询 ( = )
	 *			      $query["follower_user_id"] 按查询 ( = )
	 *			      $query["user_mobile_full"] 按查询 ( LIKE )
	 *			      $query["follower_mobile_full"] 按查询 ( LIKE )
	 *			      $query["origin"] 按来源查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *           
	 * @return array 关注记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["follow_id"],  // 关注ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["follower_id"],  // 粉丝ID 
	 *               	["follower_user_id"], // user.user_id
	 *               	["origin"],  // 来源 
	 *               	["user_follower"],  // 唯一ID 
	 *               	["data"],  // 数据 
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
	 *               	["user_inviter"], // user.inviter
	 *               	["user_follower_cnt"], // user.follower_cnt
	 *               	["user_following_cnt"], // user.following_cnt
	 *               	["user_name_message"], // user.name_message
	 *               	["user_verify_message"], // user.verify_message
	 *               	["user_client_token"], // user.client_token
	 *               	["user_user_name"], // user.user_name
	 *               	["follower_created_at"], // user.created_at
	 *               	["follower_updated_at"], // user.updated_at
	 *               	["follower_group_id"], // user.group_id
	 *               	["follower_name"], // user.name
	 *               	["follower_idno"], // user.idno
	 *               	["follower_idtype"], // user.idtype
	 *               	["follower_iddoc"], // user.iddoc
	 *               	["follower_nickname"], // user.nickname
	 *               	["follower_sex"], // user.sex
	 *               	["follower_city"], // user.city
	 *               	["follower_province"], // user.province
	 *               	["follower_country"], // user.country
	 *               	["follower_headimgurl"], // user.headimgurl
	 *               	["follower_language"], // user.language
	 *               	["follower_birthday"], // user.birthday
	 *               	["follower_bio"], // user.bio
	 *               	["follower_bgimgurl"], // user.bgimgurl
	 *               	["follower_mobile"], // user.mobile
	 *               	["follower_mobile_nation"], // user.mobile_nation
	 *               	["follower_mobile_full"], // user.mobile_full
	 *               	["follower_email"], // user.email
	 *               	["follower_contact_name"], // user.contact_name
	 *               	["follower_contact_tel"], // user.contact_tel
	 *               	["follower_title"], // user.title
	 *               	["follower_company"], // user.company
	 *               	["follower_zip"], // user.zip
	 *               	["follower_address"], // user.address
	 *               	["follower_remark"], // user.remark
	 *               	["follower_tag"], // user.tag
	 *               	["follower_user_verified"], // user.user_verified
	 *               	["follower_name_verified"], // user.name_verified
	 *               	["follower_verify"], // user.verify
	 *               	["follower_verify_data"], // user.verify_data
	 *               	["follower_mobile_verified"], // user.mobile_verified
	 *               	["follower_email_verified"], // user.email_verified
	 *               	["follower_extra"], // user.extra
	 *               	["follower_password"], // user.password
	 *               	["follower_pay_password"], // user.pay_password
	 *               	["follower_status"], // user.status
	 *               	["follower_inviter"], // user.inviter
	 *               	["follower_follower_cnt"], // user.follower_cnt
	 *               	["follower_following_cnt"], // user.following_cnt
	 *               	["follower_name_message"], // user.name_message
	 *               	["follower_verify_message"], // user.verify_message
	 *               	["follower_client_token"], // user.client_token
	 *               	["follower_user_name"], // user.user_name
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["follow.follow_id","user.user_id","user.name","user.nickname","user.mobile","follower.user_id","follower.name","follower.nickname","follower.mobile","follow.origin","follow.created_at","follow.updated_at"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "follow.follow_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_follow as follow", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "follow.user_id"); // 连接用户
 		$qb->leftJoin("xpmsns_user_user as follower", "follower.user_id", "=", "follow.follower_id"); // 连接粉丝

		// 按关键词查找
		if ( array_key_exists("keyword", $query) && !empty($query["keyword"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("follow.follower_id", "like", "%{$query['keyword']}%");
				$qb->orWhere("follow.origin","like", "%{$query['keyword']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keyword']}%");
				$qb->orWhere("user.name","like", "%{$query['keyword']}%");
				$qb->orWhere("follower.user_id","like", "%{$query['keyword']}%");
				$qb->orWhere("follower.name","like", "%{$query['keyword']}%");
				$qb->orWhere("follower.nickname","like", "%{$query['keyword']}%");
			});
		}


		// 按用户ID查询 (=)  
		if ( array_key_exists("user_id", $query) &&!empty($query['user_id']) ) {
			$qb->where("follow.user_id", '=', "{$query['user_id']}" );
		}
		  
		// 按粉丝ID查询 (=)  
		if ( array_key_exists("follower_id", $query) &&!empty($query['follower_id']) ) {
			$qb->where("follow.follower_id", '=', "{$query['follower_id']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_user_id", $query) &&!empty($query['user_user_id']) ) {
			$qb->where("user.user_id", '=', "{$query['user_user_id']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("follower_user_id", $query) &&!empty($query['follower_user_id']) ) {
			$qb->where("follower.user_id", '=', "{$query['follower_user_id']}" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("user_mobile_full", $query) &&!empty($query['user_mobile_full']) ) {
			$qb->where("user.mobile_full", 'like', "%{$query['user_mobile_full']}%" );
		}
		  
		// 按查询 (LIKE)  
		if ( array_key_exists("follower_mobile_full", $query) &&!empty($query['follower_mobile_full']) ) {
			$qb->where("follower.mobile_full", 'like', "%{$query['follower_mobile_full']}%" );
		}
		  
		// 按来源查询 (=)  
		if ( array_key_exists("origin", $query) &&!empty($query['origin']) ) {
			$qb->where("follow.origin", '=', "{$query['origin']}" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("follow.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("follow.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$follows = $qb->select( $select )->pgArray($perpage, ['follow._id'], 'page', $page);

  		foreach ($follows['data'] as & $rs ) {
			$this->format($rs);
			
  		}

  	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$follows['_sql'] = $qb->getSql();
			$follows['query'] = $query;
		}

		return $follows;
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
				$select[$idx] = "follow." .$select[$idx];
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
						array_push($linkSelect, "follow.*");
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

			
			//  连接粉丝 (user as follower )
			if ( trim($fd) == "user.*" || trim($fd) == "follower.*"  || trim($fd) == "*" ) {
				$fields = [];
				if ( method_exists("\\Xpmsns\\User\\Model\\User", 'getFields') ) {
					$fields = \Xpmsns\User\Model\User::getFields();
				}

				if ( !empty($fields) ) { 
					foreach ($fields as $field ) {
						$field = "follower.{$field} as follower_{$field}";
						array_push($linkSelect, $field);
					}

					if ( trim($fd) === "*" ) {
						array_push($linkSelect, "follow.*");
					}
					unset($select[$idx]);	
				}
			}

			else if ( strpos( $fd, "user." ) === 0 ) {
				$as = str_replace('user.', 'follower_', $select[$idx]);
				$select[$idx] = $select[$idx] . " as {$as} ";
			}

			else if ( strpos( $fd, "follower.") === 0 ) {
				$as = str_replace('follower.', 'follower_', $select[$idx]);
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
			"follow_id",  // 关注ID
			"user_id",  // 用户ID
			"follower_id",  // 粉丝ID
			"origin",  // 来源
			"user_follower",  // 唯一ID
			"data",  // 数据
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>