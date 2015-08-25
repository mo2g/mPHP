<?php
/*
作者:moyancheng
最后更新时间:2015-07-28
最后更新时间:2015-07-28
功能：

*/
namespace cache {

	class cacheModel {

		public $cahce;

		public function __construct($driver = 'file',$config = array() ) {
			\mPHP::inc( MPHP_PATH.'inc/functions.php' );//加载常用函数集
			$driver = "cache\\{$driver}";
			$this->cache = M($driver,$config);
		}

		public function in($file) {
			return $this->cache->in($file);
		}

		
		public function set($key,$data,$expire=0) {
			return $this->cache->set($key,$data,$expire);
		}

		public function get($key) {
			return $this->cache->get($key);
		}

		public function get_all() {
			return $this->cache->get_all();
		}

		public function delete($key) {
			return $this->cache->get_all($key);
		}

		public function flush() {
			return $this->cache->flush();
		}
	}
}