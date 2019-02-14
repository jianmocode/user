<?php
/**
 * Class Follow 
 * 关注数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-28 11:56:45
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
           

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Follow extends Api {

	/**
	 * 关注数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

   // @KEEP BEGIN

   /**
    * 关注某人
    */
   function follow( $query, $data ) {

        if ( empty($data["user_id"]) ) {
            throw new Excp("未提供用户ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $fo = new \Xpmsns\User\Model\Follow;
        // 处理特殊数值
        if ( array_key_exists('data', $data) && is_string($data['data']) ) {
            $data['data'] = json_decode(trim($data['data']), true);
        }

        return $fo->follow( $user_id, $data["user_id"], $data );

   }


   /**
    * 取关某人
    */
   function unfollow( $query, $data ) {

        if ( empty($data["user_id"]) ) {
            throw new Excp("未提供用户ID", 402, ["query"=>$query, "data"=>$data]);
        }

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];
        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $fo = new \Xpmsns\User\Model\Follow;
        return $fo->unfollow( $user_id, $data["user_id"]);
        
   }


   /**
    * 查询粉丝列表
    */
   function getFollowers(  $query, $data  ) {

        // 支持POST和GET查询
        $data = array_merge( $query, $data );
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $fo = new \Xpmsns\User\Model\Follow;
        return $fo->getFollowers( $user_id, $data );
   }


   /**
    * 查询关注者列表
    */
   function getFollowings( $query, $data  ) {

        // 支持POST和GET查询
        $data = array_merge( $query, $data );
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }
        
        $fo = new \Xpmsns\User\Model\Follow;
        return $fo->getFollowings( $user_id, $data );
   }

   /**
    * 查询好友列表
    */
   function getFriends() {
        
        // 支持POST和GET查询
        $data = array_merge( $query, $data );
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }
        
        $fo = new \Xpmsns\User\Model\Follow;
        return $fo->getFriends( $user_id, $data );
   }

   // @KEEP END



}