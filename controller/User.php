<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;
use \Tuanduimao\Task as Task;
use \Mina\Storage\Local as Storage;
use \Endroid\QrCode\QrCode as Qrcode;
use Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;



class UserController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {
	}

	// function test() {
	// 	$path = "/2017/10/23/926cdbe7ba53176dc0f51b82faae0a91.jpg";
	// 	$media = new \Tuanduimao\Media(['private'=>true]);
	// 	$url = $media->privateURL( $path );

	// 	$url_b = \Tuanduimao\Media::briage('imagelatest', ['name'=>'testing'], 'https://wss.xpmjs.com', 
	// 		 '4990e4107dbfe85c045cf8bbd3508652','150698766059529');

	// 	echo "<a href='{$url_b}' target='_blank' > {$url_b} </a><br/>";

	// 	echo "<a href='{$url}' target='_blank' > {$url} </a>";
	// }


	// function uptest() {
	// 	$url = "https://ss0.bdstatic.com/94oJfD_bAAcT8t7mm9GUKT-xh_/timg?image&quality=100&size=b4000_4000&sec=1508681851&di=5e8b7b83e6a0e483fd4da33d6a154ec9&src=http://www.zhlzw.com/UploadFiles/Article_UploadFiles/201204/20120412123914329.jpg";

	// 	$media = new \Tuanduimao\Media(['private'=>true]);
	// 	$rs = $media->uploadImage($url);
	// 	Utils::out( $rs );
	// }


	// 用户列表
	function index() {

		$query = $_GET;
		$query['order'] = !empty($query['order']) ? trim($query['order']) : 'created_at desc';

		$u = new \Mina\User\Model\User;
		$g = new \Mina\User\Model\Group;

		$data = [
			'users' => $u->search($query),
			"groups" => $g->getAll(),
			"query" => $query
		];

		App::render($data,'user','search.index');
		
		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
			 		"js/plugins/dropzonejs/dropzone.min.js",
			 		"js/plugins/cropper/cropper.min.js",
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	                 "用户" => APP::R('user','index'),
	                 "用户列表" =>'',
	        ]
		];
	}


	// 修改/创建用户表单
	function modify(){

		$u = new \Mina\User\Model\User;
		$g = new \Mina\User\Model\Group;

		$id = $_GET['id'];

		$data = [
			"user" => $u->getByUid($_GET['id']),
			"groups" => $g->getAll()
		];

		// Utils::out( $data );

		// return;
		App::render($data,'user','modify');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/dropzonejs/dropzone.min.js",
		    		"js/plugins/cropper/cropper.min.js"

				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css"
	 		],

			'crumb' => [
	            "用户" => APP::R('user','index'),
	            "用户列表" => APP::R('user','index'),
	            "管理用户" => '',
	        ],

	        'active'=> [
	 			'slug'=>'mina/user/user/index'
	 		]
		];
	}


	/**
	 * 删除用户
	 */
	function remove() {		

		$user_id = $_REQUEST['user_id'];

		if ( empty($user_id) ) {
			throw new Excp("未提供用户信息", 404, []);
		}

		$u = new \Mina\User\Model\User;
		$resp = $u->remove( $user_id, "user_id");

		if ( $resp === false){
			throw new Excp("删除失败", 500, ["user_id"=>$user_id]);
		}

		Utils::out(["message"=>"删除成功"]);
	}



	/**
	 * 保存用户
	 * @return true
	 */
	function save() {

		$u = new \Mina\User\Model\User;

		// throw new Excp("测试出错啦！！！", 500, ["hello"=>"world"]);
		// throw new Excp("出错啦 1", 500, [ "errors"=>["mobile"=>"手机号码已被注册"]]);
		try {
			$user_id = $u->save($_POST);
		} catch( Excp $e ){
			if ( $e->getCode() == '23000') {

				$message = $e->getMessage();
				$errors = [];
				if ( strpos($message, "key 'user_email_unique'") !== false ) {
					$errors['email'] = "邮箱地址已被注册";
				}

				if ( strpos($message, "key 'user_mobile_full_unique'") !== false ) {
					$errors['mobile'] = "手机号已被注册";
				}

				throw new Excp("账号信息保存失败", 500, [ 
					"message" => $e->getMessage(),
					"errors"=> $errors
				]);
			}

			throw $e;
		}

		echo json_encode(['code'=>0, "message"=>"保存成功", "user_id"=>$user_id]);
	}


}













