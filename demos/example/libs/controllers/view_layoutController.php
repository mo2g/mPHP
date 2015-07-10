<?php
class view_layoutController {
	public function indexAction() {
		$view = new view();//声明视图类

		//变量声明后就能在模版中使用
		$view->data['title'] = 'mPHP视图类-模版布局demo ';

		/*
		模版中可以加载其他模版，类似php中的 include，示例：
		<!--# layout:layout/head #-->
		<?php $this->_include('layout/table') ?>
		<!--# layout : layout/footer #-->
		*/

		$tpl = 'layout/main';//模版路径 ： 模版目录/layout/main.tp.html

		$view->loadTpl($tpl);//加载模版
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}