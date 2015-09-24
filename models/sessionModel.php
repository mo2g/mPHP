<?php
/*
作者:moyancheng
创建时间:2015-08-27
最后更新时间:2015-08-28

功能：因为swoole运行机制，导致原生PHP SESSION用法失效，所以写了一个简单通用的session管理模块
*/
class sessionModel {
	public $sid;
	public $save_path;
	public $cookie_lifetime = 86400;
	public $prefix = 'mphpsess_';
	public $session = [];
	public $mktime = 0;

	public $cache;

	public function __construct($cache = null) {
		$this->cache = $cache;
		$this->save_path = session_save_path();
	}

	public function start($sessid = false) {
		$sessionName = empty(mPHP::$CFG['session_name']) ? 'MPHPSESSID' : mPHP::$CFG['session_name'];
		session_name($sessionName);

		if( !empty($_GET[$sessionName]) ) {
			$sessid = $_GET[$sessionName];
		}

		if( mPHP::$swoole ) {
			$sessid = isset( $_COOKIE[$sessionName] ) ? $_COOKIE[$sessionName] : false;
			if( $sessid === false ) {
				//SESSION_ID存入cookie
				//SESSION = md5( 客户端IP + 微妙时间戳 + 随机数)
				$sessid = md5($_SERVER['REMOTE_ADDR'].microtime(1).rand(111111,999999));
				mPHP::$swoole['response']->cookie($sessionName,$sessid,time() +$this->cookie_lifetime,'/');
			}
			$this->sid = $sessid;
			$this->session = $_SESSION = self::get();
		} else {
			if( $sessid ) session_id($sessid);
			if( !isset($_SESSION) ) session_start();
		}
	}

	//获取SESSION
	public function get() {
		if( $this->cache ) {
			$key = $this->prefix . $this->sid;
			$data = $this->cache->get($key);
			return $data ? $data : [];
		} else {
			$this->mktime = 0;
			$file = $this->save_path . '/' . $this->prefix . $this->sid;
			if( file_exists($file) ) $data = unserialize(file_get_contents($file));
			if( empty($data) ) {
				return [];
			} elseif( $data['timeout'] != 0 && ($data['mktime'] + $data['timeout']) < time() ) {
				// $this->delete();
				return [];
			} else {
				$this->mktime = $data['mktime'];
				return $data['value'];
			}
		}
	}

	//保存SESSION
	public function save($timeout=0) {
		if( $this->cache ) {
			$key = $this->prefix . $this->sid;
			$data = serialize($_SESSION);
			$time = $timeout ? $timeout + time() : 0;
			$this->cache->set($key,$_SESSION,$time);
		} else {
			$time = time();
			if( $_SESSION != $this->session || ($time - $this->mktime) > 60  ) {
				//$_SESSION 有变化时更新，每过60秒更新  
				$data['value'] = $_SESSION;
				$data['timeout'] = $timeout;
				$data['mktime'] = $time;
				$file = $this->save_path . '/' . $this->prefix . $this->sid;
				return file_put_contents($file, serialize($data), LOCK_EX);
			} 
		}
	}

	public function delete() {
		if( $this->cache ) {

		} else {
			$file = $this->save_path . '/' . $this->prefix . $this->sid;
			if( file_exists($file) ) unlink($file);
		}
	}

	public function __destruct() {
		$this->save($this->cookie_lifetime);
	}
}