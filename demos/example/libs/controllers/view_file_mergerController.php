<?php
class view_file_mergerController {
	public function indexAction() {
		//调试状态关闭缓存功能，默认为调试状态。0：非调试状态 1：调试状态
		//在初始化mPHP前，可以通过配置$GLOBALS['CFG']['debug']来设定
		//初始化mPHP后，可以通过配置mPHP::$debug来设定
		mPHP::$debug = 0;

		//默认值为0：使用PHP正则表达式压缩代码
		// 1：java环境启用yuicompressor压缩
		// 如果环境支持java，可以下载yuicompressor-2.4.8.jar放置LIBS_PATH目录中
		// 文档地址：http://yui.github.io/yuicompressor/
		mPHP::$CFG['java'] = 1;

		//初始化阶段mPHP已经声明了视图类
		//mPHP::$view = new view();
		$view = mPHP::$view;

		$view->is_merger = true;//启用js、css文件合并压缩功能，默认关闭。调试状态下被禁用

		$view->data['title'] = 'mPHP合并压缩js、css代码demo';
		$tpl = 'merger';//模版名称
		
		$view->loadTpl($tpl);//加载模版
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}