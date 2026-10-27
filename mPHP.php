<?php
/*
作者:moyancheng
创建时间:2012-03-01
最后更新时间:2015-07-05
*/

global $CFG;
$CFG['start_time']	= microtime(1);//开始运行时间
$CFG['db']['count']	= 				//数据库操作总数
	$CFG['db']['select']['totle'] =	//数据库查找总数
	$CFG['db']['select']['error'] =	//数据库查找出错f总数
	$CFG['db']['insert']['totle'] =	//数据库插入总数
	$CFG['db']['insert']['error'] =	//数据库插入出错总数
	$CFG['db']['update']['totle'] =	//数据库更新总数
	$CFG['db']['update']['error'] =	//数据库更新出错总数
	$CFG['db']['delete']['totle'] =	//数据库删除总数
	$CFG['db']['delete']['error'] = 0;	//数据库删除出错总数
isset( $CFG['template']['tag_left'] ) or $CFG['template']['tag_left'] = '<!--#';		/* 模版标签，编译后替换为：<?php */
isset( $CFG['template']['tag_right'] ) or $CFG['template']['tag_right'] = '#-->';	/* 模版标签，编译后替换为：?> 	*/

//定义关键文件夹的绝对路径,提高include文件效率
defined('LIBS_PATH') or define('LIBS_PATH',							INDEX_PATH.'libs/');		//库目录
defined('MPHP_PATH') or define('MPHP_PATH',						__DIR__.'/');	//框架根目录
defined('CACHE_PATH') or define('CACHE_PATH',						LIBS_PATH.'cache/');				//缓存目录
defined('CACHE_HTML_PATH') or define('CACHE_HTML_PATH',		CACHE_PATH.'html/');				//html缓存目录
defined('TPL_C_PATH') or define('TPL_C_PATH',							CACHE_PATH.'tpl_c/');				//模版编译目录
defined('CONTROLLERS_ADMIN') or define('CONTROLLERS_ADMIN',	INDEX_PATH.'admin/libs/controllers/');	//控制器目录

defined('CONTROLLERS_PATH') or define('CONTROLLERS_PATH',		LIBS_PATH.'controllers/');	//控制器目录
defined('MODELS_PATH') or define('MODELS_PATH',					LIBS_PATH.'models/');		//model目录
defined('DAOS_PATH') or define('DAOS_PATH',						LIBS_PATH.'daos/');			//dao目录
defined('SERVICES_PATH') or define('SERVICES_PATH',					LIBS_PATH.'services/');		//services目录
defined('TPL_PATH') or define('TPL_PATH',								LIBS_PATH.'tpl/');			//模版目录
defined('LOG_PATH') or define('LOG_PATH',								LIBS_PATH.'logs/');			//日志目录

defined('STATIC_PATH') or define('STATIC_PATH',						WEB_PATH.'static/');				//静态目录
defined('TPL_MPHP_PATH') or define('TPL_MPHP_PATH',				MPHP_PATH.'tpl/');					//mPHP模版目录

if( !defined('STATIC_URL') && isset($_SERVER['SERVER_NAME']) ) {
    $dir = isset( $_SERVER['SCRIPT_NAME'] ) ? dirname($_SERVER['SCRIPT_NAME']) : '';
    if( empty($dir) ) {
        $dir = isset( $_SERVER['REQUEST_URI'] ) ? dirname($_SERVER['REQUEST_URI']) : '';
    }
    if( empty($dir) ) {
        $dir = isset( $_SERVER['DOCUMENT_URI'] ) ? dirname($_SERVER['DOCUMENT_URI']) : '';
    }
	define('STATIC_URL',		"//{$_SERVER['SERVER_NAME']}{$dir}/static/");//静态目录 URL
	defined('JS_URL') or define('JS_URL',				STATIC_URL.'js/');	//js脚本 URL
	defined('CSS_URL') or define('CSS_URL',			STATIC_URL.'css/');	//样式 URL
	defined('IMAGES_URL') or define('IMAGES_URL',	STATIC_URL.'images/');//图片 URL
}

defined('MODELS_MPHP') or define('MODELS_MPHP'			,MPHP_PATH.'models/');

