<?php
require_once(__DIR__ . '/../env.php');

use \Xpmse\Api;
use \Xpmse\Excp;
use \Xpmse\Utils;

echo "\n\Xpmsns\User\Model\User 测试... \n\n\t";

class testArticleModel extends PHPUnit_Framework_TestCase {

    /**
     * 新建用户
     */
	function testCreate() {
        $u = new \Xpmsns\user\Model\User;
        echo "hello world";
	}
	
}