<?php
if( PHP_SAPI != 'cli' ) {
	header('HTTP/1.1 404 Not Found');
	exit;
}
define('SWOOLE_DEAMON',	'swoole_server_mPHP');
define('INDEX_PATH',	__DIR__.'/example/');
define('MPHP_PATH',	realpath(__DIR__.'/../mPHP/').'/');	//框架根目录

class HttpServer{
	public static $instance;
	public static $mPHP;

	public function __construct() {
		$http = new swoole_http_server("0.0.0.0", 8059);
		$http->set(
			array(
				'worker_num' => 1,
				'daemonize' => false,
				'max_request' => 5000,
				'user' => 'apache',
				'group' => 'apache',
			)
		);
		$http->on('Start', array($this, 'onStart'));
		$http->on('WorkerStart', array($this, 'onWorkerStart'));
		$http->on('Request', array($this, 'onRequest'));
		$http->start();
	}

	public function onStart() {
		swoole_set_process_name(SWOOLE_DEAMON);
		$reload = "echo 'Reloading...'\n";
		$reload .= "pid=$(pidof ".SWOOLE_DEAMON.")\n";
		$reload .= "kill -USR1 \"\$pid\"\n";
		$reload .= "echo 'Reloaded'\n";
		file_put_contents(__DIR__.'/reload_'.SWOOLE_DEAMON.'.sh', $reload);
	}

	public function onWorkerStart() {
		include MPHP_PATH.'inc/define.php';
		include MPHP_PATH.'mPHP.php';
		self::$mPHP = mPHP::init();
	}

	public function onRequest($request,$response) {
		if( isset($request->header) ) {
			foreach ($request->header as $key => &$value) {
				$_SERVER[ strtoupper($key) ] = $value;
			}
			if( isset($_SERVER['IF-MODIFIED-SINCE']) ) {
				$_SERVER['HTTP_IF_MODIFIED_SINCE'] = &$_SERVER['IF-MODIFIED-SINCE'];
			}
			unset($request->header);
		}

		if( isset($request->server) ) {
			foreach ($request->server as $key => &$value) {
				$_SERVER[ strtoupper($key) ] = $value;
			}
			unset($request->server);
		}

		if( isset($request->get) ) {
			foreach ($request->get as $key => &$value) {
				$_GET[ $key ] = $value;
			}
			unset($request->get);
		}
		
		if( isset($request->post) ) {
			foreach ($request->post as $key => &$value) {
				$_POST[ $key ] = $value;
			}
			unset($request->post);
		}

		if( isset($request->cookie) ) {
			foreach ($request->cookie as $key => &$value) {
				$_COOKIE[ $key ] = $value;
			}
			unset($request->cookie);
		}

		if( isset($request->files) ) {
			foreach ($request->files as $key => &$value) {
				$_FILES[ $key ] = $value;
			}
			unset($request->files);
		}
		mPHP::$swoole['request'] = $request;
		mPHP::$swoole['response'] = $response;

		ob_start();
		self::$mPHP->run();

		unset($_GET,$_POST,$_SERVER,$_REQUEST);
		$_GET = $_POST = $_SERVER = $_REQUEST = array();

		$result = ob_get_clean();
		$response->end($result);
	}

	public static function init() {
		if (!self::$instance) {
			self::$instance = new HttpServer;
		}
		return self::$instance;
	}
}
HttpServer::init();
