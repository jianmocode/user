<?php
/**
 * Class Checkin 
 * 签到数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-31 17:47:37
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
             

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Checkin extends Api {

	/**
	 * 签到数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN


    /**
     * 签到
     * @method POST /_api/xpmsns/user/checkin/create
     */
    function create($query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $time = time();
        $ci = new \Xpmsns\User\Model\Checkin;

        // 签到请求清洗
        if ( $data["limit"] === "hourly") {  // 按小时
            $rows = $ci->search(["user_user_id"=>$user_id, "time_after"=>date('Y-m-d H:i:s', $time-3600)]);
            if ( $rows["total"] > 0 ) {
                throw new Excp("已经签过了, 一小时后再试.", 403, ["rows"=>$rows, "user_id"=>$user_id]);
            }

        } elseif ( $data["limit"] === "daily") { // 按天
            $rows = $ci->search(["user_user_id"=>$user_id, "time_after"=>date("Y-m-d 00:00:00")]);
            if ( $rows["total"] > 0 ) {
                throw new Excp("今天已经签过了, 明天再试.", 403, ["rows"=>$rows, "user_id"=>$user_id]);
            }

        } elseif ( $data["limit"] === "weekly") { // 按周
            $rows = $ci->search(["user_user_id"=>$user_id, "time_after"=>date('Y-m-d 00:00:00', strtotime('monday this week'))]);
            if ( $rows["total"] > 0 ) {
                throw new Excp("本周已经签过了, 下周再试.", 403, ["rows"=>$rows, "user_id"=>$user_id]);
            }

        } elseif ( $data["limit"] === "monthly") { // 按月
            $rows = $ci->search(["user_user_id"=>$user_id, "time_after"=>date('Y-m-01 00:00:00')]);
            if ( $rows["total"] > 0 ) {
                throw new Excp("本月已经签过了, 下月再试.", 403, ["rows"=>$rows, "user_id"=>$user_id]);
            }
        }

        // 处理特别数据
        if ( !empty($data["data"]) ) {
            $data["data"] = Utils::json_decode($data["data"]);
        }

        $data["user_id"] = $user_id;
        $data["time"] = date('Y-m-d H:i:s', $time);

        $rs = $ci->create( $data );
        $history  = $ci->search([
            "user_user_id" => $user_id,
            "orderby_time_desc" => "1",
            "perpage" => 8
        ]);
        $rs["history"] = $history["data"];
        
        // 触发行为
        $ci->triggerBehavior("xpmsns/user/checkin/create",  $rs);
        return $rs;
    }

    /**
     * 查询签到记录
     * @method GET /_api/xpmsns/user/checkin/search
     * @see \Xpmsns\User\Model\Checkin
     */
    function search( $query, $data ) {
        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $query["user_user_id"] = $user_id;
        $ci = new \Xpmsns\User\Model\Checkin;
        return $rows = $ci->search( $query );

    }

    // @KEEP END










}