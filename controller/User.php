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
	// 	$path = "/2017/10/22/926cdbe7ba53176dc0f51b82faae0a91.jpg";
	// 	$media = new \Tuanduimao\Media(['private'=>true]);
	// 	$url = $media->privateURL( $path );

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
			"groups" => $g->search(),
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
			"groups" => $g->search()
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

}