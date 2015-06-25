<?php
/*
作者:moyancheng
创建时间:2013-12-13
最后更新时间:2013-12-13

功能：目录操作
*/
class directoryModel {
	
	//获取当前路径下的所有文件、文件夹
	public static function view($path) {
		return glob($path);
	}
	
	/*
	功能：在指定路径下查找文件或目录，找到后立即返回该路径
	当$ture为真时，寻找目录
	*/
	public static function find($target,$path,$true = false) {
		static $file_path = 0;
		$arrInfo = glob($path . '/*');
		foreach( $arrInfo as $file) {
			$flag_dir = $flag_find = 0;
			if( is_dir($file) ) $flag_dir = 1;//当前对象为目录
			else $flag_dir = 0;//当前对象为文件
			
			if( basename($file) == $target ) $flag_find = 1;//当前对象名称与目标名称一致
			else $flag_find = 0;
			
			if( $flag_find && ( ( $true && $flag_dir ) || ( !$true && !$flag_dir ) ) ) {
				$file_path = realpath($file);//找到对象
				break;
			} else {
				if( $flag_dir ) $this->find($target,$file,$true);//继续匹配
			}
		}
		if( $file_path ) return $file_path;
	}
	
	/*
	功能：清空指定文件夹内的所有文件，文件夹保留
	*/
	public static function clearDir2($path) {
		if($handle = opendir($path)) {
			while(false !== ($file = readdir($handle) ) ) {
				if($file != '.' && $file != '..') {
					is_dir($path.$file) ? self::clearDir2($path.$file.'/') : unlink($path.$file);
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
	public static function clearDir($dir,$true = false) {
		$arrInfo = glob($path . '/*');
		foreach( $arrInfo as $file) {
			is_dir($file) ? self::clearDir($file,$true) : unlink($file);
		}
		if($true) rmdir($dir);
	}
	
	/*
	在指定路径$path下创建0~$max个目录
	*/
	public static function createDirs($path,$max) {
		for( $i = 0; $i < $max; ++$i) {
			if( !is_dir($path.$i) ) mkdir($path.$i,0755,true);
		}
	}
	/*
	在指定路径$path下创建0~$max个目录
	*/
	/*
	public static function createDir($path,$max) {
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
	public static function createDir($path) {
		$dir = dirname($path);
		if( is_dir($dir) ) {
			if( !is_dir($path) ) mkdir($path);
		} else {
			self::createDir($dir);
			self::createDir($path);
		}
		return true;
	}
	
	//创建缓存目录
	public static function dirCache() {
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
}