//核心类
class mPHP {
	public static $mPHP = false;
	public static $CFG = false;
	public static $swoole = false;
	public static $debug = false;
	public static $view = false;
	public static $response = false;
	public static $exit = false;
	public static $is_mobile = false;
	public static $db = false;
	public static $pool = false;
	public static $include_file_lists = array();
	public static $log_buffers = array();
	public static $log_buffer_size = 0;
	public static $log_shutdown_registered = false;
	public static $log_writing = false;
	public static $request_id = false;
	
	private function __construct() {
        self::inc(MPHP_PATH.'inc/functions.php');
        self::inc(MPHP_PATH.'inc/response.php');
        if(!self::$response) self::$response = new Response();
        mError::register();
		if(!self::$view) self::$view = new view();
		if(!self::$CFG) self::$CFG = $GLOBALS['CFG'];
		if(!self::$debug) self::$debug = isset(self::$CFG['debug']) ? self::$CFG['debug'] : true;
		self::inc(MPHP_PATH.'inc/sqlProfiler.php');
		sqlProfiler::boot(self::$CFG);
		spl_autoload_register([__CLASS__,'autoLoader']);
		router::init();
	}
	
	public function run() {
		safe::safeGPC();
		$cache = router::run();
		if( $cache ) return;
		$controller	= isset($_GET['c']) ? "{$_GET['c']}Controller" : 'indexController';
		$action		= isset($_GET['a']) ? "{$_GET['a']}Action"  : 'indexAction';

		if( method_exists($controller,$action) ) {
			$controller = new $controller;
			call_user_func(array($controller,$action));
		} else {
			throw new NotFoundException("Action 不存在：{$controller}::{$action}");
		}
	}

	/*
	功能：$obj = new newClass();	//自动加载特定的php文件，省去繁琐的include
	*/
	public static function autoLoader($className) {
		$file = strtr($className,array('\\' => '/')) . '.php';

		if( substr($file,-14) == 'Controller.php' ) {
			if( is_file(CONTROLLERS_PATH.$file) ) {
				include CONTROLLERS_PATH.$file;
			} else {
				throw new NotFoundException("控制器不存在：{$file}");
			}
		} elseif(substr($file,-11) == 'Service.php') {
			if( is_file(SERVICES_PATH.$file) ) {
				include SERVICES_PATH.$file;
			} else {
				throw new HttpException(500, "service 模块不存在：{$file}");
			}
		} elseif(substr($file,-7) == 'Dao.php') {
			if( is_file(DAOS_PATH.$file) ) {
				include DAOS_PATH.$file;
			} else {
				throw new HttpException(500, "dao 模块不存在：{$file}");
			}
		} elseif( substr($file,-9) == 'Model.php' ) {
			if( is_file(MODELS_MPHP.$file) ) {
				include MODELS_MPHP.$file;
			} elseif( is_file(MODELS_PATH.$file) ) {
				include MODELS_PATH.$file;
			} else {
				throw new HttpException(500, "model 模块不存在：{$file}");
			}
		} elseif( is_file(INDEX_PATH.'libs/exts/class/'.$file) ) {
			include INDEX_PATH.'libs/exts/class/'.$file;//加载外部引用类
		} else {
            self::error('访问错误！', "未定义操作 $file");
			return false;
		}
	}

	public static function error($title = '',$msg = '') {
		$logData = array(
			'time' => date('c'),
			'level' => 'ERROR',
			'error_type' => 'FRAMEWORK_ERROR',
			'message' => $title . ' ' . $msg,
			'context' => self::logContext(),
		);
		self::log('ERROR', "{$title}: {$msg}", $logData);
		self::flushLogs();

		throw new HttpException(503, $title . ($msg !== '' ? ' ' . $msg : ''));
	}

	/**
	 * 把 HTTP 异常渲染成页面（由入口统一调用）
	 */
	public static function renderHttpException($e) {
		self::status($e->status);
		if( !empty($e->headers) ) {
			foreach($e->headers as $key => $value) self::header($key, $value);
		}

		if( !function_exists('render_error_page') ) {
			$file = INDEX_PATH . 'libs/exts/function/plus.php';
			if( is_file($file) ) include_once $file;
		}
		if( function_exists('render_error_page') ) {
			render_error_page($e);
			return;
		}

		echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . (int)$e->status . '</title></head>';
		echo '<body><h1>' . (int)$e->status . '</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p></body></html>';
	}

