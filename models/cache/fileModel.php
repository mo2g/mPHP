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

		public function __construct() {
			\mPHP::inc( MPHP_PATH.'inc/functions.php' );//加载常用函数集
			$this->in('main');
		}

		public function in($file) {
			$this->fileName = CACHE_PATH . "file/{$file}.config.php";
			if( !file_exists(CACHE_PATH . 'file') ) {
				mkdir(CACHE_PATH . 'file',0755,true);
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