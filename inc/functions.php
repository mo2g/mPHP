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
				if(isset($arrConfig["$arrKey[0]"]["$arrKey[1]"])) {
					return $arrConfig["$arrKey[0]"]["$arrKey[1]"];
				} else {
					echo "配置array['$arrKey[0]']['$arrKey[1]']不存在<br><br>";
				}
			} else {
				return false;//配置文件' . $file . ' 键值'. $key.'不存在<br>';
			}
		} else {
		//更新参数与配置文件，并返回该参数
			if(strpos($key,'.')) {//更新二维数组的参数
				$arrKey = explode('.',$key);
				if(isset($arrConfig["$arrKey[0]"]["$arrKey[1]"])) {
					$arrConfig["$arrKey[0]"]["$arrKey[1]"] = $value;
					$config = "<?php\nreturn ". var_export($arrConfig,1) .';';
					file_put_contents($path,$config);
					return $arrConfig["$arrKey[0]"]["$arrKey[1]"];
				} else {
					echo "配置array['$arrKey[0]']['$arrKey[1]']不存在<br><br>";
				}
			} else {			//更新对应键值的参数
				if(isset($arrConfig["$key"])) {
					$arrConfig["$key"] = $value;
					$config = "<?php\nreturn ". var_export($arrConfig,1) .';';
					file_put_contents($path,$config);
					return $arrConfig["$key"];
				} else {
					$arrConfig[] = $value;
					$config = "<?php\nreturn ". var_export($arrConfig,1) .';';
					file_put_contents($path,$config);
					return $arrConfig;
				}
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
功能：清空指定文件夹内的所有文件，文件夹保留
*/
function clearDir2($path) {
	if($handle = opendir($path)) {
		while(false !== ($file = readdir($handle) ) ) {
			if($file != '.' && $file != '..') {
				is_dir($path.$file) ? clearDir($path.$file.'/') : unlink($path.$file);
			}
		}
		closedir($handle);
	}
}
/*
功能:清空文件夹
$dir:
$ture:当为true时,同时删除目录
*/
function clearDir($dir,$true = false) {
	foreach(glob($dir . '/*') as $file) {
		is_dir($file) ? clearDir($file,$true) : unlink($file);
	}
	if($true)rmdir($dir);
}

/*
在指定路径$path下创建0~$max个目录
*/
function createDirs($path,$max) {
	createDir($path);
	$i = 0;
	while($i < $max) {
		if( !is_dir($path.$i) ) mkdir($path.$i);
		++$i;
	}
}
/*
在指定路径$path下创建0~$max个目录
*/
/*
function createDir($path,$max) {
	if(is_dir($path)) {
		$i = 0;
		while($i < $max) {
			if(!is_dir($dir.$i)) mkdir($dir.$i);
			++$i;
		}
	} else {
		mkdir($dir);
		$i = 0;
		while($i < $max) {
			if(!is_dir($dir.$i)) mkdir($dir.$i);
			++$i;
		}
	}
}
 */

/*
功能：递归创建目录
例1：要创建/www/1/2/3,即使/www目录不存在，依然可以递归创建
效率2n - 1

暂时弃用,发现mkdir($path,true)有递归功能
*/
function createDir($path) {
	$dir = dirname($path);
	if( is_dir($dir) ) {
		if( !is_dir($path) ) mkdir($path);
	} else {
		createDir($dir);
		createDir($path);
	}
	return true;
}

//创建缓存目录
function dirCache() {
	if($arrDir = C('cache','html_cache_dir')) {
		if(is_dir(CACHE_PATH.'html')) {
			foreach($arrDir as $key => $value) {
				$dir = CACHE_PATH.'html/'.$key.'/';
				if(is_dir($dir)) {
					$i = 0;
					while($i < $value) {
						if(!is_dir($dir.$i)) mkdir($dir.$i);
						++$i;
					}
				} else {
					mkdir($dir);
					$i = 0;
					while($i < $value) {
						if(!is_dir($dir.$i)) mkdir($dir.$i);
						++$i;
					}
				}
			}
		} else {
			mkdir(CACHE_PATH.'html');
			dirCache();
		}
	}
}


function substrGbk2($str, $start, $len) {
	$tmpstr = '';
	$strlen = $start + $len;
	for($i = $start; $i <= $strlen; ++$i) {
		if( ord(substr($str, $i, 1)) > 0xa0 ) {
			$tmpstr .= substr($str, $i, 2);
			++$i;
		} else $tmpstr .= substr($str, $i, 1);
	}
	return $tmpstr;
}

//截取字符串字串-GBK (PHP)
function substrGbk($str, $start, $len) {
	$count = 0;
	for($i = $start; $i < strlen($str); ++$i){
		if($count == $len) break;
		if(preg_match("/[\x80-\xff]/", substr($str, $i, 1))) ++$i;
		++$count;         
	}
	//return substr($str, 0, $i);
	return substr($str, 0, $count);
}
//截取字符串，直到遇到关键字停止
function substrForKey($str,$key = '') {
	if( $key == '' ) $key = '<!--mPHP-->';
	if( ($leng = strpos($str,$key) ) !== false ) return substr($str,0,$leng);
	else return $str;
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
		//2014-2-28
		//由于java压缩css在手机上无法自适应屏幕，所以暂时使用PHP压缩
		//$GLOBALS['CFG']['java'] = 0;
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
		$time = filemtime($out);
		foreach($arrFile as $file) {
			touch(INDEX_PATH.$file,$time);
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




//ThinkPHP start
/**
 +----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
 +----------------------------------------------------------
 * @static
 * @access public
 +----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix) return $slice."…";
    return $slice;
}

/**
 +----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码 默认长度6位 字母和数字混合
 +----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
 +----------------------------------------------------------
 * @return string
 +----------------------------------------------------------
 */
function rand_string($len = 6,$type = 0,$addChars = '') {
    $str ='';
    switch($type) {
        case 0:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 1:
            $chars= str_repeat('0123456789',3);
            break;
        case 2:
            $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
            break;
        case 3:
            $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
            break;
        case 4:
            $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
    }
    if($len > 10 ) {//位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat($chars,$len) : str_repeat($chars,5);
    }
    if($type != 4) {
        $chars   =   str_shuffle($chars);
        $str     =   substr($chars,0,$len);
    }else{
        // 中文随机字
        for($i=0;$i<$len;$i++){
          $str.= msubstr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
        }
    }
    return $str;
}

//ThinkPHP end

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
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
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
	if( is_array( $log ) ) {
		$str = '';
		foreach( $log as $key =>  $row ) {
			$str .= "$key = $row\n";
		}
		$log = $str;
	}
	$fp = fopen($file,"a");
	flock($fp, LOCK_EX) ;
	fwrite($fp,"执行日期：".strftime("%Y-%m-%d %H:%M:%S",time())."\n".$log."\n");
	flock($fp, LOCK_UN);
	fclose($fp);
}

/*
swoole中不允许试用exit，所以使用如下方式记录PHP是否执行过  _exit()
已经执行返回：true
没有执行返回：false
*/
function _exit() {
	if( mPHP::$swoole ) {
		if( defined('EXIT_MPHP') ) {
			return true;
		} else {
			define('EXIT_MPHP' , 1);
			return false;
		}
	} else {
		exit;
	}
}