	/**
	 * 未捕获异常：记日志 + 500
	 */
	public static function handleThrowable($e) {
		$logData = array(
			'time' => date('c'),
			'level' => 'ERROR',
			'error_type' => get_class($e),
			'message' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'context' => self::logContext(),
		);
		self::log('ERROR', get_class($e) . ': ' . $e->getMessage(), $logData);
		self::flushLogs();

		self::status(500);
		if( self::$debug ) {
			echo '<pre style="padding:20px;font:13px/1.6 monospace;white-space:pre-wrap">';
			echo htmlspecialchars(get_class($e) . ': ' . $e->getMessage(), ENT_QUOTES, 'UTF-8'), "\n\n";
			echo htmlspecialchars($e->getFile() . ':' . $e->getLine(), ENT_QUOTES, 'UTF-8'), "\n\n";
			echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
			echo '</pre>';
		} else {
			echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>500</title></head><body><h1>500</h1></body></html>';
		}
	}

	//同一个文件，只加载一次
	public static function inc($filename) {
		isset(self::$include_file_lists[$filename]) or ( (self::$include_file_lists[$filename] = true) and include $filename);  
	}

	/*
	swoole中不允许使用exit，所以使用如下方式记录PHP是否执行过  _exit()
	已经执行返回：true
	没有执行返回：false
	*/
	public static function _exit($true = 0) {
		self::$exit = true;
		throw new StopException();
	}

	/*
	 * 响应头写入 Response 对象，不再直接调用 header()
	 */
	public static function header($key,$value) {
		if( self::$response ) self::$response->setHeader($key,$value);
	}

	public static function setcookie($name,$value,$expire=0,$path='/',$domain='',$secure=false,$httponly=false) {
		if( self::$response ) self::$response->setCookie($name,$value,$expire,$path,$domain,$secure,$httponly);
	}

	/*
	 * 跳转：抛异常由入口统一处理，兼容常驻运行时
	 */
	public static function redirect($url,$status=302) {
		throw new RedirectException($url,$status);
	}

	public static function status($http_status_code) {
		if( self::$response ) self::$response->setStatus($http_status_code);
	}

	public static function init() {
		if( !self::$mPHP ) self::$mPHP = new mPHP();
		return self::$mPHP;
	}

	/*
	 * 请求级重置：常驻运行时（Swoole/Webman）在两次请求之间调用
	 */
	public static function reset() {
		self::$view = new view();
		self::$response = new Response();
		self::$is_mobile = false;
		self::$request_id = false;
		self::$exit = false;
		router::$path_info = '';
		if( class_exists('container', false) ) container::reset();
		if( class_exists('sqlProfiler', false) ) sqlProfiler::reset();
	}
	
	public static function initSite() {
		initModel::initMainDir();
		initModel::initDb();
	}

	public static function requestId() {
		if( self::$request_id !== false ) return self::$request_id;

		$rid = '';
		if( !empty($_SERVER['HTTP_X_REQUEST_ID']) ) {
			$rid = (string)$_SERVER['HTTP_X_REQUEST_ID'];
		} elseif( !empty($_SERVER['UNIQUE_ID']) ) {
			$rid = (string)$_SERVER['UNIQUE_ID'];
		}
		if( $rid !== '' ) {
			$rid = preg_replace('/[^a-zA-Z0-9_\-:.]/', '', $rid);
			$rid = substr($rid, 0, 64);
		}
		if( $rid === '' ) {
			if( function_exists('random_bytes') ) {
				$rid = bin2hex(random_bytes(8));
			} else {
				$rid = substr(md5(uniqid((string)mt_rand(), true)),0,16);
			}
		}
		self::$request_id = $rid;
		return self::$request_id;
	}

	public static function logContext() {
		return array(
			'request_id' => self::requestId(),
			'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
			'uri' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
			'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
			'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'pid' => function_exists('getmypid') ? getmypid() : 0,
		);
	}

