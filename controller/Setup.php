<?php
use \Xpmse\Loader\App as App;
use \Xpmse\Utils as Utils;
use \Xpmse\Tuan as Tuan;
use \Xpmse\Excp as Excp;
use \Xpmse\Conf as Conf;
use \Xpmse\Option as Option;
use \Xpmse\Wechat as Wechat;


class SetupController extends \Xpmse\Loader\Controller {
	
	function __construct() {

		$this->models = [
			'\\Xpmsns\\User\\Model\\User',
            '\\Xpmsns\\User\\Model\\Group',
            '\\Xpmsns\\User\\Model\\Coin',
            '\\Xpmsns\\User\\Model\\Balance'
		];
	}


	/**
	 * 初始化用户相关配置项
	 * @return 
	 */
	private function init_option() {

		// 注册微信消息处理器
		Wechat::bind("xpmsns/user", "user/wechatRouter");

		$opt = new Option('xpmsns/user');

		// 短信验证码
		$sms_vcode = $opt->get("user/sms/vcode");
		if ( $sms_vcode === null ) {
			$opt->register(
				"短信验证码配置", 
				"user/sms/vcode", 
				[
					"type" => "qcloud",
					"option"=>[
						"appid" => "<your appid>",
						"appkey" => "<your appkey>",
						"sign" => "您的签名",
						"message" => "您的短信验证码为 {1} , 打死不要告诉别人！" 
					]
				],
				90
			);
		}


		// 是否开启短信验证码登录
		$sms_on = $opt->get("user/sms/on");		
		if ( $sms_on === null ) {
			$opt->register(
				"开启短信验证码", 
				"user/sms/on", 
				0,
				89
			);
		}


		// 是否开启手机号密码登录
		$mobile_on = $opt->get('user/mobile/on');
		if ( $mobile_on === null ) {
			$opt->register(
				"开启手机号登录", 
				"user/mobile/on", 
				1,
				1
			);
		}


		// 是否开启微信登录
		$wechat_on = $opt->get('user/wechat/on');
		if ( $wechat_on === null ) {
			$opt->register(
				"开启微信登录", 
				"user/wechat/on", 
				1,
				2
			);
		}


		// 是否开启手机号注册
		$mobile_signup_on = $opt->get("user/mobile/signup/on");
		if ( $mobile_signup_on === null ) {
			$opt->register(
				"开放手机号注册", 
				"user/mobile/signup/on", 
				1,
				3
			);
		}

		// 微信登录后是否绑定手机号
		$bind_mobile = $opt->get('user/bind/mobile');
		if ( $bind_mobile === null ) {
			$opt->register(
				"强制绑定手机号", 
				"user/bind/mobile", 
				0,
				4
			);
		}

		// 手机登录后是否绑定微信
		$bind_wechat = $opt->get('user/bind/wechat');
		if ( $bind_wechat === null ) {
			$opt->register(
				"强制绑定微信", 
				"user/bind/wechat", 
				0,
				4
			);
		}

		// 默认用户分组名称
		$default_group = $opt->get("user/default/group");
		if ($default_group === null ) {
			$opt->register(
				"默认分组", 
				"user/default/group", 
				"default",
				5
			);
		}
	}


	private  function remove_option(){
		$opt = new Option('xpmsns/user');
		$opt->unregister();

		// 解绑微信处理器
		Wechat::unbind("xpmsns/user");
	}

	
	private  function init_group() {
		
		$g = new \Xpmsns\User\Model\Group;
		$default = $g->getBySlug('default');
		if ( empty($default) ) {
			$g->create(['slug'=>"default", 'name'=>'默认分组']);
		}
	}


	function install() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
			try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		// 创建配置项
		try {
			$this->init_option();
		} catch( Excp $e ){
			echo $e->toJSON(); return;
		}

		// 创建默认分组
		try {
			$this->init_group();
		} catch( Excp $e ){
			echo $e->toJSON(); return;
		}

		echo json_encode('ok');
	}


	function upgrade(){
		echo json_encode('ok');	
	}

	function repair() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__schema(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}

		// 创建配置项
		try {
			$this->init_option();
		} catch( Excp $e ){
			echo $e->toJSON(); return;
		}

		// 创建默认分组
		try {
			$this->init_group();
		} catch( Excp $e ){
			echo $e->toJSON(); return;
		}

		echo json_encode('ok');		
	}


	// 卸载
	function uninstall() {

		$models = $this->models;
		$insts = [];
		foreach ($models as $mod ) {
			try { $insts[$mod] = new $mod(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}
		
		foreach ($insts as $inst ) {
			try { $inst->__clear(); } catch( Excp $e) {echo $e->toJSON(); return;}
		}


		// 移除配置项
		try {
			$this->remove_option();
		} catch( Excp $e ){
			echo $e->toJSON(); return;
		}



		echo json_encode('ok');		
	}
}