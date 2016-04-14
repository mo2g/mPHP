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
		//入队列
		public function push($queue_name,$value) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->lpush($key,$value);
		}
		//出队列
		public function pop($queue_name) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->rpop($key);
		}
		/**
		 * 阻塞式出队列,默认无限阻塞
		 * $queue_name为数组时，排在前面的队列优先处理，都取不到数据时阻塞
		 * @param $queue_name 队列名称
		 * @param $timeout 阻塞时间
		 * @return mixed
		 */
		public function blpop($queue_name,$timeout=0) {
			if( is_array($queue_name) ) {
				$key = [];
				foreach ($queue_name as $name) {
					$key[] = $this->queue_prefix . $name;
				}
			} else {
				$key = $this->queue_prefix . $queue_name;
			}
			return $this->redis->blpop($key,$timeout);
		}

		// 功能与blpop一样，只是出队列的方向不同
		public function brpop($queue_name,$timeout=0) {
			if( is_array($queue_name) ) {
				$key = [];
				foreach ($queue_name as $name) {
					$key[] = $this->queue_prefix . $name;
				}
			} else {
				$key = $this->queue_prefix . $queue_name;
			}
			return $this->redis->blpop($key,$timeout);
		}

		/*
		一个安全的队列，在一个原子时间内，执行以下两个动作
		1)将列表queue_name中的最后一个元素(尾元素)弹出，并返回给客户端。
		2)将queue_name弹出的元素插入到列表queue_save_name，作为queue_save_name列表的的头元素。

		举个例子，你有两个列表 queue_name 和 queue_save_name
		queue_name列表有元素a, b, c
		queue_save_name列表有元素x, y, z
		执行RPOPLPUSH queue_name queue_save_name之后
		queue_name列表包含元素a, b
		queue_save_name列表包含元素c, x, y, z 
		并且元素c被返回
		如果queue_name不存在，值nil被返回，并且不执行其他动作。
		如果queue_name和queue_save_name相同，则列表中的表尾元素被移动到表头，并返回该元素，可以把这种特殊情况视作列表的旋转(rotation)操作。
		*/
		public function rpoplpush($queue_name,$queue_save_name) {
			$key = $this->queue_prefix . $queue_name;
			$key_save = $this->queue_prefix . $queue_save_name;
			return $this->redis->rpoplpush($key,$key_save);
		}

		// rpoplpush的阻塞版
		public function brpoplpush($queue_name,$queue_save_name,$timeout=0) {
			$key = $this->queue_prefix . $queue_name;
			$key_save = $this->queue_prefix . $queue_save_name;
			return $this->redis->brpoplpush($key,$key_save,$timeout);
		}

		public function lrem($queue_name,$count ,$value) {
			$key = $this->queue_prefix . $queue_name;
			return $this->redis->lrem($key,$count,$value);
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