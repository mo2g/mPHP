<?php
/*
作者:moyancheng
创建时间:2015-08-27
最后更新时间:2015-10-28

功能：因为swoole运行机制，导致原生PHP SESSION用法失效，所以写了一个简单通用的session管理模块
使用方式
$session = new sessionModel();
$session->start();

swoole拓展需要手动调用 $session->save() 保存修改后的session
单纯使用mPHP框架，SESSION操作与原生PHP一致
暂时不支持原生PHP调用swoole的SESSION，反之亦然

*/
ini_set('session.serialize_handler', 'php_serialize');//PHP >= 5.5.32
class sessionModel {
	public $sid;
	public $save_path;
	public $cookie_lifetime = 86400;
	public $prefix = 'sess_';
	public $session = [];
	public $mktime = 0;

	public $cache;//存储方式，默认为文件存储

	public function __construct($cache = null) {
		$this->cache = $cache;
		$this->save_path = session_save_path();
		$this->save_path = empty($this->save_path) ? '/var/lib/php/session' : $this->save_path;
	}

	public function start($sessid_init = false) {
		$sessionName = isset(mPHP::$CFG['session_name']) ? mPHP::$CFG['session_name'] :  'PHPSESSID';

		if( !empty($_GET[$sessionName]) ) {
			$sessid = $_GET[$sessionName];
		}
		$sessid = isset( $_COOKIE[$sessionName] ) ? $_COOKIE[$sessionName] : false;
		$sessid = $sessid_init ? $sessid_init : $sessid;

		if( true || mPHP::$swoole ) {
			if( $sessid === false ) {
				//SESSION_ID存入cookie
				//SESSION = md5( 客户端IP + 微妙时间戳 + 随机数)
				$sessid = md5($_SERVER['REMOTE_ADDR'].microtime(1).rand(111111,999999));
				mPHP::$swoole['response']->cookie($sessionName,$sessid,time() +$this->cookie_lifetime,'/');
			}
			$this->sid = $sessid;
			self::get();
		} else {
			session_name($sessionName);
			if( isset($sessid) ) session_id($sessid);
			if( !isset($_SESSION) ) session_start();
		}
	}

	//获取SESSION
	public function get() {
		 $data = 'a:0:{}';
		if( $this->cache ) {
			$key = $this->prefix . $this->sid;
			$data = $this->cache->get($key);
		} else {
			$file = $this->save_path . '/' . $this->prefix . $this->sid;
			if( file_exists($file) ) {
				$this->mktime = filemtime($file);//session文件最后修改时间
				if(  $this->mktime + $this->cookie_lifetime >= time() ) {//判断session是否过期
					$data = file_get_contents($file);
				}
			} else {
				$this->mktime = time();
			}
		}
		$this->session = $_SESSION = unserialize($data);
	}

	//保存SESSION
	public function save($timeout=0) {
		if( $this->cache ) {
			$key = $this->prefix . $this->sid;
			$time = $timeout ? $timeout + time() : 0;
			$this->cache->set($key,serialize($_SESSION),$time);
		} else {
			$time = time();
			if( $_SESSION != $this->session || ($time - $this->mktime) > 60 ) {
				//$_SESSION 有变化或者每过60秒更新  
				$file = $this->save_path . '/' . $this->prefix . $this->sid;
				return file_put_contents($file, serialize($_SESSION), LOCK_EX);
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
		if( mPHP::$swoole ) {
			$this->save($this->cookie_lifetime);
		}
	}
}