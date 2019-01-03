<?php
/**
 * Class Balance 
 * 余额数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-04 01:06:42
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
         

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Balance extends Api {

	/**
	 * 余额数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


	/**
	 * 查询一条余额记录
	 * @param  array $query GET 参数
	 *               $query['select']  读取字段, 默认 ["balance.quantity","balance.type","balance.snapshot","balance.created_at","balance.updated_at","user.user_id","user.name","user.nickname","user.mobile"]
	 * 				 $query['balance_id']  按查询 (多条用 "," 分割)
     *
	 * @param  array $data  POST 参数
	 *               $data['select']  返回字段, 默认 ["balance.quantity","balance.type","balance.snapshot","balance.created_at","balance.updated_at","user.user_id","user.name","user.nickname","user.mobile"]
	 * 				 $data['balance_id']  按查询 (多条用 "," 分割)
	 *
	 * @return array 余额记录 Key Value 结构数据 
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
	protected function get( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["balance.quantity","balance.type","balance.snapshot","balance.created_at","balance.updated_at","user.user_id","user.name","user.nickname","user.mobile"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}

		// 按余额ID
		if ( !empty($data["balance_id"]) ) {
			
			$keys = explode(',', $data["balance_id"]);
			if ( count( $keys )  > 1 ) {
				$inst = new \Xpmsns\User\Model\Balance;
				return $inst->getInByBalanceId($keys, $select);
			}

			$inst = new \Xpmsns\User\Model\Balance;
			return $inst->getByBalanceId($data["balance_id"], $select);
		}

		throw new Excp("未知查询条件", 404, ['query'=>$query, 'data'=>$data]);
	}







	/**
	 * 根据条件检索余额记录
	 * @param  array $query GET 参数
	 *         	      $query['select'] 选取字段，默认选择 ["balance.quantity","balance.type","balance.snapshot","balance.created_at","balance.updated_at","user.user_id","user.name","user.nickname","user.mobile"]
	 *         	      $query['page'] 页码，默认为 1
	 *         	      $query['perpage'] 每页显示记录数，默认为 20
	 *			      $query["keywords"] 按关键词查询
	 *			      $query["balance_id"] 按余额ID查询 ( AND = )
	 *			      $query["type"] 按类型查询 ( AND = )
	 *			      $query["orderby_created_at_desc"]  按 DESC 排序
	 *			      $query["orderby_updated_at_desc"]  按 DESC 排序
     *
	 * @param  array $data  POST 参数
	 *         	      $data['select'] 选取字段，默认选择 ["name=quantity","name=type","name=snapshot","name=created_at","name=updated_at","model=%5CXpmsns%5CUser%5CModel%5CUser&name=user_id&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=name&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=nickname&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin","model=%5CXpmsns%5CUser%5CModel%5CUser&name=mobile&table=user&prefix=xpmsns_user_&alias=user&type=leftJoin"]
	 *         	      $data['page'] 页码，默认为 1
	 *         	      $data['perpage'] 每页显示记录数，默认为 20
	 *			      $data["keywords"] 按关键词查询
	 *			      $data["balance_id"] 按余额ID查询 ( AND = )
	 *			      $data["type"] 按类型查询 ( AND = )
	 *			      $data["orderby_created_at_desc"]  按 DESC 排序
	 *			      $data["orderby_updated_at_desc"]  按 DESC 排序
	 *
	 * @return array 余额记录集 {"total":100, "page":1, "perpage":20, data:[{"key":"val"}...], "from":1, "to":1, "prev":false, "next":1, "curr":10, "last":20}
	 *               data:[{"key":"val"}...] 字段
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
	protected function search( $query, $data ) {


		// 支持POST和GET查询
		$data = array_merge( $query, $data );

		// 读取字段
		$select = empty($data['select']) ? ["balance.quantity","balance.type","balance.snapshot","balance.created_at","balance.updated_at","user.user_id","user.name","user.nickname","user.mobile"] : $data['select'];
		if ( is_string($select) ) {
			$select = explode(',', $select);
		}
		$data['select'] = $select;

		$inst = new \Xpmsns\User\Model\Balance;
		return $inst->search( $data );
	}


}