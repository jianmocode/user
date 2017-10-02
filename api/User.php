<?php

namespace Mina\User\Api;

use \Tuanduimao\Loader\App;
use \Tuanduimao\Excp;
use \Tuanduimao\Utils;
use \Tuanduimao\Api;
use \Tuanduimao\Option;
use \Mina\User\Model\User as UserModel;
use \Mina\User\Model\Group as GroupModel;


/**
 * 用户API接口
 */
class User extends Api {

	/**
	 * 初始化
	 * @param array $param [description]
	 */
	function __construct() {

		parent::__construct();
		$this->allowMethod('get', ["PHP",'GET'])
		     ->allowQuery('get',  ['tagId', 'select'])
		     ->allowMethod('search', ["PHP",'GET'])
		     ->allowQuery('search',  [
		     	"select",
		     	'name','orName','inName',
		     	'fullname','orFullname','inFullname',
		     	'categoryId','orcategoryId','incategoryId',
		     	'parentId','orParentId','inParentId',
		     	'children',
		     	'hidden', 'orHidden',
		     	'status', 'orStatus',
		     	'praram','orParam',
		     	'order',
		     	'page','perpage'
		     ]);
	}



	/**
	 * 读取用户配置信息
	 * @return
	 */
	protected function option( $query=[] ) {

		$opt =  new Option("mina/user");
		$options = $opt->getAll();

		$map = $options['map'];
		unset($map["user/sms/vcode"]);

		$newmap = [];
		foreach ($map as $key => $val) {
			$newkey = str_replace("/", "_", $key);
			$newmap[$newkey] = $val;
		}

		return $newmap;
	}



	/**
	 * 用户登录 
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function login( $query=[], $data=[] ) {
		$this->authVcode();
		$u = new UserModel();
		$mobile = $data['mobile'];

		if ( empty($mobile) ) {
			throw new Excp("未知手机号码", 404, ['data'=>$data, 'query'=>$query]);
		}

		$u->login($mobile, $data['password']);
		return ['code'=>0, "message"=>"登录成功"];

	}


	/**
	 * 退出登录 (注销)
	 * @return [type] [description]
	 */
	protected function logout() {
		$u = new UserModel();
		$u->logout();
		return ['code'=>0, "message"=>"注销成功"];
	}


	/**
	 * 读取用户会话信息
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function getUserInfo( $query=[], $data=[] ) {
		$u = new UserModel();
		return $u->getUserInfo();
	}




	/**
	 * 创建一个新用户
	 * @param  array  $query [description]
	 * @param  [type] $data  [description]
	 * @return [type]        [description]
	 */
	protected function create( $query=[], $data=[] ) {

		$this->authVcode();

		$opt =  new Option("mina/user");
		$options = $opt->getAll();
		$map = $options['map'];		

		// 校验手机号码
		if ( empty($data['mobile']) ) {
			throw new Excp("手机号码格式不正确", 402, ['data'=>$data]);
		}

		// 校验短信验证码
		if( $map['user/sms/on'] == 1) {
			$data['mobile_verified'] = true;
			$data['mobile_nation'] = !empty($data['nation']) ? $data['nation'] : '86';
			if ( $this->verifySMSCode($query, $data) === false) {
				throw new Excp("短信验证码不正确", 402, ['data'=>$data]);
			}
		}


		// 检查密码
		if ( isset($data['repassword']) ) { 
			if ( $data['password'] != $data['repassword'] ) {
				throw new Excp("两次输入的密码不一致", 402, ['data'=>$data]);
			}
		}


		// Group
		$slug = $map['user/default/group'];
		$g = new GroupModel();
		$rs = $g->getBySlug($slug);
		$data['group_id'] = $rs['group_id'];


		// 数据入库
		$u = new UserModel();
		try {
			$u->create($data);
		} catch(Excp $e ){
			if ( $e->getCode() == '1062') {
				throw new Excp("手机号 {$data['mobile']} 已被注册", 402, ['data'=>$data]);	
			}
			throw $e;
		}

		return ["message"=>"注册成功", "code"=>0 ];
	}



