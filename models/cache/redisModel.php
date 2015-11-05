<?php
// $redis = new cache\redisModel();

// $redis->push('a','A'.microtime(1));
// $redis->push('a','A'.microtime(1));
// echo $redis->len('a');
// $value = $redis->pop('a');
// echo $value,"\n";
// $value = $redis->pop('a');
// echo $value;

namespace cache {
	class redisModel {
		public $redis;
		public $queue_prefix = 'rq_';

		public function __construct($arrConfig = []) {
			$host = isset($arrConfig['host']) ? $arrConfig['host'] : '127.0.0.1';
			$port = isset($arrConfig['port']) ? $arrConfig['port'] : '6379';
			$this->redis = new \redis();
			$this->redis->connect($host,$port);
		}

		/*---------------------------简单队列---------------------------*/
		//进队列
		public function push($queue_name,$value) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->lpush($key,$value);
		}
		//出队列
		public function pop($queue_name) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->lpop($key);
		}
		//队列长度
		public function len($queue_name) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->llen($key);
		}
		/*---------------------------简单队列---------------------------*/


		/*---------------------------键/值操作---------------------------*/
		/**
		 * 写缓存
		 * @param $key 缓存名称
		 * @param $data 缓存内容
		 * @param $expire 缓存有效期，0,长期有效。
		 * @return integer
		 */
		public function set($key,$data,$expire=0) {
			return $this->redis->set($key,$data,$expire);
		}

		/**
		 * 取缓存
		 * @param string $key 缓存名称
		 * @return mixed
		 */
		public function get($key) {
			return $this->redis->get($key);
		}

		/**
		 * 取所有键值
		 * @return mixed
		 */
		public function get_all_keys() {
			return $this->redis->keys('*');
		}

		/**
		 * 删除缓存
		 * @param string $key 缓存名称
		 * @return boolean
		 */
		public function delete($key) {
			return $this->redis->del($key);
		}
		//delete别名
		public function del($key) {
			return $this->redis->del($key);
		}

		/**
		 * 原子递增操作
		 * @param string $key 缓存名称
		 * @return integer
		 */
		public function incr($key) {
			return $this->redis->incr($key);
		}

		/**
		 * 原子递减操作
		 * @param string $key 缓存名称
		 * @return integer
		 */
		public function decr($key) {
			return $this->redis->incr($key);
		}
		/*---------------------------键/值操作---------------------------*/
	}
}