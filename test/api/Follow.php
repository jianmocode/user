<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;


echo "\n\Xpmsns\User\Api\User 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {
    
    // 输出信息
    protected function out( $message ) {
        fwrite(STDOUT,$message );
    }

    // 读取用户
    protected function &getUsers() {
        static $value = null;
        return $value;
    }

    /**
     * 创建一组测试用户
     * 
     */
    function testCreateUser() {
        $this->out( "\n创建单元测试用户.....");
        $u = new \Xpmsns\User\Model\User;
        $users = &$this->getUsers();

        $names = [
            "丢了灵魂的孩纸","只因太美","渔美人","敷衍不停的重演","大扎王后","森系女孩","樱桃娃娃","超人小叮当","我是飞车妞","我迷了鹿"
        ];

        for( $i=0; $i<count($names); $i++ ) {
            $this->out("{$names[$i]}...");
            $users[] = $u->create([
                "nickname" => $names[$i],
                "mobile" =>  "1311111111{$i}",
                "password" => "1111111",
                "inviter" => "unit-test",
            ]);
            $this->out( "完成\n");
        }
    }


    /**
     * 建立关注/粉丝关系关系
     * 
     * 丢了灵魂的孩纸 0  关注: 只因太美 1, 渔美人 2 
     *      只因太美 1  关注: 丢了灵魂的孩纸 0, 敷衍不停的重演 3
     * 敷衍不停的重演 3  关注: 丢了灵魂的孩纸 0, 大扎王后 4, 森系女孩 5 
     * 
     * 丢了灵魂的孩纸 0: 
     *      friend: 只因太美 1 
     *    follower: 只因太美 1, 敷衍不停的重演 3
     *   following: 只因太美 1, 渔美人 2
     * no-relation: 大扎王后 4, 森系女孩 5, 樱桃娃娃 6, 超人小叮当 7, 我是飞车妞 8, 我迷了鹿 9, 
     * 
     * 数据统计:
     * 丢了灵魂的孩纸 0: 粉丝: 2个 关注: 2个
     *      只因太美 1: 粉丝: 1个 关注: 2个
     *        渔美人 2: 粉丝: 1个 关注: 0个
     * 敷衍不停的重演 3: 粉丝: 1个 关注: 3个
     *      大扎王后 4: 粉丝: 1个 关注: 0个
     *      森系女孩 5: 粉丝: 1个 关注: 0个
     * 
     */
    function testBuildRelation() {
        $users = &$this->getUsers();
        $this->out( "\n建立用户关系.....");
        $fo = new \Xpmsns\User\Model\Follow;

        // 丢了灵魂的孩纸 0  关注: 只因太美 1, 渔美人 2 
        $fo->create(["follower_id"=>$users[0]["user_id"], "user_id"=>$users[1]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);
        $fo->create(["follower_id"=>$users[0]["user_id"], "user_id"=>$users[2]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);

        // 只因太美 1  关注: 丢了灵魂的孩纸 0, 敷衍不停的重演 3
        $fo->create(["follower_id"=>$users[1]["user_id"], "user_id"=>$users[0]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);
        $fo->create(["follower_id"=>$users[1]["user_id"], "user_id"=>$users[3]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);

        // 敷衍不停的重演 3  关注: 丢了灵魂的孩纸 0, 大扎王后 4, 森系女孩 5 
        $fo->create(["follower_id"=>$users[3]["user_id"], "user_id"=>$users[0]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);
        $fo->create(["follower_id"=>$users[3]["user_id"], "user_id"=>$users[4]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);
        $fo->create(["follower_id"=>$users[3]["user_id"], "user_id"=>$users[5]["user_id"], "origin"=>"unit-test", "data"=>["v"=>"from-unit-test"] ]);

        $this->out( "完成\n");
    }


    /**
     * 测试读取用户关系
     */
    function testGetRelation(){

        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");

        // 读取用户关系
        $relation = $fo->getRelation( $user_id, $user_ids );

        // 校验用户关系
        $this->assertTrue($relation[$user_ids[0]] == "self" );
        $this->assertTrue($relation[$user_ids[1]] == "friend" );
        $this->assertTrue($relation[$user_ids[2]] == "following" );
        $this->assertTrue($relation[$user_ids[3]] == "follower" );
        for( $i=4; $i<count($user_ids); $i++ )  {
            $this->assertTrue($relation[$user_ids[$i]] == "no-relation" );
        }
    }

    /**
     * 测试读取用户关系(从缓存中读取)
     */
    function testGetRelationFromCache(){

        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");

        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[0]) == "self" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[1]) == "friend" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[2]) == "following" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[3]) == "follower" );
        for( $i=4; $i<count($user_ids); $i++ )  {
            $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[$i]) == "no-relation" );
        }
    }


    /**
     * 测试用户模型，读取用户关系
     */
    function testGetUserRelation() {

        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");
        $fo->clearRelationCache( $user_id, $user_ids[1]); // 清除两个缓存
        $fo->clearRelationCache( $user_id, $user_ids[2]); // 清除两个缓存
        $relation = $u->getUserRelation( $user_id, $user_ids);

        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[0]) == "self" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[1]) == "friend" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[2]) == "following" );
        $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[3]) == "follower" );
        for( $i=4; $i<count($user_ids); $i++ )  {
            $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[$i]) == "no-relation" );
        }
    }

    /**
     * 测试读取粉丝列表
     */
    function testGetFollowers() {
        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");

        $followers = $fo->getFollowers( $user_id );
        $follower_ids = array_column($followers["data"], "follower_user_id");

        $this->assertTrue($user_ids[1] == $follower_ids[0] );
        $this->assertTrue($user_ids[3] == $follower_ids[1] );
    }

    /**
     * 测试读取关注的人列表
     */
    function testGetFollowings() {
        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");

        $followings = $fo->getFollowings( $user_id );
        $following_ids = array_column($followings["data"], "user_user_id");

        $this->assertTrue($user_ids[1] == $following_ids[0] );
        $this->assertTrue($user_ids[2] == $following_ids[1] );
    }


    /**
     * 测试读取互相关注的人列表
     */
    function testGetFriends() {
        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");
        $friends = $fo->getFriends( $user_id );

        // print_r( $friends );

        // $friends_ids = array_column($friends["data"], "follower_user_id");

        // $this->assertTrue($user_ids[1] == $friends_ids[0] );
    }



    /**
     * 测试清空缓存
     */
    function testClearRelationCache(){
        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $users = $u->getData("WHERE `inviter`='unit-test'");
        $user_id = $users[0]["user_id"];
        $user_ids = array_column($users, "user_id");

        $resp = $fo->clearRelationCache( $user_id, $user_ids[0]);
        $this->assertTrue($resp == 1);

        $resp = $fo->clearRelationCache( $user_id );
        $this->assertTrue($resp == 9 );
        for( $i=0; $i<count($user_ids); $i++ )  {
            $this->assertTrue($fo->getRelationFromCache($user_id, $user_ids[$i]) == false );
        }
    }


    // 清除测试数据
    function testClean(){
        $this->out( "\n清除测试数据.....");
        // 清除单元测试数据
        $u = new \Xpmsns\User\Model\User;
        $fo = new \Xpmsns\User\Model\Follow;
        $u->runSql("DELETE FROM {{table}} WHERE `inviter`=?", false, ["unit-test"]);
        $fo->runSql("DELETE FROM {{table}} WHERE `origin`=?", false, ["unit-test"]);
    }

}