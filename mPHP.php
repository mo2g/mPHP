<?php
/*
作者:moyancheng
创建时间:2012-03-01
最后更新时间: 2015-05-06
*/
if( !defined('INIT_MPHP') ) exit;

include MPHP_PATH.'inc/define.php';//加载常量集
include MPHP_PATH.'inc/functions.php';//加载常用函数集
include MPHP_PATH.'inc/plus.php';//加载常用函数集

//核心类
class mPHP {
	private static $mPHP = false;
	public static $swoole = false;
	
	public $namespace = array();
	public $controller;

	private function __construct() {
		$this->controller = new controller();
		spl_autoload_register('autoload');
	}
	
	public function run() {
		router::init();
		safe::safeGPC();
		$this->controller->load();
	}

	public function addNameSpace($path) {
		$this->namespace[] = $path;
	}

	public static function autoloadNameSpace($className) {
		$fileName = strtr($className,array('\\' => '/'));
		$fileName .= '.php';
		foreach( $this->namespace as $path ) {
			$file = $path . $fileName;
			if( is_file($file) ) {
				include $file;
			} else {
				goto_404();
			}
		}
	}

	/*
	swoole拓展会导致php原生函数header失效
	*/
	public static function header($key,$value) {
		if( mPHP::$swoole ) {
			mPHP::$swoole['response']->header($key,$value);
		} else {
			header($key . ': ' . $value );
		}
	}

	public static function status($http_status_code) {
		if( mPHP::$swoole ) {
			mPHP::$swoole['response']->status($http_status_code);
		} else {
			switch ($http_status_code) {
				case 301:
					header('HTTP/1.1 301 Moved Permanently');
					break;
				case 304:
					header('HTTP/1.1 304 Not Modified');
					break;
				case 403:
					header('HTTP/1.1 403 Forbidden');
					break;
				case 404:
					header('HTTP/1.1 404 Not Found');
					break;
				case 500:
					header('HTTP/1.1 500 Internal Server Error');
					break;
				case 503:
					header('HTTP/1.1 503 Service Temporarily Unavailable');
					header('Status: 503 Service Temporarily Unavailable');
					header('Retry-After: 3600');
					break;
			}
		}
	}
	
	public static function init() {
		if( !self::$mPHP ) self::$mPHP = new mPHP();
		return self::$mPHP;
	}
	
	public static function initSite() {
		self::initMainDir();
		self::initDb();
	}
	
	public static function initMainDir() {
		if(!is_dir(CACHE_PATH)) {
			mkdir(CACHE_PATH);
			file_put_contents(CACHE_PATH.'index.html','');
		}
		/*
		if(!is_dir(CONF_PATH)) {
			mkdir(CONF_PATH);
			file_put_contents(CONF_PATH.'index.html','');
		}
		*/
		if(!is_dir(CONTROLLERS_PATH)) {
			mkdir(CONTROLLERS_PATH);
			file_put_contents(CONTROLLERS_PATH.'index.html','');
		}

		if(!is_dir(MODELS_PATH)) {
			mkdir(MODELS_PATH);
			file_put_contents(MODELS_PATH.'index.html','');
		}
		if(!is_dir(SERVICES_PATH)) {
			mkdir(SERVICES_PATH);
			file_put_contents(SERVICES_PATH.'index.html','');
		}
		if(!is_dir(DAOS_PATH)) {
			mkdir(DAOS_PATH);
			file_put_contents(DAOS_PATH.'index.html','');
		}
		if(!is_dir(TPL_PATH)) {
			mkdir(TPL_PATH);
			file_put_contents(TPL_PATH.'index.html','');
		}
		if(!is_dir(TPL_C_PATH.'admin')) {
			mkdir(TPL_C_PATH.'admin',0755,true);
			file_put_contents(TPL_C_PATH.'index.html','');
			file_put_contents(TPL_C_PATH.'admin/index.html','');
		}
		if(!is_dir(STATIC_PATH.'merger') ) {
			mkdir(STATIC_PATH.'merger',0755,true);
			file_put_contents(STATIC_PATH.'index.html','');
			file_put_contents(STATIC_PATH.'merger/index.html','');
		}
		foreach($GLOBALS['CFG']['main_dir'] as $dir) {
			createDirs($dir['path'],$dir['totle']);
		}
	}
	
