<?php
/*
功能：Redis 缓存/队列封装
说明：
1. 本文件是项目唯一的缓存客户端（原 memcached 已下线），接口刻意与 memcachedModel 对齐：
   set / get / delete / get_all / like / flush，历史代码可平滑迁移
2. set() 第三个参数兼容 memcached 语义：
   - <= 30 天 视为「存活秒数」
   - >  30 天 视为「绝对时间戳」（历史代码中存在 set($k,$v,time()+300) 的写法）
3. 支持数组/对象写入：内部打标记后 serialize，get() 时还原
4. 容错：连接失败、运行中掉线一律返回默认值（false / 空数组），不抛异常、不重试，
   保证缓存故障时页面按「无缓存」降级，而不是 500
*/
namespace cache {
	class redisModel {
		/** 非字符串值的序列化标记，避免与普通字符串混淆 */
		const SER_PREFIX = "\x00ser:";

		public $redis;
		public $queue_prefix = 'rq_';

		public function __construct($arrConfig = []) {
			if( !is_array($arrConfig) ) $arrConfig = array();

			$host    = isset($arrConfig['host'])    ? $arrConfig['host']           : '127.0.0.1';
			$port    = isset($arrConfig['port'])    ? (int)$arrConfig['port']      : 6379;
			$timeout = isset($arrConfig['timeout']) ? (float)$arrConfig['timeout'] : 1.0;

			try {
				$redis = new \Redis();
				// 加 @ 抑制 warning：框架的 error handler 会把 warning 记成错误日志
				if( !@$redis->connect($host, $port, $timeout) ) {
					$this->redis = null;
					return;
				}
				if( !empty($arrConfig['password']) ) $redis->auth($arrConfig['password']);
				if( !empty($arrConfig['database']) ) $redis->select((int)$arrConfig['database']);
				// 真正握手：phpredis 的 connect 是惰性的，不 ping 会拖到第一个命令才报错
				$redis->ping();
				$this->redis = $redis;
			} catch( \Throwable $e ) {
				$this->redis = null;
			}
		}

		/**
		 * 统一执行入口：捕获运行期异常并降级
		 * @param callable $callback 入参为原生 Redis 对象
		 * @param mixed    $default  失败时的返回值
		 */
		private function run($callback, $default = false) {
			if( !$this->redis ) return $default;
			try {
				return $callback($this->redis);
			} catch( \Throwable $e ) {
				// 掉线后本次请求不再重试，避免每个调用点都等一次超时
				$this->redis = null;
				return $default;
			}
		}

		/*---------------------------内部：值编解码---------------------------*/

		private function pack($data) {
			if( is_string($data) ) return $data;
			return self::SER_PREFIX . serialize($data);
		}

		private function unpack($raw) {
			if( !is_string($raw) ) return $raw;
			$len = strlen(self::SER_PREFIX);
			if( strncmp($raw, self::SER_PREFIX, $len) === 0 ) {
				return @unserialize(substr($raw, $len));
			}
			return $raw;
		}

		/** memcached 语义的时间换算：大于 30 天的值按绝对时间戳处理 */
		private function normalizeTtl($expire) {
			$expire = (int)$expire;
			if( $expire > 2592000 ) $expire = $expire - time();
			return $expire > 0 ? $expire : 0;
		}

		private function prefixQueue($queue_name) {
			if( is_array($queue_name) ) {
				$key = array();
				foreach( $queue_name as $name ) $key[] = $this->queue_prefix . $name;
				return $key;
			}
			return $this->queue_prefix . $queue_name;
		}

		/*---------------------------事务操作---------------------------*/

		public function multi()       { return $this->run(function($r) { return $r->multi(); }); }
		public function exec()        { return $this->run(function($r) { return $r->exec(); }); }
		public function discard()     { return $this->run(function($r) { return $r->discard(); }); }

		/*---------------------------简单队列---------------------------*/

		//入队列
		public function push($queue_name,$value) {
			$key = $this->queue_prefix . $queue_name;
			return $this->run(function($r) use ($key,$value) { return $r->lpush($key,$value); });
		}

