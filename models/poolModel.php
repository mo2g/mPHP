<?php
/*
作者:moyancheng
创建时间:2015-09-24
最后更新时间:2015-09-24

功能：基于swoole常驻内存的，简单数据库连接池
*/
class poolModel {
	public $pool_size = 10;
	public $connection_num = 0;
	public $idle_pool = [];
	public $db_config;

	public function __construct($config = []) {
		if( $config ) {
			$this->db_config = $config;
		} else {
			$this->db_config = mPHP::$CFG['pdo'];
		}
	}

	//设置连接池大小
	public function set_pool_size($size) {
		if( $this->connection_num < $size ) {
			$this->pool_size = $size;
		}
		return $this->pool_size;
	}

	//获取连接
	public function get() {
		if( count($this->idle_pool) > 0 ) {
			//返回空闲的连接
			++$this->connection_num;
			return array_pop($this->idle_pool);
		} else {
			//不存在空闲连接
			if( $this->connection_num >= $this->pool_size ) {
				//连接数达到连接池容量最大值
				mPHP::_exit();
				return false;
			}
			//创建新连接
			++$this->connection_num;
			mPHP::$db = new pdoModel($this->db_config);
			return mPHP::$db;
		}
	}

	//释放连接
	public function free() {
		--$this->connection_num;
		$this->idle_pool[] = mPHP::$db;
		mPHP::$db = false;
	}
}