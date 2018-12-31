<?php
/**
 * Class Invite 
 * 邀请数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 22:16:01
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
               

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Invite extends Api {

	/**
	 * 邀请数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN

    /**
     * 读取邀请信息
     * @method GET /_api/xpmsns/user/invite/get
     */
    function get($query, $data ) {

        $invite_id = $query["invite_id"];
        if ( empty($invite_id) ) {

            $user_id = $query["user_id"];
            $slug = $query["slug"];

            if (empty($user_id) || empty($slug)) {
                throw new Excp("无效查询条件", 402, ["query"=>$query]);
            }
        }

        // 查找邀请信息
        $inst = new \Xpmsns\User\Model\Invite;
        if ( !empty($invite_id) ) {
            $invite = $inst->getByInviteId($invite_id);
        } else {
            $invite = $inst->getByUserSlug("{$user_id}_{$slug}");
        }

        if ( empty($invite) ) {
            throw new Excp("未找到相关邀请信息", 404, ["query"=>$query]);
        }

        // 校验过期时间
        if (  !empty($invite["expired_at"]) &&  strtotime($invite["expired_at"]) - time() <= 0 ) {
            throw new Excp("邀请链接已过期", 403, ["query"=>$query, "expired_at"=>$expired_at]);
        }

        // 读取当前用户信息
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();

        // 防止作弊

        // 记录当前访问者身份
        $invite["visitor"] = $user;
        $invite["visitor"]["is_self"] = ($user["user_id"] === $invite["user_id"]);
        return $invite;

    }



    /**
     * 创建邀请
     * @method POST /_api/xpmsns/user/invite/create
     */
    function create($query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $time = time();
        $invite = new \Xpmsns\User\Model\Invite;
        $data["user_id"] = $user_id;


        // 处理特别数据
        if ( !empty($data["data"]) ) {
            $data["data"] = Utils::json_decode($data["data"]);
        }
        // 处理过期时间
        if ( !empty($data["expired_at"]) ) {
            $data["expired_at"] = date("Y-m-d H:i:s",strtotime($data["expired_at"]));
        }

        try {
            $resp =  $invite->create( $data );
        } catch( Excp $e ) {
            if ( $e->getCode() == 1062 ) {
                throw new Excp("你已经创建过相同的邀请链接", 1062, ["user_id"=>$user_id, "data"=>$data]);
            }
            throw $e;
        }

        return $resp;

    }

    // @KEEP END





}