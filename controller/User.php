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

	// 用户列表
	function index() {

		$query = $_GET;
		$query['order'] = !empty($query['order']) ? trim($query['order']) : 'created_at desc';

		$u = new \Mina\User\Model\User;
		$g = new \Mina\User\Model\Group;

		$data = [
			'users' => $u->search($_GET),
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