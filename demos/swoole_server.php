<?php
if( PHP_SAPI != 'cli' ) {
	header('HTTP/1.1 404 Not Found');
	exit;
}
define('SWOOLE_DEAMON',	'swoole_server_mPHP');
define('INDEX_PATH',	__DIR__.'/example/');
define('MPHP_PATH',	realpath(__DIR__.'/../').'/');	//框架根目录

class HttpServer{
	public static $instance;
	public static $mPHP;
	public $session;

	public function __construct() {
		$this->http = $http = new swoole_websocket_server("0.0.0.0", 8059);
		$http->set(
			array(
				'worker_num' => 1,//启动的进程数
				'daemonize' => false,//以守护进程方式运行
				'max_request' => 5000,//
				'user' => 'apache',
				'group' => 'apache',
			)
		);

		//http
		$http->on('Start', array($this, 'onStart'));
		$http->on('WorkerStart', array($this, 'onWorkerStart'));
		$http->on('Request', array($this, 'onRequest'));

		//websocket
		// $http->on('open',[$this,'onOpen']);
		$http->on('handshake',[$this,'onHandshake']);
		$http->on('message',[$this,'onMessage']);
		$http->on('close',[$this,'onClose']);
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
		// include MPHP_PATH.'inc/functions.php';
		include MPHP_PATH.'mPHP.php';
		self::$mPHP = mPHP::init();
	}

	public  function initRequest($request) {
		if( isset($request->header) ) {
			foreach ($request->header as $key => &$value) {
				$_SERVER[ strtoupper($key) ] = $value;
			}
			if( isset($_SERVER['IF-MODIFIED-SINCE']) ) {
				$_SERVER['HTTP_IF_MODIFIED_SINCE'] = &$_SERVER['IF-MODIFIED-SINCE'];
			}
			// unset($request->header);
		}

		if( isset($request->server) ) {
			foreach ($request->server as $key => &$value) {
				$_SERVER[ strtoupper($key) ] = $value;
			}
			// unset($request->server);
		}

		if( isset($request->get) ) {
			foreach ($request->get as $key => &$value) {
				$_GET[ $key ] = $value;
			}
			// unset($request->get);
		}
		
		if( isset($request->post) ) {
			foreach ($request->post as $key => &$value) {
				$_POST[ $key ] = $value;
			}
			// unset($request->post);
		}

		if( isset($request->cookie) ) {
			foreach ($request->cookie as $key => &$value) {
				$_COOKIE[ $key ] = $value;
			}
			// unset($request->cookie);
		}

		if( isset($request->files) ) {
			foreach ($request->files as $key => &$value) {
				$_FILES[ $key ] = $value;
			}
			// unset($request->files);
		}
	}

	public function onRequest($request,$response) {
		if( $request->server['path_info'] == '/favicon.ico' ) {
			$response->status(404);
			$response->end();
			return false;//谷歌浏览器会试图获取favicon.ico文件
		} 

		$this->initRequest($request);

		mPHP::$swoole['request'] = $request;
		mPHP::$swoole['response'] = $response;

		ob_start();
		self::$mPHP->run();

		unset($_GET,$_POST,$_SERVER,$_REQUEST);
		$_GET = $_POST = $_SERVER = $_REQUEST = array();

		$result = ob_get_clean();
		$response->end($result);
	}

	 //用户接入
	public function onOpen( $server, $request) {
	}

	//WebSocket建立连接后进行握手
	public function onHandshake($request, $response) {
		$this->initRequest($request);

		//自定定握手规则，没有设置则用系统内置的（只支持version:13的） 
		if (!isset($request->header['sec-websocket-key'])) { 
			//'Bad protocol implementation: it is not RFC6455.' 
			$response->end();
			return false;
		}

		if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
			|| 16 !== strlen(base64_decode($request->header['sec-websocket-key'])) ) {
			//Header Sec-WebSocket-Key is illegal;
			$response->end();
			return false;
		}

		mPHP::$swoole['request'] = $request;
		mPHP::$swoole['response'] = $response;

		$key = base64_encode( sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		$headers = [
			'Upgrade'			=> 'websocket',
			'Connection'		=> 'Upgrade',
			'Sec-WebSocket-Accept'  => $key,
			'Sec-WebSocket-Version' => '13',
			'KeepAlive'			=> 'off',
			'Server'				=> 'mPHP for swoole'
		];

		foreach ($headers as $key => $val) {
			mPHP::header($key, $val);
		}

		mPHP::status(101);
		$this->session = new sessionModel();
		$this->session->start();
		$response->end();
		return true; 
	}
	//WebSocket接收消息
	public function onMessage( $server, $frame) {
		$this->http->close($frame->fd);
		// return;
		$_SESSION['a'] = isset( $_SESSION['a'] ) ? ++$_SESSION['a'] : 0;
		$this->s = isset( $this->s ) ? ++$this->s : 0;
		$result = $server->push($frame->fd, $this->s);
	}
	//WebSocket连接关闭
	public function onClose( $server, $fd) {
		echo "client {$fd} closed\n"; 
	}

	public static function init() {
		if (!self::$instance) {
			self::$instance = new HttpServer;
		}
		return self::$instance;
	}
}
HttpServer::init();
