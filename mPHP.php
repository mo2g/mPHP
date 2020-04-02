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

defined('STATIC_PATH') or define('STATIC_PATH',						INDEX_PATH.'static/');				//静态目录
defined('TPL_MPHP_PATH') or define('TPL_MPHP_PATH',				MPHP_PATH.'tpl/');					//mPHP模版目录

if( !defined('STATIC_URL') && isset($_SERVER['SERVER_NAME']) ) {
	$dir = isset( $_SERVER['SCRIPT_NAME'] ) ? dirname($_SERVER['SCRIPT_NAME']) : isset( $_SERVER['REQUEST_URI'] ) ? dirname($_SERVER['REQUEST_URI']) : dirname($_SERVER['DOCUMENT_URI']);
	define('STATIC_URL',		"http://{$_SERVER['SERVER_NAME']}{$dir}/static/");//静态目录 URL
	defined('JS_URL') or define('JS_URL',				STATIC_URL.'js/');	//js脚本 URL
	defined('CSS_URL') or define('CSS_URL',			STATIC_URL.'css/');	//样式 URL
	defined('IMAGES_URL') or define('IMAGES_URL',	STATIC_URL.'images/');//图片 URL
}

define('MODELS_MPHP'			,MPHP_PATH.'models/');

//核心类
class mPHP {
	public static $mPHP = false;
	public static $CFG = false;
	public static $swoole = false;
	public static $debug = false;
	public static $view = false;
	public static $exit = false;
	public static $is_mobile = false;
	public static $db = false;
	public static $pool = false;
	public static $include_file_lists = array();
	
	private function __construct() {
		if(!self::$view) self::$view = new view();
		if(!self::$CFG) self::$CFG = $GLOBALS['CFG'];
		if(!self::$debug) self::$debug = isset(self::$CFG['debug']) ? self::$CFG['debug'] : true;
		spl_autoload_register('self::autoLoader');
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
			self::error('Action不存在！', "c={$controller} a={$action} 未定义!");
			self::_exit();
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
				self::error('控制器不存在！', "{$file} 不存在!");
				self::_exit();
			}
		} elseif(substr($file,-11) == 'Service.php') {
			if( is_file(SERVICES_PATH.$file) ) {
				include SERVICES_PATH.$file;
			} else {
				self::error('service模块不存在！', "{$file} 不存在!");
				self::_exit();
			}
		} elseif(substr($file,-7) == 'Dao.php') {
			if( is_file(DAOS_PATH.$file) ) {
				include DAOS_PATH.$file;
			} else {
				self::error('dao模块不存在！', "{$file} 不存在!");
				self::_exit();
			}
		} elseif( substr($file,-9) == 'Model.php' ) {
			if( is_file(MODELS_MPHP.$file) ) {
				include MODELS_MPHP.$file;
			} elseif( is_file(MODELS_PATH.$file) ) {
				include MODELS_PATH.$file;
			} else {
				self::error('Model模块不存在！', "{$file} 不存在!");
				self::_exit();
			}
		} elseif( is_file(INDEX_PATH.'libs/exts/class/'.$file) ) {
			include INDEX_PATH.'libs/exts/class/'.$file;//加载外部引用类
		} else {
			self::error('访问错误！', "未定义操作 $file");
			self::_exit();
		}
	}

	public static function error($title = '',$msg = '') {
		if( self::$debug ) {
			self::status(503);
			self::$view->data['title'] = $title;
			self::$view->data['msg'] = $msg;
			self::$view->loadTpl('error');
		} else {
			function_exists('goto_404') ? goto_404() : self::status(404);
		}
		self::_exit();
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
		if( self::$swoole ) {
			if( self::$exit ) {
				return true;
			} else {
				self::$exit = true;
				return false;
			}
		} else {
			if( self::$exit ) {
				$true ? exit : true;
			} else {
				self::$exit = true;
				return $true ? exit : false;
			}
		}
	}

	/*
	swoole拓展会导致php原生函数header失效
	*/
	public static function header($key,$value) {
		if( self::$swoole ) {
			self::$swoole['response']->header($key,$value);
		} else {
			header($key . ': ' . $value );
		}
	}

	public static function status($http_status_code) {
		httpModel::status($http_status_code);
	}

	public static function init() {
		if( !self::$mPHP ) self::$mPHP = new mPHP();
		return self::$mPHP;
	}
	
	public static function initSite() {
		initModel::initMainDir();
		initModel::initDb();
	}
	
}

//简单路由
class router {
	public static $controller = 'index';
	public static $action = 'index';
	public static $path_info = '';
	public static $table = false;

