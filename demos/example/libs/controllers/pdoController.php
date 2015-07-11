<?php
class pdoController {
	public function indexAction() {
		$arrDb = array(
			'type' => 'mysql',//数据库类型
			'dbname' => 'test',//数据库名称
			'host' => 'localhost',//服务器IP
			'port' => '3306',//服务器端口
			'user' => 'root',//用户名
			'password' => '',//密码
			'charset' => 'utf8',//字符集
		);

		$pdo = new pdoModel($arrDb);
		$strSql = "show tables";
		$pdo -> query($strSql);
		$arrData = $pdo->fetch_all();
		//效果一样 $arrData = $pdo->query($strSql)->fetch_all();
		print_r($arrData);

		echo '<h1>控制器源代码：</h1><hr />';
		highlight_file(__FILE__); 
	}
}