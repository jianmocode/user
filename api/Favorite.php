<?php
/**
 * Class Favorite 
 * 收藏数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2019-01-12 17:53:56
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Favorite extends Api {

	/**
	 * 收藏数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */


    // @KEEP BEGIN

    /**
     * 添加资源或地址到收藏夹
     * @method POST /_api/xpmsns/user/favorite/create
     */
    function create($query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $time = time();
        $fav = new \Xpmsns\User\Model\Favorite;
        $data["user_id"] = $user_id;
        try {
            $resp =  $fav->create( $data );
        } catch( Excp $e ) {
            if ( $e->getCode() == 1062 ) {
                throw new Excp("你已经收藏过了", 1062, ["user_id"=>$user_id, "data"=>$data]);
            }
            throw $e;
        }

        try {  // 触发用户收藏行为
            \Xpmsns\User\Model\Behavior::trigger("xpmsns/user/favorite/create", $resp);
        }catch(Excp $e) { $e->log(); }

        return $resp;

    }


    /**
     * 移除收藏记录
     * @method POST /_api/xpmsns/user/favorite/create
     */
    function remove($query, $data ) {


        if (empty($data["favorite_id"])){
            throw new Excp("未指定收藏记录", 402, ["query"=>$query, "data"=>$data]);
        }

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $time = time();
        $fav = new \Xpmsns\User\Model\Favorite;
        $favorite_id = $data["favorite_id"];

        $favorite = $fav->getByFavoriteId($favorite_id);
        if ( $favorite["user_id"] != $user_id ) {
            throw new Excp("您没有该收藏的删除权限", 403, ["user_id"=>$user_id, "data"=>$data]);
        }

       $resp = $fav->remove($favorite_id, "favorite_id");

       if ( $resp == true ) {
           return ["code"=>0, "message"=>"移除收藏成功"];
       }

       throw new Excp("移除收藏失败", 500, ["query"=>$query, "data"=>$data]);

    }


    /**
     * 查询收藏记录
     * @method GET /_api/xpmsns/user/favorite/search
     * @see \Xpmsns\User\Model\Favorite
     */
    function search( $query, $data ) {
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $query["user_user_id"] = $user_id;
        $fav = new \Xpmsns\User\Model\Favorite;
        $rows = $fav->search( $query );
        $fav->getSource($rows["data"]);
        return $rows;

    }



    // @KEEP END




}