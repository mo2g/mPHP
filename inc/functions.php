<?php
/*
作者:moyancheng
最后更新时间:2013-05-17
最后更新时间:2015-05-06
*/
if( !defined('INIT_MPHP') ) exit;

/*
功能：$obj = new newClass();	//自动加载特定的php文件，省去繁琐的include
*/
function autoload($className) {
	static $view = false;
	static $flag = false;

	if( $view === false ) $view = new view();
	if( $flag === false ) $flag = !$GLOBALS['CFG']['debug'];

	$file = strtr($className,array('\\' => '/')) . '.php';
	
	if( substr($file,-14) == 'Controller.php' ) {
		if( is_file(CONTROLLERS_PATH.$file) ) {
			include CONTROLLERS_PATH.$file;
		} else {
			if( $flag ) {
				goto_404();
			} else {
				$view->data['title'] = '控制器不存在！';
				$view->data['msg'] = "{$file} 不存在!";
				$view->loadTpl('error');
			}
		}
	} elseif(substr($file,-11) == 'Service.php') {
		if( is_file(SERVICES_PATH.$file) ) {
			include SERVICES_PATH.$file;
		} else {
			if( $flag ) {
				goto_404();
			} else {
				$view->data['title'] = 'service模块不存在！';
				$view->data['msg'] = "{$file} 不存在!";
				$view->loadTpl('error');
			}
		}
	} elseif(substr($file,-7) == 'Dao.php') {
		if( is_file(DAOS_PATH.$file) ) {
			include DAOS_PATH.$file;
		} else {
			if( $flag ) {
				goto_404();
			} else {
				$view->data['title'] = 'dao模块不存在！';
				$view->data['msg'] = "{$file} 不存在!";
				$view->loadTpl('error');
			}
		}
	} elseif( substr($file,-9) == 'Model.php' ) {
		if( is_file(MODELS_MPHP.$file) ) {
			include MODELS_MPHP.$file;
		} elseif( is_file(MODELS_PATH.$file) ) {
			include MODELS_PATH.$file;
		} else {
			if( $flag ) {
				goto_404();
			} else {
				$view->data['title'] = 'Model模块不存在！';
				$view->data['msg'] = "{$file} 不存在!";
				$view->loadTpl('error');
			}
		}
	} else {
		if( $flag ) {
			goto_404();
		} else {
			$view->data['title'] = '访问错误！';
			$view->data['msg'] = "未定义操作 $file";
			$view->loadTpl('error');
		}
	}
}

//显示某时刻运行详情
//使用示例:
//run_info(__FILE__,__LINE__,1);
function run_info($file,$line,$true = false) {
	echo "<div style='display:none'><br>程序运行至文件 $file ,第 $line 行共消耗",(microtime(1) - $GLOBALS['CFG']['start_time']) * 1000,'ms；<br>';
	if($true) {
		$intSelectTotle = $GLOBALS['CFG']['db']['select']['totle'];
		$intSelectError = $GLOBALS['CFG']['db']['select']['error'];
		$intInsertTotle = $GLOBALS['CFG']['db']['insert']['totle'];
		$intInsertError = $GLOBALS['CFG']['db']['insert']['error'];
		$intUpdateTotle = $GLOBALS['CFG']['db']['update']['totle'];
		$intUpdateError = $GLOBALS['CFG']['db']['update']['error'];
		$intDeleteTotle = $GLOBALS['CFG']['db']['delete']['totle'];
		$intDeleteError = $GLOBALS['CFG']['db']['delete']['error'];
		$intTotle = $intSelectTotle + $intInsertTotle + $intUpdateTotle + $intDeleteTotle;
		echo '<br><table width="300px">',
				'<caption>数据库操作共',$intTotle,'次</caption>',
				'<tr><th>sql语句</th><th>操作总数</th><th>错误次数</th></tr>',
				'<tr><td align=center>select</td><td align=center>',$intSelectTotle,'</td><td align=center>',$intSelectError,'</td></tr>',
				'<tr><td align=center>insert</td><td align=center>',$intInsertTotle,'</td><td align=center>',$intInsertError,'</td></tr>',
				'<tr><td align=center>update</td><td align=center>',$intUpdateTotle,'</td><td align=center>',$intUpdateError,'</td></tr>',
				'<tr><td align=center>delete</td><td align=center>',$intDeleteTotle,'</td><td align=center>',$intDeleteError,'</td></tr>',
			'</table><pre>';
		print_r(get_included_files());
		echo '</pre>';
	}
	echo '</div>';
}

