<?php
/*
作者:moyancheng
最后更新时间:2013-05-17
*/
if( !defined('INIT_MPHP') ) exit;

/*
功能：$obj = new newClass();	//自动加载特定的php文件，省去繁琐的include
*/
function __autoload($className) {
	global $CFG;
	static $view = 0;
	if(!$view) $view = new view();
	if( substr($className,-10) == 'Controller' ) {
		if( is_file(CONTROLLERS_MPHP."{$className}.php") ) include CONTROLLERS_MPHP."{$className}.php";
		elseif( is_file(CONTROLLERS_PATH."{$className}.php") ) include CONTROLLERS_PATH."{$className}.php";
		else {
			if($CFG['404']) goto_404();
			$view->data['title'] = '控制器不存在！';
			$view->data['msg'] = "{$className}.php 不存在!";
			$view->loadTpl('error');
		}
	} elseif( substr($className,-5) == 'Model' ) {
		if( is_file(MODELS_MPHP."{$className}.php") )		include MODELS_MPHP."{$className}.php";
		elseif( is_file(MODELS_MPHP."system/{$className}.php") )	include MODELS_MPHP."system/{$className}.php";
		elseif( is_file(MODELS_PATH."{$className}.php") )	include MODELS_PATH."{$className}.php";
		else {
			if($CFG['404']) goto_404();
			$view->data['title'] = 'Model模块不存在！';
			$view->data['msg'] = "{$className}.php 不存在!";
			$view->loadTpl('error');
		}
	} elseif(substr($className,-7) == 'Service') {
		if( is_file(SERVICES_MPHP."{$className}.php") )		include SERVICES_MPHP."{$className}.php";
		elseif( is_file(SERVICES_PATH."{$className}.php") )	include SERVICES_PATH."{$className}.php";
		else {
			if($CFG['404']) goto_404();
			$view->data['title'] = 'service模块不存在！';
			$view->data['msg'] = "{$className}.php 不存在!";
			$view->loadTpl('error');
		}
	} elseif(substr($className,-3) == 'Dao') {
		if( is_file(DAOS_MPHP."{$className}.php") )		include DAOS_MPHP."{$className}.php";
		elseif( is_file(DAOS_PATH."{$className}.php") )	include DAOS_PATH."{$className}.php";
		else {
			if($CFG['404']) goto_404();
			$view->data['title'] = 'dao模块不存在！';
			$view->data['msg'] = "{$className}.php 不存在!";
			$view->loadTpl('error');
		}
	} else {
		if($CFG['404']) goto_404();
		$view->data['title'] = '访问错误！';
		$view->data['msg'] = "未定义操作 $className";
		$view->loadTpl('error');
	}
}

//显示某时刻运行详情
function run_info($file,$line,$true = false) {
	echo "<br>程序运行至文件 $file ,第 $line 行共消耗",(microtime(1) - $GLOBALS['CFG']['start_time']) * 1000,'ms；<br>';
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
			'</table>';
	}
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
	global $CFG;
	if( $strUrl === '' ) {
		return $_GET['c'] == 'blog' ? BLOG_URL : INDEX_URL;
	}
	//如果链接地址以http://开头就不用INDEX_URL
	if( substr($strUrl,0,7) == 'http://' ) {
		$leng = strpos($strUrl,'/',7);
		$url = substr($strUrl,0,$leng).'/';
		$strUrl = strtr($strUrl,array($url=>''));
	} else {
		$url = "http://{$_SERVER['HTTP_HOST']}/";
	}
	
	$intDepth = 0;
	$intDepthMax = $CFG['url_depth'];
	$arrGet = $arrVal =  $arrData =  array();
	$strUrl = trim($strUrl,'?');
	$arrData = explode('&',$strUrl);
	foreach( $arrData as $str) {
		$arrVal = explode('=',$str);
		$arrGet[$arrVal[0]] = $arrVal[1];
	}
	if($CFG['url_type'] == 'DIR') $flag = '/';
	elseif($CFG['url_type'] == 'NODIR') $flag = '-';
	else {
		if($strUrl[0] != '?') $strUrl = "{$url}?{$strUrl}";
		return $strUrl;
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
	if($true)$strUrl = $url.trim($strUrl,'/-')."{$CFG['url_suffix']}";
	else $strUrl =  $url.trim($strUrl,'/-');
	unset($CFG);
	
	return $strUrl;
}

