<?php
/*
作者:moyancheng
创建时间:2012-03-01
最后更新时间: 2015-05-02
*/
if( !defined('INIT_MPHP') ) exit;

include MPHP_PATH.'inc/define.php';//加载常量集
include MPHP_PATH.'inc/functions.php';//加载常用函数集
include MPHP_PATH.'inc/plus.php';//加载常用函数集

//核心类
class mPHP {
	private static $mPHP = 0;
	
	public $autoLoadPath = array();
	public $controller;

	private function __construct() {
		$this->controller = new controller();
		spl_autoload_register('self::autoload');
	}
	
	public function run() {
		router::init();
		safe::safeGPC();
		$this->controller->load();
	}

	public function addAutoLoadPath($path) {
		$this->autoLoadPath[] = $path;
	}

	public static function autoloadNameSpace($className) {
		$fileName = strtr($className,array('\\' => '/'));
		$fileName .= '.php';
		foreach( $this->autoLoadPath as $path ) {
			$file = $path . $fileName;
			if( is_file($file) ) {
				include $file;
			} else {
				goto_404();
			}
		}
	}

	/*
	功能：$obj = new newClass();	//自动加载特定的php文件，省去繁琐的include
	*/
	public static function autoload($className) {
		static $view = false;
		static $flag = false;

		if( $view === false ) $view =new view();
		if( $flag === false ) $flag = $GLOBALS['CFG']['404'];
		
		if( substr($className,-10) == 'Controller' ) {
			if( is_file(CONTROLLERS_PATH."{$className}.php") ) include CONTROLLERS_PATH."{$className}.php";
			else {
				if( $flag ) {
					goto_404();
				} else {
					$view->data['title'] = '控制器不存在！';
					$view->data['msg'] = "{$className}.php 不存在!";
					$view->loadTpl('error');
				}
			}
		} elseif( substr($className,-5) == 'Model' ) {
			if( is_file(MODELS_MPHP."{$className}.php") )		include MODELS_MPHP."{$className}.php";
			elseif( is_file(MODELS_MPHP."system/{$className}.php") )	include MODELS_MPHP."system/{$className}.php";
			elseif( is_file(MODELS_PATH."{$className}.php") )	include MODELS_PATH."{$className}.php";
			else {
				if( $flag ) {
					goto_404();
				} else {
					$view->data['title'] = 'Model模块不存在！';
					$view->data['msg'] = "{$className}.php 不存在!";
					$view->loadTpl('error');
				}
			}
		} elseif(substr($className,-7) == 'Service') {
			if( is_file(SERVICES_MPHP."{$className}.php") )		include SERVICES_MPHP."{$className}.php";
			elseif( is_file(SERVICES_PATH."{$className}.php") )	include SERVICES_PATH."{$className}.php";
			else {
				if( $flag ) {
					goto_404();
				} else {
					$view->data['title'] = 'service模块不存在！';
					$view->data['msg'] = "{$className}.php 不存在!";
					$view->loadTpl('error');
				}
			}
		} elseif(substr($className,-3) == 'Dao') {
			if( is_file(DAOS_MPHP."{$className}.php") )		include DAOS_MPHP."{$className}.php";
			elseif( is_file(DAOS_PATH."{$className}.php") )	include DAOS_PATH."{$className}.php";
			else {
				if( $flag ) {
					goto_404();
				} else {
					$view->data['title'] = 'dao模块不存在！';
					$view->data['msg'] = "{$className}.php 不存在!";
					$view->loadTpl('error');
				}
			}
		} else {
			if( $flag ) {
				goto_404();
			} else {
				$view->data['title'] = '访问错误！';
				$view->data['msg'] = "未定义操作 $className";
				$view->loadTpl('error');
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
		global $CFG;
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
		foreach($CFG['main_dir'] as $dir) {
			createDirs($dir['path'],$dir['totle']);
		}
		unset($CFG);
	}
	
	//初始化数据库
	public static function initDb() {
		global $CFG;
		$db = new pdoModel($CFG['pdo']);
		$db->initDb('initdata/tables.sql');
		unset($CFG);
		unset($db);
	}
}

//简单路由
class router {
	public static $controller = 'index';
	public static $action = 'index';

	public static function init() {
		$path_info = self::path_info();
		$path_info = preg_replace('#^/\w+\.php#', '', $path_info);
mlog($path_info);
		if( !empty($path_info) ) {
			$splits = explode('/', trim($path_info, '/'));
		} else {
			return false;
		}

		if( empty($_GET['c']) ) $_GET['c'] = empty($splits[0]) ? self::$controller : $splits[0];
		if( empty($_GET['a']) ) $_GET['a'] = empty($splits[1]) ? self::$action : $splits[1];

		$count = count($splits);
		for($i = 2; $i < $count; $i += 2) {
			if( isset($splits[$i]) && isset($splits[$i+1])) $_GET[$splits[$i]]=$splits[$i+1];
		}
		if( is_array($_GET)) $_REQUEST = array_merge($_GET, $_REQUEST);
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
						if($count > 0) break; //只要匹配上一个，则停止匹配，故在router.config.php从上到下有优先权。
					}
				}
			}
			$html_url_subffix = $CFG['url_suffix'];
			if( $html_url_subffix && TRUE == ($url_suffix_pos = strrpos($path_info, $html_url_subffix) ) ) $path_info = substr($path_info, 0, $url_suffix_pos);
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
			global $CFG;
			self::$CFG = &$CFG;
			unset($CFG);
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
		global $CFG;
		//if(!self::$mem) self::$mem = new memcachedModel($CFG['memcached']);
		if(!self::$httpsqsModel) self::$httpsqsModel = M('httpsqs');
		$CFG['mem'] = self::$mem;
		unset($CFG);
	}
}

