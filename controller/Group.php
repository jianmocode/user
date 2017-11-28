<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Task as Task;
use \Xpmsns\Storage\Local as Storage;
use \Endroid\QrCode\QrCode as Qrcode;
use Endroid\QrCode\LabelAlignment;
use \Endroid\QrCode\ErrorCorrectionLevel;



class GroupController extends \Xpmse\Loader\Controller {
	
	function __construct() {
	}


	// 群组列表
	function index() {

		$query = $_GET;
		$query['order'] = !empty($query['order']) ? trim($query['order']) : 'created_at desc';
		
		$g = new \Xpmsns\User\Model\Group;

		$data = [
			"groups" => $g->search( $query ),
			"query" => $query
		];

		App::render($data,'group','search.index');
		
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
	                 "用户组" => APP::R('group','index'),
	                 "用户组列表" =>'',
	        ]
		];
	}


	// 修改/创建用户组表单
	function modify(){

		$g = new \Xpmsns\User\Model\Group;
		$id = $_GET['id'];

		$data = [
			"group" => $g->getByGid($_GET['id']),
		];

		App::render($data,'group','modify');
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
	            "用户组" => APP::R('group','index'),
	            "用户组列表" => APP::R('group','index'),
	            "管理用户组" => '',
	        ],

	        'active'=> [
	 			'slug'=>'xpmsns/user/group/index'
	 		]
		];
	}


	/**
	 * 删除用户组
	 */
	function remove() {		

		$group_id = $_REQUEST['group_id'];

		if ( empty($group_id) ) {
			throw new Excp("未提供用户信息", 404, []);
		}

		$g = new \Xpmsns\User\Model\Group;
		$resp = $g->remove( $group_id, "group_id");

		if ( $resp === false){
			throw new Excp("删除失败", 500, ["group_id"=>$group_id]);
		}

		Utils::out(["message"=>"删除成功"]);
	}



	/**
	 * 保存用户组
	 * @return true
	 */
	function save() {

		$g = new \Xpmsns\User\Model\Group;

		try {
			$group_id = $g->save($_POST);
		} catch( Excp $e ){
			if ( $e->getCode() == '23000' || $e->getCode() == "1062" ) {

				$message = $e->getMessage();
				$errors = [];
				if ( strpos($message, "key 'group_slug_unique'") !== false ) {
					$errors['slug'] = "别名已经存在";
				}

				if ( strpos($message, "key 'group_name_unique'") !== false ) {
					$errors['name'] = "名称已经存在";
				}

				throw new Excp("用户组信息保存失败", 500, [ 
					"message" => $e->getMessage(),
					"errors"=> $errors
				]);
			}

			throw $e;
		}

		echo json_encode(['code'=>0, "message"=>"保存成功", "group_id"=>$group_id]);
	}



}













