<?php
class websocketController {
	public function indexAction() {
		$view = new view();//声明视图类
		$view->data['title'] = 'mPHP websocket demo ';
		$view->loadTpl('websocket');//加载模版
	}
}