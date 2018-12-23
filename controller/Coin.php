<?php
/**
 * Class CoinController
 * 积分控制器
 *
 * 程序作者: XpmSE机器人
 * 最后修改: 2018-12-23 18:11:39
 * 程序母版: /data/stor/private/templates/xpmsns/model/code/controller/Name.php
 */

use \Xpmse\Loader\App;
use \Xpmse\Excp;
use \Xpmse\Utils;

class CoinController extends \Xpmse\Loader\Controller {


	function __construct() {
	}

	/**
	 * 积分列表检索
	 */
	function index() {	

		$search  = $query = $_GET;
		$inst = new \Xpmsns\User\Model\Coin;
		if ( !empty($search['order']) ) {
			$order = $search['order'];
			unset( $search['order'] );
			$search[$order] = 1;
		}

		$response = $inst->search($search);
		$data = [
			'_TITLE' => "积分列表检索",
			'query' => $query,
			'response' => $response
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		App::render($data,'coin','search.index');

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
	            "积分" => APP::R('coin','index'),
	            "积分管理" =>'',
	        ]
		];
	}


	/**
	 * 积分详情表单
	 */
	function detail() {

		$coin_id = trim($_GET['coin_id']);
		$action_name = '新建积分';
		$inst = new \Xpmsns\User\Model\Coin;
		
		if ( !empty($coin_id) ) {
			$rs = $inst->getByCoinId($coin_id);
			if ( !empty($rs) ) {
				$action_name =  $rs['coin_id'];
			}
		}

		$data = [
			'action_name' =>  $action_name,
			'coin_id'=>$coin_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}


		App::render($data,'coin','form');

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
	            "积分" => APP::R('coin','index'),
	            "积分管理" =>APP::R('coin','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/coin/index'
	 		]
		];

	}



	/**
	 * 保存积分
	 * @return
	 */
	function save() {
		$data = $_POST;
		$inst = new \Xpmsns\User\Model\Coin;
		$rs = $inst->saveByCoinId( $data );
		echo json_encode($rs);
	}

	/**
	 * 删除积分
	 * @return [type] [description]
	 */
	function remove(){
		$coin_id = $_POST['coin_id'];
		$inst = new \Xpmsns\User\Model\Coin;
		$coin_ids =$inst->remove( $coin_id, "coin_id" );
		echo json_encode(['message'=>"删除成功", 'extra'=>['$coin_ids'=>$coin_ids]]);
	}

	/**
	 * 复制积分
	 * @return
	 */
	function duplicate(){
		$coin_id = $_GET['coin_id'];
		$inst = new \Xpmsns\User\Model\Coin;
		$rs = $inst->getByCoinId( $coin_id );
		$action_name =  $rs['coin_id'] . ' 副本';

		// 删除唯一索引字段
		unset($rs['coin_id']);

		// 复制图片

		$data = [
			'action_name' =>  $action_name,
			'coin_id'=>$coin_id,
			'rs' => $rs
		];

		if ( $_GET['debug'] == 1 ) {
			Utils::out($data);
			return;
		}

		
		App::render($data,'coin','form');

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
	            "积分" => APP::R('coin','index'),
	            "积分管理" =>APP::R('coin','index'),
	            "$action_name" => ''
	        ],
	        'active'=> [
	 			'slug'=>'xpmsns/user/coin/index'
	 		]
		];
	}



}