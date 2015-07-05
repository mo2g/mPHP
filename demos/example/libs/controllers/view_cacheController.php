<?php
class view_cacheController {
	public function indexAction() {
		//调试状态关闭缓存功能，默认为调试状态。0：非调试状态 1：调试状态
		//在初始化mPHP前，可以通过配置$GLOBALS['CFG']['debug']来设定
		//初始化mPHP后，可以通过配置mPHP::$debug来设定
		mPHP::$debug = 0;

		//初始化阶段mPHP已经声明了视图类
		//mPHP::$view = new view();
		$view = mPHP::$view;

		$tpl = 'view';//模版名称
		$file = CACHE_PATH . "html/{$tpl}.html";//缓存html文件保存位置
		$time = 5;//缓存时间（秒）

		$cache = $view->cache($file,$time);
		if( $cache ) return true;//缓存有效期内直接返回

		sleep(1);//模拟耗时操作

		$view->data['title'] = 'mPHP视图类缓存demo ';
		$view->data['h'] = 'hello ';
		$view->data['w'] = 'world ';

		$view->loadTpl($tpl);//加载模版
		highlight_file(__FILE__); 
	}
}