	//初始化数据库
	public static function initDb() {
		$db = new pdoModel($GLOBALS['CFG']['pdo']);
		$db->initDb('initdata/tables.sql');
		unset($db);
	}
}

//简单路由
class router {
	public static $controller = 'index';
	public static $action = 'index';

	public static function init() {
		$mark = ',';
		$path_info = self::path_info();
		$path_info = preg_replace('#^/\w+\.php#', $mark, $path_info);
		if($path_info == '/') $path_info = $mark;

		if( !empty($path_info) ) $splits = explode($mark, trim($path_info, $mark));
		else return false;

		if( empty($_GET['c']) ) $_GET['c'] = empty($splits[0]) ? self::$controller : $splits[0];
		if( empty($_GET['a']) ) $_GET['a'] = empty($splits[1]) ? self::$action : $splits[1];

		$count = count($splits);
		for($i = 2; $i < $count; $i += 2) {
			if( isset($splits[$i]) && isset($splits[$i+1])) $_GET[$splits[$i]] = $splits[$i+1];
		}
		$_REQUEST = array_merge($_GET, $_REQUEST);
	}

	public static function path_info() {
		$path_info = '';
		if( !empty($_SERVER['PATH_INFO']) ) {
			global $CFG;
			$path_info = $_SERVER['PATH_INFO'];
			//是否开启了路由
			if( !empty($CFG['router']) ) {
				$first_param = substr($path_info,1,strpos($path_info,'/',1) - 1); //获取url上的第一个参数，用于对象router中的路由规则；
				$config = $CFG['router'];
				
				if( isset($config[$first_param])) {
					foreach ($config[$first_param] as $v) {
						$count = 0; //记录成功替换的个数
						$path_info = preg_replace($v[0],$v[1],$path_info,-1,$count);
						if($count > 0) break; //只要匹配上一个，则停止匹配，故在$CFG['router']从上到下有优先权。
					}
				}
			}
			$url_suffix = !empty($CFG['url_suffix']) ? $CFG['url_suffix'] : false;
			if( $url_suffix && $url_suffix != '/' && ($url_suffix_pos = strrpos($path_info, $url_suffix) ) ) $path_info = substr($path_info, 0, $url_suffix_pos);
			if( $CFG['url_type'] == 'NODIR') $path_info = str_replace('-', '/', $path_info); // 无目录的user-info-15.html
			unset($CFG);
		}
		return $path_info;
	}

}


//控制器
class controller {
	public static $view = 0;
	public static $visit = 0;   
	public static $check = 0;
	public static $register = array();
	public static $CFG = 0;
	public $service = 0;
	public $is_mobile = 0;
	
	public function __construct() {
		if( !self::$CFG ) {
			self::$CFG = &$GLOBALS['CFG'];
		}
		if(!self::$view) self::$view = new view();
		
		$this->is_mobile = is_mobile();
		
		//session处理，防止跨域丢失
		if( isset($_GET['PHPSESSID']) ) {
			$PHPSESSID = $_GET['PHPSESSID'];
		} elseif( isset($_COOKIE['www_mo2g_session_id']) ) {
			$PHPSESSID = $_COOKIE['www_mo2g_session_id'];
		}
		if($PHPSESSID) session_id($PHPSESSID);
		if( !isset($_SESSION) ) session_start();
		
		//if(!self::$visit) self::$visit = M('visit');
		$service = get_class($this);
		if(empty(self::$register[$service]) && ( $service != 'controller' ) ) {
			if( !defined('INIT_ADMIN') ) {
				$service = str_replace('Controller','',$service);
				self::$register[$service] = S($service);
			}
		}
		
		if( $service != 'controller' && !defined('INIT_ADMIN')) {
			$this->service = self::$register[$service];
		}
	}
	