	public static function init() {
		if( defined('SWOOLE_DEAMON') ) {
			$table = new swoole_table(1024);
			$table->column('ctime', swoole_table::TYPE_INT, 4);	//缓存创建时间戳
			$table->column('etime', swoole_table::TYPE_INT, 4);	//缓存失效时间戳
			$table->column('file', swoole_table::TYPE_STRING, 64);	//缓存文件路径
			$table->create();
			self::$table = $table;
		} else {
			self::$table = new cache\cacheModel('file');
			self::$table ->in('router');
		}
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
		self::$path_info = $path_info;
		mPHP::$is_mobile = mobileModel::is_mobile();

		//路由缓存逻辑
		if( self::cache() ) return 0;
		// 使用 /?c=index&a=index 方式访问
		parse_str($path_info,$_get);
		if( !empty($_get['c']) ) $_GET['c'] = $_get['c'];
		if( !empty($_get['a']) ) $_GET['a'] = $_get['a'];
		unset($_get);
		if( !empty($_GET['c']) || !empty($_GET['a']) ) {
			return 1;
		}
		// ------------------------

		//使用路由规则解析URL
		//是否开启了路由
		if( !empty(mPHP::$CFG['router']) ) {
			$first_param = substr($path_info,1,strpos($path_info,'/',1) - 1); //获取url上的第一个参数，用于对象router中的路由规则；
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

			if( empty($_GET['c']) ) $_GET['c'] = empty($splits[0]) ? self::$controller : $splits[0];
			if( empty($_GET['a']) ) $_GET['a'] = empty($splits[1]) ? self::$action : $splits[1];

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

	public static function cache() {
		$cache = false;
		if( !mPHP::$debug && ( $data = self::get() ) && $data['etime'] > $_SERVER['REQUEST_TIME'] && file_exists($data['file']) ) {
			$createTime = date("D, d M Y H:i:s",$data['ctime']);
			mPHP::header('Cache-Control','max-age=0');
			mPHP::header('Last-Modified',$createTime);
			if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $createTime ) {
				mPHP::status(304);
			} else {
				include $data['file'];
			}
			mPHP::_exit();
			$cache = true;//相关控制器已缓存
		}
		return $cache;
	}

	public static function get() {
		$key = self::$path_info;
		if( mPHP::$is_mobile ) {
			$key .= '(mobile)';
		}
		return self::$table->get($key);
	}

	public static function set($arrData) {
		$key = self::$path_info;
		if( mPHP::$is_mobile ) {
			$key .= '(mobile)';
		}
        self::$table ->in('router');
		self::$table->set($key, $arrData);
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
		mPHP::$pool->free();
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
	public $is_merger = false;
	public $is_mini_html = false;

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

		//路由缓存逻辑
		if( $time ) {
			$ctime = $_SERVER['REQUEST_TIME'];
			$date = date('Y-m-d H:i:s');
			$arrData['html'] .= "<!-- mPHP html cache $date -->";
			$strDir = dirname($arrData['file']);
			if( !is_dir($strDir) ) mkdir($strDir,0775,true);
			file_put_contents($arrData['file'],$arrData['html']);
			mPHP::header('Cache-Control','max-age=0');
			mPHP::header('Last-Modified',date("D, d M Y H:i:s",$ctime));

			$data = array(
				'ctime' => $ctime , 
				'etime' => $ctime + $time , 
				'file' => $arrData['file']
			);
			router::set($data);
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
		$css = file_merger($arrMergerCss,crc32(implode($arrMergerCss,'')).'.css');
		$js = file_merger($arrMergerJs,crc32(implode($arrMergerJs,'')).'.js');
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
		if( isset($this->data) && is_array($this->data) ) {
			foreach($this->data as $key => $val) $$key = $val;
		}

		$tpl_file = TPL_PATH."{$tpl}.tpl.html";
		$tpl_c_file = TPL_C_PATH . "{$tpl}.tpl.php";

		//判断是否需要重新编译模版
		if( !file_exists($tpl_c_file) || filemtime($tpl_file) != filemtime($tpl_c_file) ) {
			$flag = true;
			//tpl模版 转译 php文件 并保存
			if( file_exists($tpl_file) ) {
				$html = file_get_contents($tpl_file);
			} elseif( file_exists(TPL_MPHP_PATH."{$tpl}.tpl.html") ) {
				$tpl_file = TPL_MPHP_PATH."{$tpl}.tpl.html";
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
		return $arrData;
	}
	
	//应该优先使用路由缓存功能，方便，性能也更好
	public function cache($file,$cacheTime) {
		$this->is_cache = true;
		$time = $_SERVER['REQUEST_TIME'];

		$createTime = file_exists($file) ? filemtime($file) : 0;
		if( ($createTime + $cacheTime >= $time ) && !mPHP::$debug ) {
			$createTime = date("D, d M Y H:i:s",$createTime);
			mPHP::header('Cache-Control','max-age=0');
			mPHP::header('Last-Modified',$createTime);
			if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $createTime ) {
				mPHP::status(304);
			} else {
				include $file;
			}
			mPHP::_exit();
			return true;
		}
		return false;
	}
	
	//清除所有静态Html
	public static function clearCache() {
		directoryModel::clearDir(CACHE_PATH);
	}
}

class safe {
	public static $magic_quotes_gpc;
	
	public function __construct() {
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
		$strKey = '';
		$string = 'abcdefghijkmnpqrstuvwxyz2356789';
		$i = 5;
		while($i) {
			$index = rand(0,30);
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