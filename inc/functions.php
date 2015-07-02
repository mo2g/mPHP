<?php
/*
作者:moyancheng
最后更新时间:2013-05-17
最后更新时间:2015-07-02
*/

//显示某时刻运行详情
//使用示例:
//run_info(__FILE__,__LINE__,1);
function run_info($file = __FILE__,$line = __LINE__,$true = false) {
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

function mlog($log = '',$file = '') {
	if( $file == '' ) $file = INDEX_PATH.'log.txt';
	$log = print_r($log,true);
	$log = "date：" . date("Y-m-d H:i:s") . "\n{$log}\n";
	file_put_contents($file,$log,FILE_APPEND|LOCK_EX);
}