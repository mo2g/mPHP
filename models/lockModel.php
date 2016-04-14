<?php
/*
$lock = M('lock');
$lock = new lockModel();
if( $lock->lock('1') ) {
	sleep(2);
	$lock->unlock();
} else {
	echo 'locking....';
}
*/
class lockModel {
	private $fp;

	public function lock($file) {
		$dir = CACHE_PATH . 'lock/';
		if( !is_dir($dir) ) mkdir($dir,0755,true);
		$path = $dir . $file;
		$this->fp = fopen( $path, 'w' );
		if (!$this->fp) {
			return false;
		}

		$flag = flock ( $this->fp, LOCK_EX | LOCK_NB );
		if( !$flag ) fclose ( $this->fp );
		return $flag;
	}

	public function unlock() {
		if (!$this->fp) {
			return false;
		}
		$flag = flock ( $this->fp, LOCK_UN );
		fclose ( $this->fp );
		return $flag;
	}
}