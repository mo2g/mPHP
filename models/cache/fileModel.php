<?php
/*
作者:moyancheng
最后更新时间:2015-06-06
最后更新时间:2015-07-28
功能：

*/
namespace cache {

	class fileModel {
		public $fileName;
		public $path = '';
		public $suffix = '.config.php';

		public function __construct() {
			$this->path = CACHE_PATH . 'file';
			\mPHP::inc( MPHP_PATH.'inc/functions.php' );//加载常用函数集
			$this->in('main');
		}

		public function setPath($path) {
			$this->path = $path;
			return $this->path;
		}

		public function setSuffix($suffix) {
			$this->suffix = $suffix;
			return $this->suffix;
		}

		public function in($file) {
			$path = $this->path;
			$this->fileName = "{$path}/{$file}.config.php";
			if( !file_exists($path) ) {
				mkdir($path,0755,true);
			}

			if( !file_exists($this->fileName) ) {
				$this->flush();
			}
		}
		
		public function set($key,$data,$expire=0) {
			return C($this->fileName,$key,$data);
		}

		public function get($key) {
			return C($this->fileName,$key);
		}

		public function get_all() {
			return C($this->fileName);
		}

		public function delete($key) {
			$arrData = include $this->fileName;
			unset($arrData[$key]);
			file_put_contents($this->fileName, $arrData);
		}

		public function flush() {
			$strData = "<?php\nreturn array();";
			file_put_contents($this->fileName, $strData);
		}
	}
}