/*
功能：
	1.读取配置文件单个参数，或多个参数（数组）
	2.修改配置文件单个参数，或多个参数（数组）
$file：文件名
$key:读取/修改的键值，若为空则返回该配置文件所有参数
	1.$key可以当作二维数使用，使用方法$key = a.b ，函数会读取配置文件中的array[a][b]
$value:新参数/数组
*/
function C($path,$key = '',$value = '') {
	static $arrCfg = array();
	if( isset($arrCfg[$key]) ) {
	} else {
	}
	if( substr($path,-10) != 'config.php') $path = CONF_PATH. "$path.config.php";
	if(is_file($path)) {
		$arrConfig = include $path;
		//直接返回值
		if($value == '') {
			if($key == '') {							//返回该配置文件所有参数
				return $arrConfig;
			} elseif(array_key_exists($key,$arrConfig)) {	//返回对应键值的参数
				return $arrConfig["$key"];
			} elseif(strpos($key,'.')) {				//返回对应二维数组的参数
				$arrKey = explode('.',$key);
				if(isset($arrConfig[$arrKey[0]][$arrKey[1]])) {
					return $arrConfig[$arrKey[0]][$arrKey[1]];
				} else {
					return false;//echo "配置array[$arrKey[0]][$arrKey[1]]不存在<br><br>";
				}
			} else {
				return false;//配置文件' . $file . ' 键值'. $key.'不存在<br>';
			}
		} else {
		//更新参数与配置文件，并返回该参数
			if(strpos($key,'.')) {//更新二维数组的参数
				$arrKey = explode('.',$key);
				$arrConfig[$arrKey[0]][$arrKey[1]] = $value;
				$config = "<?php\nreturn ". var_export($arrConfig,1) .';';
				file_put_contents($path,$config);
				return $arrConfig[$arrKey[0]][$arrKey[1]];
			} else {
				//更新对应键值的参数
				$arrConfig[$key] = $value;
				$config = "<?php\nreturn ". var_export($arrConfig,1) .';';
				file_put_contents($path,$config);
				return $arrConfig[$key];
			}
		}
	} else {
		return false;//echo '配置文件'.$file.'不存在<br>';
	}
}

/*
功能：url转换
?c=article&a=category&type=node&key=a&page=2
   article  -category     -node    -a     -2.html

?c=article&a=index&type=node&page=1
c=article
a=index
type=node
page=1

   article  -index     -node     -1.html

?c=article&a=view&id=1
   article  -view   -1.html
U('?c=article&a=view',array('id'=1,'page'=1));
U('?c=article&a=view&id=1&page=1');
article-view-1-1.html
article/view/1/1.html
*/
function U($strUrl = '',$true = true) {
	$arrGet = $arrVal =  $arrData =  array();
	$intDepth = 0;
	$intDepthMax = $GLOBALS['CFG']['url_depth'];
	
	if( $strUrl === '' ) return $_GET['c'] == 'blog' ? BLOG_URL : INDEX_URL;
	
	$arrData = array(
		'?' => '',
		'index.php' => ''
	);
	$strUrl = strtr($strUrl,$arrData);
	
	//如果链接地址以http://开头就不用INDEX_URL
	if( substr($strUrl,0,7) == 'http://' || substr($strUrl,0,8) == 'https://' ) {
		$leng = strpos($strUrl,'/',7);
		$url = substr($strUrl,0,$leng).'/';
		$strUrl = strtr($strUrl,array($url => ''));
	} else {
		$url = INDEX_URL;
	}
	
	if($GLOBALS['CFG']['url_type'] == 'DIR') $flag = '/';
	elseif($GLOBALS['CFG']['url_type'] == 'NODIR') $flag = '-';
	else {
		if($strUrl[0] != '?') $strUrl = "{$url}?{$strUrl}";
		return $strUrl;
	}
	
	$arrData = explode('&',$strUrl);
	foreach( $arrData as $str) {
		$arrVal = explode('=',$str);
		$arrGet[$arrVal[0]] = $arrVal[1];
	}

	$strUrl = '';
	foreach($arrGet as $key => $val) {
		if( $intDepth < $intDepthMax ) {
			$strUrl .= "{$val}{$flag}";
			++$intDepth;
		} else {
			$strUrl .= "{$val}-";
		}
	}
	
	$strUrl =  $url.trim($strUrl,'/-');
	if($true)$strUrl .= $GLOBALS['CFG']['url_suffix'];
	
	return $strUrl;
}