//为逻辑处理服务提供数据库操作
class dao {
	public static $db = 0;
	public static $table_prefix = 0;
	
	public function __construct() {
		global $CFG;
		if(!self::$db) self::$db = new pdoModel($CFG['pdo']);
		if(!self::$table_prefix) self::$table_prefix = $CFG['table_prefix'];
		unset($CFG);
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
		
		global $CFG;
		if(!$CFG['debug']) {
			$arrData['html'] = mini_html( $this->merger($arrData['html']) );
		} else {
			$arrData['html'] = $this->merger($arrData['html']);
		}
		unset($CFG);
		
		//管理员浏览页面不缓存
		//游客浏览页面，创建网站地图，缓存
		if( !isset($_SESSION['user']['admin']) || $_SESSION['user']['admin'] >= 9 || $_GET['c'] == 'adminCache' ) {
			$date = date('Y-m-d H:i');
			$arrData['html'] .= "<!-- mPHP html cache $date -->";
			if( ($strDir = dirname($arrData['file']) ) && !is_dir($strDir) ) mkdir($strDir,0775,true);
			file_put_contents($arrData['file'],$arrData['html']);
			$createTime = filemtime($arrData['file']) ;
			header("Cache-Control: max-age=0");
			header("Last-Modified: " . date("D, d M Y H:i:s",$createTime) );
		}
		echo $arrData['html'];
	}
	
	//编译模版
	public function tplCompile($str) {
		global $CFG;
		//处理include标签
		$str = preg_replace( "/<!--#\s*layout\s*:\s*([^ ]+);*\s*#-->/", '<?php $this->_include(\'\\1\') ?>', $str );
		/*替换<!--# #-->标签为<?php ?>*/
		$str = strtr($str,array($CFG['template']['tag_left'] => '<?php ', $CFG['template']['tag_right'] => ' ?>'));
		unset($CFG);
		return $str;
	}
	
	//合并style、script
	public function merger($str) {
		$root = U();
		$len = strlen($root);
		$arrMergerCss = $arrMergerJs = array();
		$script = "#<script.*src=['\"](/.+\.js)['\"].*></script>#";
		$style	= "#<link.*href=['\"](/[^'\"]+\.css[^'\"]*)['\"].*>#";
		$i = preg_match_all($style,$str,$arrStyle);
		$str = preg_replace($style,'',$str);
		$i = preg_match_all($script,$str,$arrScript);
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
		$css = $this->file_merger($arrMergerCss,crc32(implode($arrMergerCss,'')).'.css');
		$js = $this->file_merger($arrMergerJs,crc32(implode($arrMergerJs,'')).'.js');
		$str = strtr( $str,array('<link />'=>$css) );
		$str = strtr( $str,array('<script></script>'=>$js) );
		return $str;
	}
	
