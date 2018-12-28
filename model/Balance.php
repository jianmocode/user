<?php
/**
 * Class Balance 
 * 余额数据模型
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-28 13:02:30
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/model/Name.php
 */
namespace Xpmsns\User\Model;
         
use \Xpmse\Excp;
use \Xpmse\Model;
use \Xpmse\Utils;
use \Xpmse\Conf;
use \Xpmse\Loader\App as App;


class Balance extends Model {




	/**
	 * 余额数据模型
	 * @param array $param 配置参数
	 *              $param['prefix']  数据表前缀，默认为 xpmsns_user_
	 */
	function __construct( $param=[] ) {

		parent::__construct(array_merge(['prefix'=>'xpmsns_user_'],$param));
		$this->table('balance'); // 数据表名称 xpmsns_user_balance

	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 计算用户账户余额
     * @param string $user_id 用户ID
     */
    function sum( $user_id ) {

        $sum = $this->query()
                    ->where("user_id","=", $user_id)
                    ->sum("quantity")
                ;

        return intval($sum);
    }
    // @KEEP END


	/**
	 * 创建数据表
	 * @return $this
	 */
	public function __schema() {

		// 余额ID
		$this->putColumn( 'balance_id', $this->type("string", ["length"=>128, "unique"=>true, "null"=>true]));
		// 用户ID
		$this->putColumn( 'user_id', $this->type("string", ["length"=>128, "index"=>true, "null"=>true]));
		// 数量
		$this->putColumn( 'quantity', $this->type("integer", ["length"=>1, "index"=>true, "null"=>true]));
		// 类型
		$this->putColumn( 'type', $this->type("string", ["length"=>32, "index"=>true, "default"=>"increase", "null"=>true]));
		// 数据快照
		$this->putColumn( 'snapshot', $this->type("longText", ["json"=>true, "null"=>true]));

		return $this;
	}


	/**
	 * 处理读取记录数据，用于输出呈现
	 * @param  array $rs 待处理记录
	 * @return
	 */
	public function format( & $rs ) {


		// 格式化: 类型
		// 返回值: "_type_types" 所有状态表述, "_type_name" 状态名称,  "_type" 当前状态表述, "type" 当前状态数值
		if ( array_key_exists('type', $rs ) && !empty($rs['type']) ) {
			$rs["_type_types"] = [
		  		"increase" => [
		  			"value" => "increase",
		  			"name" => "增加",
		  			"style" => "success"
		  		],
		  		"decrease" => [
		  			"value" => "decrease",
		  			"name" => "减少",
		  			"style" => "danger"
		  		],
			];
			$rs["_type_name"] = "type";
			$rs["_type"] = $rs["_type_types"][$rs["type"]];
		}

 
		// <在这里添加更多数据格式化逻辑>
		
		return $rs;
	}

	
	/**
	 * 按余额ID查询一条余额记录
	 * @param string $balance_id 唯一主键
	 * @return array $rs 结果集 
	 *          	  $rs["balance_id"],  // 余额ID 
	 *          	  $rs["user_id"],  // 用户ID 
	 *                $rs["user_user_id"], // user.user_id
	 *          	  $rs["quantity"],  // 数量 
	 *          	  $rs["type"],  // 类型 
	 *          	  $rs["snapshot"],  // 数据快照 
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
	 */
	public function getByBalanceId( $balance_id, $select=['*']) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}


		// 增加表单查询索引字段
		array_push($select, "balance.balance_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_balance as balance", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "balance.user_id"); // 连接用户
		$qb->where('balance_id', '=', $balance_id );
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
	 * 按余额ID查询一组余额记录
	 * @param array   $balance_ids 唯一主键数组 ["$balance_id1","$balance_id2" ...]
	 * @param array   $order        排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @param array   $select       选取字段，默认选取所有
	 * @return array 余额记录MAP {"balance_id1":{"key":"value",...}...}
	 */
	public function getInByBalanceId($balance_ids, $select=["balance.balance_id","user.name","user.nickname","user.mobile","balance.quantity","balance.type"], $order=["balance.created_at"=>"desc"] ) {
		
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "balance.balance_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_balance as balance", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "balance.user_id"); // 连接用户
		$qb->whereIn('balance.balance_id', $balance_ids);
		
		// 排序
		foreach ($order as $field => $order ) {
			$qb->orderBy( $field, $order );
		}
		$qb->select( $select );
		$data = $qb->get()->toArray(); 

		$map = [];

 		foreach ($data as & $rs ) {
			$this->format($rs);
			$map[$rs['balance_id']] = $rs;
			
 		}

 

