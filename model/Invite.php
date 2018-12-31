<?php
/**
 * Class Invite 
 * 邀请数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 21:08:39
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
               
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Invite extends Model {




	/**
	 * 邀请数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('invite'); // 数据表名称 xpmsns_user_invite

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 重载SaveBy
     */
    public function saveBy( $uniqueKey,  $data,  $keys=null , $select=["*"]) {
        if ( !empty($data["user_id"]) &&  !empty($data["slug"]) ) {
            $data["user_slug"] = "DB::RAW(CONCAT(`user_id`,'_',`slug`))";
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
				"user_slug"=>"DB::RAW(CONCAT('_','".time() . rand(10000,99999). "_', `user_slug`))"
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

		// 邀请ID
		$this->putColumn( 'invite_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 别名
		$this->putColumn( 'slug', $this->type("string", ["length"=>32, "index"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 唯一别名
		$this->putColumn( 'user_slug', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 资源ID
		$this->putColumn( 'outer_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 来源
		$this->putColumn( 'orgin', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 地址
		$this->putColumn( 'url', $this->type("string", ["length"=>600, "null"=>true]));
		// 是否跳转
		$this->putColumn( 'redirect', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 过期时间
		$this->putColumn( 'expired_at', $this->type("timestamp", ["index"=>true, "null"=>true]));
		// 数据
		$this->putColumn( 'data', $this->type("text", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 是否跳转
		// 返回值: "_redirect_types" 所有状态表述, "_redirect_name" 状态名称,  "_redirect" 当前状态表述, "redirect" 当前状态数值
		if ( array_key_exists('redirect', $rs ) && !empty($rs['redirect']) ) {
			$rs["_redirect_types"] = [
		  		"0" => [
		  			"value" => "0",
		  			"name" => "不跳转",
		  			"style" => "muted"
		  		],
		  		"1" => [
		  			"value" => "1",
		  			"name" => "自动跳转",
		  			"style" => "success"
		  		],
			];
			$rs["_redirect_name"] = "redirect";
			$rs["_redirect"] = $rs["_redirect_types"][$rs["redirect"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按邀请ID查询一条邀请记录
	 * @param string $invite_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["invite_id"],  // 邀请ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["user_slug"],  // 唯一别名 
	 *          	  $rs["outer_id"],  // 资源ID 
	 *          	  $rs["orgin"],  // 来源 
	 *          	  $rs["url"],  // 地址 
	 *          	  $rs["redirect"],  // 是否跳转 
	 *          	  $rs["expired_at"],  // 过期时间 
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
	 */
	public function getByInviteId( $invite_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户
		$qb->where('invite_id', '=', $invite_id );
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
	 * 按邀请ID查询一组邀请记录
	 * @param array   $invite_ids 唯一主键数组 ["$invite_id1","$invite_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 邀请记录MAP {"invite_id1":{"key":"value",...}...}
	 */
	public function getInByInviteId($invite_ids, $select=["invite.invite_id","invite.slug","user.user_id","user.name","user.nickname","user.mobile","invite.orgin","invite.outer_id","invite.expired_at","invite.redirect"], $order=["invite.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户
		$qb->whereIn('invite.invite_id', $invite_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['invite_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按邀请ID保存邀请记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByInviteId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("invite_id", $data, ["invite_id", "user_slug"], ['_id', 'invite_id']);
		return $this->getByInviteId( $rs['invite_id'], $select );
	}
	
	/**
	 * 按唯一别名查询一条邀请记录
	 * @param string $user_slug 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["invite_id"],  // 邀请ID 
	 *          	  $rs["slug"],  // 别名 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["user_slug"],  // 唯一别名 
	 *          	  $rs["outer_id"],  // 资源ID 
	 *          	  $rs["orgin"],  // 来源 
	 *          	  $rs["url"],  // 地址 
	 *          	  $rs["redirect"],  // 是否跳转 
	 *          	  $rs["expired_at"],  // 过期时间 
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
	 */
	public function getByUserSlug( $user_slug, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户
		$qb->where('user_slug', '=', $user_slug );
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
	 * 按唯一别名查询一组邀请记录
	 * @param array   $user_slugs 唯一主键数组 ["$user_slug1","$user_slug2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 邀请记录MAP {"user_slug1":{"key":"value",...}...}
	 */
	public function getInByUserSlug($user_slugs, $select=["invite.invite_id","invite.slug","user.user_id","user.name","user.nickname","user.mobile","invite.orgin","invite.outer_id","invite.expired_at","invite.redirect"], $order=["invite.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户
		$qb->whereIn('invite.user_slug', $user_slugs);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['user_slug']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按唯一别名保存邀请记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByUserSlug( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("user_slug", $data, ["invite_id", "user_slug"], ['_id', 'invite_id']);
		return $this->getByInviteId( $rs['invite_id'], $select );
	}


	/**
	 * 添加邀请记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["invite_id"]) ) { 
			$data["invite_id"] = $this->genId();
        }
        
        // @KEEP BEGIN
        if ( !empty($data["user_id"]) &&  !empty($data["slug"]) ) {
            $data["user_slug"] = "DB::RAW(CONCAT(`user_id`,'_',`slug`))";
        }
        // @KEEP END

		return parent::create( $data );
	}


	/**
	 * 查询前排邀请记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 邀请记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["invite.invite_id","invite.slug","user.user_id","user.name","user.nickname","user.mobile","invite.orgin","invite.outer_id","invite.expired_at","invite.redirect"], $order=["invite.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户


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
	 * 按条件检索邀请记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["invite.invite_id","invite.slug","user.user_id","user.name","user.nickname","user.mobile","invite.orgin","invite.outer_id","invite.expired_at","invite.redirect"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["invite_id"] 按邀请ID查询 ( = )
	 *			      $query["slug"] 按别名查询 ( = )
	 *			      $query["user_user_id"] 按查询 ( = )
	 *			      $query["orgin"] 按来源查询 ( = )
	 *			      $query["outer_id"] 按资源ID查询 ( = )
	 *			      $query["redirect"] 按是否跳转查询 ( = )
	 *			      $query["expired_at"] 按过期时间查询 ( < )
	 *			      $query["orderby_created_at_desc"]  按创建时间倒序 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按更新时间倒序 DESC 排序
	 *			      $query["orderby_expired_at_asc"]  按过期时间 ASC 排序
	 *			      $query["orderby_expired_at_desc"]  按过期时间倒序 DESC 排序
	 *           
	 * @return array 邀请记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["invite_id"],  // 邀请ID 
	 *               	["slug"],  // 别名 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["user_slug"],  // 唯一别名 
	 *               	["outer_id"],  // 资源ID 
	 *               	["orgin"],  // 来源 
	 *               	["url"],  // 地址 
	 *               	["redirect"],  // 是否跳转 
	 *               	["expired_at"],  // 过期时间 
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
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["invite.invite_id","invite.slug","user.user_id","user.name","user.nickname","user.mobile","invite.orgin","invite.outer_id","invite.expired_at","invite.redirect"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "invite.invite_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_invite as invite", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "invite.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("invite.invite_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("invite.slug","like", "%{$query['keywords']}%");
				$qb->orWhere("invite.outer_id","like", "%{$query['keywords']}%");
				$qb->orWhere("invite.orgin","like", "%{$query['keywords']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keywords']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keywords']}%");
				$qb->orWhere("user.name","like", "%{$query['keywords']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keywords']}%");
			});
		}


		// 按邀请ID查询 (=)  
		if ( array_key_exists("invite_id", $query) &&!empty($query['invite_id']) ) {
			$qb->where("invite.invite_id", '=', "{$query['invite_id']}" );
		}
		  
		// 按别名查询 (=)  
		if ( array_key_exists("slug", $query) &&!empty($query['slug']) ) {
			$qb->where("invite.slug", '=', "{$query['slug']}" );
		}
		  
		// 按查询 (=)  
		if ( array_key_exists("user_user_id", $query) &&!empty($query['user_user_id']) ) {
			$qb->where("user.user_id", '=', "{$query['user_user_id']}" );
		}
		  
		// 按来源查询 (=)  
		if ( array_key_exists("orgin", $query) &&!empty($query['orgin']) ) {
			$qb->where("invite.orgin", '=', "{$query['orgin']}" );
		}
		  
		// 按资源ID查询 (=)  
		if ( array_key_exists("outer_id", $query) &&!empty($query['outer_id']) ) {
			$qb->where("invite.outer_id", '=', "{$query['outer_id']}" );
		}
		  
		// 按是否跳转查询 (=)  
		if ( array_key_exists("redirect", $query) &&!empty($query['redirect']) ) {
			$qb->where("invite.redirect", '=', "{$query['redirect']}" );
		}
		  
		// 按过期时间查询 (<)  
		if ( array_key_exists("expired_at", $query) &&!empty($query['expired_at']) ) {
			$qb->where("invite.expired_at", '<', "{$query['expired_at']}" );
		}
		  

		// 按创建时间倒序 DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("invite.created_at", "desc");
		}

		// 按更新时间倒序 DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("invite.updated_at", "desc");
		}

		// 按过期时间 ASC 排序
		if ( array_key_exists("orderby_expired_at_asc", $query) &&!empty($query['orderby_expired_at_asc']) ) {
			$qb->orderBy("invite.expired_at", "asc");
		}

		// 按过期时间倒序 DESC 排序
		if ( array_key_exists("orderby_expired_at_desc", $query) &&!empty($query['orderby_expired_at_desc']) ) {
			$qb->orderBy("invite.expired_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$invites = $qb->select( $select )->pgArray($perpage, ['invite._id'], 'page', $page);

 		foreach ($invites['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$invites['_sql'] = $qb->getSql();
			$invites['query'] = $query;
		}

		return $invites;
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
				$select[$idx] = "invite." .$select[$idx];
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
						array_push($linkSelect, "invite.*");
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
			"invite_id",  // 邀请ID
			"slug",  // 别名
			"user_id",  // 用户ID
			"user_slug",  // 唯一别名
			"outer_id",  // 资源ID
			"orgin",  // 来源
			"url",  // 地址
			"redirect",  // 是否跳转
			"expired_at",  // 过期时间
			"data",  // 数据
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>