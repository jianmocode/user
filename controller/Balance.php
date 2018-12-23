<?php
/**
 * Class BalanceController
 * 余额控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 18:15:52
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class BalanceController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 余额列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\User\Model\Balance;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "余额列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'balance','search.index');

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
	            "余额" => APP::R('balance','index'),
	            "余额管理" =>'',
	        ]
		];
	}


	/**
	 * 余额详情表单
	 */
	function detail() {

		$balance_id = trim($_GET['balance_id']);
		$action_name = '新建余额';
		$inst = new \Xpmsns\User\Model\Balance;
		
		if ( !empty($balance_id) ) {
			$rs = $inst->getByBalanceId($balance_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['balance_id'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'balance_id'=>$balance_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'balance','form');

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
	            "余额" => APP::R('balance','index'),
	            "余额管理" =>APP::R('balance','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/balance/index'
	 		]
		];

	}



	/**
	 * 保存余额
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\User\Model\Balance;
		$rs = $inst->saveByBalanceId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除余额
	 * @return [type] [description]
	 */
	function remove(){
		$balance_id = $_POST['balance_id'];
		$inst = new \Xpmsns\User\Model\Balance;
		$balance_ids =$inst->remove( $balance_id, "balance_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$balance_ids'=>$balance_ids]]);
	}

	/**
	 * 复制余额
	 * @return
	 */
	function duplicate(){
		$balance_id = $_GET['balance_id'];
		$inst = new \Xpmsns\User\Model\Balance;
		$rs = $inst->getByBalanceId( $balance_id );
		$action_name =  $rs['balance_id'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['balance_id']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'balance_id'=>$balance_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'balance','form');

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
	            "余额" => APP::R('balance','index'),
	            "余额管理" =>APP::R('balance','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/balance/index'
	 		]
		];
	}



}