    public static function log($level, $message, $context = array()) {
		if( !defined('LOG_PATH') ) return false;
		$level = strtoupper(trim((string)$level));
		if( $level == 'WARN' ) $level = 'WARNING';
		if( !isset(mError::$_levels[$level]) ) $level = 'INFO';

		if( is_array($message) || is_object($message) ) {
			$message = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		$message = trim(strtr((string)$message, array("\r\n" => "\n", "\r" => "\n")), "\n");
		if( $message === '' ) return true;
		if( !is_array($context) ) $context = array('extra' => $context);

		if( !self::$log_shutdown_registered ) {
			self::$log_shutdown_registered = true;
			register_shutdown_function([__CLASS__, 'flushLogs']);
		}
		if( !is_dir(LOG_PATH) ) @mkdir(LOG_PATH,0755,true);

		$date = date('Y-m-d');
		$filepath = LOG_PATH ."{$level}-{$date}.log";
		$request_id = self::requestId();
		$line = date('Y-m-d H:i:s') . " [{$level}] [rid={$request_id}] {$message}";
		if( !empty($context) ) {
			$line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		$line .= "\n";
		if( !isset(self::$log_buffers[$filepath]) ) self::$log_buffers[$filepath] = '';
		self::$log_buffers[$filepath] .= $line;
		self::$log_buffer_size += strlen($line);

		$max = isset(self::$CFG['log_buffer_size']) ? (int)self::$CFG['log_buffer_size'] : 65536;
		if( $max < 1024 ) $max = 1024;
		if( self::$log_buffer_size >= $max || $level == 'ERROR' || $level == 'CRITICAL' ) {
			self::flushLogs();
		}
		return true;
    }

	public static function flushLogs() {
		if( self::$log_writing ) return false;
		if( empty(self::$log_buffers) ) return true;

		self::$log_writing = true;
		if( !is_dir(LOG_PATH) ) @mkdir(LOG_PATH,0755,true);
		foreach(self::$log_buffers as $filepath => $content) {
			if( $content === '' ) continue;
			@file_put_contents($filepath,$content,FILE_APPEND | LOCK_EX);
		}
		self::$log_buffers = array();
		self::$log_buffer_size = 0;
		self::$log_writing = false;
		return true;
	}
	
}

//简单路由
class router {
	public static $controller = 'index';
	public static $action = 'index';
	public static $path_info = '';
	public static $table = false;

	public static function init() {
		// 路由表已废弃：nginx 负责静态文件，控制器负责路由，无需再维护索引
	}

	public static function run() {
		$cache = self::path_info();

		if( $cache === -1 ) {
		} else if( $cache === 0 ) {
			return true;//已缓存
		} else if( $cache === 1 ) {
		} else if( $cache === 2 ) {
		} else if( $cache === 3 ) {
		}
		// $_REQUEST = array_merge($_GET, $_REQUEST);
		return false;//未缓存
	}

	public static function path_info() {
		$path_info = '';
		if( !empty( $_SERVER['PATH_INFO'] ) ) {
			$path_info = $_SERVER['PATH_INFO'];
		} else if( !empty( $_SERVER['REQUEST_URI'] ) ) {
			$path_info = $_SERVER['REQUEST_URI'];
		} else if( !empty( $_SERVER['QUERY_STRING'] ) ) {
			$path_info = $_SERVER['QUERY_STRING'];
		} else if( !empty( $_SERVER['argv'][1] ) ) {
			$path_info = $_SERVER['argv'][1];
		}

		if( empty($path_info) ) return -1;

		$path_info = preg_replace('#^/\w+\.php#', '/', $path_info);
		$path_only = $path_info;
		if( strpos($path_only, '?') !== false ) {
			$path_only = parse_url($path_only, PHP_URL_PATH);
			if( $path_only === null ) $path_only = $path_info;
		}
		self::$path_info = $path_only;
		mPHP::$is_mobile = mobileModel::is_mobile();

		// 使用 /?c=index&a=index 方式访问
		$query = '';
		if( strpos($path_info, '?') !== false ) {
			$query = parse_url($path_info, PHP_URL_QUERY);
		} elseif( strpos($path_info, '=') !== false ) {
			$query = $path_info;
		}
		if( $query !== null && $query !== '' ) {
			parse_str($query,$_get);
			// 只有 query 里真的带了 c / a 才走「查询串路由」，
			// 否则 /search?wd=php 会被入口处的默认 c=blog 抢先短路
			if( !empty($_get['c']) || !empty($_get['a']) ) {
				if( !empty($_get['c']) ) $_GET['c'] = $_get['c'];
				if( !empty($_get['a']) ) $_GET['a'] = $_get['a'];
				unset($_get);
				return 1;
			}
			unset($_get);
		}
		$path_info = $path_only;
		// ------------------------

		//使用路由规则解析URL
		//是否开启了路由
		if( !empty(mPHP::$CFG['router']) ) {
			$slash_pos = strpos($path_info,'/',1);
			$first_param = $slash_pos === false ? ltrim($path_info,'/') : substr($path_info,1,$slash_pos - 1); //获取url上的第一个参数，用于对象router中的路由规则；
			$config = mPHP::$CFG['router'];

			if( isset($config[$first_param])) {
				foreach ($config[$first_param] as $v) {
					$count = 0; //记录成功替换的个数
					$path_info = preg_replace($v[0],$v[1],$path_info,-1,$count);
					if($count > 0) break; //只要匹配上一个，则停止匹配，故在$CFG['router']从上到下有优先权。
				}
			}
		}
		$url_suffix = !empty(mPHP::$CFG['url_suffix']) ? mPHP::$CFG['url_suffix'] : false;
		if( $url_suffix && $url_suffix != '/' && ($url_suffix_pos = strrpos($path_info, $url_suffix) ) ) $path_info = substr($path_info, 0, $url_suffix_pos);
		if( isset(mPHP::$CFG['url_type']) && mPHP::$CFG['url_type'] == 'NODIR') $path_info = str_replace('-', '/', $path_info); // 无目录的user-info-15.html

		if( self::$path_info != $path_info ) {
			$mark = ',';
			$path_info = preg_replace('#^/\w+\.php#', $mark, $path_info);
			if($path_info == '/') $path_info = $mark;

			$splits = explode($mark, trim($path_info, $mark));

			// 路由解析结果优先于入口处的默认值（否则 /view/1/ 会被默认的 c=blog 覆盖）
			$_GET['c'] = empty($splits[0]) ? (empty($_GET['c']) ? self::$controller : $_GET['c']) : $splits[0];
			$_GET['a'] = empty($splits[1]) ? (empty($_GET['a']) ? self::$action  : $_GET['a']) : $splits[1];

			$count = count($splits);
			for($i = 2; $i < $count; $i += 2) {
				if( isset($splits[$i]) && isset($splits[$i+1])) $_GET[$splits[$i]] = $splits[$i+1];
			}
			return 2;
		}
		// ------------------------

		/*
		直接解析url
		http://xxx/q/w/e/r/t/y 解析为
		$_GET['c'] = q
		$_GET['a'] = w
		$_GET['e'] = r
		$_GET['t'] = y
		*/
		$path_info = trim(self::$path_info,'/');
		$_get = explode('/', $path_info);
		if( !empty($_get[0]) ) $_GET['c'] = $_get[0];
		if( !empty($_get[1]) ) $_GET['a'] = $_get[1];
		$count = count($_get);
		for($i = 2; $i < $count; $i += 2) {
			if( !empty($_get[$i]) && isset($_get[$i+1]) && $_get[$i] != 'c' && $_get[$i] != 'a' ) $_GET[$_get[$i]] = $_get[$i+1];
		}
		if( !empty($_GET['c']) || !empty($_GET['a']) ) return 3;

		return 4;
	}
}


//控制器
class controller {
	public static $view = false;
	
	public function __construct() {
		if(!self::$view) self::$view = mPHP::$view;
	}
}

//为控制器提供逻辑处理服务
class service {}

//为逻辑处理服务提供数据库操作
class dao {
	public static $db = 0;
	public static $table_prefix = 0;
	
	public function __construct() {
		if(!self::$db) self::$db = db::init();
		if(!self::$table_prefix) self::$table_prefix = mPHP::$CFG['table_prefix'];
	}

	public function __destruct() {
		if(mPHP::$pool) mPHP::$pool->free();
		self::$db = false;
	}
}

class db {
	public static $db = [];

	public static function init($name = 'master') {
		if( empty(self::$db[$name]) ) {
			if( !mPHP::$pool ) mPHP::$pool = M('pool');
			self::$db[$name] = mPHP::$pool->get();
		}
		return self::$db[$name];
	}
}

/*
视图类
一:加载模版文件 

二:编译模版文件
	处理Inlcude
	替换<!--#标签为<?php
*/
class view {
	public $is_cache = false;
	public $includeCount = 0;
	public $includeMs = 0.0;
	public $is_merger = false;
	public $is_mini_html = false;

    public $data = [];

	//加载xxx.tpl.html模版文件
	//$tpl:模版文件
	//$file:根据模版生成的静态文件
	//$dir:模版文件夹分支（默认为空）
	//$time:静态文件缓存时间
	public function loadTpl($tpl,$file = '', $time = 0) {
		mPHP::inc( MPHP_PATH.'inc/functions.php' );//加载常用函数集
		ob_start();
		$arrData = $this->_include($tpl,$file);
		ob_end_clean();

		if(!mPHP::$debug) {
			if( $this->is_merger ) $arrData['html'] = $this->merger($arrData['html']);
			if( $this->is_mini_html ) $arrData['html'] = mini_html( $arrData['html'] );
		}

		if( class_exists('sqlProfiler', false) && sqlProfiler::enabled() ) {
			sqlProfiler::emitHeaders();
			$arrData['html'] .= sqlProfiler::debugHtml();
		}
		echo $arrData['html'];
	}
	
	//编译模版
	public function tplCompile($str) {
		//处理include标签
		$str = preg_replace( "/<!--#\s*layout\s*:\s*([^ ]+);*\s*#-->/", '<?php $this->_include(\'\\1\') ?>', $str );
		/*替换<!--# #-->标签为<?php ?>*/
		$str = strtr($str,array(mPHP::$CFG['template']['tag_left'] => '<?php ', mPHP::$CFG['template']['tag_right'] => ' ?>'));
		return $str;
	}
	
	//合并style、script
	public function merger($str) {
		$root = U();
		$arrMergerCss = $arrMergerJs = array();
		$script = "#<script.*src=['\"](((?!(http|https)://))[^'\"]+\.js)['\"].*></script>#";
		$style	=  "#<link.*href=['\"](((?!(http|https)://))[^'\"]+\.css[^'\"]*)['\"].*>#";
		preg_match_all($style,$str,$arrStyle);
		$str = preg_replace($style,'',$str);
		preg_match_all($script,$str,$arrScript);
		$str = preg_replace($script,'',$str);
		
		foreach( $arrStyle[1] as &$row) {
			if( substr($row,0,7) != 'http://' && substr($row,0,8) != 'https://' && substr($row,0,2) != '//' ) {
				$row = strtr( $row,array($root=>'') );
				$arrMergerCss[] = $row;
			}
		}
		foreach( $arrScript[1] as &$row) {
			if( substr($row,0,7) != 'http://' && substr($row,0,8) != 'https://' && substr($row,0,2) != '//' ) {
				$row = strtr( $row,array($root=>'') );
				$arrMergerJs[] = $row;
			}
		}
		$css = file_merger($arrMergerCss,crc32(implode('',$arrMergerCss)).'.css');
		$js = file_merger($arrMergerJs,crc32(implode('',$arrMergerJs)).'.js');
        
		$str = preg_replace('#<link\s*/>#', $css, $str,1);
		$str = preg_replace('#<script\s*>\s*</script>#', $js, $str,1);
		return $str;
	}
	
	/*转换所有include语句
	<?php include 'a' ?>
	<?php include 'b' ?>
	转成 
	<?php include TPL_C_PATH.'a.tpl.php' ?>
	<?php include TPL_C_PATH.'b.tpl.php' ?>
	并编译生成
	TPL_C_PATH.'a.tpl.php'文件
	TPL_C_PATH.'b.tpl.php'文件
	*/
	public function _include($tpl, $file = '') {
		$__t0 = microtime(true);
		if( isset($this->data) && is_array($this->data) ) {
			foreach($this->data as $key => $val) $$key = $val;
		}

		$tpl_file = TPL_PATH."{$tpl}.tpl.html";
		$tpl_c_file = TPL_C_PATH . "{$tpl}.tpl.php";
		$tpl_exists = file_exists($tpl_file);
		if( !$tpl_exists && file_exists(TPL_MPHP_PATH."{$tpl}.tpl.html") ) {
			$tpl_file = TPL_MPHP_PATH."{$tpl}.tpl.html";
			$tpl_exists = true;
		}

		//判断是否需要重新编译模版
		// 源模板不存在时也必须重编译：否则删掉/改名一个 tpl 后，旧编译产物会被一直复用
		$needs_compile = !file_exists($tpl_c_file) || !$tpl_exists || ( filemtime($tpl_file) != filemtime($tpl_c_file) );
		if( $needs_compile ) {
			$flag = true;
			//tpl模版 转译 php文件 并保存
			if( $tpl_exists ) {
				$html = file_get_contents($tpl_file);
			} else {
				$flag = false;
				$title = '模版文件不存在';
				$msg = "$tpl_file";
				$tpl_file = TPL_MPHP_PATH."error.tpl.html";
				$html = file_get_contents($tpl_file);
			}
			$html = '<?php if(!defined("INIT_MPHP"))return;?>' . $this->tplCompile($html);//替换标签
			file_exists(dirname ($tpl_c_file)) or mkdir(dirname ($tpl_c_file),0755,true);
			file_put_contents($tpl_c_file,$html);
			if( $flag ) touch($tpl_c_file,filemtime($tpl_file));//编译文件与模版文件同步修改时间
		}

		//php文件 转译 静态html
		ob_start();
		include $tpl_c_file;
		$html = ob_get_clean();
		echo $html;

		if($file == '') $file = CACHE_HTML_PATH . "{$tpl}.html";

		$arrData['file'] = $file;
		$arrData['html'] = $html;
		$this->includeCount++;
		$this->includeMs += (microtime(true) - $__t0) * 1000;
		return $arrData;
	}
	
	//应该优先使用路由缓存功能，方便，性能也更好
	public function cache($file,$cacheTime) {
		return false;// HTML 文件缓存已移除，缓存由 nginx/静态文件负责
	}
	
	//清除所有静态Html
	public static function clearCache() {
		directoryModel::clearDir(CACHE_PATH);
	}
}

class safe {
	
	//简单的过滤，防止get post cookie注入
	public static function safeGPC() {
		self::filter($_GET);
		self::filter($_POST);
		self::filter($_COOKIE);
	}
	
	public static function filter(&$value) {
		if( is_array($value) ) {
			foreach( $value as &$row) self::filter($row);
		} else {
			$value = trim($value);
			$value = str_replace("\0", '', $value);
			$mode = isset(mPHP::$CFG['input_escape']) ? mPHP::$CFG['input_escape'] : '';
			if( $mode === 'legacy' ) {
				$value = addslashes(htmlspecialchars($value, ENT_QUOTES));
			} elseif( $mode === 'html' ) {
				$value = htmlspecialchars($value, ENT_QUOTES);
			}
		}
	}
	
	//还原字符串
	public static function restore($str) {
		return htmlspecialchars_decode(stripslashes($str));
	}
	
	//随机生成验证码
	public static function getKey() {
		$strKey = '';
		$string = 'abcdefghijkmnpqrstuvwxyz2356789';
		$i = 5;
		while($i) {
		$index = rand(0,29);
			$strKey .= $string[$index];
			--$i;
		}
		return $strKey;
	}
	
	//生成令牌
	public static function getToken() {
		return  md5(uniqid(rand(), true));
	}
	
	//检测跳转域名是否正确
	public static function checkDomain() {}
	
}

class mError {
    public static $_levels = array(
		'CRITICAL' => '1',
		'ERROR' => '2',
		'WARNING' => '3',
		'NOTICE' => '4',
		'INFO' => '5',
		'DEBUG' => '6',
		'ALL' => '7'
	);
	private static $registered = false;
	private static $handling = false;
	private static $rendering = false;
	private static $reservedMemory;

    public static function register() {
		if( self::$registered ) return;
		self::$registered = true;
		self::$reservedMemory = str_repeat(' ', 1024 * 512); // preallocate 512KB
        set_error_handler([__CLASS__, 'errorHandler'], E_ALL);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        register_shutdown_function([__CLASS__, 'shutdownHandler']);
    }

    public static function errorHandler($errcode, $errmsg, $errfile, $errline) {
		if( (error_reporting() & $errcode) === 0 ) return true;
        self::handler($errcode, $errmsg, $errfile, $errline);
		return true;
    }

    public static function exceptionHandler($exception) {
		$message = $exception instanceof \Throwable ? $exception->getMessage() : (string)$exception;
		$trace = $exception instanceof \Throwable ? $exception->getTraceAsString() : '';
		$file = $exception instanceof \Throwable ? $exception->getFile() : __FILE__;
		$line = $exception instanceof \Throwable ? $exception->getLine() : __LINE__;
        self::handler(E_ERROR, $message, $file, $line, $trace, true);
		mPHP::flushLogs();
		self::renderFatal('Uncaught Exception', $message, $file, $line);
    }

    public static function shutdownHandler() {
		self::$reservedMemory = null; // Free memory so we have enough to process logging
		$e = error_get_last();
		if( is_array($e) && isset($e['type']) && self::isFatalErrorType($e['type']) ) {
			self::handler($e['type'], $e['message'], $e['file'], $e['line'], '', true);
			self::renderFatal('Fatal Error', $e['message'], $e['file'], $e['line']);
		}
		mPHP::flushLogs();
    }

	private static function renderFatal($title, $errmsg, $errfile, $errline) {
		if( self::$rendering ) return;
		self::$rendering = true;
		mPHP::status(500);//status() 定义在 mPHP 上，写成 self:: 会触发「未定义方法」二次致命错误
		self::flushLogs();
		// 致命错误发生在 shutdown / error handler 里，此路径必须直接 exit，不能再抛异常
		if( mPHP::$debug ) {
			$msg = "{$errmsg}<br>{$errfile}:{$errline}<br>request_id=" . mPHP::requestId();
			try {
				if( class_exists('view', false) && mPHP::$view ) {
					self::$view->data['title'] = $title;
					self::$view->data['msg']   = $msg;
					self::$view->loadTpl('error');
				} else {
					echo "{$title}: {$errmsg} in {$errfile}:{$errline} request_id=" . mPHP::requestId();
				}
			} catch (Throwable $e) {
				echo "{$title}: {$errmsg} in {$errfile}:{$errline}";
			}
		}
		exit;
	}

	private static function errorName($errcode) {
		$map = array(
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE',
			E_STRICT => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED => 'E_DEPRECATED',
			E_USER_DEPRECATED => 'E_USER_DEPRECATED',
		);
		return isset($map[$errcode]) ? $map[$errcode] : 'E_UNKNOWN';
	}

	private static function level($errcode) {
		$critical = array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR);
		$warning = array(E_WARNING,E_CORE_WARNING,E_COMPILE_WARNING,E_USER_WARNING,E_RECOVERABLE_ERROR);
		$notice = array(E_NOTICE,E_USER_NOTICE,E_STRICT,E_DEPRECATED,E_USER_DEPRECATED);
		if( in_array($errcode,$critical,true) ) return 'CRITICAL';
		if( in_array($errcode,$warning,true) ) return 'WARNING';
		if( in_array($errcode,$notice,true) ) return 'NOTICE';
		return 'ERROR';
	}

	private static function requestContext() {
		return mPHP::logContext();
	}

	private static function isFatalErrorType($errcode) {
		$fatal = array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR,E_RECOVERABLE_ERROR);
		return in_array($errcode, $fatal, true);
	}

    public static function handler($errcode, $errmsg, $errfile, $errline, $trace = '', $force_fatal = false) {
        if( self::$handling ) return;
		self::$handling = true;

		$level = self::level($errcode);
			$logData = array(
				'time' => date('c'),
				'level' => $level,
				'error_type' => self::errorName($errcode),
			'code' => $errcode,
			'message' => (string)$errmsg,
			'file' => (string)$errfile,
			'line' => (int)$errline,
			'context' => self::requestContext(),
			);
			if( $trace !== '' ) $logData['trace'] = $trace;

			mPHP::log($level, (string)$errmsg, $logData);
			self::$handling = false;

		$is_fatal = $force_fatal || self::isFatalErrorType($errcode);
		if( $is_fatal ) {
			mPHP::flushLogs();
			self::renderFatal('PHP Error', (string)$errmsg, (string)$errfile, (int)$errline);
		}
    }

}
