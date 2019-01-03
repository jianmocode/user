<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;

echo "\n\Xpmsns\User\Model\User 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {

    /**
     * 测试订阅器: 邀请注册任务 (用户注册行为发生时, 触发此函数, 可在后台暂停或关闭)
     */
	function testOnInviteChange() {
        $user = new \Xpmsns\user\Model\User;
        $loop = true;
        while( $loop ){
            fwrite(STDOUT,"请输入邀请人手机号码:\n");
            $mobile = trim(fgets(STDIN));

            // 验证用户是否存在
            $uinfo  = $user->getByMobile($mobile);
            if ( !empty($uinfo) ) {
                $loop = false;
            } else {
                fwrite(STDOUT,"[ERROR]用户不存在\n");
            }
        }

        $user->onInviteChange(
            [],
            ["outer_id"=>"invite"],
            ["inviter"=>$uinfo],
            []
        );
	}
	
}