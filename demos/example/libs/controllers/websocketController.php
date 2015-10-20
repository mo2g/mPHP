<?php
class websocketController {
	public function indexAction() {
		$view = new view();//声明视图类

		//变量声明后就能在模版中使用
		$view->data['title'] = 'mPHP websocket demo ';

		/*
		*/
		$view->loadTpl('websocket');//加载模版
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}