	/**
	 * 校验手机短信验证码
	 * @param  array  $query [description]
	 * @param  array  $data  [description]
	 * @return [type]        [description]
	 */
	protected function verifySMSCode( $query=[], $data=[] ) {
		$u = new UserModel();
		$mobile = $data['mobile'];
		$nation  = !empty($data['nation']) ? $data['nation'] : '86';
		$smscode = $data['smscode'];
		return $u->verifySMSCode($mobile, $smscode, $nation);
	}


	/**
	 * 读取手机短信验证码
	 * @param  array $query [description]
	 * @return [type]        [description]
	 */
	protected function getSMSCode( $query = [] ) {

		$this->authVcodeOnly();

		// 是否开启验证码
		$opt =  new Option("mina/user");
		$options = $opt->getAll();
		$map = $options['map'];

		if( $map['user/sms/on'] != 1) {
			throw new Excp("非法请求 未开启短信验证码", 401, ['query'=>$query]);
		}

		// 提交信息校验
		if ( empty($query['mobile']) ) {
			throw new Excp("非法请求 未知手机号码", 401, ['query'=>$query]);
		}

		$now = time();
		$lock_time = 60;
		$locked_at = intval($_SESSION['SMSCODE:locked_at']);

		if ( ( $now - $locked_at ) < $lock_time ) {
			throw new Excp("非法请求 请求过于频繁", 403, ['locked_at'=>$locked_at]);
		}


		// 发送短信验证码
		$u = new UserModel();
		$nation = !empty($query['nation']) ? $query['nation'] : '86';
		$u->SMSCode( $query['mobile'], $nation );

		// 锁定
		$_SESSION['SMSCODE:locked_at'] = $now;
		return ["message"=>"发送成功", "code"=>0 ];

	}






	/**
	 * 查询标签列表
	 *
	 * 读取字段 select 默认 name
	 *
	 *    示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *    许可值: "*","tag_id","name","param"
	 * 
	 * 
	 * 查询条件
	 * 	  1. 按名称查询  name | orName | inName
	 * 	  3. 按标签ID查询  tagId | orTagId | inTagId 
	 * 	  8. 按参数标记查询  param | orParam
	 * 	  
	 * 排序方式 order 默认 tag_id  updated_at asc, tag_id desc
	 * 
	 *    1. 按标签更新顺序  updated_at
	 *    2. 按标签创建顺序  tag_id  
	 *    
	 *
	 * 当前页码 page    默认 1 
	 * 每页数量 perpage 默认 50 
	 * 	
	 * 
	 * @param  array  $query 
	 * @return array 文章结果集列表
	 */
	protected function search( $query=[] ) {

		$this->authVcode();
		return $query;

	}



	/**
	 * 读取标签详情信息
	 * @param  array  $query Query 查询
	 *                   int ["name"]  标签详情
	 *                   
	 *          string|array ["select"] 读取字段  
	 *          			 示例:  ["*"] /["tag_id", "name" ....] / "*" / "tag_id,name"
	 *          		     许可值: "*","tag_id","name","param"
	 *                    
	 * @return Array 文章数据
	 * 
	 */
	protected function get( $query=[] ) {

		// 验证数值
		if ( empty($query['name']) ) {
			throw new Excp(" name 参数错误", 400, ['query'=>$query]);
		}

		$name = $query['name'];
		$select = empty($query['select']) ? '*' : $query['select'];
		$select = is_array($select) ? $select : explode(',', $select);

		// 验证 Select 参数
		$allowFields = ["*","tag_id","name","param"];

		foreach ($select as $idx => $field) {
			if ( !in_array($field, $allowFields)){
				throw new Excp(" select 参数错误 ($field 非法字段)", 400, ['query'=>$query]);
			}
		}
		
		$cate = new \Mina\Pages\Model\Tag;
		$rs = $cate->getLine("WHERE name=:name LIMIT 1", $select, ["name"=>$name]);
		if ( empty($rs) ) {
			throw new Excp("标签不存在", 404,  ['query'=>$query]);
		}

		return $rs;
	}

}