//调试，输出变量
function P($val,$true = true) {
	echo '<pre>';
	if( is_array($val) ) print_r($val);
	//elseif( is_string($val) || is_numeric($val) ) echo $val;
	else var_dump($val);
	echo '</pre>';
	if($true) _exit();
}

//返回唯一的 Service 实例
//同一个 Service 只实例化一次
function S($service) {
	static $arrService = array();
	$service = "{$service}Service";
	if( !isset( $arrService[$service] ) ) $arrService[$service] = new $service;
	return $arrService[$service];
}
//返回唯一的 Dao 实例
//同一个 Dao 只实例化一次
function D($dao) {
	static $arrDao = array();
	$dao = "{$dao}Dao";
	if( !isset( $arrDao[$dao] ) ) $arrDao[$dao] = new $dao;
	return $arrDao[$dao];
}
//返回唯一的 Model 实例
//同一个 Model 只实例化一次
function M($model,$arrConfig = array() ) {
	static $arrModel = array();
	$model = "{$model}Model";
	if( !isset( $arrModel[$model] ) ) {
		if( $arrConfig ) {
			$arrModel[$model] = new $model($arrConfig);
		} else {
			$arrModel[$model] = new $model();
		}
	}
	return $arrModel[$model];
}

/*
功能：多个js或css，合并为一个js或css，并压缩
$arrPath:合并文件数组
$out:输出文件
$cache:是否缓存，默认为false,会输出mo2g.js?1389424132
*/
function file_merger($arrFile,$out,$cache=false) {
	$url = STATIC_URL;
	$return = "{$url}merger/{$out}";
	$dir = STATIC_PATH. 'merger/';
	$out = "{$dir}{$out}";
	
	if( substr($arrFile[0],-2) == 'js' ) {
		 $type = 'js';
	} elseif( substr($arrFile[0],-3) == 'css' ) {
		$type = 'css';
	}

	//调试模式,按常规加载js,css
	if( $GLOBALS['CFG']['debug'] ) {
		$out = '';
		foreach($arrFile as $key => $file) {
			if( $type == 'js' ) {
				$out .= "<script src=\"{$file}\" type=\"text/javascript\"></script>\n";
			} elseif( $type == 'css' ) {
				$out .= "<link href=\"{$file}\" rel=\"stylesheet\" type=\"text/css\">\n";
			}
		}
		return $out;
	}
	
	if( file_exists($out) ) {
		//判断是否有文件被修改
		$cacheFile = M('cacheFile');
		if( $type == 'js' ) {
			$cacheFile->in('js');
		} else {
			$cacheFile->in('css');
		}
		$arrTime = $cacheFile->get_all();
		$flag = 0;//0:没有文件被修改;1:有文件被修改
		foreach($arrFile as $file) {
			$key = strtr($file,'.',',');
			$time = $arrTime[$key];//缓存的最后更新时间
			$filemtime = filemtime(INDEX_PATH.$file);//当前文件最后更新时间
			if( $time != $filemtime ) {
				$flag = 1;
				$cacheFile->set($key,$filemtime);//更新缓存
			}
		}
	} else {
		$flag = 1;
	}
	
	if( $flag ) {
		//当文件不存在,或者子文件被修改,就执行下边的程序
		//正式环境启动压缩
		ob_start();
		foreach($arrFile as $key => $file) {
			include INDEX_PATH.$file;
		}
		$str = ob_get_clean();
		
		$tmp = $dir. 'tmp';
		
		if($GLOBALS['CFG']['java']) {
		//java程序精简文件
			file_put_contents($tmp,$str);
			//文档地址：http://yui.github.io/yuicompressor/
			if( $type == 'js' ) {
				$exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.8.jar --type js --charset utf-8 $tmp -o $out";//压缩JS
			} elseif( $type == 'css' ) {
				//$exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.8.jar --type css --charset utf-8 --nomunge --preserve-semi --disable-optimizations $tmp -o $out";//压缩CSS
				$exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.8.jar --type css --charset utf-8 $tmp -o $out";//压缩CSS
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
	}
	
	//2014-2-28
	//由于java压缩css在手机上无法自适应屏幕，所以暂时使用PHP压缩
	if( $type == 'css' ) {
		//$GLOBALS['CFG']['java'] = 1;
	}
	
	if( $type == 'js' ) return "<script type=\"text/javascript\" src=\"{$return}\"></script>\n";
	elseif( $type == 'css' ) return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$return}\">\n";
}

//精简html : 清除换行符,清除制表符,去掉注释标记
function mini_html($html) {
	$arrData = preg_split( '/(<pre.*?\/pre>)/ms', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$html = '';
	foreach ( $arrData as $str ) {
		if ( strpos( $str, '<pre' ) !== 0 ) {
			$str = preg_replace( '#/\*.+?\*/#s','', $str );//过滤脚本注释 /* */
			$str = preg_replace( '#(?<!:)(?<!\\\\)(?<!\')(?<!")//(?<!\')(?<!").*\n#','', $str );//过滤脚本注释 //
			$str = preg_replace( '#<!--[^\[<>].*[^\]!]-->#sU', '', $str );//移除html注释 <!-- --> 
			$str = preg_replace( '#[\n\r\t]+#', ' ', $str );//空格替换回车或tab
			$str = preg_replace( '#\s{2,}#', ' ', $str );//多个空格合并为一个空格
			$str = preg_replace( '#>\s<#', '><', $str );//移除标签间的空白
		}
		$html .= $str;
	}
	return $html;
}

//网站维护期间使用，避免搜索引擎误判
function goto_503() {
	mPHP::status(503);
	$view = new view();
	$file = CACHE_PATH . 'html/404.html';
	$view->cache($file,$GLOBALS['CFG']['html_cache_time']);
	$view->loadTpl('503');
	_exit();
	return true;
}

//404，页面没找到
function goto_404() {
	mPHP::status(404);
	$view = new view();
	$file = INDEX_PATH . '404.html';
	$cacheTime = $GLOBALS['CFG']['html_cache_time'];
	$createTime = file_exists($file) ? filemtime($file) : 0;
	$time = $_SERVER['REQUEST_TIME'];
	if( ($createTime + $cacheTime >= $time ) && !$GLOBALS['CFG']['debug'] ) {
		include $file;
		_exit();
		return true;
	}
	$is_mobile = is_mobile();
	
	$blogService = new blogService();
	$arrBlogType = $blogService->getTypeByBlog();
	$arrTypeAll = $blogService->getTypeAll();
	$arrTypeTop = $blogService->getTypeByTop();
	$arrBlogNav = $arrTypeAll[$arrBlogType[0]['id']];
	$arrTags = $blogService->getTagsAll();
	//$arrNav = $blogService->getCategoryTitleByEnglishInBlogNav('blog',$arrBlogNav);
	
	//只显示包含5篇以上文章的标签
	foreach( $arrTags as $key => $row ) {
		if( $row['totle'] < 5 ) unset($arrTags[$key]);
	}
	
	$view->data['type_top'] = $arrTypeTop;
	$view->data['type_all'] = $arrTypeAll;
	$view->data['blog_nav'] = $arrBlogNav;
	$view->data['tags'] = $arrTags;
	$view->data['title'] = '404';
	if( $is_mobile ) {
		if($GLOBALS['CFG']['404']) $view->loadTpl('mobile_404',$file);
		else $view->loadTpl('admin/mobile_404',$file);
	} else {
		if($GLOBALS['CFG']['404']) $view->loadTpl('404',$file);
		else $view->loadTpl('admin/404',$file);
	}
	_exit();
}

//403，页面没权限
function goto_403() {
	mPHP::status(301);
	$view = new view();
	$file = CACHE_PATH . 'html/403.html';
	$cache = $view->cache($file,$GLOBALS['CFG']['html_cache_time']);
	if( $cache) {
		_exit();
		return true;
	}
	$view->loadTpl('403');
	_exit();
}

//301，页面重定向
function goto_301($url) {
	mPHP::status(301);
	mPHP::header('Location',$url);
	_exit();
}

//302，页面临时跳转
function goto_302($url) {
	mPHP::status(302);
	mPHP::header('Location',$url);
	_exit();
}




function msgDb() {
	$db = new pdoModel($GLOBALS['CFG']['pdo']);
	$arrData = $arrTmp = array();
	$table_index = "{$GLOBALS['CFG']['table_prefix']}message_index";
	$table_content = "{$GLOBALS['CFG']['table_prefix']}message_content";
	$strSql = "select a.*,b.reply b_reply
	from $table_index a,$table_content b 
	where a.id = b.id and b.reply != ''
	";
	$arrData = $db->query($strSql)->fetch_all();
	//P($arrData);
	foreach($arrData as $row) {
		$arrTmp = array(
			'aid' => $row['aid'],
			'uid' => 1,
			'pid' => $row['id'],
			'rid' => $row['id'],
			'reply' => 1,
			'name' => '磨延城',
			'email' => 'moyancheng@gmail.com',
			'home' => 'blog.mo2g.com',
			'date' => $row['reply_date'],
		);
		$db->insert($table_index,$arrTmp);
		
		$arrTmp = array(
			'id' => $db->insert_id("{$table_index}_id_seq"),
			'content' => $row['b_reply'],
		);
		$db->insert($table_content,$arrTmp);
	}
}

//检测访问设备是否为手机，手机访问返回true，非手机则返回false
function is_mobile() {
	$is_mobile = false;
	$user_agent = $_SERVER['HTTP_USER_AGENT'] ? $_SERVER['HTTP_USER_AGENT'] : $_SERVER['USER-AGENT'];
	$mobile_agents = Array("240x320","acer","acoon","acs-","abacho","ahong","airness","alcatel",
	"amoi","android","anywhereyougo.com","applewebkit/525","applewebkit/532","asus","audio","au-mic",
	"avantogo","becker","benq","bilbo","bird","blackberry","blazer","bleu","cdm-","compal","coolpad",
	"danger","dbtel","dopod","elaine","eric","etouch","fly ","fly_","fly-","go.web","goodaccess",
	"gradiente","grundig","haier","hedy","hitachi","htc","huawei","hutchison","inno","ipad","ipaq",
	"ipod","jbrowser","kddi","kgt","kwc","lenovo",
	"lg ","lg2","lg3","lg4","lg5","lg7","lg8","lg9","lg-","lge-","lge9",
	"longcos","maemo","mercator","meridian","micromax","midp","mini","mitsu",
	"mmm","mmp","mobi","mot-","moto","nec-","netfront","newgen","nexian","nf-browser","nintendo",
	"nitro","nokia","nook","novarra","obigo","palm","panasonic","pantech","philips","phone","pg-",
	"playstation","pocket","pt-","qc-","qtek","rover","sagem","sama","samu","sanyo","samsung","sch-",
	"scooter","sec-","sendo","sgh-","sharp","siemens","sie-","softbank","sony","spice","sprint","spv",
	"symbian","tablet","talkabout","tcl-","teleca","telit","tianyu","tim-","toshiba","tsm","up.browser",
	"utec","utstar","verykool","virgin","vk-","voda","voxtel","vx","wap","wellco","wig browser","wii",
	"windows ce","wireless","xda","xde","zte");
	foreach($mobile_agents as $device) {
		if(stristr($user_agent, $device)) {
			$is_mobile = true;
			break;
		}
	}
	return $is_mobile;
}

/**
* 判断是否ajax方式
* @return bool
*/
function is_ajax() {
	if (!empty($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
		return true;
	}
	return false;
}

//获取文件拓展名
function getFileExt($strName) {
    return strtolower( strrchr( $strName , '.' ) );
}

function mimg($img,$w,$h) {
	if( strpos($img,'attachments') ) {
		$h = empty($h) ? $h : $w;
	} else {
		return $img;
	}
}

function token() {
	return safe::getToken();
}

function mlog($log = '',$file = '') {
	if( $file == '' ) $file = INDEX_PATH.'log.txt';
	$log = print_r($log,true);
	$log = "date：" . date("Y-m-d H:i:s") . "\n{$log}\n";
	file_put_contents($file,$log,FILE_APPEND|LOCK_EX);
}

/*
swoole中不允许试用exit，所以使用如下方式记录PHP是否执行过  _exit()
已经执行返回：true
没有执行返回：false
*/
function _exit() {
	if( mPHP::$swoole ) {
		if( $GLOBALS['EXIT_MPHP'] ) {
			return true;
		} else {
			$GLOBALS['EXIT_MPHP'] = 1;
			return false;
		}
	} else {
		exit;
	}
}