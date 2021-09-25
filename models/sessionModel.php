<?php
/*
作者:moyancheng
创建时间:2015-08-27
最后更新时间:2016-05-24

功能：因为swoole运行机制，导致原生PHP SESSION用法失效，所以写了一个简单通用的session管理模块
使用方式
$session = new sessionModel();
$session->start();

swoole拓展需要手动调用 $session->save() 保存修改后的session
单纯使用mPHP框架，SESSION操作与原生PHP一致
暂时不支持原生PHP调用swoole的SESSION，反之亦然
-------------------
PHP版本>=5.532，可以正常使用原生PHP操作session

*/
ini_set('session.serialize_handler', 'php_serialize');//PHP >= 5.5.32
class sessionModel {
	public $sid;
	public $save_path;
	public $cookie_lifetime = 86400;
	public $time_session_id = 120;//session id更新时间间隔
	public $prefix = 'sess_';
	public $renamed_sessionid = false;//是否启用定时更新sessionid，默认禁用

	public $session = [];

	public $cache;//存储方式，默认为文件存储

	public function __construct($cache = null) {
		$this->cache = $cache;
		$this->save_path = session_save_path();
		$this->save_path = empty($this->save_path) ? '/var/lib/php/session' : $this->save_path;
	}

	public function setRenamedSessionid($status = false) {
		$this->renamed_sessionid = $status;
	}

	public function start($sessid_init = false,$path = '/') {
		$sessionName = isset(mPHP::$CFG['session_name']) ? mPHP::$CFG['session_name'] :  'PHPSESSID';

		if( !empty($_GET[$sessionName]) ) {
			$sessid = $_GET[$sessionName];
		}
		$sessid = isset( $_COOKIE[$sessionName] ) ? $_COOKIE[$sessionName] : false;
		$sessid = $sessid_init ? $sessid_init : $sessid;

		if( mPHP::$swoole ) {
			if( $sessid === false ) {
				$sessid = md5($_SERVER['REMOTE_ADDR'].microtime(1).rand(111111,999999));//SESSION_ID = md5( 客户端IP + 微妙时间戳 + 随机数)
			}
			$this->sid = $sessid;
			self::get();

			//定时更新sessionid，增大劫持难度
			if( !isset($_SESSION['createtime']) ) {
				$_SESSION['createtime'] = time();
			} elseif($_SESSION['createtime'] + $this->time_session_id <= time() ) {
				$file = $this->save_path . '/' . $this->prefix . $sessid;
				if( is_file($file) ) unlink($file);//删除旧SESSION缓存文件
				$_SESSION['createtime'] = time();
				if( $this->renamed_sessionid ) {
					$this->sid = $sessid = md5($_SERVER['REMOTE_ADDR'].microtime(1).rand(111111,999999));
				}
			}
			mPHP::$swoole['response']->cookie($sessionName,$sessid,time() +$this->cookie_lifetime,$path);//SESSION_ID存入cookie
		} else {
			session_name($sessionName);
			if( isset($sessid) ) session_id($sessid);
			if( !isset($_SESSION) ) session_start();

			//定时更新sessionid，增大劫持难度
			if( !isset($_SESSION['createtime']) ) {
				$_SESSION['createtime'] = time();
			} elseif($_SESSION['createtime'] + $this->time_session_id <= time() ) {
				$_SESSION['createtime'] = time();
				session_regenerate_id(true);
			}
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
				$mktime = filemtime($file);//session文件最后修改时间
				if(  $mktime + $this->cookie_lifetime >= time() ) {//判断session是否过期
					$data = file_get_contents($file);
				}
			}
		}
		$this->session = $_SESSION = unserialize($data);
	}

	//保存SESSION
	public function save($timeout = 0) {
		if( $this->cache ) {
			$key = $this->prefix . $this->sid;
			$time = $timeout ? $timeout + time() : 0;
			$this->cache->set($key,serialize($_SESSION),$time);
		} else {
			$time = time();
			$file = $this->save_path . '/' . $this->prefix . $this->sid;
			if( $_SESSION != $this->session ) {
				return file_put_contents($file, serialize($_SESSION), LOCK_EX);//$_SESSION 有变化则更新$_SESSION缓存文件
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

	//获取/设置当前会话名称
	public function sessionName($sessionName = false) {
		if( $sessionName === false ) {
			if( mPHP::$swoole ) {
				$sessionName = isset(mPHP::$CFG['session_name']) ? mPHP::$CFG['session_name'] :  'MPHPSESSID';
			} else {
				$sessionName = isset(mPHP::$CFG['session_name']) ? mPHP::$CFG['session_name'] :  'PHPSESSID';
			}
		}
		$this->sessionName = $sessionName;
		return $sessionName;
	}

	public function __destruct() {
		if( mPHP::$swoole ) {
			$this->save($this->cookie_lifetime);
		}
	}
}