		return $map;
	}


	/**
	 * 按余额ID保存余额记录。(记录不存在则创建，存在则更新)
	 * @param array $data 记录数组 (key:value 结构)
	 * @param array $select 返回的字段，默认返回全部
	 * @return array 数据记录数组
	 */
	public function saveByBalanceId( $data, $select=["*"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "balance.balance_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段
		$rs = $this->saveBy("balance_id", $data, ["balance_id"], ['_id', 'balance_id']);
		return $this->getByBalanceId( $rs['balance_id'], $select );
	}


	/**
	 * 添加余额记录
	 * @param  array $data 记录数组  (key:value 结构)
	 * @return array 数据记录数组 (key:value 结构)
	 */
	function create( $data ) {
		if ( empty($data["balance_id"]) ) { 
			$data["balance_id"] = $this->genId();
		}
		return parent::create( $data );
	}


	/**
	 * 查询前排余额记录
	 * @param integer $limit 返回记录数，默认100
	 * @param array   $select  选取字段，默认选取所有
	 * @param array   $order   排序方式 ["field"=>"asc", "field2"=>"desc"...]
	 * @return array 余额记录数组 [{"key":"value",...}...]
	 */
	public function top( $limit=100, $select=["balance.balance_id","user.name","user.nickname","user.mobile","balance.quantity","balance.type"], $order=["balance.created_at"=>"desc"] ) {

		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "balance.balance_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_balance as balance", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "balance.user_id"); // 连接用户


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
	 * 按条件检索余额记录
	 * @param  array  $query
	 *         	      $query['select'] 选取字段，默认选择 ["balance.balance_id","user.name","user.nickname","user.mobile","balance.quantity","balance.type"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["balance_id"] 按余额ID查询 ( = )
	 *			      $query["type"] 按类型查询 ( = )
	 *			      $query["orderby_created_at_desc"]  按name=created_at DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按name=updated_at DESC 排序
	 *           
	 * @return array 余额记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               	["balance_id"],  // 余额ID 
	 *               	["user_id"],  // 用户ID 
	 *               	["user_user_id"], // user.user_id
	 *               	["quantity"],  // 数量 
	 *               	["type"],  // 类型 
	 *               	["snapshot"],  // 数据快照 
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
	 */
	public function search( $query = [] ) {

		$select = empty($query['select']) ? ["balance.balance_id","user.name","user.nickname","user.mobile","balance.quantity","balance.type"] : $query['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 增加表单查询索引字段
		array_push($select, "balance.balance_id");
		$inwhereSelect = $this->formatSelect( $select ); // 过滤 inWhere 查询字段

		// 创建查询构造器
		$qb = Utils::getTab("xpmsns_user_balance as balance", "{none}")->query();
 		$qb->leftJoin("xpmsns_user_user as user", "user.user_id", "=", "balance.user_id"); // 连接用户

		// 按关键词查找
		if ( array_key_exists("keywords", $query) && !empty($query["keywords"]) ) {
			$qb->where(function ( $qb ) use($query) {
				$qb->where("balance.balance_id", "like", "%{$query['keywords']}%");
				$qb->orWhere("balance.quantity","like", "%{$query['keywords']}%");
				$qb->orWhere("user.user_id","like", "%{$query['keywords']}%");
				$qb->orWhere("user.mobile_full","like", "%{$query['keywords']}%");
				$qb->orWhere("user.name","like", "%{$query['keywords']}%");
				$qb->orWhere("user.nickname","like", "%{$query['keywords']}%");
			});
		}


		// 按余额ID查询 (=)  
		if ( array_key_exists("balance_id", $query) &&!empty($query['balance_id']) ) {
			$qb->where("balance.balance_id", '=', "{$query['balance_id']}" );
		}
		  
		// 按类型查询 (=)  
		if ( array_key_exists("type", $query) &&!empty($query['type']) ) {
			$qb->where("balance.type", '=', "{$query['type']}" );
		}
		  

		// 按name=created_at DESC 排序
		if ( array_key_exists("orderby_created_at_desc", $query) &&!empty($query['orderby_created_at_desc']) ) {
			$qb->orderBy("balance.created_at", "desc");
		}

		// 按name=updated_at DESC 排序
		if ( array_key_exists("orderby_updated_at_desc", $query) &&!empty($query['orderby_updated_at_desc']) ) {
			$qb->orderBy("balance.updated_at", "desc");
		}


		// 页码
		$page = array_key_exists('page', $query) ?  intval( $query['page']) : 1;
		$perpage = array_key_exists('perpage', $query) ?  intval( $query['perpage']) : 20;

		// 读取数据并分页
		$balances = $qb->select( $select )->pgArray($perpage, ['balance._id'], 'page', $page);

 		foreach ($balances['data'] as & $rs ) {
			$this->format($rs);
			
 		}

 	
		// for Debug
		if ($_GET['debug'] == 1) { 
			$balances['_sql'] = $qb->getSql();
			$balances['query'] = $query;
		}

		return $balances;
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
				$select[$idx] = "balance." .$select[$idx];
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
						array_push($linkSelect, "balance.*");
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
			"balance_id",  // 余额ID
			"user_id",  // 用户ID
			"quantity",  // 数量
			"type",  // 类型
			"snapshot",  // 数据快照
			"created_at",  // 创建时间
			"updated_at",  // 更新时间
		];
	}

}

?>