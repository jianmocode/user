<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;


echo "\n\Xpmsns\User\Api\Checkin 测试... \n\n\t";


class testArticleModel extends PHPUnit_Framework_TestCase {

    protected function CheckIn( $user_id, $time ) {
        
        $ci = new \Xpmsns\User\Model\Checkin;

        $data["user_id"] = $user_id;
        $data["time"] = date("Y-m-d H:i:s", $time);

        $rs = $ci->create( $data );
        $history  = $ci->search([
            "user_id" => $user_id,
            "orderby_time_desc" => "1",
            "perpage" => 8
        ]);
        $rs["history"] = $history["data"];
        
        // 触发行为
        $ci->triggerBehavior("xpmsns/user/checkin/create",  $rs);
        return $rs;
    }


    protected function out( $message ) {
        fwrite(STDOUT,$message );
    }

     // 读取用户
     protected function &getUser() {
        static $value = null;
        return $value;
    }

    function testCreateUser() {

        $this->out( "\n创建单元测试用户.....");
        $u = new \Xpmsns\User\Model\User;
        $user = &$this->getUser();
        $user = $u->create([
            "mobile" =>  "131" . time(),
            "password" => "1111111"
        ]);

        $this->out( "完成\n");
    }


    // 从当前时刻，连续签到7次
    function testCreate7DaysFromNow() {

        $this->out( "\n\n测试连续签到7天\n-------\n");

        $u = new \Xpmsns\User\Model\User;
        $user = &$this->getUser();
        $user_id = $user["user_id"];
        $u->loginSetSession( $user_id );
       

        $this->out( "\n" );
        $this->out( "模拟当前时刻: ". date("Y年m月d日 H:i:s")  . "\n");
        $this->out( "积分余额: ". $u->getCoin( $user_id ) . "\n");
        $this->out( "\n");
       

        $the7days = [];
        $now = time();
        for( $i=0; $i<7; $i++) {
            $the7days[$i] = $now + 86400 * $i;
        }

        // 任务副本
        $ut = new \Xpmsns\User\Model\UserTask;

        // 连续签到7次
        foreach( $the7days as $i=>$time ) {
            $day = $i+1;
            $coinBefore = $u->getCoin( $user_id );
            $rs = $this->CheckIn( $user_id,  $time);
            sleep(1);
            $coinAfter =  $u->getCoin( $user_id );
            $this->out( 
                 "第{$day}天 " . date("Y年m月d日 H:i:s", strtotime($rs["time"]) ).  
                 " 签到ID: ". $rs["checkin_id"] .
                 " 积分余额: {$coinBefore} -> {$coinAfter} (+". ($coinAfter - $coinBefore) .  ")\n"
            );

            // 检查任务副本
            $task = $ut->getByTaskSlugAndUserId("checkin", $user_id );
            $quantity = $task["quantity"];
            $this->assertTrue($task["usertask"]["process"] == $i + 1  );
            $this->assertTrue(($coinAfter - $coinBefore) == $quantity[$i] );
        }        
    }


    // 连续签到4天, 第5天中断, 第6天,第7天继续
    function testCreate4Days5Break6And7Continue() {
        
        $this->out( "\n\n测试连续签到4天, 第5天中断, 第6天,第7天继续\n-------\n");
        $u = new \Xpmsns\User\Model\User;
        $user = &$this->getUser();
        $user_id = $user["user_id"];
        $u->loginSetSession( $user_id );
        

        // 模拟7天后
        $now = time() + 86400 * 7;
        $this->out( "\n" );
        $this->out( "模拟当前时刻: ". date("Y年m月d日 H:i:s", $now)  . "\n");
        $this->out( "积分余额: ". $u->getCoin( $user_id ) . "\n");
        $this->out( "\n");
       
        $the7days = [];
        for( $i=0; $i<7; $i++) {
            $the7days[$i] = $now + 86400 * $i;
        }

        $ut = new \Xpmsns\User\Model\UserTask;

        // 连续签到4次
        foreach( $the7days as $i=>$time ) {
            
            $day = $i+1;
            if ( $i == 3 ) {
                $this->out( 
                    "第{$day}天 " . date("Y年m月d日 H:i:s", strtotime($rs["time"]) ) .
                    " 签到中断\n"
                );
                continue;
            }

            if ( $i < 3 ) {
                $j = $i;
            } else {
                $j =  $i-4;
            }
            
            $coinBefore = $u->getCoin( $user_id );
            $rs = $this->CheckIn( $user_id,  $time);
            sleep(1);
            $coinAfter =  $u->getCoin( $user_id );
            $this->out( 
                 "第{$day}天 " . date("Y年m月d日 H:i:s", strtotime($rs["time"]) ).  
                 " 签到ID: ". $rs["checkin_id"] .
                 " 积分余额: {$coinBefore} -> {$coinAfter} (+". ($coinAfter - $coinBefore) .  ")\n"
            );

            // 检查任务副本
            $task = $ut->getByTaskSlugAndUserId("checkin", $user_id );
            $quantity = $task["quantity"];
        
            $this->assertTrue($task["usertask"]["process"] == $j +1 );
            $this->assertTrue(($coinAfter - $coinBefore) == $quantity[$j] );
        }
    }


    // 清除测试数据
    function testClean(){

        $user = &$this->getUser();
        $user_id = $user["user_id"];

        $this->out( "\n清除测试数据.....");
        sleep(5);

        // 清除单元测试数据
        $u = new \Xpmsns\User\Model\User;
        $ut = new \Xpmsns\User\Model\UserTask; 
        $coin = new \Xpmsns\User\Model\Coin; 
        $ci = new \Xpmsns\User\Model\Checkin;
        $ut->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $coin->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $ci->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $u->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $this->out( "完成\n");
    }

}