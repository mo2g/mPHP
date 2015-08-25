<?php
/*
作者:moyancheng
创建时间:2013-05-13
最后更新时间:2015-07-28
功能：
1.封装memcached常用操作
2.添加like匹配操作
*/
namespace cache {
	class memcachedModel {
		private $mem;
		public function __construct($arrConfig = array() ) {
			$mark = isset( $arrConfig['mark'] ) ? $arrConfig['mark'] : serialize($arrConfig);//标记mem服务，避免重复连接

			$mem = new Memcached($mark);
			if( isset($arrConfig['auth']) && isset($arrConfig['password']) ) {
				//需要认证的mem服务
				if( count( $mem->getServerList() ) == 0 ) {
					$mem->setOption(Memcached::OPT_COMPRESSION, false);
					$mem->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
					$mem->addServer($arrConfig['host'],$arrConfig['port']);
					$mem->setSaslAuthData($arrConfig['auth'], $arrConfig['password']);
				}
			} else {
				$mem->addServer($arrConfig['host'],$arrConfig['port']);//不需要认证的mem服务
			}
			$this->mem = $mem;
		}

		/**
		 * 写缓存
		 * @param $key 缓存名称
		 * @param $data 缓存内容
		 * @param $expire 缓存有效期，0,长期有效。
		 * @return integer
		 */
		public function set($key,$data,$expire=0) {
			return $this->mem->set($key,$data,$expire);
		}

		/**
		 * 取缓存
		 * @param string $key 缓存名称
		 * @return mixed
		 */
		public function get($key) {
			return $this->mem->get($key);
		}
		
		public function get_all() {
			return $this->mem->fetchAll();
		}
		
		/*
		模拟like匹配操作
		$like:为正则表达式
		*/
		public function like($like,$key = '') {
			$arrData = $arrTmp = array();
			if($key !== '')$arrTmp = $this->get($key);
			else  $arrTmp = $this->get_all();
			
			foreach($arrTmp as $key => $data) {
				if( preg_match($like,$key) ) $arrData[$key] = $data;
			}
			unset($arrTmp);
			return $arrData;
		}

		/**
		 * 删除缓存
		 * @param string $key 缓存名称
		 * @return boolean
		 */
		public function delete($key) {
			return $this->mem->delete($key);
		}


		/**
		 * 作废memcached的缓存，所有的项目
		 * @return booean
		 */
		public function flush() {
			return $this->mem->flush();
		}
	}
}