	public function load() {
		if( isset($_GET['c']) ) $controller = "{$_GET['c']}Controller";
		elseif( isset($_POST['c']) ) $controller = "{$_POST['c']}Controller";
		else $controller = 'indexController';

		if( isset($_GET['a']) ) $action = "{$_GET['a']}Action";
		elseif( isset($_POST['a']) ) $action = "{$_POST['a']}Action";
		else $action = 'indexAction';

		if( method_exists($controller,$action) ) {
			$controller = new $controller;
			call_user_func(array($controller,$action));
		} else {
			if( !_exit() ) {
				goto_503();
			}
			/*
			self::$view->data['title'] = '对不起，此页面暂不开放';
			$file = CACHE_PATH . 'html/build.html';
			self::$view->loadTpl('build',$file);
			*/
		}
		
	}
	
	public function __destruct() {
	}
}

//为控制器提供逻辑处理服务
class service {
	public static $mem = 0;
	public static $httpsqsModel = 0;
	
	public function __construct() {
		//if(!self::$mem) self::$mem = new memcachedModel($CFG['memcached']);
		if(!self::$httpsqsModel) self::$httpsqsModel = M('httpsqs');
		$GLOBALS['CFG']['mem'] = self::$mem;
	}
}

//为逻辑处理服务提供数据库操作
class dao {
	public static $db = 0;
	public static $table_prefix = 0;
	
	public function __construct() {
		if(!self::$db) self::$db = db::init();
		if(!self::$table_prefix) self::$table_prefix = $GLOBALS['CFG']['table_prefix'];
	}
}

class db {
	public static $db = array();

