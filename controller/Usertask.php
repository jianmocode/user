<?php
/**
 * Class UsertaskController
 * 任务控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 22:55:38
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class UsertaskController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 任务列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\User\Model\Usertask;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "任务列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'usertask','search.index');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			'js/plugins/jquery-tags-input/jquery.tagsinput.min.js',
		    		'js/plugins/jquery-ui/jquery-ui.min.js',
	        		'js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js',
				],
			'css'=>[
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css"
	 		],
			'crumb' => [
	            "任务" => APP::R('usertask','index'),
	            "任务管理" =>'',
	        ]
		];
	}


	/**
	 * 任务详情表单
	 */
	function detail() {

		$usertask_id = trim($_GET['usertask_id']);
		$action_name = '新建任务';
		$inst = new \Xpmsns\User\Model\Usertask;
		
		if ( !empty($usertask_id) ) {
			$rs = $inst->getByUsertaskId($usertask_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['usertask_id'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'usertask_id'=>$usertask_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'usertask','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/select2/i18n/zh-CN.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js",
		    		"js/plugins/summernote/summernote.min.js",
		    		"js/plugins/summernote/lang/summernote-zh-CN.js",
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css",
	 			"js/plugins/summernote/summernote.css",
	 			"js/plugins/summernote/summernote-bs3.min.css"
	 		],

			'crumb' => [
	            "任务" => APP::R('usertask','index'),
	            "任务管理" =>APP::R('usertask','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/usertask/index'
	 		]
		];

	}



	/**
	 * 保存任务
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\User\Model\Usertask;
		$rs = $inst->saveByUsertaskId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除任务
	 * @return [type] [description]
	 */
	function remove(){
		$usertask_id = $_POST['usertask_id'];
		$inst = new \Xpmsns\User\Model\Usertask;
		$usertask_ids =$inst->remove( $usertask_id, "usertask_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$usertask_ids'=>$usertask_ids]]);
	}

	/**
	 * 复制任务
	 * @return
	 */
	function duplicate(){
		$usertask_id = $_GET['usertask_id'];
		$inst = new \Xpmsns\User\Model\Usertask;
		$rs = $inst->getByUsertaskId( $usertask_id );
		$action_name =  $rs['usertask_id'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['usertask_id']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'usertask_id'=>$usertask_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'usertask','form');

		return [
			'js' => [
		 			"js/plugins/select2/select2.full.min.js",
		 			"js/plugins/dropzonejs/dropzone.min.js",
		 			"js/plugins/cropper/cropper.min.js",
		 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.js",
		 			"js/plugins/bootstrap-datepicker/bootstrap-datepicker.min.js",
		 			'js/plugins/masked-inputs/jquery.maskedinput.min.js',
		 			"js/plugins/jquery-validation/jquery.validate.min.js",
		    		"js/plugins/jquery-ui/jquery-ui.min.js"
				],
			'css'=>[
				"js/plugins/bootstrap-datepicker/bootstrap-datepicker3.min.css",
	 			"js/plugins/select2/select2.min.css",
	 			"js/plugins/select2/select2-bootstrap.min.css",
	 			"js/plugins/jquery-tags-input/jquery.tagsinput.min.css"
	 		],

			'crumb' => [
	            "任务" => APP::R('usertask','index'),
	            "任务管理" =>APP::R('usertask','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/usertask/index'
	 		]
		];
	}



}