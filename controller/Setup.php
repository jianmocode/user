<?php
use \Tuanduimao\Loader\App as App;
use \Tuanduimao\Utils as Utils;
use \Tuanduimao\Tuan as Tuan;
use \Tuanduimao\Excp as Excp;
use \Tuanduimao\Conf as Conf;


class SetupController extends \Tuanduimao\Loader\Controller {
	
	function __construct() {

		$this->models = [
			'\\Mina\\User\\Model\\User'
		];
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
		echo json_encode('ok');		
	}
}