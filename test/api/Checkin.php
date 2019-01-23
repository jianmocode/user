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

    // 从当前时刻，连续签到7次
    function testCreate7daysFromNow() {

        $u = new \Xpmsns\User\Model\User;
        $user = $u->create([
            "mobile" =>  "131" . time(),
            "password" => "1111111"
        ]);

        $user_id = $user["user_id"];
        $u->loginSetSession( $user_id );

        echo "\n";
        echo "当前时刻: ". date("Y年m月d日 H:i:s")  . "\n";
        echo "积分余额: ". $u->getCoin( $user_id ) . "\n";
        echo "\n";
        
        $the7days = [];
        $now = time();
        for( $i=0; $i<7; $i++) {
            $the7days[$i] = $now + 86400 * $i;
        }

        // 连续签到7次
        foreach( $the7days as $i=>$time ) {
            $day = $i+1;
            $coinBefore = $u->getCoin( $user_id );
            $rs = $this->CheckIn( $user_id,  $time);
            echo "第{$day}天 " . date("Y年m月d日 H:i:s", strtotime($rs["time"]) ).  
                 " 签到ID: ". $rs["checkin_id"] .
                 " 积分余额: {$coinBefore} -> ". $u->getCoin( $user_id ) . "\n"
                 ;
        }

        // 清理数据
        $ut = new \Xpmsns\User\Model\UserTask; 
        $coin = new \Xpmsns\User\Model\Coin; 
        $ut->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $coin->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        $u->runSql("DELETE FROM {{table}} WHERE `user_id`=?", false, [$user_id]);
        
    }

}