		//出队列
		public function pop($queue_name) {
			$key = $this->queue_prefix . $queue_name;
			return $this->run(function($r) use ($key) { return $r->rpop($key); });
		}

		/**
		 * 与旧版 redisModel（libs/models/redisModel.php）兼容的入队方法
		 * 注意：不额外加队列前缀，保持历史 key 不变，避免消费端取不到数据
		 */
		public function put($queue_name,$value) {
			return $this->run(function($r) use ($queue_name,$value) { return $r->rpush($queue_name,$value); });
		}

		/**
		 * 阻塞式出队列,默认无限阻塞
		 * $queue_name为数组时，排在前面的队列优先处理，都取不到数据时阻塞
		 */
		public function blpop($queue_name,$timeout=0) {
			$key = $this->prefixQueue($queue_name);
			return $this->run(function($r) use ($key,$timeout) { return $r->blpop($key,$timeout); });
		}

		// 功能与blpop一样，只是出队列的方向不同
		public function brpop($queue_name,$timeout=0) {
			$key = $this->prefixQueue($queue_name);
			return $this->run(function($r) use ($key,$timeout) { return $r->brpop($key,$timeout); });
		}

		// 原子地把 queue_name 尾部元素移到 queue_save_name 头部
		public function rpoplpush($queue_name,$queue_save_name) {
			$k1 = $this->queue_prefix . $queue_name;
			$k2 = $this->queue_prefix . $queue_save_name;
			return $this->run(function($r) use ($k1,$k2) { return $r->rpoplpush($k1,$k2); });
		}

		// rpoplpush的阻塞版
		public function brpoplpush($queue_name,$queue_save_name,$timeout=0) {
			$k1 = $this->queue_prefix . $queue_name;
			$k2 = $this->queue_prefix . $queue_save_name;
			return $this->run(function($r) use ($k1,$k2,$timeout) { return $r->brpoplpush($k1,$k2,$timeout); });
		}

		public function lrem($queue_name,$count,$value) {
			$key = $this->queue_prefix . $queue_name;
			return $this->run(function($r) use ($key,$count,$value) { return $r->lrem($key,$count,$value); });
		}

		//队列长度
		public function len($queue_name) {
			$key = $this->queue_prefix . $queue_name;
			return $this->run(function($r) use ($key) { return $r->llen($key); });
		}

		/*---------------------------哈希表操作---------------------------*/

		public function hset($key,$field,$data)   { return $this->run(function($r) use ($key,$field,$data) { return $r->hset($key,$field,$data); }); }
		public function hsetnx($key,$field,$data) { return $this->run(function($r) use ($key,$field,$data) { return $r->hsetnx($key,$field,$data); }); }
		public function hmset($key,$data)         { return $this->run(function($r) use ($key,$data) { return $r->hmset($key,$data); }); }
		public function hget($key,$field)         { return $this->run(function($r) use ($key,$field) { return $r->hget($key,$field); }); }
		public function hmget($key,$field)        { return $this->run(function($r) use ($key,$field) { return $r->hmget($key,$field); }); }
		public function hgetall($key)             { return $this->run(function($r) use ($key) { return $r->hgetall($key); }, array()); }
		public function hdel($key,$field)         { return $this->run(function($r) use ($key,$field) { return $r->hdel($key,$field); }); }
		public function hlen($key)                { return $this->run(function($r) use ($key) { return $r->hlen($key); }); }
		public function hexists($key,$field)      { return $this->run(function($r) use ($key,$field) { return $r->hexists($key,$field); }); }
		public function hincrby($key,$field,$increment) { return $this->run(function($r) use ($key,$field,$increment) { return $r->hincrby($key,$field,$increment); }); }
		public function hkeys($key)               { return $this->run(function($r) use ($key) { return $r->hkeys($key); }, array()); }
		public function hvals($key)               { return $this->run(function($r) use ($key) { return $r->hvals($key); }, array()); }

