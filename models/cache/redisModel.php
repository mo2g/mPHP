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

		/*---------------------------事务操作---------------------------*/
		/*
		标记一个事务块的开始。
		返回值：
			总是返回 OK 。
		*/
		public function multi() {
			return $this->redis->multi();
		}
		/*
		执行所有事务块内的命令。
		返回值：
			事务块内所有命令的返回值，按命令执行的先后顺序排列。
			当操作被打断时，返回空值 nil 。
		*/
		public function exec() {
			return $this->redis->exec();
		}
		/*
		取消事务，放弃执行事务块内的所有命令。
		返回值：
			总是返回 OK 。
		*/
		public function discard() {
			return $this->redis->discard();
		}
		/*---------------------------事务操作---------------------------*/
		

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
			return $this->redis->brpop($key,$timeout);
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

		/*
		$data = $redis->brpoplpush('queue_name','queue_save_name'));  
		print_r($data);
		//逻辑操作
		$redis->lrem($queue_save_name,$data);//最后移除数据
		*/
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

		/*---------------------------哈希表操作---------------------------*/

		/*
		将哈希表key中的域field的值设为value。
		返回值：
			如果field是哈希表中的一个新建域，并且值设置成功，返回1。
			如果哈希表中域field已经存在且旧值已被新值覆盖，返回0。
		*/
		public function hset($key,$field,$data) {
			return $this->redis->hset($key,$field,$data);
		}

		/*
		将哈希表key中的域field的值设置为value，当且仅当域field不存在。
		返回值：
			设置成功，返回1。
			如果给定域已经存在且没有操作被执行，返回0。
		*/
		public function hsetnx($key,$field,$data) {
			return $this->redis->hsetnx($key,$field,$data);
		}

		/*
		data = [
			'field' => value,
			'field' => value
			'field' => value
		];
		同时将多个field - value(域-值)对设置到哈希表key中。
		此命令会覆盖哈希表中已存在的域。
		如果key不存在，一个空哈希表被创建并执行HMSET操作。
		返回值：
			如果命令执行成功，返回OK。
			当key不是哈希表(hash)类型时，返回一个错误。
		*/
		public function hmset($key,$data) {
			return $this->redis->hmset($key,$data);
		}

		/*
		返回哈希表key中给定域field的值。
		返回值：
			给定域的值。
			当给定域不存在或是给定key不存在时，返回nil。
		*/
		public function hget($key,$field) {
			return $this->redis->hget($key,$field);
		}

		/*
		返回哈希表key中，一个或多个给定域的值。
		返回值：
			如果给定的域不存在于哈希表，那么返回一个nil值。
			一个包含多个给定域的关联值的表，表值的排列顺序和给定域参数的请求顺序一样。
		*/
		public function hmget($key,$field) {
			return $this->redis->hmget($key,$field);
		}

		/*
		返回哈希表key中，所有的域和值。
		返回值：
			以列表形式返回哈希表的域和域的值。 若key不存在，返回空列表。
		*/
		public function hgetall($key) {
			return $this->redis->hgetall($key);
		}

		/*
		删除哈希表key中的一个或多个指定域，不存在的域将被忽略。
		返回值:
			被成功移除的域的数量，不包括被忽略的域。
		*/
		public function hdel($key,$field) {
			return $this->redis->hdel($key,$field);
		}

		/*
		返回哈希表key中域的数量。
		返回值：
			哈希表中域的数量。
			当key不存在时，返回0。
		*/
		public function hlen($key) {
			return $this->redis->hlen($key);
		}

		/*
		查看哈希表key中，给定域field是否存在。
		返回值:
			如果哈希表含有给定域，返回1。
			如果哈希表不含有给定域，或key不存在，返回0。
		*/
		public function hexists($key,$field) {
			return $this->redis->hexists($key,$field);
		}

		/*
		为哈希表key中的域field的值加上增量increment。
		增量也可以为负数，相当于对给定域进行减法操作。
		如果key不存在，一个新的哈希表被创建并执行HINCRBY命令。
		如果域field不存在，那么在执行命令前，域的值被初始化为0。
		对一个储存字符串值的域field执行HINCRBY命令将造成一个错误。
		本操作的值限制在64位(bit)有符号数字表示之内。
		返回值:
			执行HINCRBY命令之后，哈希表key中域field的值。
		*/
		// public function hincrby($key,$data) {
		// 	return $this->redis->hincrby($key,$field,$increment);
		// }
		public function hincrby($key,$field,$increment) {
			return $this->redis->hincrby($key,$field,$increment);
		}

		/*
		返回哈希表key中的所有域。
		返回值：
			一个包含哈希表中所有域的表。
			当key不存在时，返回一个空表。
		*/
		public function hkeys($key) {
			return $this->redis->hkeys($key);
		}

		/*
		返回哈希表key中的所有值。
		返回值：
			一个包含哈希表中所有值的表。
			当key不存在时，返回一个空表。
		*/
		public function hvals($key) {
			return $this->redis->hvals($key);
		}
		/*---------------------------哈希表操作---------------------------*/


		/*---------------------------键/值操作---------------------------*/

		/*
		查找所有符合给定模式 pattern 的 key 。
		返回值：
			符合给定模式的 key 列表。
		*/
		public function keys($key) {
			return $this->redis->keys($key);
		}

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