//调试，输出变量
function P($val,$true = true) {
	echo '<pre>';
	if( is_array($val) ) print_r($val);
	//elseif( is_string($val) || is_numeric($val) ) echo $val;
	else var_dump($val);
	echo '</pre>';
	if($true) exit;
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
$type:默认为js
*/
function file_merger($arrFile,$out) {
	global $CFG;
	$url =  $_GET['c'] == 'blog' ? BLOG_URL : INDEX_URL;
	$return = "{$url}static/merger/{$out}";
	$dir = STATIC_PATH. 'merger/';
	$out = "{$dir}{$out}";
	if( substr($arrFile[0],-2) == 'js' || (isset($arrFile['mPHP']) && substr($arrFile['mPHP'][0],-2) == 'js') ) $type = 'js';
	elseif( substr($arrFile[0],-3) == 'css' || (isset($arrFile['mPHP']) && substr($arrFile['mPHP'][0],-3) == 'css') ) $type = 'css';
	if( !is_file($out) || $CFG['debug']) {
		if( $CFG['debug'] ) {
			$out = '';
			foreach($arrFile as $key => $file) {
				if($key === 'mPHP') {
					foreach($file as $adminfile) {
						if( $type == 'js' ) {
							$out .= "<script type=\"text/javascript\" src=\"{$url}static/js/{$adminfile}\"></script>\n";
						} elseif( $type == 'css' ) {
							$out .= "<link href=\"{$url}static/css/{$adminfile}\" rel=\"stylesheet\" type=\"text/css\">\n";
						}
					}
				} else {
					if( $type == 'js' ) {
						$out .= "<script type=\"text/javascript\" src=\"{$url}static/js/{$file}\"></script>\n";
					} elseif( $type == 'css' ) {
						$out .= "<link href=\"{$url}static/css/{$file}\" rel=\"stylesheet\" type=\"text/css\">\n";
					}
				}
			}
			return $out;
		}
		ob_start();
		foreach($arrFile as $key => $file) {
			if($key === 'mPHP') foreach($file as $adminfile) include STATIC_MPHP."{$type}/{$adminfile}";
			else include STATIC_PATH."{$type}/{$file}";
		}
		$str =  ob_get_clean();
		$tmp = $dir. 'tmp';
		if( $CFG['debug'] ) file_put_contents($out,$str);
		else {
			if(0) {
				file_put_contents($tmp,$str);
				if( $type == 'js' ) $exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.2.jar --type js --charset utf-8 -v $tmp > $out";//压缩JS
				elseif( $type == 'css' ) $exec = "java -jar ".STATIC_PATH."yuicompressor-2.4.2.jar --type css --charset utf-8 -v $tmp > $out";//压缩CSS
				`$exec` ;
			} else {
				//测试阶段
				//$strSql = preg_replace('/#.+\n/','',$strSql);//过滤注释
				//$str = preg_replace('#^//.*\n$/i#','',$str);//过滤注释 //
				//$str = preg_replace('#/\*.+\*/#','',$str);//过滤注释 /* */
				//$str = preg_replace( '#[\n\r\t]+#', ' ', $str );//空格替换回车或tab
				file_put_contents($out,$str);
			}
		}
	}
	unset($CFG);
	return $return;
}

//精简html : 清除换行符,清除制表符,去掉注释标记
function mini_html($html) {
	$arrData = preg_split( '/(<pre.*?\/pre>)/ms', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
	$html = '';
	foreach ( $arrData as $str ) {
		if ( strpos( $str, '<pre' ) !== 0 ) {
			$str = preg_replace( '/<!--.*-->/i', '', $str );//移除 <!-- --> 注释
			$str = preg_replace( '/[\\n\\r\\t]+/', ' ', $str );//空格替换回车或tab
			$str = preg_replace( '/\\s{2,}/', ' ', $str );//多个空格合并为一个空格
			$str = preg_replace( '/>\\s</', '><', $str );//移除标签间的空白
		}
		$html .= $str;
	}
	return $html;
}
//403，页面没权限
function goto_403() {
	global $CFG;
	static $view = 0;
	if(!$view) $view = new view();
	$file = CACHE_PATH . 'html/403.html';
	$view->cache($file,$CFG['html_cache_time']);
	$view->loadTpl('403');
	unset($CFG);
	exit;
}
//404，页面没找到
function goto_404() {
	global $CFG;
	header('HTTP/1.1 404 Not Found');
	$view = new view();
	$file = CACHE_PATH . 'html/404.html';
	$view->cache($file,$CFG['html_cache_time']);
	if($CFG['404'])$view->loadTpl('404');
	else $view->loadTpl(array('404'));
	unset($CFG);
	exit;
}
//301，页面重定向
function goto_301($url) {
	header('HTTP/1.1 301 Moved Permanently');
	header("Location: {$url}");
	exit;
}

