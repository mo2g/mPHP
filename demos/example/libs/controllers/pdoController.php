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

		mPHP::$CFG['pdo'] = $arrDb;

		$strSql = "show tables";

		$pool = new pool($arrDb);
		$pool->set_pool_size(2);

		$pdo1 = $pool->get();
		$pool->free($pdo1);
		$arrData = $pdo1->query($strSql)->fetch_all();
		

		print_r($arrData);
		echo '<br>','<br>','<br>';

		$pdo = $pool->get();
		echo '<br>','<br>','<br>';
		// $pdo = new pdoModel($arrDb);
		

		$pdo2 = $pool->get();
		// $pool->free($pdo2);
		echo '<br>','<br>','<br>';
		

		


		$pdo2 -> query($strSql);
		$arrData = $pdo2->fetch_all();
		//效果一样 $arrData = $pdo->query($strSql)->fetch_all();
		print_r($arrData);

		$dao = new dao();
	}
}