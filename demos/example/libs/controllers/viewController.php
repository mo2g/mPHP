<?php
class viewController {
	public function indexAction() {
		$view = new view();//声明视图类

		//变量声明后就能在模版中使用
		$view->data['title'] = 'mPHP视图类demo ';
		$view->data['h'] = 'hello ';
		$view->data['w'] = 'world ';

		/*
		模版中可以使用默认的PHP标签：<?php echo time(); ?>
		也可以使用mPHP定义的标签： <!--# echo time(); #-->
		或者自己定义的标签，视图类会自动转换成原生PHP形式：<?php echo time(); ?>
		<!--# echo time(); #-->标签设计的初衷：PHP原生标签会导致部分编辑器无法正常解析html代码，可能会影响前端开发人员的编码心情
		*/
		$view->loadTpl('view');//加载模版
		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}