		/*---------------------------键/值操作---------------------------*/

		/** 查找符合模式的 key（数据量大时请改用 get_all 的 SCAN 实现） */
		public function keys($key) {
			return $this->run(function($r) use ($key) {
				$arr = $r->keys($key);
				return is_array($arr) ? $arr : array();
			}, array());
		}

		/**
		 * 写缓存（兼容 memcached 的 set 语义）
		 * @param string $key    缓存名称
		 * @param mixed  $data   缓存内容，支持数组/对象
		 * @param int    $expire 存活秒数；> 30 天视为绝对时间戳；0 表示长期有效
		 * @return bool
		 */
		public function set($key,$data,$expire=0) {
			if( !$this->redis ) return false;
			$value  = $this->pack($data);
			$expire = $this->normalizeTtl($expire);
			return $this->run(function($r) use ($key,$value,$expire) {
				if( $expire > 0 ) return $r->setex($key,$expire,$value);
				return $r->set($key,$value);
			});
		}

		/**
		 * 取缓存
		 * @param string $key 缓存名称
		 * @return mixed 未命中返回 false
		 */
		public function get($key) {
			$raw = $this->run(function($r) use ($key) { return $r->get($key); });
			if( $raw === false || $raw === null ) return false;
			return $this->unpack($raw);
		}

		/**
		 * 取所有键值（SCAN 实现，避免 keys * 阻塞主线程）
		 * @return array
		 */
		public function get_all($pattern = '*') {
			if( !$this->redis ) return array();
			$arrData  = array();
			$iterator = null;
			while( true ) {
				$arrKeys = $this->run(function($r) use (&$iterator,$pattern) {
					return $r->scan($iterator,$pattern,500);
				});
				if( is_array($arrKeys) ) {
					foreach( $arrKeys as $key ) $arrData[$key] = $this->get($key);
				}
				if( empty($this->redis) ) break;
				if( $iterator === 0 || $iterator === null ) break;
			}
			return $arrData;
		}

		/**
		 * 模拟 memcached 的 like 匹配
		 * @param string $like 正则
		 * @param string $key  为数组型缓存名时只过滤该键内部
		 */
		public function like($like,$key = '') {
			if( $key !== '' ) {
				$arrTmp = $this->get($key);
				if( !is_array($arrTmp) ) return array();
			} else {
				$arrTmp = $this->get_all();
			}
			$arrData = array();
			foreach( $arrTmp as $k => $data ) {
				if( preg_match($like,$k) ) $arrData[$k] = $data;
			}
			return $arrData;
		}

		// 所有键名
		public function get_all_keys() {
			return $this->keys('*');
		}

		/**
		 * 删除缓存
		 * @return int 实际删除的键数量
		 */
		public function delete($key) {
			return (int)$this->run(function($r) use ($key) { return $r->del($key); }, 0);
		}

		//delete别名
		public function del($key) {
			return $this->delete($key);
		}

		// 键是否存在
		public function exists($key) {
			return (bool)$this->run(function($r) use ($key) { return $r->exists($key); });
		}

		/**
		 * 设置过期时间
		 * @param int $expire 存活秒数
		 */
		public function expire($key,$expire) {
			$expire = $this->normalizeTtl($expire);
			return $this->run(function($r) use ($key,$expire) { return $r->expire($key,$expire); });
		}

		// 剩余存活秒数；-1 永不过期，-2 不存在
		public function ttl($key) {
			return (int)$this->run(function($r) use ($key) { return $r->ttl($key); }, -2);
		}

		// 原子递增
		public function incr($key) {
			return $this->run(function($r) use ($key) { return $r->incr($key); });
		}

		// 原子递减
		public function decr($key) {
			return $this->run(function($r) use ($key) { return $r->decr($key); });
		}

		/**
		 * 清空当前库（等价于 memcached 的 flush）
		 * 注意：只作用于当前 database，不影响其他库
		 */
		public function flush() {
			return $this->run(function($r) { return $r->flushDB(); });
		}
	}
}
