<?php
class indexController {
	public function indexAction() {
		echo 'hello world';
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}