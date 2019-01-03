<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;

echo "\n\Xpmsns\User\Api\User 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {

    /**
     * 测试注册新用户接口
     */
	function testCreate() {

        $opt =  new \Xpmse\Option("xpmsns/user");
		$options = $opt->getAll();
        $map = $options['map'];		
        
        $u = new \Xpmsns\user\Api\User;
        $vcode = $u->__test_getvcode();
      
        // 校验短信验证码
		if( $map['user/sms/on'] == 1) {

            fwrite(STDOUT,"请输入手机号码:\n");
            $mobile = trim(fgets(STDIN));


            // 验证用户是否存在
            $user = new \Xpmsns\User\Model\User;
            $uinfo  = $user->getByMobile($mobile);
            if ( !empty($uinfo) ) {
                fwrite(STDOUT,"手机号码已注册, 是否删除该用户? Y/N:\n");
                $confirm = trim(fgets(STDIN));
                if ( strtolower($confirm) != "y") {
                    exit;
                }

                $user->remove($uinfo["_id"]);
            }
            
            // 发送短信验证码
            $u->call("getSMSCode", ["nation"=>"86", "mobile"=>$mobile, "_vcode"=>$vcode]);

            fwrite(STDOUT,"请输入短信验证码:\n");
            $smscode = trim(fgets(STDIN));
        }

        $data = [
            "name" => "小明",
            "nation" => "86",
            "password" => "Test1221",
            "repassword" => "Test1221",
            "group_slug" => "default",
            "mobile" => $mobile,
            "smscode" => $smscode,
            "_vcode" => $vcode
        ];
        
        $resp = $u->call("create", [], $data );
        $this->assertTrue($resp["code"] == 0);
	}
	
}