	public function file_merger($arrFile,$out,$cache=false) {
		global $CFG;
		$url = STATIC_URL;
		$return = "{$url}merger/{$out}";
		$dir = STATIC_PATH. 'merger/';
		$out = "{$dir}{$out}";
		
		if( substr($arrFile[0],-2) == 'js' ) {
			 $type = 'js';
		} elseif( substr($arrFile[0],-3) == 'css' ) {
			$type = 'css';
			//2014-2-28
			//由于java压缩css在手机上无法自适应屏幕，所以暂时使用PHP压缩
			$CFG['java'] = 0;
		}
		
		if( file_exists($out) ) {
			//判断是否有文件被修改
			$flag = 0;//0:没有文件被修改;1:有文件被修改
			foreach($arrFile as $file) {
				if( filemtime($out) != filemtime(INDEX_PATH.$file) ) {
					$flag = 1;
					break;
				}
			}
		} else {
			$flag = 1;
		}
		
		//当文件不存在,或者子文件被修改,就执行下边的程序
		if( $flag || $CFG['debug']) {
			//调试模式,按常规加载js,css
			if( $CFG['debug'] ) {
				$out = '';
				foreach($arrFile as $key => $file) {
					if( $type == 'js' ) {
						$out .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
					} elseif( $type == 'css' ) {
						$out .= "<link href=\"{$file}\" rel=\"stylesheet\" type=\"text/css\">\n";
					}
				}
				return $out;
			} else {
			//正式环境启动压缩
				ob_start();
				foreach($arrFile as $key => $file) {
					include INDEX_PATH.$file;
				}
				$str = ob_get_clean();
				
				$tmp = $dir. 'tmp';
				
				if($CFG['java']) {
				//java程序精简文件
					file_put_contents($tmp,$str);
					if( $type == 'js' ) {
						$exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.2.jar --type js --charset utf-8 -v $tmp > $out";//压缩JS
					} elseif( $type == 'css' ) {
						 $exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.2.jar --type css --charset utf-8 -v $tmp > $out";//压缩CSS
					}
					`$exec` ;
				} else {
				//php程序精简文件
				//测试阶段
					$str = preg_replace( '#/\*.+?\*/#s','', $str );//过滤注释 /* */
					$str = preg_replace( '#(?<!http:)(?<!\\\\)(?<!\')(?<!")//(?<!\')(?<!").*\n#','', $str );//过滤注释 //
					$str = preg_replace( '#[\n\r\t]+#',' ', $str );//回车 tab替换成空格
					$str = preg_replace( '#\s{2,}#',' ', $str );//两个以上空格合并为一个
					file_put_contents($out,$str);
				}
				$time = filemtime($out);
				foreach($arrFile as $file) {
					touch(INDEX_PATH.$file,$time);
				}
			}
		}
		
		//2014-2-28
		//由于java压缩css在手机上无法自适应屏幕，所以暂时使用PHP压缩
		if( $type == 'css' ) {
			$CFG['java'] = 1;
		}
		
		unset($CFG);
		if( $type == 'js' ) return "<script type=\"text/javascript\" src=\"{$return}\"></script>\n";
		elseif( $type == 'css' ) return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$return}\">\n";
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
		global $CFG;
		$time = $_SERVER['REQUEST_TIME'];
		if( isset($_SESSION['user']['admin']) && $_SESSION['user']['admin'] < 9 ) {
			$cacheTime = 0;
		}
		$createTime = file_exists($file) ? filemtime($file) : 0;
		if( ($createTime + $cacheTime >= $time ) && !$CFG['debug'] ) {
			unset($CFG);
			$createTime = date("D, d M Y H:i:s",$createTime);
			header("Cache-Control: max-age=0");
			header("Last-Modified: " . $createTime );
			if( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $createTime ) {
				header('HTTP/1.1 304 Not Modified'); 
			} else {
				include $file;
			}
			return true;
		} else {
			unset($CFG);
		}
		return false;
	}
	
	//清除所有静态Html
	public static function clearCache() {
		clearDir(CACHE_PATH);
	}
	
}

class safe {
	public $string;
	public $strKey;
	
	public function __construct() {
		if( !isset($_SESSION) )session_start();
		
		$this->strKey = '';
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
			if( get_magic_quotes_gpc() ) {
				$value = htmlspecialchars(trim($value), ENT_QUOTES);
			} else {
				$value = addslashes(htmlspecialchars(trim($value), ENT_QUOTES));
			}
		}
	}
	
	//还原字符串
	public static function restore($str) {
		if(get_magic_quotes_gpc()) return htmlspecialchars_decode(stripcslashes($str));
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