	public static function init($name = 'master') {
		if( empty(self::$db[$name]) ) self::$db[$name] = new pdoModel($GLOBALS['CFG']['pdo']);
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
	public $is_merger = false;
	public $is_mini_html = false;

	public function __construct() {
	}
	
	//加载xxx.tpl.html模版文件
	//$tpl:模版文件
	//$file:根据模版生成的静态文件
	//$dir:模版文件夹分支（默认为空）
	//$cacheTime:静态文件缓存时间
	public function loadTpl($tpl,$file = '') {
		ob_start();
		$arrData = $this->_include($tpl,$file);
		ob_end_clean();
		
		if($GLOBALS['CFG']['debug']) {
		} else {
			if( $this->is_merger ) $arrData['html'] = $this->merger($arrData['html']);
			if( $this->is_mini_html ) $arrData['html'] = mini_html( $arrData['html'] );
		}
		if( $this->is_cache ) {
			$date = date('Y-m-d H:i:s');
			$arrData['html'] .= "<!-- mPHP html cache $date -->";
			$strDir = dirname($arrData['file']);
			if( !is_dir($strDir) ) mkdir($strDir,0775,true);
			file_put_contents($arrData['file'],$arrData['html']);
			$createTime = filemtime($arrData['file']) ;
			mPHP::header('Cache-Control','max-age=0');
			mPHP::header('Last-Modified',date("D, d M Y H:i:s",$createTime));
		}
		
		echo $arrData['html'];
	}
	
	//编译模版
	public function tplCompile($str) {
		//处理include标签
		$str = preg_replace( "/<!--#\s*layout\s*:\s*([^ ]+);*\s*#-->/", '<?php $this->_include(\'\\1\') ?>', $str );
		/*替换<!--# #-->标签为<?php ?>*/
		$str = strtr($str,array($GLOBALS['CFG']['template']['tag_left'] => '<?php ', $GLOBALS['CFG']['template']['tag_right'] => ' ?>'));
		return $str;
	}
	
	//合并style、script
	public function merger($str) {
		$root = U();
		$arrMergerCss = $arrMergerJs = array();
		$script = "#<script.*src=['\"](/.+\.js)['\"].*></script>#";
		$style	= "#<link.*href=['\"](/[^'\"]+\.css[^'\"]*)['\"].*>#";
		preg_match_all($style,$str,$arrStyle);
		$str = preg_replace($style,'',$str);
		preg_match_all($script,$str,$arrScript);
		$str = preg_replace($script,'',$str);
		foreach( $arrStyle[1] as &$row) {
			if( $row[0] == '/' ) {
				$row = strtr( $row,array($root=>'') );
				$arrMergerCss[] = $row;
			}
		}
		foreach( $arrScript[1] as &$row) {
			if( $row[0] == '/' ) {
				$row = strtr( $row,array($root=>'') );
				$arrMergerJs[] = $row;
			}
		}
		$css = file_merger($arrMergerCss,crc32(implode($arrMergerCss,'')).'.css');
		$js = file_merger($arrMergerJs,crc32(implode($arrMergerJs,'')).'.js');
		$str = strtr( $str,array('<link />'=>$css) );
		$str = strtr( $str,array('<script></script>'=>$js) );
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
		if( is_array($this->data) ) {
			foreach($this->data as $key => &$val) $$key = $val;
		}

		$tpl_file = TPL_PATH."{$tpl}.tpl.html";
		$tpl_c_file = TPL_C_PATH . "{$tpl}.tpl.php";
		//判断是否需要重新编译模版
		if( !file_exists($tpl_c_file) || filemtime($tpl_file) != filemtime($tpl_c_file) ) {
			//tpl模版 转译 php文件 并保存
			$html = file_get_contents($tpl_file);
			$html = '<?php if(!defined("INIT_MPHP"))exit;?>' . $this->tplCompile($html);//替换标签
			file_put_contents($tpl_c_file,$html);
			touch($tpl_c_file,filemtime($tpl_file));//编译文件与模版文件同步修改时间
		}
		
		//php文件 转译 静态html
		ob_start();
		include $tpl_c_file;
		$html = ob_get_clean();
		echo $html;

		if($file == '') $file = CACHE_HTML_PATH . "{$tpl}.html";
		$arrData['file'] = $file;
		$arrData['html'] = $html;
		return $arrData;
	}
	
	public function cache($file,$cacheTime) {
		$this->is_cache = true;
		$time = $_SERVER['REQUEST_TIME'];
		if( isset($_SESSION['user']['admin']) && $_SESSION['user']['admin'] < 9 ) {
			$cacheTime = 0;
		}
		$createTime = file_exists($file) ? filemtime($file) : 0;
		if( ($createTime + $cacheTime >= $time ) && !$GLOBALS['CFG']['debug'] ) {
			$createTime = date("D, d M Y H:i:s",$createTime);
			mPHP::header('Cache-Control','max-age=0');
			mPHP::header('Last-Modified',$createTime);
			if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $createTime ) {
				mPHP::status(304);
			} else {
				include $file;
			}
			_exit();
			return true;
		}
		return false;
	}
	
	//清除所有静态Html
	public static function clearCache() {
		clearDir(CACHE_PATH);
	}
	
}

class safe {
	public static $magic_quotes_gpc;
	
	public function __construct() {
		if( !isset($_SESSION) ) session_start();
		if( !self::$magic_quotes_gpc ) self::$magic_quotes_gpc = get_magic_quotes_gpc();
	}
	
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
			if( self::$magic_quotes_gpc ) {
				$value = htmlspecialchars(trim($value), ENT_QUOTES);
			} else {
				$value = addslashes(htmlspecialchars(trim($value), ENT_QUOTES));
			}
		}
	}
	
	//还原字符串
	public static function restore($str) {
		if( self::$magic_quotes_gpc ) return htmlspecialchars_decode(stripcslashes($str));
		else return htmlspecialchars_decode($str);
	}
	
	//随机生成验证码
	public static function getKey() {
		$string = 'abcdefghijklmnopqrstuvwxyz123567890';
		$i = 5;
		while($i) {
			$index = rand(0,34);
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
	public static function checkDomain() {
	}
	
}