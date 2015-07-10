<?php
class view_mini_htmlController {
	public function indexAction() {
		//调试状态关闭缓存功能，默认为调试状态。0：非调试状态 1：调试状态
		//在初始化mPHP前，可以通过配置$GLOBALS['CFG']['debug']来设定
		//初始化mPHP后，可以通过配置mPHP::$debug来设定
		mPHP::$debug = 0;

		//初始化阶段mPHP已经声明了视图类
		//mPHP::$view = new view();
		$view = mPHP::$view;

		$view->is_mini_html = true;//启用压缩html功能，默认关闭。调试状态下被禁用

		$view->data['title'] = 'mPHP压缩html代码demo';
		$tpl = 'mini';//模版名称
		
		$view->loadTpl($tpl);//加载模版
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}