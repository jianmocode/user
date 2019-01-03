<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;


echo "\n\Xpmsns\User\Api\User 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {

    // 第一个用户
    protected function &getUser1() {
        static $value = null;
        return $value;
    }

    // 第二个用户
    protected function &getUser2() {
        static $value = null;
        return $value;
    }


    /**
     * 测试注册新用户接口
     */
	function testCreate() {

        fwrite(STDOUT,"测试新用户注册...\n");

        $opt =  new \Xpmse\Option("xpmsns/user");
		$options = $opt->getAll();
        $map = $options['map'];		
        
        $u = new \Xpmsns\user\Api\User;
        $user = new \Xpmsns\User\Model\User;
        $vcode = $u->__test_getvcode();
      
        // 校验短信验证码
		if( $map['user/sms/on'] == 1) {

            fwrite(STDOUT,"请输入手机号码:\n");
            $mobile = trim(fgets(STDIN));
            
            // 验证用户是否存在
            $uinfo  = $user->getByMobile($mobile);
            if ( !empty($uinfo) ) {
                fwrite(STDOUT,"手机号码已注册, 是否删除该用户? Y/N(Y):\n");
                $confirm = trim(fgets(STDIN));
                if ( strtolower($confirm) != "y" && !empty($confirm)) {
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
            "sex" => 1,
            "password" => "Test1221",
            "repassword" => "Test1221",
            "group_slug" => "default",
            "mobile" => $mobile,
            "smscode" => $smscode,
            "_vcode" => $vcode
        ];
    
        $resp = $u->call("create", [], $data );
        $this->assertTrue($resp["code"] == 0);
        $user1 = &$this->getUser1();
        $user1 = $user->getByMobile($mobile);
    }

    /**
     * 测试用户邀请注册
     */
    function testInviteCreate() {
        
        fwrite(STDOUT,"测试用户邀请注册...\n");

        $user1 = &$this->getUser1();
        $opt =  new \Xpmse\Option("xpmsns/user");
		$options = $opt->getAll();
        $map = $options['map'];		
        
        $u = new \Xpmsns\user\Api\User;
        $user = new \Xpmsns\User\Model\User;
        $vcode = $u->__test_getvcode();
      
        // 校验短信验证码
		if( $map['user/sms/on'] == 1) {

            while( $mobile == "" || $mobile == $user1["mobile"] ){
                if( $mobile == $user1["mobile"] ){
                    fwrite(STDOUT,"[ERROR]发起邀请用户和被邀请用户手机号码不能相同\n");
                }
                fwrite(STDOUT,"请输入手机号码:\n");
                $mobile = trim(fgets(STDIN));
            }
           
            // 验证用户是否存在
            $uinfo  = $user->getByMobile($mobile);
            if ( !empty($uinfo) ) {
                fwrite(STDOUT,"手机号码已注册, 是否删除该用户? Y/N(Y):\n");
                $confirm = trim(fgets(STDIN));
                if ( strtolower($confirm) != "y" && !empty($confirm)) {
                    exit;
                }

                $user->remove($uinfo["_id"]);
            }
            
            // 发送短信验证码
            $u->call("getSMSCode", ["nation"=>"86", "mobile"=>$mobile, "_vcode"=>$vcode]);

            fwrite(STDOUT,"请输入短信验证码:\n");
            $smscode = trim(fgets(STDIN));
        }

        $i = new \Xpmsns\user\Model\Invite;
        $i->setInviter($user1["user_id"]);
       
        $data = [
            "name" => "小红",
            "nation" => "86",
            "sex" => 0,
            "password" => "Test1221",
            "repassword" => "Test1221",
            "group_slug" => "default",
            "mobile" => $mobile,
            "smscode" => $smscode,
            "_vcode" => $vcode
        ];
    
        $resp = $u->call("create", [], $data );
        $this->assertTrue($resp["code"] == 0);

        // 验证邀请人信息
        $user2 = &$this->getUser2();
        $user2 = $user->getByMobile($mobile);
        $this->assertTrue($user2["inviter"] == $user1["user_id"]);
    }
	
}