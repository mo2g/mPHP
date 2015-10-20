<?php
class indexController {
	public function indexAction() {
		echo 'hello world';
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}

	public function sessionAction() {
		// $session = M('session');
		$session = new sessionModel();
		$session->start();
		// setcookie('sdfsdf',1234,0,'/');
		$_SESSION["a"]++;
		print_r($_SESSION);
	}

	public function phpinfoAction() {
		phpinfo();
	}
}