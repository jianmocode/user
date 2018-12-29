<?php
/**
 * Class Usertask 
 * 任务副本数据接口 
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-28 18:15:17
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/api/Name.php
 */
namespace Xpmsns\User\Api;
          

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;
use \Xpmse\Api;

class Usertask extends Api {

	/**
	 * 任务副本数据接口
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * 自定义函数 
	 */

    // @KEEP BEGIN
    
    /**
     * 接受任务
     * @method POST 
     * @param string task_id  [选填]任务ID ( task_id/slug 必填一项 )
     * @param string slug [选填]任务别名( task_id/slug 必填一项 )
     * @return array 任务副本结构体
     */
    function accept( $query, $data ) {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->getUserInfo();
        $user_id = $user["user_id"];

        if ( empty($user_id) ) {
            throw new Excp("用户尚未登录", 402, ["query"=>$query, "data"=>$data]);
        }

        $task_id = $data["task_id"];
        $slug = $data["slug"];

        if ( empty($slug) && empty( $task_id ) ) {
              throw new Excp("请提供任务ID或者任务别名", 402, ["query"=>$query, "data"=>$data]);
        }

        $utask = new \Xpmsns\User\Model\UserTask;
        if ( !empty($task_id) ) {
            return $utask->acceptByTaskId( $task_id, $user_id);
        } 
        
        return $utask->acceptBySlug($slug, $user_id);
    }


    /**
     * 取消任务
     */
    function cancel( $query, $data ) {

    }

    /**
     * 更新任务
     */
    function update( $query, $data ) {

    }
    // @KEEP END


}